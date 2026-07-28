@extends('layouts.app')

@section('title', $project->name)

@section('content')
    <div class="fixed inset-0 bg-gradient-to-br from-gray-50 to-gray-100/50 -z-10"></div>
    <div class="w-full px-4">

        <!-- Back -->
        <a href="{{ route('discussion.index', $project->token) }}"
            class="inline-flex items-center gap-3 text-s text-gray-500 hover:text-gray-700 mb-5">
            <i class="fa-solid fa-arrow-left"></i>
            Back
        </a>

        <!-- Project Header -->
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-xl font-bold text-gray-900">{{ $project->name }}</h1>
                <p class="text-sm text-gray-500 mt-0.5">
                    {{ $threads->count() }} topik diskusi · {{ $project->workspace->name ?? 'workspace' }}
                </p>
            </div>

            @if ($project->canContribute(Auth::user()))
                <button type="button" onclick="openAddTopicModal()"
                    class="group inline-flex items-center px-5 py-2.5 text-white font-medium rounded-xl bg-indigo-600 hover:bg-indigo-700 shadow-lg shadow-indigo-500/30 transition-all duration-300">
                    <i class="fa-solid fa-plus mr-2 transition-transform group-hover:rotate-90"></i>
                    Create New Topic
                </button>
            @endif
        </div>

        @php
            $avatarColors = [
                ['bg' => 'bg-pink-100',   'text' => 'text-pink-700'],
                ['bg' => 'bg-blue-100',   'text' => 'text-blue-700'],
                ['bg' => 'bg-green-100',  'text' => 'text-green-700'],
                ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-700'],
                ['bg' => 'bg-purple-100', 'text' => 'text-purple-700'],
                ['bg' => 'bg-orange-100', 'text' => 'text-orange-700'],
            ];
            $userProjectRole = $project->roleForUser(Auth::user());
            $isPrivileged    = $project->created_by === Auth::id()
                            || in_array($userProjectRole, ['owner', 'manager', 'member'])
                            || $project->workspace->isAdmin(Auth::user());
        @endphp

        <!-- Select Action Bar — muncul saat ada topik dipilih -->
        <div id="selectActionBar"
            class="hidden items-center justify-between mb-4 px-4 py-2.5
                   bg-white border border-gray-200 rounded-2xl shadow-sm transition-all duration-200">
            <span class="text-sm text-gray-500">
                <span id="selectCount" class="font-semibold text-blue-600">0</span> topik dipilih
            </span>
            <div class="flex items-center gap-2">
                <button type="button" onclick="cancelSelectMode()"
                    class="px-3 py-1.5 text-sm text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                    Batal
                </button>
                <button type="button" onclick="deleteSelected()"
                    class="px-3 py-1.5 text-sm font-medium text-red-600 bg-red-50 hover:bg-red-100
                           rounded-lg transition-colors flex items-center gap-1.5">
                    <i class="fa-solid fa-trash text-xs"></i>
                    Hapus
                </button>
            </div>
        </div>

        <!-- Discussion List -->
        <div class="space-y-3" id="discussion-list">

            @forelse($threads as $index => $thread)
                @php
                    $color    = $avatarColors[$index % count($avatarColors)];
                    $lastMsg  = $thread->messages->first();
                    $canDelete = $isPrivileged;
                @endphp

                <a href="{{ route('discussion.chat', [$project->id, $thread->id]) }}"
                    id="thread-{{ $thread->id }}"
                    data-thread-id="{{ $thread->id }}"
                    data-thread-title="{{ addslashes($thread->title) }}"
                    data-can-delete="{{ $canDelete ? 'true' : 'false' }}"
                    data-delete-url="{{ route('discussion.threads.destroy', [$project->id, $thread->id]) }}"
                    data-edit-url="{{ route('discussion.threads.update', [$project->id, $thread->id]) }}"
                    class="thread-card group relative flex items-center gap-3
                            bg-white border border-gray-200 rounded-2xl
                            px-6 py-4
                            hover:border-blue-200 hover:shadow-md hover:-translate-y-0.5
                            transition-all duration-200">

                    {{-- Checkbox area — selalu ada ruangnya (pl-3 di card), kotak muncul saat hover --}}
                    <div class="thread-cb-wrap flex-shrink-0 w-6 flex items-center justify-center">
                        <div class="thread-cb w-5 h-5 rounded-md border-2 border-gray-300 bg-white
                                    flex items-center justify-center
                                    transition-all duration-150"
                            style="opacity:0;">
                            <i class="fa-solid fa-check text-white thread-cb-icon hidden"></i>
                        </div>
                    </div>

                    {{-- Avatar --}}
                    <div class="thread-avatar w-11 h-11 rounded-2xl {{ $color['bg'] }}
                                flex items-center justify-center flex-shrink-0
                                transition-all duration-150">
                        <span class="text-sm font-bold {{ $color['text'] }}">
                            {{ strtoupper(substr($thread->title, 0, 1)) }}
                        </span>
                    </div>

                    {{-- Content --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h3 class="text-sm font-semibold text-gray-900
                                           group-hover:text-blue-600 transition-colors">
                                    {{ $thread->title }}
                                </h3>
                                <p class="text-sm text-gray-500 mt-0.5 truncate" id="preview-{{ $thread->id }}">
                                    @if ($lastMsg)
                                        <span class="font-medium text-gray-700">{{ $lastMsg->user->name ?? 'Unknown' }}:</span>
                                        {{ Str::limit($lastMsg->content, 70) }}
                                    @else
                                        Belum ada pesan.
                                    @endif
                                </p>
                            </div>

                            {{-- Time + Badge --}}
                            <div class="flex flex-col items-end gap-1 flex-shrink-0 pr-3">
                                <span class="text-xs text-gray-400 whitespace-nowrap" id="time-{{ $thread->id }}">
                                    @if ($lastMsg)
                                        @php $time = $lastMsg->created_at; @endphp
                                        @if ($time->isToday())
                                            {{ $time->format('H:i') }}
                                        @elseif ($time->isYesterday())
                                            Yesterday
                                        @else
                                            {{ $time->format('d M Y') }}
                                        @endif
                                    @endif
                                </span>
                                <span id="badge-{{ $thread->id }}"
                                    data-count="{{ $thread->unread_count }}"
                                    class="inline-flex items-center justify-center min-w-[20px] h-5 px-1.5
                                           bg-green-500 text-white text-xs font-bold rounded-full leading-none
                                           {{ $thread->unread_count > 0 ? '' : 'hidden' }}">
                                    {{ $thread->unread_count > 99 ? '99+' : $thread->unread_count }}
                                </span>
                            </div>
                        </div>
                    </div>
                </a>

            @empty
                <div class="flex flex-col items-center justify-center py-20 px-6 text-center
                            bg-white border border-dashed border-gray-300 rounded-2xl">
                    <div class="w-16 h-16 rounded-2xl bg-gray-100 flex items-center justify-center mb-4">
                        <i class="fa-regular fa-comment-dots text-gray-400 text-2xl"></i>
                    </div>
                    <h3 class="text-base font-semibold text-gray-800">Belum ada topik diskusi</h3>
                    <p class="text-sm text-gray-500 mt-1">Mulai buat topik baru untuk diskusi project.</p>
                </div>
            @endforelse
        </div>

        <!-- Custom Context Menu -->
        <div id="contextMenu"
            class="fixed z-50 hidden w-48 bg-white border border-gray-200 rounded-xl shadow-xl py-1 overflow-hidden">
            <div id="contextMenuTitle"
                class="px-4 py-2 text-xs font-semibold text-gray-400 truncate border-b border-gray-100"></div>
            <button id="contextMenuEdit"
                class="w-full flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition-colors text-left hidden">
                <i class="fa-solid fa-pencil text-gray-400 text-xs w-3.5"></i>
                Edit Topik
            </button>
            <button id="contextMenuDelete"
                class="w-full flex items-center gap-2.5 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition-colors text-left hidden">
                <i class="fa-solid fa-trash text-xs w-3.5"></i>
                Hapus Topik
            </button>
        </div>

        <!-- Hidden form untuk delete single (context menu) -->
        <form id="deleteThreadForm" method="POST" class="hidden">
            @csrf
            @method('DELETE')
        </form>

        <!-- Modal Edit Topik -->
        <div id="editTopicModal" class="fixed inset-0 z-50 hidden items-center justify-center"
            style="background:rgba(17,24,39,0.45);backdrop-filter:blur(2px);">
            <div class="bg-white rounded-2xl shadow-2xl p-6 transform transition-all duration-200 scale-95 opacity-0"
                style="width:360px;max-width:calc(100vw - 2rem);" id="editModalContent">
                <div class="mb-5">
                    <h3 class="text-base font-bold text-gray-900">Edit Topik</h3>
                </div>
                <form id="editTopicForm" method="POST">
                    @csrf
                    @method('PATCH')
                    <div class="mb-5">
                        <label class="block text-sm text-gray-600 mb-1.5">Nama Topik</label>
                        <input type="text" name="name" id="edit_thread_name" required maxlength="255"
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm
                                   focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" onclick="closeEditTopicModal()"
                            class="px-4 py-2 text-sm text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                            Batal
                        </button>
                        <button type="submit"
                            class="px-4 py-2 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors">
                            Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ADD TOPIC MODAL -->
        @if ($project->canContribute(Auth::user()))
            <div id="addTopicModal" class="fixed inset-0 z-50 hidden items-center justify-center"
                style="background:rgba(17,24,39,0.45);backdrop-filter:blur(2px);">
                <div class="bg-white rounded-2xl shadow-2xl p-6 transform transition-all duration-200 scale-95 opacity-0"
                    style="width:360px;max-width:calc(100vw - 2rem);" id="modalContent">
                    <div class="mb-5">
                        <h3 class="text-base font-bold text-gray-900">Buat topik baru</h3>
                        <p class="text-xs text-gray-400 mt-0.5">Project: {{ $project->name }}</p>
                    </div>
                    <form action="{{ route('discussion.threads.store', $project->id) }}" method="POST">
                        @csrf
                        <div class="mb-5">
                            <label for="thread_name" class="block text-sm text-gray-600 mb-1.5">Nama Topik</label>
                            <input type="text" name="name" id="thread_name" value="{{ old('name') }}" required
                                maxlength="255"
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm
                                    focus:outline-none focus:ring-2 focus:ring-blue-500
                                    @error('name') border-red-400 @enderror">
                            @error('name')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="flex justify-end gap-2">
                            <button type="button" onclick="closeAddTopicModal()"
                                class="px-4 py-2 text-sm text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                                Batal
                            </button>
                            <button type="submit"
                                class="px-4 py-2 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors">
                                Buat Topik
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        @push('scripts')
        <script>
        let selectMode = false;
        const selected = new Set();
        function updateActionBar() {
            const bar   = document.getElementById('selectActionBar');
            const count = document.getElementById('selectCount');
            count.textContent = selected.size;
            if (selected.size > 0) {
                bar.classList.remove('hidden');
                bar.classList.add('flex');
            } else {
                bar.classList.add('hidden');
                bar.classList.remove('flex');
                if (selectMode) cancelSelectMode();
            }
        }

        function setCardVisual(card, isSelected) {
            const cb     = card.querySelector('.thread-cb');
            const cbIcon = card.querySelector('.thread-cb-icon');
            const avatar = card.querySelector('.thread-avatar');

            if (isSelected) {
                card.classList.add('!border-blue-400', '!bg-blue-50', '!shadow-none', '!translate-y-0');
                cb.style.opacity     = '1';
                cb.style.background  = '#3b82f6';
                cb.style.borderColor = '#3b82f6';
                cbIcon.style.display = 'block';
                avatar.classList.add('opacity-40');
            } else {
                card.classList.remove('!border-blue-400', '!bg-blue-50', '!shadow-none', '!translate-y-0');
                cb.style.background  = 'white';
                cb.style.borderColor = '#d1d5db';
                cbIcon.style.display = 'none';
                avatar.classList.remove('opacity-40');
                // Tetap tampil jika select mode aktif
                cb.style.opacity = selectMode ? '1' : '0';
            }
        }

        function enterSelectMode(firstCard) {
            selectMode = true;
            document.querySelectorAll('.thread-cb').forEach(cb => {
                cb.style.opacity = '1';
                cb.style.setProperty('opacity', '1', 'important'); // force override
            });
            toggleCard(firstCard);
        }

        function cancelSelectMode() {
            selectMode = false;
            selected.clear();
            document.querySelectorAll('a[data-thread-id]').forEach(card => {
                const cb     = card.querySelector('.thread-cb');
                const cbIcon = card.querySelector('.thread-cb-icon');
                const avatar = card.querySelector('.thread-avatar');
                card.classList.remove('!border-blue-400', '!bg-blue-50', '!shadow-none', '!translate-y-0');
                cb.style.opacity     = '0';
                cb.style.background  = 'white';
                cb.style.borderColor = '#d1d5db';
                cbIcon.style.display = 'none';
                avatar.classList.remove('opacity-40');
            });
            updateActionBar();
        }

        function toggleCard(card) {
            const id = card.dataset.threadId;
            if (selected.has(id)) {
                selected.delete(id);
                setCardVisual(card, false);
            } else {
                selected.add(id);
                setCardVisual(card, true);
            }
            updateActionBar();
        }

        async function deleteSelected() {
            if (selected.size === 0) return;

            const deletable = [...selected].filter(id => {
                const card = document.getElementById(`thread-${id}`);
                return card && card.dataset.canDelete === 'true';
            });

            if (deletable.length === 0) {
                alert('Kamu tidak punya izin untuk menghapus topik yang dipilih.');
                return;
            }

            const label = deletable.length === 1 ? '1 topik' : `${deletable.length} topik`;
            if (!confirm(`Hapus ${label}?\nSemua pesan di dalamnya akan ikut terhapus.`)) return;

            const csrf      = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const projectId = {{ $project->id }};

            await Promise.all(deletable.map(threadId => {
                const body = new FormData();
                body.append('_method', 'DELETE');
                body.append('_token', csrf);
                return fetch(`/discussion/${projectId}/threads/${threadId}`, {
                    method: 'POST',
                    body,
                });
            }));

            deletable.forEach(id => {
                document.getElementById(`thread-${id}`)?.remove();
            });

            cancelSelectMode();
        }

        document.querySelectorAll('a[data-thread-id]').forEach(card => {
            const cb = card.querySelector('.thread-cb');

            card.addEventListener('mouseenter', () => {
                if (!selectMode) cb.style.opacity = '1';
            });
            card.addEventListener('mouseleave', () => {
                if (!selectMode) {
                    cb.style.opacity = '0';
                }
            });

            let pressTimer = null;
            card.addEventListener('touchstart', () => {
                pressTimer = setTimeout(() => {
                    if (!selectMode) enterSelectMode(card);
                }, 500);
            }, { passive: true });
            card.addEventListener('touchend',  () => clearTimeout(pressTimer));
            card.addEventListener('touchmove', () => clearTimeout(pressTimer));

            card.querySelector('.thread-cb-wrap').addEventListener('click', e => {
                e.preventDefault();
                e.stopPropagation();
                if (!selectMode) enterSelectMode(card);
                else toggleCard(card);
            });

            // Klik card biasa
            card.addEventListener('click', e => {
                if (selectMode) {
                    e.preventDefault();
                    toggleCard(card);
                }
            });

            card.addEventListener('contextmenu', e => {
                if (selectMode) { e.preventDefault(); return; }
                showContextMenu(e, card);
            });
        });

        function openAddTopicModal() {
            const modal   = document.getElementById('addTopicModal');
            const content = document.getElementById('modalContent');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            requestAnimationFrame(() => {
                content.classList.remove('scale-95', 'opacity-0');
                content.classList.add('scale-100', 'opacity-100');
                document.getElementById('thread_name')?.focus();
            });
        }

        function closeAddTopicModal() {
            const modal   = document.getElementById('addTopicModal');
            const content = document.getElementById('modalContent');
            content.classList.remove('scale-100', 'opacity-100');
            content.classList.add('scale-95', 'opacity-0');
            setTimeout(() => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.getElementById('thread_name')?.closest('form')?.reset();
            }, 200);
        }

        document.getElementById('addTopicModal')?.addEventListener('click', e => {
            if (e.target.id === 'addTopicModal') closeAddTopicModal();
        });

        @if ($errors->any())
            openAddTopicModal();
        @endif

        function openEditTopicModal(title, editUrl) {
            const modal   = document.getElementById('editTopicModal');
            const content = document.getElementById('editModalContent');
            const form    = document.getElementById('editTopicForm');
            const input   = document.getElementById('edit_thread_name');
            form.action   = editUrl;
            input.value   = title;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            requestAnimationFrame(() => {
                content.classList.remove('scale-95', 'opacity-0');
                content.classList.add('scale-100', 'opacity-100');
                input.focus();
                input.select();
            });
        }

        function closeEditTopicModal() {
            const modal   = document.getElementById('editTopicModal');
            const content = document.getElementById('editModalContent');
            content.classList.remove('scale-100', 'opacity-100');
            content.classList.add('scale-95', 'opacity-0');
            setTimeout(() => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }, 200);
        }

        document.getElementById('editTopicModal')?.addEventListener('click', e => {
            if (e.target.id === 'editTopicModal') closeEditTopicModal();
        });

        const contextMenu = document.getElementById('contextMenu');
        const ctxTitle    = document.getElementById('contextMenuTitle');
        const ctxEdit     = document.getElementById('contextMenuEdit');
        const ctxDelete   = document.getElementById('contextMenuDelete');
        const deleteForm  = document.getElementById('deleteThreadForm');

        function showContextMenu(e, card) {
            e.preventDefault();
            const title     = card.dataset.threadTitle;
            const canDelete = card.dataset.canDelete === 'true';
            const deleteUrl = card.dataset.deleteUrl;
            const editUrl   = card.dataset.editUrl;

            ctxTitle.textContent        = title;
            contextMenu.dataset.editUrl = editUrl;
            contextMenu.dataset.title   = title;
            deleteForm.action           = deleteUrl;

            canDelete ? ctxEdit.classList.remove('hidden')   : ctxEdit.classList.add('hidden');
            canDelete ? ctxDelete.classList.remove('hidden') : ctxDelete.classList.add('hidden');

            contextMenu.classList.remove('hidden');
            const menuW = contextMenu.offsetWidth;
            const menuH = contextMenu.offsetHeight;
            let x = e.clientX, y = e.clientY;
            if (x + menuW > window.innerWidth)  x = window.innerWidth  - menuW - 8;
            if (y + menuH > window.innerHeight) y = window.innerHeight - menuH - 8;
            contextMenu.style.left = x + 'px';
            contextMenu.style.top  = y + 'px';
        }

        function closeContextMenu() { contextMenu.classList.add('hidden'); }

        ctxEdit.addEventListener('click', () => {
            closeContextMenu();
            openEditTopicModal(contextMenu.dataset.title, contextMenu.dataset.editUrl);
        });

        ctxDelete.addEventListener('click', () => {
            const title = ctxTitle.textContent;
            closeContextMenu();
            if (confirm(`Hapus topik "${title}"?\nSemua pesan di dalamnya akan ikut terhapus.`)) {
                deleteForm.submit();
            }
        });

        document.addEventListener('click', closeContextMenu);
        document.addEventListener('scroll', closeContextMenu, true);
        contextMenu.addEventListener('contextmenu', e => e.preventDefault());

        const unreadUrl = "{{ route('discussion.unread', $project->id) }}";

        function updateBadges() {
            if (selectMode) return;

            fetch(unreadUrl, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                }
            })
            .then(res => res.json())
            .then(threads => {
                const list   = document.getElementById('discussion-list');
                const sorted = [...threads].sort((a, b) => {
                    const tA = a.last_message?.created_at ? new Date(a.last_message.created_at) : new Date(0);
                    const tB = b.last_message?.created_at ? new Date(b.last_message.created_at) : new Date(0);
                    return tB - tA;
                });

                threads.forEach(thread => {
                    const badge = document.getElementById(`badge-${thread.id}`);
                    if (badge) {
                        const count = thread.unread_count;
                        badge.dataset.count = count;
                        if (count > 0) {
                            badge.textContent = count > 99 ? '99+' : count;
                            badge.classList.remove('hidden');
                        } else {
                            badge.classList.add('hidden');
                        }
                    }

                    const time = document.getElementById(`time-${thread.id}`);
                    if (time && thread.last_message) time.textContent = thread.last_message.time;

                    const preview = document.getElementById(`preview-${thread.id}`);
                    if (preview && thread.last_message) {
                        const sender = document.createElement('span');
                        sender.className = 'font-medium text-gray-700';
                        sender.textContent = `${thread.last_message.user_name}:`;
                        preview.replaceChildren(
                            sender,
                            document.createTextNode(` ${thread.last_message.content}`)
                        );
                    }
                });

                sorted.forEach(thread => {
                    const el = document.getElementById(`thread-${thread.id}`);
                    if (el && list) list.appendChild(el);
                });
            })
            .catch(err => console.error('Polling error:', err));
        }

        updateBadges();
        setInterval(updateBadges, 3000);
        </script>
        @endpush
    </div>
@endsection
