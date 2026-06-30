<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class SsoLoginController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        return redirect()->route('sso.redirect');
    }
}
