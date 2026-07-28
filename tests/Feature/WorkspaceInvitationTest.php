<?php

namespace Tests\Feature;

use App\Mail\InvitationMail;
use App\Models\ActivityLog;
use App\Models\Invitation;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\Support\CreatesProjectTemplateTestSchema;
use Tests\TestCase;

class WorkspaceInvitationTest extends TestCase
{
    use CreatesProjectTemplateTestSchema;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createProjectTemplateTestSchema();
        Schema::table('users', function (Blueprint $table): void {
            $table->string('profile_photo')->nullable();
        });
        Schema::table('workspaces', function (Blueprint $table): void {
            $table->string('invite_token')->nullable()->unique();
            $table->timestamp('invite_token_expires_at')->nullable();
        });
        Schema::table('workspace_members', function (Blueprint $table): void {
            $table->foreignId('invited_by')->nullable();
            $table->string('status')->default('active');
        });
        Schema::create('invitations', function (Blueprint $table): void {
            $table->id();
            $table->string('email');
            $table->string('token')->unique();
            $table->string('pending_key', 64)->nullable()->unique();
            $table->string('type');
            $table->unsignedBigInteger('invitable_id');
            $table->foreignId('invited_by');
            $table->string('role')->default('member');
            $table->string('status')->default('pending');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamps();
            $table->index(['type', 'invitable_id', 'status']);
        });
        Schema::create('project_threads', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id');
        });

        RateLimiter::clear('workspace-member-search');
        RateLimiter::clear('workspace-invitations');
        RateLimiter::clear('workspace-invitation-resend');
        Mail::fake();
    }

    public function test_owner_and_admin_can_search_by_name_and_email_without_sensitive_fields_or_n_plus_one(): void
    {
        $fixture = $this->workspaceFixture();
        $namedUser = User::factory()->create([
            'name' => 'Searchable Candidate',
            'email' => 'candidate@example.test',
        ]);

        foreach ([$fixture['owner'], $fixture['admin']] as $actor) {
            $response = $this->actingAs($actor)->getJson(route('workspaces.members.candidates', [
                $fixture['workspace']->token,
                'search' => 'searchable',
            ]));

            $response
                ->assertOk()
                ->assertJsonPath('data.0.id', $namedUser->id)
                ->assertJsonPath('data.0.membership_status', 'available')
                ->assertJsonMissingPath('data.0.password')
                ->assertJsonMissingPath('data.0.remember_token');
        }

        $this->actingAs($fixture['owner'])
            ->getJson(route('workspaces.members.candidates', [
                $fixture['workspace']->token,
                'search' => 'CANDIDATE@EXAMPLE.TEST',
            ]))
            ->assertOk()
            ->assertJsonPath('data.0.email', 'candidate@example.test');

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($fixture['owner'])->getJson(route('workspaces.members.candidates', [
            $fixture['workspace']->token,
            'search' => 'candidate',
        ]))->assertOk();
        $initialQueries = count(DB::getQueryLog());

        User::factory()->count(5)->create(['name' => 'Candidate Result']);
        DB::flushQueryLog();
        $this->actingAs($fixture['owner'])->getJson(route('workspaces.members.candidates', [
            $fixture['workspace']->token,
            'search' => 'candidate',
        ]))->assertOk();
        $expandedQueries = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame($initialQueries, $expandedQueries);
    }

    public function test_member_cannot_search_and_existing_member_is_not_selectable(): void
    {
        $fixture = $this->workspaceFixture();

        $this->actingAs($fixture['member'])
            ->getJson(route('workspaces.members.candidates', [
                $fixture['workspace']->token,
                'search' => $fixture['admin']->email,
            ]))
            ->assertForbidden();

        $this->actingAs($fixture['owner'])
            ->getJson(route('workspaces.members.candidates', [
                $fixture['workspace']->token,
                'search' => $fixture['admin']->email,
            ]))
            ->assertOk()
            ->assertJsonPath('data.0.membership_status', 'already_member');
    }

    public function test_registered_users_are_added_directly_with_admin_or_member_role(): void
    {
        $fixture = $this->workspaceFixture();

        foreach (Workspace::INVITABLE_ROLE_LABELS as $role => $label) {
            $candidate = User::factory()->create();

            $this->actingAs($fixture['owner'])
                ->post(route('workspaces.invitations.store', $fixture['workspace']->token), [
                    'user_id' => $candidate->id,
                    'role' => $role,
                ])
                ->assertRedirect()
                ->assertSessionHas('success', "{$candidate->name} berhasil ditambahkan sebagai {$label}.");

            $this->assertDatabaseHas('workspace_members', [
                'workspace_id' => $fixture['workspace']->id,
                'user_id' => $candidate->id,
                'role' => $role,
                'invited_by' => $fixture['owner']->id,
                'status' => 'active',
            ]);
        }

        $this->assertDatabaseCount('invitations', 0);
    }

    public function test_registered_user_validation_rejects_owner_duplicate_and_unauthorized_actor(): void
    {
        $fixture = $this->workspaceFixture();
        $candidate = User::factory()->create();

        $this->actingAs($fixture['owner'])
            ->post(route('workspaces.invitations.store', $fixture['workspace']->token), [
                'user_id' => $candidate->id,
                'role' => 'owner',
            ])
            ->assertSessionHasErrors('role');

        $this->actingAs($fixture['owner'])
            ->post(route('workspaces.invitations.store', $fixture['workspace']->token), [
                'user_id' => $fixture['member']->id,
                'role' => 'member',
            ])
            ->assertSessionHasErrors('candidate');

        $this->actingAs($fixture['member'])
            ->post(route('workspaces.invitations.store', $fixture['workspace']->token), [
                'user_id' => $candidate->id,
                'role' => 'member',
            ])
            ->assertForbidden();

        $this->assertFalse($fixture['workspace']->members()->whereKey($candidate->id)->exists());
    }

    public function test_new_email_creates_normalized_secure_pending_invitation_and_queues_mail(): void
    {
        $fixture = $this->workspaceFixture();
        $plainTextToken = null;

        $this->actingAs($fixture['admin'])
            ->post(route('workspaces.invitations.store', $fixture['workspace']->token), [
                'email' => '  NEW.Person@Example.TEST ',
                'role' => 'admin',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Undangan telah dikirim ke new.person@example.test.');

        $invitation = Invitation::query()->sole();
        Mail::assertQueued(InvitationMail::class, function (InvitationMail $mail) use (&$plainTextToken): bool {
            $plainTextToken = $mail->plainTextToken;

            return $mail->hasTo('new.person@example.test')
                && $mail->roleLabel === 'Workspace Admin';
        });

        $this->assertSame('new.person@example.test', $invitation->email);
        $this->assertSame(Workspace::ROLE_ADMIN, $invitation->role);
        $this->assertSame(Invitation::hashToken($plainTextToken), $invitation->getRawOriginal('token'));
        $this->assertNotSame($plainTextToken, $invitation->getRawOriginal('token'));
        $this->assertTrue($invitation->expires_at->isBetween(now()->addDays(6), now()->addDays(8)));
        $this->get(route('invitations.accept', $invitation->getRawOriginal('token')))
            ->assertRedirect(route('login'));
    }

    public function test_invalid_email_owner_role_and_duplicate_pending_invitation_are_rejected(): void
    {
        $fixture = $this->workspaceFixture();

        $this->actingAs($fixture['owner'])
            ->post(route('workspaces.invitations.store', $fixture['workspace']->token), [
                'email' => 'not-an-email',
                'role' => 'member',
            ])
            ->assertSessionHasErrors('email');

        $this->actingAs($fixture['owner'])
            ->post(route('workspaces.invitations.store', $fixture['workspace']->token), [
                'email' => 'pending@example.test',
                'role' => 'owner',
            ])
            ->assertSessionHasErrors('role');

        $payload = ['email' => 'pending@example.test', 'role' => 'member'];
        $this->actingAs($fixture['owner'])
            ->post(route('workspaces.invitations.store', $fixture['workspace']->token), $payload)
            ->assertRedirect();
        $this->actingAs($fixture['owner'])
            ->post(route('workspaces.invitations.store', $fixture['workspace']->token), $payload)
            ->assertSessionHasErrors('candidate');

        $this->assertDatabaseCount('invitations', 1);
    }

    public function test_valid_token_acceptance_requires_matching_email_and_is_one_time(): void
    {
        $fixture = $this->workspaceFixture();
        [$invitation, $plainTextToken] = $this->createEmailInvitation(
            $fixture,
            'invitee@example.test',
            Workspace::ROLE_ADMIN,
        );
        $invitee = User::factory()->create(['email' => 'invitee@example.test']);
        $otherUser = User::factory()->create(['email' => 'other@example.test']);

        $this->actingAs($otherUser)
            ->withSession(['invitation_token' => $plainTextToken])
            ->post(route('invitations.join'))
            ->assertForbidden();
        $this->assertFalse($fixture['workspace']->members()->whereKey($otherUser->id)->exists());

        $this->actingAs($invitee)
            ->withSession(['invitation_token' => $plainTextToken])
            ->post(route('invitations.join'))
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('workspace_members', [
            'workspace_id' => $fixture['workspace']->id,
            'user_id' => $invitee->id,
            'role' => Workspace::ROLE_ADMIN,
        ]);
        $this->assertDatabaseHas('invitations', [
            'id' => $invitation->id,
            'status' => Invitation::STATUS_ACCEPTED,
        ]);

        $this->actingAs($invitee)
            ->withSession(['invitation_token' => $plainTextToken])
            ->post(route('invitations.join'))
            ->assertUnprocessable();
        $this->assertSame(1, DB::table('workspace_members')->where([
            'workspace_id' => $fixture['workspace']->id,
            'user_id' => $invitee->id,
        ])->count());
    }

    public function test_expired_and_revoked_tokens_are_rejected(): void
    {
        $fixture = $this->workspaceFixture();
        [$expired, $expiredToken] = $this->createEmailInvitation($fixture, 'expired@example.test');
        $expired->update(['expires_at' => now()->subMinute()]);

        $this->get(route('invitations.accept', $expiredToken))
            ->assertRedirect(route('login'));

        [$revoked, $revokedToken] = $this->createEmailInvitation($fixture, 'revoked@example.test');
        $revoked->update(['revoked_at' => now(), 'pending_key' => null]);

        $this->get(route('invitations.accept', $revokedToken))
            ->assertRedirect(route('login'));
    }

    public function test_legacy_plaintext_token_remains_usable_until_it_is_rotated(): void
    {
        $fixture = $this->workspaceFixture();
        $legacyToken = 'legacy-plaintext-token';
        $invitation = Invitation::create([
            'email' => 'legacy@example.test',
            'token' => $legacyToken,
            'type' => 'workspace',
            'invitable_id' => $fixture['workspace']->id,
            'invited_by' => $fixture['owner']->id,
            'role' => Workspace::ROLE_MEMBER,
            'status' => Invitation::STATUS_PENDING,
            'expires_at' => now()->addDay(),
            'last_sent_at' => null,
        ]);

        $this->get(route('invitations.accept', $legacyToken))->assertOk();

        $this->actingAs($fixture['owner'])
            ->post(route('workspaces.invitations.resend', [
                $fixture['workspace']->token,
                $invitation,
            ]))
            ->assertRedirect();

        $this->get(route('invitations.accept', $legacyToken))
            ->assertRedirect(route('login'));
    }

    public function test_pending_invitations_render_and_resend_rotates_token_without_duplicate(): void
    {
        $fixture = $this->workspaceFixture();
        [$invitation, $oldToken] = $this->createEmailInvitation($fixture, 'pending-list@example.test');
        $oldExpiry = $invitation->expires_at;
        Mail::fake();
        $newToken = null;

        $this->actingAs($fixture['owner'])
            ->get(route('workspaces.show', $fixture['workspace']->token).'?tab=members')
            ->assertOk()
            ->assertSee('Pending Invitations')
            ->assertSee('pending-list@example.test')
            ->assertSee('Workspace Member');

        $this->travel(1)->day();
        $this->actingAs($fixture['admin'])
            ->post(route('workspaces.invitations.resend', [
                $fixture['workspace']->token,
                $invitation,
            ]))
            ->assertRedirect();

        Mail::assertQueued(InvitationMail::class, function (InvitationMail $mail) use (&$newToken): bool {
            $newToken = $mail->plainTextToken;

            return true;
        });

        $invitation->refresh();
        $this->assertDatabaseCount('invitations', 1);
        $this->assertNotSame(Invitation::hashToken($oldToken), $invitation->getRawOriginal('token'));
        $this->assertSame(Invitation::hashToken($newToken), $invitation->getRawOriginal('token'));
        $this->assertTrue($invitation->expires_at->greaterThan($oldExpiry));
        $this->get(route('invitations.accept', $oldToken))->assertRedirect(route('login'));
        $this->post(route('logout'));
        $this->get(route('invitations.accept', $newToken))->assertOk();
    }

    public function test_revoke_invalidates_token_and_unauthorized_resend_or_revoke_is_forbidden(): void
    {
        $fixture = $this->workspaceFixture();
        [$invitation, $plainTextToken] = $this->createEmailInvitation($fixture, 'cancel@example.test');

        $this->actingAs($fixture['member'])
            ->post(route('workspaces.invitations.resend', [
                $fixture['workspace']->token,
                $invitation,
            ]))
            ->assertForbidden();
        $this->actingAs($fixture['member'])
            ->delete(route('workspaces.invitations.revoke', [
                $fixture['workspace']->token,
                $invitation,
            ]))
            ->assertForbidden();

        $this->actingAs($fixture['owner'])
            ->delete(route('workspaces.invitations.revoke', [
                $fixture['workspace']->token,
                $invitation,
            ]))
            ->assertRedirect();

        $this->assertNotNull($invitation->fresh()->revoked_at);
        $this->get(route('invitations.accept', $plainTextToken))
            ->assertRedirect(route('login'));
    }

    public function test_reusable_link_is_member_only_expires_and_can_be_disabled_or_regenerated(): void
    {
        $fixture = $this->workspaceFixture();
        $invitee = User::factory()->create();

        $this->actingAs($fixture['owner'])
            ->post(route('workspaces.invite.generate', $fixture['workspace']->token))
            ->assertRedirect();
        $workspace = $fixture['workspace']->fresh();
        $firstToken = $workspace->invite_token;
        $this->assertTrue($workspace->hasActiveInviteLink());

        $this->actingAs($invitee)
            ->post(route('workspaces.invite.accept', $workspace->token), ['token' => $firstToken])
            ->assertRedirect();
        $this->assertDatabaseHas('workspace_members', [
            'workspace_id' => $workspace->id,
            'user_id' => $invitee->id,
            'role' => Workspace::ROLE_MEMBER,
        ]);

        $this->actingAs($fixture['owner'])
            ->post(route('workspaces.invite.reset', $workspace->token))
            ->assertRedirect();
        $this->assertNotSame($firstToken, $workspace->fresh()->invite_token);

        $activeToken = $workspace->fresh()->invite_token;
        $workspace->update(['invite_token_expires_at' => now()->subMinute()]);
        $this->get(route('workspaces.invite.join', $activeToken))
            ->assertRedirect(route('login'));

        $this->actingAs($fixture['owner'])
            ->delete(route('workspaces.invite.revoke', $workspace->token))
            ->assertRedirect();
        $this->assertNull($workspace->fresh()->invite_token);
    }

    public function test_search_send_and_resend_endpoints_are_rate_limited(): void
    {
        $fixture = $this->workspaceFixture();
        [$invitation] = $this->createEmailInvitation($fixture, 'rate-limit@example.test');

        for ($attempt = 1; $attempt <= 31; $attempt++) {
            $response = $this->actingAs($fixture['owner'])
                ->getJson(route('workspaces.members.candidates', [
                    $fixture['workspace']->token,
                    'search' => 'candidate',
                ]));
        }
        $response->assertTooManyRequests();

        for ($attempt = 1; $attempt <= 11; $attempt++) {
            $response = $this->actingAs($fixture['owner'])
                ->post(route('workspaces.invitations.store', $fixture['workspace']->token), [
                    'email' => 'invalid',
                    'role' => 'member',
                ]);
        }
        $response->assertTooManyRequests();

        for ($attempt = 1; $attempt <= 6; $attempt++) {
            $response = $this->actingAs($fixture['admin'])
                ->post(route('workspaces.invitations.resend', [
                    $fixture['workspace']->token,
                    $invitation,
                ]));
        }
        $response->assertTooManyRequests();
    }

    public function test_workspace_invitation_does_not_change_project_membership_or_owner_rules(): void
    {
        $fixture = $this->workspaceFixture();
        $candidate = User::factory()->create();
        $project = Project::factory()
            ->for($fixture['workspace'])
            ->for($fixture['owner'], 'creator')
            ->create();

        $this->actingAs($fixture['owner'])
            ->post(route('workspaces.invitations.store', $fixture['workspace']->token), [
                'user_id' => $candidate->id,
                'role' => 'member',
            ])
            ->assertRedirect();

        $this->assertFalse($project->members()->whereKey($candidate->id)->exists());

        $this->actingAs($fixture['admin'])
            ->patch(route('workspaces.members.update', [
                $fixture['workspace']->token,
                $fixture['owner'],
            ]), ['role' => 'member'])
            ->assertSessionHasErrors('role');
        $this->actingAs($fixture['admin'])
            ->delete(route('workspaces.members.destroy', [
                $fixture['workspace']->token,
                $fixture['owner'],
            ]))
            ->assertSessionHasErrors('member');
    }

    public function test_registered_member_and_email_invitation_write_structured_activity_without_tokens(): void
    {
        $fixture = $this->workspaceFixture();
        $candidate = User::factory()->create(['name' => 'Andi Registered']);

        $this->actingAs($fixture['owner'])
            ->post(route('workspaces.invitations.store', $fixture['workspace']->token), [
                'user_id' => $candidate->id,
                'role' => Workspace::ROLE_ADMIN,
            ])
            ->assertRedirect();

        $memberLog = $this->workspaceEvent(ActivityLog::EVENT_WORKSPACE_MEMBER_ADDED);
        $this->assertSame($fixture['owner']->id, $memberLog->user_id);
        $this->assertSame($fixture['workspace']->id, $memberLog->entity_id);
        $this->assertSame($candidate->id, $memberLog->new_value['target_user_id']);
        $this->assertSame('Workspace Admin', $memberLog->new_value['role_label']);
        $this->assertSame('registered_user', $memberLog->new_value['source']);
        $this->assertStringContainsString('Workspace Admin', $memberLog->description);

        [$invitation, $plainTextToken] = $this->createEmailInvitation(
            $fixture,
            'audit-email@example.test',
        );
        $invitationLog = $this->workspaceEvent(ActivityLog::EVENT_WORKSPACE_INVITATION_SENT);
        $serializedLog = json_encode($invitationLog->toArray(), JSON_THROW_ON_ERROR);

        $this->assertSame($invitation->id, $invitationLog->new_value['invitation_id']);
        $this->assertSame('audit-email@example.test', $invitationLog->new_value['target_email']);
        $this->assertSame('Workspace Member', $invitationLog->new_value['role_label']);
        $this->assertSame('email_invitation', $invitationLog->new_value['source']);
        $this->assertStringNotContainsString($plainTextToken, $serializedLog);
        $this->assertStringNotContainsString($invitation->getRawOriginal('token'), $serializedLog);
        $this->assertStringContainsString(
            'Workspace Owner',
            ActivityLog::describeWorkspaceEvent(
                ActivityLog::EVENT_WORKSPACE_MEMBER_ADDED,
                [
                    'target_name' => 'Owner Label',
                    'role_label' => Workspace::roleLabel(Workspace::ROLE_OWNER),
                ],
            ),
        );
    }

    public function test_resend_and_revoke_write_machine_readable_activity(): void
    {
        $fixture = $this->workspaceFixture();
        [$invitation] = $this->createEmailInvitation($fixture, 'resend-audit@example.test');

        $this->actingAs($fixture['admin'])
            ->post(route('workspaces.invitations.resend', [
                $fixture['workspace']->token,
                $invitation,
            ]))
            ->assertRedirect();

        $resentLog = $this->workspaceEvent(ActivityLog::EVENT_WORKSPACE_INVITATION_RESENT);
        $this->assertSame($fixture['admin']->id, $resentLog->user_id);
        $this->assertSame($invitation->id, $resentLog->new_value['invitation_id']);
        $this->assertSame(Invitation::STATUS_PENDING, $resentLog->new_value['status']);

        $this->actingAs($fixture['owner'])
            ->delete(route('workspaces.invitations.revoke', [
                $fixture['workspace']->token,
                $invitation,
            ]))
            ->assertRedirect();

        $revokedLog = $this->workspaceEvent(ActivityLog::EVENT_WORKSPACE_INVITATION_REVOKED);
        $this->assertSame('revoked', $revokedLog->new_value['status']);
        $this->assertSame('email_invitation', $revokedLog->new_value['source']);
    }

    public function test_accept_invitation_writes_activity_with_recipient_as_actor(): void
    {
        $fixture = $this->workspaceFixture();
        [$invitation, $plainTextToken] = $this->createEmailInvitation(
            $fixture,
            'accepted-audit@example.test',
            Workspace::ROLE_ADMIN,
        );
        $invitee = User::factory()->create([
            'name' => 'Accepted User',
            'email' => 'accepted-audit@example.test',
        ]);

        $this->actingAs($invitee)
            ->withSession(['invitation_token' => $plainTextToken])
            ->post(route('invitations.join'))
            ->assertRedirect();

        $log = $this->workspaceEvent(ActivityLog::EVENT_WORKSPACE_INVITATION_ACCEPTED);
        $this->assertSame($invitee->id, $log->user_id);
        $this->assertSame($invitee->id, $log->new_value['target_user_id']);
        $this->assertSame($fixture['owner']->id, $log->new_value['inviter_id']);
        $this->assertSame($invitation->id, $log->new_value['invitation_id']);
        $this->assertSame('Workspace Admin', $log->new_value['role_label']);
        $this->assertStringContainsString('menerima undangan', $log->displayDescription());
    }

    public function test_reusable_link_events_are_logged_without_link_token(): void
    {
        $fixture = $this->workspaceFixture();
        $invitee = User::factory()->create(['name' => 'Reusable Link User']);

        $this->actingAs($fixture['owner'])
            ->post(route('workspaces.invite.generate', $fixture['workspace']->token))
            ->assertRedirect();
        $workspace = $fixture['workspace']->fresh();
        $firstToken = $workspace->invite_token;

        $generatedLog = $this->workspaceEvent(ActivityLog::EVENT_WORKSPACE_INVITE_LINK_REGENERATED);
        $this->assertSame('reusable_link', $generatedLog->new_value['source']);
        $this->assertSame('Workspace Member', $generatedLog->new_value['role_label']);
        $this->assertNotNull($generatedLog->new_value['expires_at']);

        $this->actingAs($invitee)
            ->post(route('workspaces.invite.accept', $workspace->token), [
                'token' => $firstToken,
            ])
            ->assertRedirect();

        $joinedLog = $this->workspaceEvent(ActivityLog::EVENT_WORKSPACE_JOINED_VIA_INVITE_LINK);
        $this->assertSame($invitee->id, $joinedLog->user_id);
        $this->assertSame($invitee->id, $joinedLog->new_value['target_user_id']);
        $this->assertSame(Workspace::ROLE_MEMBER, $joinedLog->new_value['role']);

        $this->actingAs($fixture['owner'])
            ->post(route('workspaces.invite.reset', $workspace->token))
            ->assertRedirect();
        $this->assertSame(
            2,
            ActivityLog::query()
                ->where('new_value->event', ActivityLog::EVENT_WORKSPACE_INVITE_LINK_REGENERATED)
                ->count(),
        );

        $activeToken = $workspace->fresh()->invite_token;
        $this->actingAs($fixture['owner'])
            ->delete(route('workspaces.invite.revoke', $workspace->token))
            ->assertRedirect();
        $disabledLog = $this->workspaceEvent(ActivityLog::EVENT_WORKSPACE_INVITE_LINK_DISABLED);
        $this->assertSame('disabled', $disabledLog->new_value['status']);

        $serializedLogs = ActivityLog::query()->get()->toJson();
        $this->assertStringNotContainsString($firstToken, $serializedLogs);
        $this->assertStringNotContainsString($activeToken, $serializedLogs);
    }

    public function test_role_change_and_member_removal_log_old_and_new_role(): void
    {
        $fixture = $this->workspaceFixture();

        $this->actingAs($fixture['owner'])
            ->patch(route('workspaces.members.update', [
                $fixture['workspace']->token,
                $fixture['member'],
            ]), ['role' => Workspace::ROLE_ADMIN])
            ->assertRedirect();

        $roleLog = $this->workspaceEvent(ActivityLog::EVENT_WORKSPACE_MEMBER_ROLE_CHANGED);
        $this->assertSame(Workspace::ROLE_MEMBER, $roleLog->old_value['role']);
        $this->assertSame(Workspace::ROLE_ADMIN, $roleLog->new_value['role']);
        $this->assertSame('Workspace Member', $roleLog->new_value['old_role_label']);
        $this->assertSame('Workspace Admin', $roleLog->new_value['role_label']);

        $this->actingAs($fixture['owner'])
            ->delete(route('workspaces.members.destroy', [
                $fixture['workspace']->token,
                $fixture['member'],
            ]))
            ->assertRedirect();

        $removedLog = $this->workspaceEvent(ActivityLog::EVENT_WORKSPACE_MEMBER_REMOVED);
        $this->assertSame($fixture['member']->id, $removedLog->new_value['target_user_id']);
        $this->assertSame('removed', $removedLog->new_value['status']);
    }

    public function test_activity_log_failure_rolls_back_registered_member_addition(): void
    {
        $fixture = $this->workspaceFixture();
        $candidate = User::factory()->create();

        ActivityLog::creating(function (): never {
            throw new RuntimeException('Forced invitation activity failure.');
        });
        $this->withoutExceptionHandling();

        try {
            $this->actingAs($fixture['owner'])
                ->post(route('workspaces.invitations.store', $fixture['workspace']->token), [
                    'user_id' => $candidate->id,
                    'role' => Workspace::ROLE_MEMBER,
                ]);
            $this->fail('Expected the activity log failure to be thrown.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Forced invitation activity failure.', $exception->getMessage());
        } finally {
            ActivityLog::flushEventListeners();
        }

        $this->assertFalse($fixture['workspace']->members()->whereKey($candidate->id)->exists());
        $this->assertSame(0, ActivityLog::query()->count());
    }

    public function test_workspace_activity_visibility_masks_email_and_excludes_outsiders(): void
    {
        $fixture = $this->workspaceFixture();
        $outsider = User::factory()->create();
        $this->createEmailInvitation($fixture, 'private-invite@example.test');

        $this->actingAs($fixture['owner'])
            ->get(route('activity.log'))
            ->assertOk()
            ->assertSee('private-invite@example.test');

        $this->actingAs($fixture['admin'])
            ->get(route('activity.log'))
            ->assertOk()
            ->assertSee('private-invite@example.test');

        $this->actingAs($fixture['member'])
            ->get(route('activity.log'))
            ->assertOk()
            ->assertSee('p***@example.test')
            ->assertDontSee('private-invite@example.test');

        $this->actingAs($outsider)
            ->get(route('activity.log'))
            ->assertOk()
            ->assertDontSee('private-invite@example.test')
            ->assertDontSee('p***@example.test');
    }

    public function test_project_activity_presentation_remains_unchanged(): void
    {
        $fixture = $this->workspaceFixture();
        $project = Project::factory()
            ->for($fixture['workspace'])
            ->for($fixture['owner'], 'creator')
            ->create();
        $log = ActivityLog::create([
            'user_id' => $fixture['owner']->id,
            'action' => 'created',
            'entity_type' => 'project',
            'entity_id' => $project->id,
            'description' => 'Membuat project regression.',
        ]);

        $this->assertNull($log->event);
        $this->assertSame('Membuat project regression.', $log->displayDescription());
        $this->assertSame('fa-plus', $log->presentation()['icon']);
    }

    public function test_invitation_ui_uses_partials_central_roles_and_accessible_safe_javascript(): void
    {
        $root = resource_path('views/workspaces/partials/members');

        foreach ([
            '_invite-modal.blade.php',
            '_invite-search-results.blade.php',
            '_pending-invitations.blade.php',
            '_pending-invitation-item.blade.php',
        ] as $file) {
            $this->assertFileExists($root.'/'.$file);
        }

        $modal = file_get_contents($root.'/_invite-modal.blade.php');
        $javascript = file_get_contents(resource_path('js/workspace-members.js'));
        $this->assertStringContainsString('aria-modal="true"', $modal);
        $this->assertStringContainsString('Workspace::INVITABLE_ROLE_LABELS', $modal);
        $this->assertStringNotContainsString('value="owner"', $modal);
        $this->assertStringContainsString('textContent', $javascript);
        $this->assertStringNotContainsString('innerHTML', $javascript);
    }

    /**
     * @return array{owner: User, admin: User, member: User, workspace: Workspace}
     */
    private function workspaceFixture(): array
    {
        $owner = User::factory()->create(['name' => 'Workspace Owner']);
        $admin = User::factory()->create(['name' => 'Workspace Admin']);
        $member = User::factory()->create(['name' => 'Workspace Member']);
        $workspace = Workspace::factory()->for($owner, 'creator')->create();

        $workspace->members()->attach([
            $owner->id => [
                'role' => Workspace::ROLE_OWNER,
                'status' => 'active',
                'joined_at' => now(),
            ],
            $admin->id => [
                'role' => Workspace::ROLE_ADMIN,
                'status' => 'active',
                'joined_at' => now(),
            ],
            $member->id => [
                'role' => Workspace::ROLE_MEMBER,
                'status' => 'active',
                'joined_at' => now(),
            ],
        ]);

        return compact('owner', 'admin', 'member', 'workspace');
    }

    /**
     * @param  array{owner: User, admin: User, member: User, workspace: Workspace}  $fixture
     * @return array{Invitation, string}
     */
    private function createEmailInvitation(
        array $fixture,
        string $email,
        string $role = Workspace::ROLE_MEMBER,
    ): array {
        Mail::fake();
        $plainTextToken = null;

        $this->actingAs($fixture['owner'])
            ->post(route('workspaces.invitations.store', $fixture['workspace']->token), [
                'email' => $email,
                'role' => $role,
            ])
            ->assertRedirect();

        Mail::assertQueued(InvitationMail::class, function (InvitationMail $mail) use (&$plainTextToken): bool {
            $plainTextToken = $mail->plainTextToken;

            return true;
        });

        return [
            Invitation::query()->where('email', strtolower($email))->sole(),
            $plainTextToken,
        ];
    }

    private function workspaceEvent(string $event): ActivityLog
    {
        return ActivityLog::query()
            ->where('new_value->event', $event)
            ->latest('id')
            ->firstOrFail();
    }
}
