<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Facades\Socialite;
use Spatie\Permission\Models\Role;

class GoogleAuthController extends Controller
{
    /**
     * Redirect ke Google OAuth
     */
    public function redirect()
    {
        $inviteToken = request('invite_token') ?? session('workspace_invite_token');

        return Socialite::driver('google')
            ->stateless()
            ->with(['state' => $inviteToken ?? ''])
            ->redirect();
    }

    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            // Ambil invite token dari state
            $inviteToken = request('state') ?: null;

            $user = User::where('google_id', $googleUser->id)->first();

            if ($user) {
                if (! $user->is_active) {
                    return redirect()->route('login')
                        ->with('error', 'Akun Anda tidak aktif.');
                }

                DB::transaction(function () use ($user): void {
                    $this->synchronizeMissingGlobalRole($user);
                });
                Auth::login($user);
            } else {
                $existingUser = User::where('email', $googleUser->email)->first();

                if ($existingUser) {
                    if (! $existingUser->is_active) {
                        return redirect()->route('login')
                            ->with('error', 'Akun Anda tidak aktif.');
                    }

                    DB::transaction(function () use ($existingUser, $googleUser): void {
                        $existingUser->update([
                            'google_id' => $googleUser->id,
                            'avatar_url' => $googleUser->avatar,
                        ]);
                        $this->synchronizeMissingGlobalRole($existingUser);
                    });
                    Auth::login($existingUser);
                } else {
                    $newUser = DB::transaction(function () use ($googleUser): User {
                        $newUser = User::create([
                            'name' => $googleUser->name,
                            'email' => $googleUser->email,
                            'google_id' => $googleUser->id,
                            'avatar_url' => $googleUser->avatar,
                            'email_verified_at' => now(),
                            'password' => null,
                            'has_password' => false,
                            'role' => 'member',
                        ]);
                        $newUser->syncRoles(['member']);

                        return $newUser;
                    });
                    Auth::login($newUser);
                }
            }

            // Redirect ke halaman konfirmasi jika ada invite token
            if ($inviteToken) {
                return redirect()->route('workspaces.invite.join', $inviteToken);
            }

            if (session('invitation_token')) {
                return redirect()->route('invitations.accept', session('invitation_token'));
            }

            return redirect()->intended('/dashboard');
        } catch (\Exception $e) {
            return redirect()->route('login')
                ->with('error', 'Gagal login dengan Google. Silakan coba lagi.');
        }
    }

    private function synchronizeMissingGlobalRole(User $user): void
    {
        if ($user->roles()->where('guard_name', 'web')->exists()) {
            return;
        }

        $legacyRole = trim((string) $user->role);
        $roleName = Role::query()
            ->where('name', $legacyRole)
            ->where('guard_name', 'web')
            ->exists()
                ? $legacyRole
                : 'member';

        if ($user->role !== $roleName) {
            $user->role = $roleName;
            $user->save();
        }

        $user->syncRoles([$roleName]);
    }
}
