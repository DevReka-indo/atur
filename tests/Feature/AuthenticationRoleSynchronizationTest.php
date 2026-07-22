<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Spatie\Permission\Exceptions\RoleDoesNotExist;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthenticationRoleSynchronizationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createTestSchema();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_gate_bypass_requires_exact_spatie_super_admin_role(): void
    {
        Permission::create(['name' => 'authentication.audit', 'guard_name' => 'web']);

        $spatieSuperAdmin = User::factory()->superAdmin()->create();
        $legacyOnlySuperAdmin = User::create($this->userAttributes(
            'legacy-super-admin@example.test',
            'super_admin',
        ));
        $similarRole = Role::create(['name' => 'super_admin_assistant', 'guard_name' => 'web']);
        $similarRoleUser = User::factory()->member()->create();
        $similarRoleUser->syncRoles([$similarRole]);

        $this->assertTrue(Gate::forUser($spatieSuperAdmin)->allows('authentication.audit'));
        $this->assertFalse(Gate::forUser($legacyOnlySuperAdmin)->allows('authentication.audit'));
        $this->assertFalse(Gate::forUser($similarRoleUser)->allows('authentication.audit'));
    }

    public function test_native_registration_sets_legacy_and_spatie_member_roles(): void
    {
        $response = $this->post('/register', [
            'name' => 'Native Member',
            'email' => 'native-member@example.test',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::where('email', 'native-member@example.test')->firstOrFail();

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($user);
        $this->assertSame('member', $user->role);
        $this->assertTrue($user->hasRole('member'));
    }

    public function test_native_registration_rolls_back_when_member_role_is_missing(): void
    {
        Role::where('name', 'member')->where('guard_name', 'web')->delete();
        $this->withoutExceptionHandling();

        try {
            $this->post('/register', [
                'name' => 'Incomplete Member',
                'email' => 'incomplete-member@example.test',
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

            $this->fail('Registration should fail when the member role is unavailable.');
        } catch (RoleDoesNotExist) {
            $this->assertDatabaseMissing('users', ['email' => 'incomplete-member@example.test']);
            $this->assertDatabaseCount('model_has_roles', 0);
        }
    }

    public function test_new_google_user_receives_legacy_and_spatie_member_roles(): void
    {
        $this->fakeGoogleUser('google-new', 'google-new@example.test');

        $response = $this->get(route('google.callback'));
        $user = User::where('email', 'google-new@example.test')->firstOrFail();

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
        $this->assertSame('member', $user->role);
        $this->assertTrue($user->hasRole('member'));
    }

    public function test_existing_google_users_keep_their_super_admin_and_contributor_roles(): void
    {
        foreach (['super_admin', 'contributor'] as $role) {
            $user = User::factory()->create([
                'email' => "google-{$role}@example.test",
                'google_id' => "google-{$role}",
                'role' => $role,
            ]);
            $this->fakeGoogleUser("google-{$role}", $user->email);

            $this->get(route('google.callback'))->assertRedirect('/dashboard');

            $user->refresh();
            $this->assertSame($role, $user->role);
            $this->assertTrue($user->hasRole($role));
            $this->assertFalse($user->hasRole('member'));
        }
    }

    public function test_existing_google_user_with_valid_legacy_role_is_synchronized(): void
    {
        $user = User::create($this->userAttributes(
            'google-legacy-contributor@example.test',
            'contributor',
            'google-legacy-contributor',
        ));
        $this->fakeGoogleUser('google-legacy-contributor', $user->email);

        $this->get(route('google.callback'))->assertRedirect('/dashboard');

        $user->refresh();
        $this->assertSame('contributor', $user->role);
        $this->assertTrue($user->hasRole('contributor'));
    }

    public function test_existing_google_user_with_invalid_legacy_role_falls_back_to_member(): void
    {
        $user = User::create($this->userAttributes(
            'google-invalid-role@example.test',
            'invalid-role',
            'google-invalid-role',
        ));
        $this->fakeGoogleUser('google-invalid-role', $user->email);

        $this->get(route('google.callback'))->assertRedirect('/dashboard');

        $user->refresh();
        $this->assertSame('member', $user->role);
        $this->assertTrue($user->hasRole('member'));
    }

    private function fakeGoogleUser(string $id, string $email): void
    {
        Socialite::fake('google', (new SocialiteUser)->map([
            'id' => $id,
            'name' => 'Google User',
            'email' => $email,
            'avatar' => 'https://example.test/avatar.png',
        ]));
    }

    /**
     * @return array<string, mixed>
     */
    private function userAttributes(string $email, string $role, ?string $googleId = null): array
    {
        return [
            'name' => 'Authentication User',
            'email' => $email,
            'password' => 'password',
            'role' => $role,
            'is_active' => true,
            'google_id' => $googleId,
        ];
    }

    private function createTestSchema(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->boolean('has_password')->default(true);
            $table->string('role')->default('member');
            $table->boolean('is_active')->default(true);
            $table->string('profile_photo')->nullable();
            $table->string('job_title')->nullable();
            $table->string('department')->nullable();
            $table->string('google_id')->nullable();
            $table->string('avatar_url')->nullable();
            $table->string('sso_id')->nullable();
            $table->string('employee_id')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        $permissionMigration = require database_path('migrations/2026_07_22_083512_create_permission_tables.php');
        $permissionMigration->up();
    }
}
