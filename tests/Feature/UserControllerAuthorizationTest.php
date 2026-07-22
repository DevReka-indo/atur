<?php

namespace Tests\Feature;

use App\Http\Controllers\UserController;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UserControllerAuthorizationTest extends TestCase
{
    /** @var list<string> */
    private const USER_MANAGEMENT_PERMISSIONS = [
        'management-users.view',
        'management-users.create',
        'management-users.update',
        'management-users.delete',
        'management-users.toggle-status',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->createTestSchema();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_index_and_show_require_view_permission_inside_controller(): void
    {
        $target = User::factory()->member()->create();

        $this->assertControllerActionIsForbidden(
            fn () => app(UserController::class)->index(Request::create('/management-users')),
            'management-users.view',
        );
        $this->assertControllerActionIsForbidden(
            fn () => app(UserController::class)->show($target),
            'management-users.view',
        );
    }

    public function test_create_and_store_require_create_permission_inside_controller(): void
    {
        $this->assertControllerActionIsForbidden(
            fn () => app(UserController::class)->create(),
            'management-users.create',
        );
        $this->assertControllerActionIsForbidden(
            fn () => app(UserController::class)->store(Request::create('/management-users', 'POST')),
            'management-users.create',
        );
    }

    public function test_edit_and_update_require_update_permission_inside_controller(): void
    {
        $target = User::factory()->member()->create();

        $this->assertControllerActionIsForbidden(
            fn () => app(UserController::class)->edit($target),
            'management-users.update',
        );
        $this->assertControllerActionIsForbidden(
            fn () => app(UserController::class)->update(Request::create('/management-users/1', 'PUT'), $target),
            'management-users.update',
        );
    }

    public function test_destroy_requires_delete_permission_inside_controller(): void
    {
        $target = User::factory()->member()->create();

        $this->assertControllerActionIsForbidden(
            fn () => app(UserController::class)->destroy($target),
            'management-users.delete',
        );
    }

    public function test_toggle_status_requires_toggle_status_permission_inside_controller(): void
    {
        $target = User::factory()->member()->create();

        $this->assertControllerActionIsForbidden(
            fn () => app(UserController::class)->toggleStatus($target),
            'management-users.toggle-status',
        );
    }

    private function assertControllerActionIsForbidden(callable $action, string $requiredPermission): void
    {
        $actor = User::factory()->member()->create();
        $actor->givePermissionTo(array_values(array_diff(
            self::USER_MANAGEMENT_PERMISSIONS,
            [$requiredPermission],
        )));
        $this->actingAs($actor);

        try {
            $action();
            $this->fail('The controller action did not enforce its permission.');
        } catch (AuthorizationException $exception) {
            $this->assertSame('This action is unauthorized.', $exception->getMessage());
        }
    }

    private function createTestSchema(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->string('role')->default('member');
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });

        $permissionMigration = require database_path('migrations/2026_07_22_083512_create_permission_tables.php');
        $permissionMigration->up();
    }
}
