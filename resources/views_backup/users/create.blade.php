@extends('layouts.app')

@section('title', 'Tambah User')

@section('content')
    <div class="min-h-screen">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

            {{-- Breadcrumb --}}
            <nav class="flex items-center gap-2 text-sm text-gray-500 mb-6">
                <a href="{{ route('management-users.index') }}" class="hover:text-indigo-600 transition-colors">
                    User Management
                </a>
                <i class="fa-solid fa-chevron-right text-xs text-gray-400"></i>
                <span class="text-gray-700 font-medium">Add User</span>
            </nav>

            {{-- Header --}}
            <div class="mb-8">
                <h1
                    class="text-3xl font-bold bg-gradient-to-r from-gray-900 to-gray-600
                           bg-clip-text text-transparent">
                    Add User
                </h1>
                <p class="text-gray-600 mt-2">Create a new user account for the system</p>
            </div>

            {{-- FLASH ERROR --}}
            @if ($errors->any())
                <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Card --}}
            <div
                class="bg-white/90 backdrop-blur-sm rounded-2xl shadow-xl
                        border border-gray-200/60 overflow-hidden max-w-3xl">

                {{-- Accent Bar --}}
                <div class="h-1.5 bg-[#CCD5AE]"></div>

                <div class="p-6 sm:p-8">
                    <form method="POST" action="{{ route('management-users.store') }}" class="space-y-6">
                        @csrf

                        {{-- NAME --}}
                        <div>
                            <label for="name" class="block text-sm font-semibold text-gray-800 mb-2">
                                Full Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="name" name="name" value="{{ old('name') }}"
                                placeholder="Enter full name"
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl
                                       focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500
                                       transition-all duration-200
                                       @error('name') border-red-400 bg-red-50/50 @enderror"
                                required>
                            @error('name')
                                <div class="mt-2 text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg px-3 py-2">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        {{-- EMAIL --}}
                        <div>
                            <label for="email" class="block text-sm font-semibold text-gray-800 mb-2">
                                Email <span class="text-red-500">*</span>
                            </label>
                            <input type="email" id="email" name="email" value="{{ old('email') }}"
                                placeholder="Example@email.com"
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl
                                       focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500
                                       transition-all duration-200
                                       @error('email') border-red-400 bg-red-50/50 @enderror"
                                required>
                            @error('email')
                                <div class="mt-2 text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg px-3 py-2">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        {{-- ROLE --}}
                        <div>
                            <label for="role" class="block text-sm font-semibold text-gray-800 mb-2">
                                Role <span class="text-red-500">*</span>
                            </label>
                            <select id="role" name="role"
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl appearance-none
                                       focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500
                                       transition-all duration-200
                                       @error('role') border-red-400 bg-red-50/50 @enderror"
                                required>
                                <option value="" disabled {{ old('role') ? '' : 'selected' }}>Choose a role</option>
                                <option value="member" {{ old('role') === 'member' ? 'selected' : '' }}>Member</option>
                                <option value="super_admin" {{ old('role') === 'super_admin' ? 'selected' : '' }}>Super
                                    Admin</option>
                            </select>
                            @error('role')
                                <div class="mt-2 text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg px-3 py-2">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        {{-- PASSWORD --}}
                        <div>
                            <label for="password" class="block text-sm font-semibold text-gray-800 mb-2">
                                Password <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input type="password" id="password" name="password" placeholder="At least 6 karakter"
                                    class="w-full px-4 py-3 pr-11 border border-gray-300 rounded-xl
                                           focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500
                                           transition-all duration-200
                                           @error('password') border-red-400 bg-red-50/50 @enderror"
                                    required>
                                <button type="button" onclick="togglePassword()"
                                    class="absolute inset-y-0 right-3 flex items-center text-gray-400 hover:text-gray-600">
                                    <i id="password-icon" class="fa-solid fa-eye text-sm"></i>
                                </button>
                            </div>
                            @error('password')
                                <div class="mt-2 text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg px-3 py-2">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        {{-- Buttons --}}
                        <div class="flex flex-col sm:flex-row gap-3 pt-4 border-t border-gray-100">
                            <button type="submit"
                                class="inline-flex items-center justify-center px-6 py-3 text-white
                                       font-semibold rounded-xl bg-blue-600 hover:bg-blue-700
                                       shadow-lg shadow-blue-500/30
                                       transition-all duration-300 transform hover:-translate-y-0.5">
                                Add User
                            </button>

                            <a href="{{ route('management-users.index') }}"
                                class="inline-flex items-center justify-center px-6 py-3 text-gray-700
                                       font-medium rounded-xl border border-gray-300 bg-white
                                       hover:bg-gray-50 transition-all duration-200">
                                <i class="fa-solid fa-xmark mr-2"></i>
                                Cancle
                            </a>
                        </div>

                    </form>
                </div>
            </div>

        </div>
    </div>

    <script>
        function togglePassword() {
            const input = document.getElementById('password');
            const icon = document.getElementById('password-icon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }
    </script>
@endsection
