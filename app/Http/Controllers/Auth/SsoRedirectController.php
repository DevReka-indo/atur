<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SsoRedirectController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $state = Str::random(40);

        $request->session()->put('sso_state', $state);

        $authorizeUrl = rtrim((string) config('services.sso.base_url'), '/') . '/sso/authorize?' . http_build_query([
            'client_id' => config('services.sso.client_id'),
            'redirect_uri' => config('services.sso.callback_url'),
            'state' => $state,
            'prompt' => 'login',
        ]);

        return redirect()->away($authorizeUrl);
    }
}
