@extends('layouts.guest')

@section('title', 'Login')

@section('content')
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="w-full max-w-md">
            <!-- Card -->
            <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-8 transition-shadow hover:shadow-2xl">

                <!-- Header -->
                <div class="text-center mb-8">
                    <h1 class="text-2xl font-bold text-gray-900">Welcome!</h1>
                    <p class="text-gray-500 text-sm mt-1">Sign in to your account to continue</p>
                </div>

                <!-- Session Status -->
                @if (session('status'))
                    <div class="mb-6 p-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-700 text-center">
                        {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="space-y-5">
                    @csrf

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">
                            Email Address
                        </label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                            autocomplete="username"
                            class="w-full border border-gray-300 rounded-xl px-4 py-3 text-gray-900 placeholder-gray-400
                                focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none transition-all"
                            placeholder="email@example.com" />
                        @error('email')
                            <p class="mt-1.5 text-xs text-red-500 flex items-center">
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                        clip-rule="evenodd" />
                                </svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">
                            Password
                        </label>
                        <input id="password" type="password" name="password" required autocomplete="current-password"
                            class="w-full border border-gray-300 rounded-xl px-4 py-3 text-gray-900 placeholder-gray-400
                                focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none transition-all"
                            placeholder="Enter your password" />
                        @error('password')
                            <p class="mt-1.5 text-xs text-red-500 flex items-center">
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                        clip-rule="evenodd" />
                                </svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <!-- Remember & Forgot -->
                    <div class="flex items-center justify-between text-sm">
                        <label class="inline-flex items-center cursor-pointer group">
                            <input type="checkbox" name="remember"
                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 focus:ring-offset-0 h-4 w-4 transition-colors">
                            <span class="ms-2 text-gray-600 group-hover:text-gray-800 transition-colors">Remember me</span>
                        </label>

                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}"
                                class="text-blue-600 hover:text-blue-700 font-medium transition-colors">
                                Forgot password?
                            </a>
                        @endif
                    </div>

                    <!-- Login Button -->
                    <button type="submit"
                        class="w-full bg-red-600 hover:bg-red-700 active:bg-red-800 text-white font-semibold py-3 px-4 rounded-xl
                            transition-all duration-200 shadow-md hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                        Login
                    </button>
                </form>

                <!-- Divider -->
                <div class="relative my-6">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-200"></div>
                    </div>
                    <div class="relative flex justify-center text-xs">
                        <span class="px-3 bg-white text-gray-400">or continue with</span>
                    </div>
                </div>

                <!-- Google Login -->
                <a href="{{ route('google.login') }}{{ request('invite_token') ? '?invite_token=' . request('invite_token') : '' }}"
                    class="w-full flex items-center justify-center border border-gray-300 rounded-xl px-4 py-3
                           bg-white hover:bg-gray-50 active:bg-gray-100 transition-all duration-200 text-gray-700 font-medium
                           focus:outline-none focus:ring-2 focus:ring-gray-300 focus:ring-offset-2">
                    <svg class="w-5 h-5 mr-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                        <path fill="#4285F4"
                            d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                        <path fill="#34A853"
                            d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
                        <path fill="#FBBC05"
                            d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" />
                        <path fill="#EA4335"
                            d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" />
                    </svg>
                    Sign in with Google
                </a>
                <!-- SSO Login -->
                <a href="{{ route('sso.login') }}"
                    class="mt-3 inline-flex w-full items-center justify-center rounded-lg bg-blue-600 px-4 py-3 text-sm font-semibold text-white hover:bg-blue-700">
                    Login dengan SSO
                </a>
                <!-- Register -->
                <p class="text-center text-sm text-gray-600 mt-6">
                    Don't have an account?
                    <a href="{{ route('register') }}"
                        class="font-semibold text-blue-600 hover:text-blue-700 transition-colors">
                        Sign up
                    </a>
                </p>
            </div>

        </div>
    </div>
@endsection
