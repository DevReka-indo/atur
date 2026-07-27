<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();
        if (session('workspace_invite_token')) {
            $token = session('workspace_invite_token');

            return redirect()->route('workspaces.invite.join', $token);
        }
        if (session('invitation_token')) {
            return redirect()->route('invitations.accept', session('invitation_token'));
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $loggedInViaSso = $user && ! empty($user->sso_id);

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($loggedInViaSso) {
            $ssoLogoutUrl = config('services.sso.logout_url');
            $afterLogoutUrl = config('services.sso.after_logout_url', url('/login'));

            if ($ssoLogoutUrl) {
                return redirect()->away($ssoLogoutUrl.'?'.http_build_query([
                    'client_id' => config('services.sso.client_id'),
                    'redirect_uri' => $afterLogoutUrl,
                ]));
            }
        }

        return redirect('/');
    }
}
