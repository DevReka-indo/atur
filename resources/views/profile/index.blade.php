@extends('layouts.app')
@section('title', 'Profile')
@section('content')

    <div class="fixed inset-0 bg-gradient-to-br from-gray-50 to-gray-100/50 -z-10"></div>
    <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:py-8">

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

        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8">

            {{-- FORM 1: FOTO PROFIL --}}
            <form id="form-photo" action="{{ route('profile.photo.update') }}" method="POST" enctype="multipart/form-data">
                @csrf

                {{-- Hidden input untuk hapus foto --}}
                <input type="hidden" name="remove_photo" id="remove_photo" value="0">

                <h2 class="text-lg font-semibold text-gray-900 mb-2 flex items-center gap-2">
                    <span class="w-1.5 h-5 bg-indigo-500 rounded-full"></span>
                    Foto Profil
                </h2>

                <div class="flex flex-col items-center justify-center gap-4 p-5 bg-white rounded-xl pt-0">
                    {{-- Avatar wrapper --}}
                    <div class="relative w-28 h-28" id="avatarWrap">

                        {{-- Foto atau inisial --}}
                        @if (Auth::user()->profile_photo)
                            <img src="{{ asset('storage/' . Auth::user()->profile_photo) }}" id="avatarImg"
                                class="w-28 h-28 rounded-full object-cover border-4 border-white shadow-md">
                        @else
                            <div id="avatarInitial"
                                class="w-28 h-28 rounded-full bg-indigo-100 flex items-center justify-center
                                    text-2xl font-bold text-indigo-700 border-4 border-white shadow-md">
                                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                            </div>
                        @endif

                        {{-- Overlay edit di tengah --}}
                        <div id="editOverlay" onclick="togglePhotoDropdown()"
                            class="absolute inset-0 rounded-full bg-black/40 flex flex-col items-center
                                justify-center gap-0.5 opacity-0 hover:opacity-100 transition-opacity
                                duration-200 cursor-pointer">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                            </svg>
                            <span class="text-white text-xs font-medium">Edit</span>
                        </div>

                        {{-- Dropdown menu --}}
                        <div id="photoDropdown"
                            class="hidden absolute top-[120px] left-1/2 -translate-x-1/2 z-20
        bg-white rounded-xl shadow-lg border border-gray-100
        w-48 overflow-hidden whitespace-nowrap">

                            {{-- Pilih dari galeri --}}
                            <button type="button" onclick="triggerFileInput()"
                                class="flex items-center gap-2.5 w-full px-4 py-2.5 text-sm text-gray-700
                                    hover:bg-gray-50 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-500" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <rect x="3" y="3" width="18" height="18" rx="2" />
                                    <circle cx="8.5" cy="8.5" r="1.5" />
                                    <polyline points="21 15 16 10 5 21" />
                                </svg>
                                Pilih dari galeri
                            </button>

                            {{-- Hapus foto (hanya muncul jika ada foto) --}}
                            @if (Auth::user()->profile_photo)
                                <div class="border-t border-gray-100"></div>
                                <button type="button" id="btnHapusFoto" onclick="hapusFotoProfil()"
                                    class="flex items-center gap-2.5 w-full px-4 py-2.5 text-sm text-red-500
                                        hover:bg-red-50 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round">
                                        <polyline points="3 6 5 6 21 6" />
                                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                                        <path d="M10 11v6" />
                                        <path d="M14 11v6" />
                                        <path d="M9 6V4h6v2" />
                                    </svg>
                                    Hapus foto profil
                                </button>
                            @endif
                        </div>
                    </div>

                    {{-- Input file tersembunyi --}}
                    <input type="file" name="profile_photo" id="profile_photo" accept="image/*" class="hidden"
                        onchange="previewFoto(this)">

                    <p class="text-xs text-gray-400 text-center">Klik foto untuk mengganti<br>Maks. 2MB</p>
                </div>
            </form>

            <div class="border-t mb-2 border-gray-200 my-8"></div>

            {{-- GRID: PROFILE INFO (KIRI) & PASSWORD (KANAN) --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

                {{-- FORM 2: PROFILE INFO (KIRI) --}}
                <form id="form-info" action="{{ route('profile.update') }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <h2 class="text-lg font-semibold text-gray-900 mb-6 flex items-center gap-2">
                        <span class="w-1.5 h-5 bg-indigo-500 rounded-full"></span>
                        Profile Information
                    </h2>
                    <div class="space-y-5">
                        <div>
                            <label class="text-sm font-medium text-gray-700 mb-1.5 block">Full Name</label>
                            <input type="text" name="name" value="{{ old('name', $user->name) }}"
                                class="w-full border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-[#fafafa]">
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700 mb-1.5 block">Job Title</label>
                            <input type="text" name="job_title" value="{{ old('job_title', $user->job_title) }}"
                                class="w-full border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-[#fafafa]">
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700 mb-1.5 block">Email</label>
                            <input type="email" name="email" value="{{ old('email', $user->email) }}"
                                class="w-full border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-[#fafafa]">
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700 mb-1.5 block">Department</label>
                            <input type="text" name="department" value="{{ old('department', $user->department) }}"
                                class="w-full mb-8 border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-[#fafafa]">
                        </div>
                    </div>
                </form>

                {{-- FORM 3: PASSWORD (KANAN) --}}
                <form id="form-password" action="{{ route('password.update') }}" method="POST">
                    @csrf
                    @method('PUT')
                    <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                        <span class="w-1.5 h-5 bg-indigo-500 rounded-full"></span>
                        @if (!$user->has_password)
                            Create Password
                            <span
                                class="text-xs font-normal text-amber-600 ml-2 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded-full">
                                Not set
                            </span>
                        @else
                            Update Password
                            <span class="text-xs font-normal text-gray-500 ml-2">(opsional)</span>
                        @endif
                    </h2>

                    <div class="space-y-5">
                        @if ($user->has_password)
                            <div>
                                <label class="text-sm font-medium text-gray-700 mb-1.5 block">Current Password</label>
                                <input type="password" name="current_password"
                                    class="w-full border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-[#fafafa]">
                            </div>
                        @endif
                        <div>
                            <label class="text-sm font-medium text-gray-700 mb-1.5 block">New Password</label>
                            <input type="password" name="password"
                                class="w-full border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-[#fafafa] {{ $errors->updatePassword->has('password') ? 'border-red-500' : '' }}">
                            @if ($errors->updatePassword->has('password'))
                                <p class="text-xs text-red-500 mt-1">{{ $errors->updatePassword->first('password') }}</p>
                            @endif
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700 mb-1.5 block">Confirm Password</label>
                            <input type="password" name="password_confirmation"
                                class="w-full border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-[#fafafa] {{ $errors->updatePassword->has('password_confirmation') ? 'border-red-500' : '' }}">
                            @if ($errors->updatePassword->has('password'))
                                <p class="text-xs text-red-500 mt-1">Password confirmation does not match.</p>
                            @endif
                        </div>
                    </div>
                </form>

            </div>

            <div class="border-t border-gray-200 my-8"></div>

            {{-- SAVE ALL & DELETE ACCOUNT (TENGAH, FULL WIDTH) --}}
            <div class="flex flex-col items-center justify-center space-y-4">

                {{-- SAVE ALL BUTTON --}}
                <button type="button" id="btn-save-all"
                    class="w-full max-w-8xl px-8 py-3 bg-gradient-to-r from-indigo-600 to-indigo-700 text-white rounded-xl
                        hover:from-indigo-700 hover:to-indigo-800 shadow-md hover:shadow-lg transition-all
                        font-semibold flex items-center justify-center gap-2">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span id="btn-text">Save All Changes</span>
                </button>

                {{-- DELETE ACCOUNT --}}
                <div class="w-full max-w-8xl p-5 bg-red-50/60 rounded-xl border border-red-100">
                    <form id="delete-form" action="{{ route('profile.destroy') }}" method="POST">
                        @csrf
                        @method('DELETE')
                    </form>
                    <h2 class="text-lg font-semibold text-red-700 mb-2 flex items-center gap-2">
                        <i class="fa-solid fa-triangle-exclamation text-red-500"></i>
                        Delete Account
                    </h2>
                    <p class="text-sm text-red-600/80 mb-4">
                        Once your account is deleted, all data will be permanently removed.
                    </p>
                    <button type="button" onclick="openDeleteModal()"
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

    {{-- DELETE MODAL (Tetap sama) --}}
    <div id="deleteModal" class="fixed inset-0 bg-black/40 hidden items-center justify-center z-50">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">

            <h2 class="text-lg font-semibold text-gray-900 mb-2">Konfirmasi Hapus Akun</h2>
            <p class="text-sm text-gray-600 mb-4">
                Tindakan ini tidak bisa dibatalkan.
            </p>

            @if ($user->has_password)
                <input type="password" id="delete-password" placeholder="Masukkan password"
                    class="w-full border rounded-lg p-2 mb-4 focus:ring-2 focus:ring-red-500">
            @endif

            <div class="flex justify-end gap-2">
                <button onclick="closeDeleteModal()" class="px-4 py-2 text-gray-600 hover:text-gray-800">
                    Batal
                </button>

                <button onclick="submitDelete()" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                    Hapus
                </button>
            </div>
        </div>
    </div>

    @push('scripts')
        {{-- SCRIPT TETAP SAMA PERSIS --}}
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

            function confirmDelete() {
                let password = null;
                const isGoogleUser = {{ !$user->has_password ? 'true' : 'false' }};

                if (!isGoogleUser) {
                    password = prompt('Masukkan password untuk konfirmasi hapus akun:');
                    if (!password) return;
                }

                const form = document.getElementById('delete-form');
                if (password) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'password';
                    input.value = password;
                    form.appendChild(input);
                }
                form.submit();
            }

            function togglePhotoDropdown() {
                const dd = document.getElementById('photoDropdown');
                dd.classList.toggle('hidden');
            }

            // Tutup dropdown saat klik di luar
            document.addEventListener('click', function(e) {
                const wrap = document.getElementById('avatarWrap');
                const dd = document.getElementById('photoDropdown');
                if (wrap && !wrap.contains(e.target)) {
                    dd.classList.add('hidden');
                }
            });

            function triggerFileInput() {
                document.getElementById('photoDropdown').classList.add('hidden');
                document.getElementById('profile_photo').click();
            }

            function previewFoto(input) {
                if (input.files && input.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        // Hapus inisial jika ada
                        const initial = document.getElementById('avatarInitial');
                        if (initial) initial.remove();

                        // Update atau buat img
                        let img = document.getElementById('avatarImg');
                        if (!img) {
                            img = document.createElement('img');
                            img.id = 'avatarImg';
                            img.className = 'w-28 h-28 rounded-full object-cover border-4 border-white shadow-md';
                            document.getElementById('editOverlay').before(img);
                        }
                        img.src = e.target.result;

                        // Tampilkan tombol hapus
                        const btnHapus = document.getElementById('btnHapusFoto');
                        if (!btnHapus) {
                            // Tambah divider + tombol hapus secara dinamis
                            const dd = document.getElementById('photoDropdown');
                            dd.insertAdjacentHTML('beforeend', `
                                <div class="border-t border-gray-100"></div>
                                <button type="button" id="btnHapusFoto" onclick="hapusFotoProfil()"
                                    class="flex items-center gap-2.5 w-full px-4 py-2.5 text-sm text-red-500 hover:bg-red-50 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                                        <path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/>
                                    </svg>
                                    Hapus foto profil
                                </button>
                            `);
                        }
                    };
                    reader.readAsDataURL(input.files[0]);
                }
            }

            function hapusFotoProfil() {
                document.getElementById('photoDropdown').classList.add('hidden');

                // Kembalikan ke inisial
                const img = document.getElementById('avatarImg');
                if (img) img.remove();

                const wrap = document.getElementById('avatarWrap');
                const initial = document.createElement('div');
                initial.id = 'avatarInitial';
                initial.className =
                    'w-28 h-28 rounded-full bg-indigo-100 flex items-center justify-center text-2xl font-bold text-indigo-700 border-4 border-white shadow-md';
                initial.textContent = '{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}';
                wrap.prepend(initial);

                // Beri sinyal ke server bahwa foto dihapus
                document.getElementById('remove_photo').value = '1';
                document.getElementById('profile_photo').value = '';

                // Hapus tombol hapus dari dropdown
                const btnHapus = document.getElementById('btnHapusFoto');
                if (btnHapus) btnHapus.previousElementSibling.remove(), btnHapus.remove();
            }

            function openDeleteModal() {
                document.getElementById('deleteModal').classList.remove('hidden');
                document.getElementById('deleteModal').classList.add('flex');
            }

            function closeDeleteModal() {
                document.getElementById('deleteModal').classList.add('hidden');
            }

            function submitDelete() {
                const form = document.getElementById('delete-form');
                const isGoogleUser = {{ !$user->has_password ? 'true' : 'false' }};

                if (!isGoogleUser) {
                    const password = document.getElementById('delete-password').value;
                    if (!password) {
                        alert('Password wajib diisi');
                        return;
                    }
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'password';
                    input.value = password;
                    form.appendChild(input);
                }
                form.submit();
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
                sessionStorage.removeItem('submit_info');
                sessionStorage.removeItem('submit_password');
            });
        </script>
    @endpush
@endsection
