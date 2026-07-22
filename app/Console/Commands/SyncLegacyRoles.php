<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class SyncLegacyRoles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions:sync-legacy-roles {--chunk=200 : Number of users processed per chunk}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize legacy users.role values to Spatie global roles';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $validRoles = ['super_admin', 'contributor', 'member'];

        foreach ($validRoles as $role) {
            if (! Role::where('name', $role)->where('guard_name', 'web')->exists()) {
                $this->error("Role {$role} belum tersedia. Jalankan RolePermissionSeeder terlebih dahulu.");

                return self::FAILURE;
            }
        }

        $chunkSize = max(1, (int) $this->option('chunk'));
        $synced = 0;
        $mappedAdmin = 0;
        $unknown = 0;

        User::query()->chunkById($chunkSize, function ($users) use (&$synced, &$mappedAdmin, &$unknown, $validRoles): void {
            foreach ($users as $user) {
                $legacyRole = (string) $user->role;

                if ($legacyRole === 'admin') {
                    $mappedRole = 'member';
                    $mappedAdmin++;
                    Log::warning('Legacy global role admin mapped to member.', ['user_id' => $user->id]);
                } elseif (in_array($legacyRole, $validRoles, true)) {
                    $mappedRole = $legacyRole;
                } else {
                    $unknown++;
                    Log::warning('Unknown legacy global role was not synchronized.', [
                        'user_id' => $user->id,
                        'role' => $legacyRole,
                    ]);
                    $this->warn("User {$user->id}: role '{$legacyRole}' dilewati.");

                    continue;
                }

                $user->syncRoles([$mappedRole]);
                $synced++;
            }
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->info("Selesai: {$synced} user tersinkron, {$mappedAdmin} admin dipetakan ke member, {$unknown} role tidak dikenal dilewati.");

        return self::SUCCESS;
    }
}
