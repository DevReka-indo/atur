<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SsoTokenService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

            $isNewUser = ! $user;

            if (! $user) {
                $user = new User();
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

            // Mapping role SSO ke role ATUR (string: super_admin / member)
            $user->role = $this->mapSsoRoleToLocalRole(
                $ssoUser['roles'] ?? [],
                $user->role ?? null,
                $isNewUser
            );

            $user->save();

            Auth::login($user, true);

            $request->session()->regenerate();

            return redirect(config('services.sso.after_login_url', '/'));
        } catch (Throwable $e) {
            report($e);

            return redirect()
                ->route('sso.login')
                ->with('error', 'Login SSO gagal: ' . $e->getMessage());
        }
    }

    /**
     * Mapping role dari SSO ke role lokal ATUR.
     *
     * - Kalau role SSO mengandung admin/super-admin -> super_admin
     * - Kalau user lama dan tidak match apapun -> pertahankan role lama
     * - Kalau user baru dan tidak match apapun -> default member
     */
    private function mapSsoRoleToLocalRole(array $roles, ?string $currentRole, bool $isNewUser): string
    {
        $roles = collect($roles)->map(fn ($r) => strtolower((string) $r));

        if ($roles->contains('admin') || $roles->contains('super-admin') || $roles->contains('super_admin')) {
            return 'super_admin';
        }

        if (! $isNewUser && $currentRole) {
            return $currentRole;
        }

        return 'member';
    }
}
