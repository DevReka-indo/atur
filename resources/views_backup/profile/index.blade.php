@extends('layouts.app')
@section('title', 'Profile')
@section('content')
    <div class="min-h-screen bg-gray-50">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:py-8">

            {{-- HEADER --}}
            <div class="mb-10">
                <h1 class="text-3xl font-bold text-gray-900">Profile Settings</h1>
                <p class="text-gray-600 mt-2">Manage your account information and security</p>
            </div>

            {{-- FLASH MESSAGE --}}
            @if (session('status'))
                <div id="flash-message"
                    class="mb-6 p-4 rounded-xl border flex items-center gap-3 shadow-sm
                bg-emerald-50 border-emerald-200 text-emerald-800">
                    <i class="fa-solid fa-check-circle"></i>
                    <span class="text-sm font-medium">
                        @if (session('status') == 'profile-updated')
                            Profile updated successfully.
                        @elseif(session('status') == 'photo-updated')
                            Photo uploaded successfully.
                        @elseif(session('status') == 'photo-deleted')
                            Profile photo removed successfully.
                        @elseif(session('status') == 'password-updated')
                            Password updated successfully.
                        @endif
                    </span>
                    <button onclick="this.closest('#flash-message').remove()" class="ml-auto opacity-60 hover:opacity-100">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <script>
                    setTimeout(() => {
                        const el = document.getElementById('flash-message');
                        if (el) {
                            el.style.opacity = '0';
                            el.style.transition = 'opacity 0.3s';
                            setTimeout(() => el.remove(), 300);
                        }
                    }, 5000);
                </script>
            @endif

            @if ($errors->any())
                <div class="mb-6 p-4 rounded-xl border bg-red-50 border-red-200 text-red-800 text-sm">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Notif JS --}}
            <div id="js-notification" class="hidden mb-6 p-4 rounded-xl border flex items-center gap-3 shadow-sm">
                <i id="js-notif-icon" class="fa-solid"></i>
                <span id="js-notif-message" class="text-sm font-medium"></span>
                <button onclick="document.getElementById('js-notification').classList.add('hidden')"
                    class="ml-auto opacity-60 hover:opacity-100">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8 space-y-8">

                {{-- FORM 1: FOTO PROFIL --}}
                <form id="form-photo" action="{{ route('profile.photo.update') }}" method="POST"
                    enctype="multipart/form-data">
                    @csrf
                    <h2 class="text-lg font-semibold text-gray-900 mb-5 flex items-center gap-2">
                        <span class="w-1.5 h-5 bg-indigo-500 rounded-full"></span>
                        Foto Profil
                    </h2>
                    <div class="flex items-center gap-8 p-5 bg-[#A3E1EE] rounded-xl border border-[#0096c7]">
                        @if (Auth::user()->profile_photo)
                            <img src="{{ asset('storage/' . Auth::user()->profile_photo) }}"
                                class="w-28 h-28 rounded-2xl object-cover border-4 border-white shadow-md"
                                id="photo-preview">
                        @else
                            <div class="w-28 h-28 rounded-2xl bg-gradient-to-br from-indigo-100 to-purple-100
                                    flex items-center justify-center text-2xl font-bold text-indigo-700
                                    border-4 border-white shadow-md"
                                id="photo-preview-text">
                                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                            </div>
                        @endif
                        <div class="flex items-center gap-3">
                            <input type="file" name="profile_photo" id="profile_photo" accept="image/*"
                                class="text-sm border border-gray-300 rounded-lg p-2 bg-white hover:border-indigo-400 transition-colors"
                                onchange="previewPhoto(this)">
                            <span class="text-xs text-gray-500">Max: 2MB</span>
                        </div>
                    </div>
                </form>

                <div class="border-t border-gray-200"></div>

                {{-- FORM 2: PROFILE INFO --}}
                <form id="form-info" action="{{ route('profile.update') }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <h2 class="text-lg font-semibold text-gray-900 mb-6 flex items-center gap-2">
                        <span class="w-1.5 h-5 bg-indigo-500 rounded-full"></span>
                        Profile Information
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="text-sm font-medium text-gray-700 mb-1.5 block">Full Name</label>
                            <input type="text" name="name" value="{{ old('name', $user->name) }}"
                                class="mt-1 w-full border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-[#fafafa]">
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700 mb-1.5 block">Email</label>
                            <input type="email" name="email" value="{{ old('email', $user->email) }}"
                                class="mt-1 w-full border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-[#fafafa]">
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700 mb-1.5 block">Job Title</label>
                            <input type="text" name="job_title" value="{{ old('job_title', $user->job_title) }}"
                                class="mt-1 w-full border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-[#fafafa]">
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700 mb-1.5 block">Department</label>
                            <input type="text" name="department" value="{{ old('department', $user->department) }}"
                                class="mt-1 w-full border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-[#fafafa]">
                        </div>
                    </div>
                </form>

                <div class="border-t border-gray-200"></div>

                {{-- FORM 3: PASSWORD --}}
                <form id="form-password" action="{{ route('password.update') }}" method="POST">
                    @csrf
                    @method('PUT')
                    <h2 class="text-lg font-semibold text-gray-900 mb-6 flex items-center gap-2">
                        <span class="w-1.5 h-5 bg-indigo-500 rounded-full"></span>
                        Update Password <span class="text-xs font-normal text-gray-500 ml-2">(opsional)</span>
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="text-sm font-medium text-gray-700 mb-1.5 block">Current Password</label>
                            <input type="password" name="current_password"
                                class="mt-1 w-full border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-[#fafafa]">
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700 mb-1.5 block">New Password</label>
                            <input type="password" name="password"
                                class="mt-1 w-full border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-[#fafafa]">
                        </div>
                        <div class="md:col-span-2">
                            <label class="text-sm font-medium text-gray-700 mb-1.5 block">Confirm Password</label>
                            <input type="password" name="password_confirmation"
                                class="mt-1 w-full border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-[#fafafa]">
                        </div>
                    </div>
                </form>

                {{-- SAVE ALL BUTTON --}}
                <div class="pt-6">
                    <button type="button" id="btn-save-all"
                        class="w-full px-8 py-3 bg-gradient-to-r from-indigo-600 to-indigo-700 text-white rounded-xl
                               hover:from-indigo-700 hover:to-indigo-800 shadow-md hover:shadow-lg transition-all
                               font-semibold flex items-center justify-center gap-2">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span id="btn-text">Save All Changes</span>
                    </button>
                </div>

                <div class="border-t border-gray-200"></div>

                {{-- DELETE ACCOUNT --}}
                <div class="p-5 bg-red-50/60 rounded-xl border border-red-100">
                    <h2 class="text-lg font-semibold text-red-700 mb-2 flex items-center gap-2">
                        <i class="fa-solid fa-triangle-exclamation text-red-500"></i>
                        Delete Account
                    </h2>
                    <p class="text-sm text-red-600/80 mb-4">
                        Once your account is deleted, all data will be permanently removed.
                    </p>
                    <button onclick="openDeleteModal()"
                        class="w-full px-5 py-3 bg-gradient-to-r from-red-600 to-red-700 text-white rounded-xl
                               hover:from-red-700 hover:to-red-800 shadow-md hover:shadow-lg transition-all
                               font-semibold flex items-center justify-center gap-2">
                        <i class="fa-regular fa-trash-can"></i>
                        Delete Account
                    </button>
                </div>

            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function showJsNotif(message, type = 'info') {
                const notif = document.getElementById('js-notification');
                const icon = document.getElementById('js-notif-icon');
                const msg = document.getElementById('js-notif-message');

                const configs = {
                    info: {
                        box: 'bg-blue-50 border-blue-200 text-blue-800',
                        icon: 'fa-info-circle text-blue-500'
                    },
                    success: {
                        box: 'bg-emerald-50 border-emerald-200 text-emerald-800',
                        icon: 'fa-check-circle text-emerald-500'
                    },
                    warning: {
                        box: 'bg-amber-50 border-amber-200 text-amber-800',
                        icon: 'fa-circle-exclamation text-amber-500'
                    }
                };
                const config = configs[type] || configs.info;

                notif.className = `mb-6 p-4 rounded-xl border flex items-center gap-3 shadow-sm ${config.box}`;
                icon.className = `fa-solid ${config.icon}`;
                msg.textContent = message;

                notif.scrollIntoView({
                    behavior: 'smooth',
                    block: 'nearest'
                });

                setTimeout(() => {
                    notif.style.opacity = '0';
                    notif.style.transition = 'opacity 0.3s';
                    setTimeout(() => {
                        notif.classList.add('hidden');
                        notif.style.opacity = '';
                    }, 300);
                }, 3000);
            }

            function previewPhoto(input) {
                if (input.files && input.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const preview = document.getElementById('photo-preview');
                        const previewText = document.getElementById('photo-preview-text');
                        if (preview) {
                            preview.src = e.target.result;
                        } else if (previewText) {
                            previewText.outerHTML = `<img src="${e.target.result}" id="photo-preview"
                                class="w-28 h-28 rounded-2xl object-cover border-4 border-white shadow-md">`;
                        }
                    }
                    reader.readAsDataURL(input.files[0]);
                }
            }

            document.getElementById('btn-save-all').addEventListener('click', function() {
                const hasPhoto = document.getElementById('profile_photo').files.length > 0;
                const hasPassword = document.querySelector('[name="password"]').value.length > 0;

                const fields = ['name', 'email', 'job_title', 'department'];
                const originals = {
                    name: "{{ $user->name }}",
                    email: "{{ $user->email }}",
                    job_title: "{{ $user->job_title }}",
                    department: "{{ $user->department }}"
                };
                const hasInfoChange = fields.some(field => {
                    const input = document.querySelector(`[name="${field}"]`);
                    return input && input.value !== (originals[field] ?? '');
                });

                if (!hasPhoto && !hasPassword && !hasInfoChange) {
                    showJsNotif('Tidak ada perubahan untuk disimpan.', 'info');
                    return;
                }

                const btn = this;
                btn.disabled = true;
                btn.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> Saving...`;

                if (hasPhoto) {
                    sessionStorage.setItem('submit_info', hasInfoChange ? '1' : '0');
                    sessionStorage.setItem('submit_password', hasPassword ? '1' : '0');
                    document.getElementById('form-photo').submit();
                } else if (hasInfoChange) {
                    sessionStorage.setItem('submit_password', hasPassword ? '1' : '0');
                    document.getElementById('form-info').submit();
                } else if (hasPassword) {
                    document.getElementById('form-password').submit();
                }
            });

            document.addEventListener('DOMContentLoaded', function() {
                if (sessionStorage.getItem('submit_info') === '1') {
                    sessionStorage.removeItem('submit_info');
                    document.getElementById('form-info').submit();
                    return;
                }

                if (sessionStorage.getItem('submit_password') === '1') {
                    sessionStorage.removeItem('submit_password');
                    document.getElementById('form-password').submit();
                    return;
                }

                // Bersihkan sisa sessionStorage jika ada
                sessionStorage.removeItem('submit_info');
                sessionStorage.removeItem('submit_password');
            });
        </script>
    @endpush
@endsection
