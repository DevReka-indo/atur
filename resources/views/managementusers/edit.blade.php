@extends('layouts.app')

@section('content')
    <div class="min-h-screen p-6">
        <div class="max-w-3xl mx-auto">

            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Edit User</h1>

                <a href="{{ route('management-users.index') }}"
                    class="px-4 py-2 bg-gray-500 text-white rounded-xl hover:bg-gray-600 transition">
                    ← Back
                </a>
            </div>

            <!-- Card -->
            <div class="bg-white shadow-xl rounded-2xl p-8 border border-gray-200">

                <form method="POST" action="{{ route('management-users.update', $management_user) }}">
                    @csrf
                    @method('PUT')

                    {{-- NAME --}}
                    <div class="mb-5">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Name
                        </label>

                        <input type="text" name="name" value="{{ old('name', $management_user->name) }}"
                            class="w-full px-4 py-2 border rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none">

                        @error('name')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- EMAIL --}}
                    <div class="mb-5">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Email
                        </label>

                        <input type="email" name="email" value="{{ old('email', $management_user->email) }}"
                            class="w-full px-4 py-2 border rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none">

                        @error('email')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- ROLE --}}
                    <div class="mb-5">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Role
                        </label>

                        <select name="role"
                            class="w-full px-4 py-2 border rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none">

                            @foreach ($roles as $role)
                                <option value="{{ $role->name }}"
                                    {{ old('role', $management_user->role) === $role->name ? 'selected' : '' }}>
                                    {{ str($role->name)->headline() }}
                                </option>
                            @endforeach

                        </select>

                        @error('role')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- PASSWORD (OPTIONAL) --}}
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            New Password (Optional)
                        </label>

                        <input type="password" name="password" placeholder="Leave blank if not changing"
                            class="w-full px-4 py-2 border rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none">

                        @error('password')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- SUBMIT --}}
                    <div class="flex justify-end">
                        <button type="submit"
                            class="px-6 py-2 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition shadow">
                            Update User
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>
@endsection
