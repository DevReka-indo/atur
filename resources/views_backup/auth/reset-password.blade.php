@extends('layouts.guest')

@section('title', 'Reset Password')

@section('content')
<div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">

        <div class="bg-white py-8 px-6 shadow-lg rounded-2xl">

             <div class="flex justify-center">
            <div class="bg-blue-100 p-3 rounded-full">
                <svg class="h-8 w-8 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </div>
        </div>

        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
            Reset Password
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600">
            Please enter your new password below
        </p>

            <form method="POST" action="{{ route('password.store') }}" class="space-y-6">
                @csrf

                <!-- Hidden Token & Email -->
                <input type="hidden" name="token" value="{{ $request->route('token') }}">
                <input type="hidden" name="email" value="{{ request('email') }}">

                <!-- New Password -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">
                        New Password
                    </label>
                    <div class="mt-1">
                        <input
                            id="password" name="password" type="password" required autocomplete="new-password"
                            class="w-full border border-gray-300 rounded-xl px-4 py-3 text-gray-900 placeholder-gray-400
                                focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none transition-all"
                            placeholder="Enter your password"
                        >
                    </div>
                    @error('password')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Re-enter Password -->
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700">
                        Confirm Password
                    </label>
                    <div class="mt-1">
                        <input
                            id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"
                            class="w-full border border-gray-300 rounded-xl px-4 py-3 text-gray-900 placeholder-gray-400
                                focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none transition-all"
                            placeholder="Confirm your password"
                        >
                    </div>
                    @error('password_confirmation')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Submit Button -->
                <div>
                    <button
                        type="submit"
                        class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-lg shadow-sm text-sm font-semibold text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-all duration-200">
                        Reset Password
                    </button>
                </div>
            </form>

            <!-- Back to Login Link -->
            <div class="mt-6">
                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-300"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-2 bg-white text-gray-500">
                            Or
                        </span>
                    </div>
                </div>

                <div class="mt-6 text-center">
                    <a href="{{ route('login') }}" class="font-medium text-indigo-600 hover:text-indigo-500">
                        Back to Login
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
