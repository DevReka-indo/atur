<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SsoTokenService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class SsoCallbackController extends Controller
{
    public function __invoke(Request $request, SsoTokenService $ssoTokenService): RedirectResponse
    {
        $request->validate([
            'sso_token' => ['required', 'string'],
            'state' => ['required', 'string'],
        ]);

        $expectedState = $request->session()->pull('sso_state');

        if (! $expectedState || ! hash_equals($expectedState, $request->string('state')->toString())) {
            return redirect()
                ->route('sso.login')
                ->with('error', 'Login SSO gagal: state tidak valid. Silakan login ulang.');
        }

        try {
            $ssoUser = $ssoTokenService->verify(
                $request->string('sso_token')->toString()
            );

            // Urutan pencocokan: employee_id dulu, lalu email, baru buat baru
            $user = null;

            if (! empty($ssoUser['employee_id']) && Schema::hasColumn('users', 'employee_id')) {
                $user = User::where('employee_id', $ssoUser['employee_id'])->first();
            }

            if (! $user && ! empty($ssoUser['email'])) {
                $user = User::where('email', $ssoUser['email'])->first();
            }

            if ($user && ! $user->is_active) {
                return redirect()
                    ->route('sso.login')
                    ->with('error', 'Akun Anda tidak aktif.');
            }

            if (! $user) {
                $user = new User;
            }

            $user->name = $ssoUser['name'] ?? $user->name ?? $ssoUser['employee_id'] ?? 'SSO User';

            if (! empty($ssoUser['email'])) {
                $user->email = $ssoUser['email'];
            }

            if (Schema::hasColumn('users', 'employee_id')) {
                $user->employee_id = $ssoUser['employee_id'] ?? $user->employee_id;
            }

            if (Schema::hasColumn('users', 'sso_id')) {
                $user->sso_id = $ssoUser['sso_id'] ?? $ssoUser['sub'] ?? $user->sso_id;
            }

            if (! $user->exists || empty($user->password)) {
                $user->password = bcrypt(Str::random(40));
            }

            if (! $user->exists) {
                $user->is_active = true;
            }

            $mappedRole = $this->mapSsoRoleToLocalRole($ssoUser['roles'] ?? []);

            DB::transaction(function () use ($user, $mappedRole): void {
                $user->role = $mappedRole;
                $user->save();
                $user->syncRoles([$mappedRole]);
            });

            Auth::login($user, true);

            $request->session()->regenerate();

            if ($request->session()->has('invitation_token')) {
                return redirect()->route(
                    'invitations.accept',
                    $request->session()->get('invitation_token'),
                );
            }

            if ($request->session()->has('workspace_invite_token')) {
                return redirect()->route(
                    'workspaces.invite.join',
                    $request->session()->get('workspace_invite_token'),
                );
            }

            return redirect(config('services.sso.after_login_url', '/'));
        } catch (Throwable $e) {
            report($e);

            return redirect()
                ->route('sso.login')
                ->with('error', 'Login SSO gagal: '.$e->getMessage());
        }
    }

    /**
     * Mapping role dari SSO ke role lokal ATUR.
     *
     * - Kalau role SSO mengandung admin/super-admin -> super_admin
     * - Kalau role SSO mengandung contributor -> contributor
     * - Selain itu -> member
     */
    private function mapSsoRoleToLocalRole(array $roles): string
    {
        $roles = collect($roles)->map(fn ($r) => strtolower((string) $r));

        if ($roles->contains('admin') || $roles->contains('super-admin') || $roles->contains('super_admin')) {
            return 'super_admin';
        }

        if ($roles->contains('contributor')) {
            return 'contributor';
        }

        return 'member';
    }
}
