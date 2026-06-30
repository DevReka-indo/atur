@extends('layouts.guest')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-white">
    <div class="w-full max-w-md bg-[#FAEDCD] p-6 rounded shadow border border-gray-300">
        <h2 class="text-3xl font-bold mb-2 text-center text-[#3e2723]">Verify Your Email</h2>
        <p class="text-center text-sm text-[#6d4c41] mb-6">Please check your email</p>

        <div class="mb-4 text-sm text-gray-700">
            {{ __('Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.') }}
        </div>

        @if (session('status') == 'verification-link-sent')
            <div class="mb-4 font-medium text-sm text-green-600">
                {{ __('A new verification link has been sent to the email address you provided during registration.') }}
            </div>
        @endif

        <div class="mt-6">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf

                <button type="submit"
                        class="w-full py-3 bg-red-600 text-white rounded-md
                            font-semibold hover:bg-red-700 transition">
                    {{ __('Resend Verification Email') }}
                </button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf

                <button type="submit"
                        class="w-full mt-4 py-3 bg-gray-200 text-gray-700 rounded-md
                            font-semibold hover:bg-gray-300 transition">
                    {{ __('Log Out') }}
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
