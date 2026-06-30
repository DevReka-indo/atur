@extends('layouts.app')

@section('content')
    <div class="flex flex-col h-full overflow-hidden overflow-x-hidden bg-gray-100 font-sans" id="chat-root">
        {{-- header --}}
        <header class="flex items-center gap-2.5 px-4 py-2.5 bg-white border-b border-gray-200 shadow-sm flex-shrink-0 z-10">
            <a href="{{ route('discussion.show', $project) }}"
                class="flex items-center justify-center w-9 h-9 rounded-full text-gray-500 no-underline hover:bg-gray-100 transition-colors flex-shrink-0"
                title="Kembali">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </a>

            <div
                class="w-10 h-10 rounded-full flex items-center justify-center text-base font-bold flex-shrink-0 {{ $project->getInitialColor() }}">
                {{ strtoupper(substr($project->name, 0, 1)) }}
            </div>

            <div class="flex-1 min-w-0">
                <h1 class="text-sm font-semibold text-gray-900 m-0 truncate">{{ $thread->title }}</h1>
                <p class="text-xs text-gray-500 m-0 truncate">{{ $project->workspace->name ?? $project->name }}</p>
            </div>

            <div class="flex gap-1 flex-shrink-0">
                <button
                    class="w-9 h-9 rounded-full border-0 bg-transparent text-gray-500 flex items-center justify-center cursor-pointer transition-colors hover:bg-gray-100 [&.active]:bg-emerald-50 [&.active]:text-teal-700"
                    id="btn-search-toggle" title="Cari pesan">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z" />
                    </svg>
                </button>
            </div>
        </header>

        {{-- search bar --}}
        <div class="bg-white border-b border-gray-200 max-h-0 overflow-hidden transition-all duration-200 flex-shrink-0 [&.open]:max-h-14"
            id="search-bar-wrap">
            <div class="flex items-center gap-2 px-4 py-2">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    class="text-gray-400 flex-shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z" />
                </svg>
                <input type="text" id="search-input" placeholder="Cari pesan..."
                    class="flex-1 border-0 outline-none text-sm text-gray-900 bg-transparent placeholder-gray-400"
                    autocomplete="off">
                <span class="text-xs text-gray-400 min-w-12 text-center" id="search-count"></span>
                <button
                    class="w-7 h-7 rounded-full border-0 bg-transparent text-gray-500 text-lg flex items-center justify-center cursor-pointer hover:bg-gray-100 disabled:opacity-30 disabled:cursor-default transition-colors"
                    id="search-prev" disabled title="Sebelumnya">&#8249;</button>
                <button
                    class="w-7 h-7 rounded-full border-0 bg-transparent text-gray-500 text-lg flex items-center justify-center cursor-pointer hover:bg-gray-100 disabled:opacity-30 disabled:cursor-default transition-colors"
                    id="search-next" disabled title="Berikutnya">&#8250;</button>
                <button
                    class="w-7 h-7 rounded-full border-0 bg-transparent text-gray-400 text-sm flex items-center justify-center cursor-pointer hover:bg-red-50 hover:text-red-600 transition-colors"
                    id="search-close" title="Tutup">&#10005;</button>
            </div>
        </div>

        {{-- Chat --}}
        <main class="flex-1 overflow-y-auto overflow-x-hidden px-4 pt-3 pb-2 bg-gray-100 scroll-smooth" id="chat-container">
            @forelse($messages as $index => $message)
                @php
                    $isMe = $message->user_id == auth()->id();
                    $theme = $message->user->getChatTheme();
                    $prevMessage = $index > 0 ? $messages[$index - 1] : null;
                    $nextMessage = isset($messages[$index + 1]) ? $messages[$index + 1] : null;
                    $isFirstInGroup = !$prevMessage || $prevMessage->user_id != $message->user_id;
                    $isLastInGroup = !$nextMessage || $nextMessage->user_id != $message->user_id;
                    $showDateSeparator =
                        $index === 0 ||
                        ($prevMessage &&
                            $message->created_at->format('Y-m-d') !== $prevMessage->created_at->format('Y-m-d'));
                    if ($message->created_at->isToday()) {
                        $dateLabel = 'Hari ini';
                    } elseif ($message->created_at->isYesterday()) {
                        $dateLabel = 'Kemarin';
                    } else {
                        $dateLabel = $message->created_at->translatedFormat('d F Y');
                    }
                @endphp

                @if ($showDateSeparator)
                    <div class="flex justify-center my-3.5 mb-2.5">
                        <span
                            class="bg-white text-gray-500 text-xs font-medium px-3.5 py-0.5 rounded-full shadow-sm border border-black/5">
                            {{ $dateLabel }}
                        </span>
                    </div>
                @endif

                <div class="flex items-end gap-1.5 mb-0.5 cursor-pointer select-none
                    {{ $isMe ? 'flex-row-reverse' : 'flex-row' }}
                    {{ $isFirstInGroup ? 'mt-2.5' : '' }}
                    {{ $isLastInGroup ? 'mb-1.5' : '' }}"
                    data-id="{{ $message->id }}" data-is-me="{{ $isMe ? '1' : '0' }}"
                    data-content="{{ $message->content }}">

                    @if (!$isMe)
                        <div class="w-8 flex-shrink-0 flex items-end">
                            @if ($isLastInGroup)
                                @if ($message->user->profile_photo)
                                    <img src="{{ asset('storage/' . $message->user->profile_photo) }}"
                                        class="w-8 h-8 rounded-full object-cover flex-shrink-0 shadow-sm"
                                        title="{{ $message->user->name }}">
                                @else
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0 shadow-sm {{ $theme['avatar'] }}"
                                        title="{{ $message->user->name }}">
                                        {{ $theme['initial'] }}
                                    </div>
                                @endif
                            @endif
                        </div>
                    @endif

                    <div class="{{ $isMe ? 'items-end' : 'items-start' }} flex flex-col min-w-0"
                        style="max-width: min(65%, 340px)">
                        @if (!$isMe && $isFirstInGroup)
                            <span class="text-xs font-semibold text-teal-700 mb-0.5 pl-3">
                                {{ $message->user->name }}
                            </span>
                        @endif

                        {{-- Bubble --}}
                        <div class="relative group bubble-wrap" data-bubble-wrap>

                            <div class="relative px-3 pt-1.5 pb-1.5 shadow-sm after:content-[''] after:table after:clear-both transition-all duration-100
    {{ $isMe ? 'bg-green-100 text-gray-900 hover:brightness-95' : 'bg-white text-gray-900 hover:brightness-95' }}
    {{ $isFirstInGroup ? ($isMe ? 'bubble-tail-right' : 'bubble-tail-left') : '' }}
    {{ $isFirstInGroup && $isLastInGroup
        ? ($isMe
            ? 'rounded-2xl rounded-tr-sm'
            : 'rounded-2xl rounded-tl-sm')
        : ($isFirstInGroup
            ? ($isMe
                ? 'rounded-2xl rounded-tr-sm'
                : 'rounded-2xl rounded-tl-sm')
            : ($isLastInGroup
                ? ($isMe
                    ? 'rounded-2xl rounded-br-sm'
                    : 'rounded-2xl rounded-bl-sm')
                : 'rounded-2xl')) }}"
                                data-content="{{ $message->content }}">

                                @if ($message->attachment_path)
                                    @php $ext = strtolower(pathinfo($message->attachment_path, PATHINFO_EXTENSION)); @endphp
                                    @if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']))
                                        <img src="{{ asset('storage/' . $message->attachment_path) }}"
                                            class="max-w-56 max-h-44 rounded-xl block mb-1 cursor-zoom-in object-cover"
                                            alt="attachment" onclick="window.open(this.src)">
                                    @else
                                        <a href="{{ asset('storage/' . $message->attachment_path) }}" target="_blank"
                                            class="inline-flex items-center gap-1.5 text-xs text-teal-700 no-underline bg-teal-50 rounded-lg px-2.5 py-1.5 mb-1 border border-teal-200 hover:bg-teal-100 transition-colors">
                                            <svg width="16" height="16" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                            </svg>
                                            {{ basename($message->attachment_path) }}
                                        </a>
                                    @endif
                                @endif
                                {{-- GANTI dengan ini: --}}
                                <span class="bubble-text text-sm leading-relaxed"
                                    style="word-break: break-word; overflow-wrap: anywhere; white-space: pre-wrap; display: block;">{{ $message->content }}@if ($message->is_edited ?? false)<span class="text-xs text-gray-400 italic"> diedit</span>@endif<span style="display: inline-block; width: 48px;"> </span></span>
                                <span class="{{ $isMe ? 'text-green-600' : 'text-gray-400' }} text-xs whitespace-nowrap"
                                    style="float: right; margin-top: -1.2rem; line-height: 1.6;">{{ $message->created_at->format('H:i') }}</span>
                                @if ($isMe)
                                    <button
                                        class="bubble-menu-btn absolute top-1 right-1 w-5 h-5 rounded-sm items-center justify-center text-gray-600 opacity-0 group-hover:opacity-100 transition-opacity duration-150 hidden group-hover:flex z-10 bg-black/[0.08]"
                                        data-menu-btn title="Opsi">
                                        <i class="fa-solid fa-angle-down text-xs"></i>
                                    </button>
                                @endif
                            </div>

                            @if ($isMe)
                                <div class="bubble-dropdown hidden absolute right-full top-0 mr-2 bg-white rounded-xl shadow-xl min-w-40 z-50 py-1 origin-top-right"
                                    data-dropdown>
                                    <button
                                        class="flex items-center gap-2.5 w-full px-4 py-2.5 border-0 bg-transparent text-sm text-gray-700 cursor-pointer text-left hover:bg-gray-50 transition-colors dropdown-edit-btn">
                                        <svg width="15" height="15" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                        Edit Pesan
                                    </button>
                                    <div class="h-px bg-gray-100 my-1"></div>
                                    <button
                                        class="flex items-center gap-2.5 w-full px-4 py-2.5 border-0 bg-transparent text-sm text-red-600 cursor-pointer text-left hover:bg-red-50 transition-colors dropdown-delete-btn">
                                        <svg width="15" height="15" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        Hapus Pesan
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="flex flex-col items-center justify-center h-full min-h-64 text-center p-8">
                    <div class="w-16 h-16 bg-white/80 rounded-full flex items-center justify-center mb-3.5">
                        <svg width="36" height="36" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            class="text-gray-300">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                    </div>
                    <p class="text-sm font-semibold text-gray-500 m-0 mb-1.5">Belum ada pesan</p>
                    <p class="text-xs text-gray-400 m-0">Mulai diskusi dengan mengirim pesan pertama</p>
                </div>
            @endforelse
        </main>

        {{-- input --}}
        <footer class="bg-gray-100 px-3 pt-1.5 pb-2.5 border-t border-gray-200 flex-shrink-0">
            <div class="flex items-center gap-2">
                {{-- Edit badge --}}
                <div class="hidden items-center gap-1.5 bg-emerald-50 border-l-2 border-teal-700 rounded-t-lg px-3 py-1.5 text-xs font-semibold text-teal-700 -mb-0.5"
                    id="edit-badge">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    <span>Mode Edit</span>
                    <button type="button" id="cancel-edit" title="Batalkan"
                        class="ml-auto border-0 bg-transparent text-teal-700 cursor-pointer text-sm leading-none">&#10005;</button>
                </div>

                <input type="text" id="message-input" placeholder="Ketik pesan..."
                    class="flex-1 bg-white border-0 rounded-3xl px-4 py-2.5 text-sm text-gray-900 outline-none shadow-sm focus:shadow-md focus:ring-2 focus:ring-green-200 placeholder-gray-400 transition-shadow min-w-0"
                    autocomplete="off">

                <button type="button" id="send-btn" title="Kirim"
                    class="w-10 h-10 rounded-full border-0 bg-gray-300 text-white flex items-center justify-center cursor-pointer flex-shrink-0 shadow-md transition-all duration-200 active:scale-95"
                    id="send-btn">
                    <i class="fa-solid fa-paper-plane text-sm"></i>
                </button>
            </div>
        </footer>
    </div>

    {{-- hapus --}}
    <div class="fixed inset-0 bg-black/45 z-50 hidden items-center justify-center p-5 [&.show]:flex" id="delete-modal">
        <div class="bg-white rounded-2xl px-6 pt-7 pb-5 max-w-xs w-full text-center shadow-2xl animate-modal-in">
            <div class="w-12 h-12 bg-orange-50 rounded-full flex items-center justify-center mx-auto mb-3 text-orange-600">
                <svg width="26" height="26" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                </svg>
            </div>
            <h3 class="text-base font-bold text-gray-900 m-0 mb-1.5">Hapus Pesan?</h3>
            <p class="text-sm text-gray-500 m-0 mb-5 leading-relaxed">Pesan akan dihapus permanen dan tidak bisa
                dikembalikan.</p>
            <div class="flex gap-2.5">
                <button
                    class="flex-1 py-2.5 rounded-xl border-0 text-sm font-semibold cursor-pointer bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors"
                    id="delete-cancel">Batal</button>
                <button
                    class="flex-1 py-2.5 rounded-xl border-0 text-sm font-semibold cursor-pointer bg-red-600 text-white hover:bg-red-700 transition-colors"
                    id="delete-confirm">Hapus</button>
            </div>
        </div>
    </div>

    <style>
        .bubble-tail-left::before {
            content: '';
            position: absolute;
            top: 0;
            left: -7px;
            width: 0;
            height: 0;
            border-top: 7px solid white;
            border-left: 7px solid transparent;
        }

        .bubble-tail-right::before {
            content: '';
            position: absolute;
            top: 0;
            right: -7px;
            width: 0;
            height: 0;
            border-top: 7px solid #dcfce7;
            border-right: 7px solid transparent;
        }

        .bubble-wrap {
            width: fit-content;
            max-width: 100%;
            display: block;
        }
    </style>
@endsection

@push('scripts')
    <script>
        (function() {
            const ms = document.getElementById('main-scroll');
            if (ms && document.getElementById('chat-root')) {
                ms.style.cssText =
                    'padding: 0 !important; overflow: hidden !important; display: flex !important; flex-direction: column !important; height: 0 !important; flex: 1 1 0% !important;';
            }
        })();

        document.addEventListener('DOMContentLoaded', function() {

            const PROJECT_ID = {{ $project->id }};
            const THREAD_ID = {{ $thread->id }};
            const CSRF = document.querySelector('meta[name="csrf-token"]').content;

            const container = document.getElementById('chat-container');
            const msgInput = document.getElementById('message-input');
            const sendBtn = document.getElementById('send-btn');
            const editBadge = document.getElementById('edit-badge');
            const cancelEdit = document.getElementById('cancel-edit');
            const delModal = document.getElementById('delete-modal');
            const delCancel = document.getElementById('delete-cancel');
            const delConfirm = document.getElementById('delete-confirm');

            let editingId = null;
            let deletingId = null;
            let openDropdown = null;

            /* ── Scroll to bottom ── */
            function scrollBottom(smooth) {
                container.scrollTo({
                    top: container.scrollHeight,
                    behavior: smooth ? 'smooth' : 'instant'
                });
            }
            scrollBottom(false);
            msgInput.focus();

            /* ── Send button state ── */
            function updateSendBtn() {
                const ok = msgInput.value.trim().length > 0;
                if (ok) {
                    sendBtn.classList.add('bg-green-500', 'shadow-[0_4px_12px_rgba(34,197,94,0.4)]',
                        'cursor-pointer');
                    sendBtn.classList.remove('bg-gray-300', 'cursor-default');
                } else {
                    sendBtn.classList.add('bg-gray-300', 'cursor-default');
                    sendBtn.classList.remove('bg-green-500', 'shadow-[0_4px_12px_rgba(34,197,94,0.4)]',
                        'cursor-pointer');
                }
            }
            msgInput.addEventListener('input', updateSendBtn);

            sendBtn.addEventListener('click', sendMessage);
            msgInput.addEventListener('keydown', e => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendMessage();
                }
            });

            async function sendMessage() {
                const content = msgInput.value.trim();
                if (!content) return;
                if (sendBtn.classList.contains('loading')) return;
                sendBtn.classList.add('loading');

                try {
                    if (editingId) {
                        const resp = await fetch(
                            `/discussion/${PROJECT_ID}/thread/${THREAD_ID}/messages/${editingId}`, {
                                method: 'PATCH',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': CSRF,
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({
                                    content
                                }),
                            });
                        if (!resp.ok) throw new Error('Gagal edit');
                        const data = await resp.json();

                        const row = document.querySelector(`[data-id="${editingId}"]`);
                        if (row) {
                            const bubble = row.querySelector('[data-content]');
                            const textEl = bubble.querySelector('.bubble-text');
                            textEl.textContent = data.content;
                            bubble.dataset.content = data.content;
                            if (!bubble.querySelector('.edited-label')) {
                                const lbl = document.createElement('span');
                                lbl.className = 'text-xs text-gray-400 italic ml-0.5 edited-label';
                                lbl.textContent = 'diedit';
                                textEl.after(lbl);
                            }
                        }
                        cancelEditMode();

                    } else {
                        const resp = await fetch(`/discussion/${PROJECT_ID}/thread/${THREAD_ID}/messages`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': CSRF,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                content
                            }),
                        });
                        if (!resp.ok) throw new Error('Gagal kirim');
                        const data = await resp.json();
                        appendMessage(data);
                    }

                    msgInput.value = '';
                    updateSendBtn();
                    scrollBottom(true);

                } catch (err) {
                    console.error(err);
                    alert('Terjadi kesalahan: ' + err.message);
                } finally {
                    sendBtn.classList.remove('loading');
                }
            }

            function appendMessage(data) {
                const div = document.createElement('div');
                div.className = 'flex items-end gap-1.5 mb-1.5 mt-2.5 cursor-pointer select-none flex-row-reverse';
                div.dataset.id = data.id;
                div.dataset.isMe = '1';
                div.dataset.content = data.content;
                div.innerHTML = `
                <div class="flex flex-col min-w-0 items-end" style="max-width: min(65%, 340px)">
                <div class="relative group bubble-wrap" data-bubble-wrap>
                            <div class="relative px-3 pt-1.5 pb-1.5 shadow-sm bubble-tail-right after:content-[''] after:table after:clear-both bg-green-100 text-gray-900 rounded-2xl rounded-tr-sm" data-content="${escHtml(data.content)}">
                                ${data.attachment_url ? buildAttachHTML(data.attachment_url, data.attachment_name) : ''}
                                <span class="bubble-text text-sm leading-relaxed" style="word-break: break-word; overflow-wrap: anywhere; white-space: pre-wrap; display: block;">${escHtml(data.content)}<span style="display: inline-block; width: 48px;"> </span></span>
                                <span class="text-green-600 text-xs whitespace-nowrap" style="float: right; margin-top: -1.2rem; line-height: 1.6;">${data.time}</span>
                                <button class="bubble-menu-btn absolute top-1 right-1 w-5 h-5 rounded-sm items-center justify-center text-gray-600 opacity-0 group-hover:opacity-100 transition-opacity duration-150 hidden group-hover:flex z-10 bg-black/[0.08]" data-menu-btn title="Opsi">
                                    <i class="fa-solid fa-angle-down text-xs"></i>
                                </button>
                            </div>
                            <div class="bubble-dropdown hidden absolute right-full top-0 mr-2 bg-white rounded-xl shadow-xl min-w-40 z-50 py-1 origin-top-right" data-dropdown>
                                <button class="flex items-center gap-2.5 w-full px-4 py-2.5 border-0 bg-transparent text-sm text-gray-700 cursor-pointer text-left hover:bg-gray-50 transition-colors dropdown-edit-btn">
                                    <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    Edit Pesan
                                </button>
                                <div class="h-px bg-gray-100 my-1"></div>
                                <button class="flex items-center gap-2.5 w-full px-4 py-2.5 border-0 bg-transparent text-sm text-red-600 cursor-pointer text-left hover:bg-red-50 transition-colors dropdown-delete-btn">
                                    <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    Hapus Pesan
                                </button>
                            </div>
                        </div>
                    </div>`;
                attachBubbleDropdown(div);
                container.appendChild(div);
            }

            function buildAttachHTML(url, name) {
                const ext = (url.split('.').pop() || '').toLowerCase();
                if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext)) {
                    return `<img src="${url}" class="max-w-56 max-h-44 rounded-xl block mb-1 cursor-zoom-in object-cover" alt="attachment" onclick="window.open(this.src)">`;
                }
                return `<a href="${url}" target="_blank" class="inline-flex items-center gap-1.5 text-xs text-teal-700 no-underline bg-teal-50 rounded-lg px-2.5 py-1.5 mb-1 border border-teal-200">📎 ${escHtml(name || 'file')}</a>`;
            }

            function escHtml(str) {
                return String(str)
                    .replace(/&/g, '&amp;').replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
            }

            function closeOpenDropdown() {
                if (openDropdown) {
                    openDropdown.classList.remove('block', 'animate-drop-in');
                    openDropdown.classList.add('hidden');
                    openDropdown = null;
                }
            }

            function attachBubbleDropdown(row) {
                if (row.dataset.isMe !== '1') return;

                const menuBtn = row.querySelector('[data-menu-btn]');
                const dropdown = row.querySelector('[data-dropdown]');
                const editBtn = row.querySelector('.dropdown-edit-btn');
                const delBtn = row.querySelector('.dropdown-delete-btn');

                if (!menuBtn || !dropdown) return;

                menuBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    if (openDropdown && openDropdown !== dropdown) closeOpenDropdown();
                    const isOpen = dropdown.classList.contains('show');
                    if (isOpen) {
                        closeOpenDropdown();
                    } else {
                        dropdown.classList.remove('hidden');
                        dropdown.classList.add('block', 'animate-drop-in');
                        openDropdown = dropdown;
                    }
                });

                const bubble = row.querySelector('[data-content]');
                if (bubble) {
                    bubble.addEventListener('click', function(e) {
                        if (e.target.closest('[data-menu-btn]') || e.target.closest('[data-dropdown]'))
                            return;
                        e.stopPropagation();
                        if (openDropdown && openDropdown !== dropdown) closeOpenDropdown();
                        const isOpen = dropdown.classList.contains('show');
                        if (isOpen) {
                            closeOpenDropdown();
                        } else {
                            dropdown.classList.remove('hidden');
                            dropdown.classList.add('show');
                            openDropdown = dropdown;
                        }
                    });
                }

                editBtn && editBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    closeOpenDropdown();
                    const msgId = row.dataset.id;
                    const content = row.querySelector('[data-content]')?.dataset.content || '';
                    editingId = msgId;
                    msgInput.value = content;
                    editBadge.classList.remove('hidden');
                    editBadge.classList.add('flex');
                    sendBtn.classList.add('active');
                    msgInput.focus();
                });

                delBtn && delBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    closeOpenDropdown();
                    deletingId = row.dataset.id;
                    delModal.classList.remove('hidden');
                    delModal.classList.add('show');
                });
            }

            document.querySelectorAll('[data-is-me="1"]').forEach(attachBubbleDropdown);
            document.addEventListener('click', closeOpenDropdown);

            cancelEdit.addEventListener('click', cancelEditMode);

            function cancelEditMode() {
                editingId = null;
                msgInput.value = '';
                editBadge.classList.add('hidden');
                editBadge.classList.remove('flex');
                updateSendBtn();
            }

            delCancel.addEventListener('click', () => {
                delModal.classList.remove('show');
                delModal.classList.add('hidden');
                deletingId = null;
            });
            delModal.addEventListener('click', e => {
                if (e.target === delModal) {
                    delModal.classList.remove('show');
                    delModal.classList.add('hidden');
                    deletingId = null;
                }
            });

            delConfirm.addEventListener('click', async () => {
                if (!deletingId) return;
                delConfirm.textContent = 'Menghapus...';
                delConfirm.disabled = true;

                try {
                    const resp = await fetch(
                        `/discussion/${PROJECT_ID}/thread/${THREAD_ID}/messages/${deletingId}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': CSRF,
                                'Accept': 'application/json'
                            },
                        });
                    if (!resp.ok) throw new Error('Gagal hapus');

                    const row = document.querySelector(`[data-id="${deletingId}"]`);
                    if (row) {
                        row.classList.add('transition-all', 'duration-200', 'opacity-0', 'scale-95');
                        setTimeout(() => row.remove(), 200);
                    }
                    delModal.classList.remove('show');
                    delModal.classList.add('hidden');
                    deletingId = null;

                } catch (err) {
                    alert('Gagal menghapus pesan: ' + err.message);
                } finally {
                    delConfirm.textContent = 'Hapus';
                    delConfirm.disabled = false;
                }
            });

            const btnToggle = document.getElementById('btn-search-toggle');
            const searchWrap = document.getElementById('search-bar-wrap');
            const searchInput = document.getElementById('search-input');
            const searchCount = document.getElementById('search-count');
            const btnPrev = document.getElementById('search-prev');
            const btnNext = document.getElementById('search-next');
            const btnClose = document.getElementById('search-close');

            let matches = [],
                matchIdx = 0;
            const origMap = new Map();
            document.querySelectorAll('.bubble-text').forEach(el => origMap.set(el, el.innerHTML));

            const openSearch = () => {
                searchWrap.classList.add('open');
                btnToggle.classList.add('active');
                setTimeout(() => searchInput.focus(), 200);
            };
            const closeSearch = () => {
                searchWrap.classList.remove('open');
                btnToggle.classList.remove('active');
                searchInput.value = '';
                clearHL();
                searchCount.textContent = '';
            };
            const clearHL = () => {
                origMap.forEach((html, el) => el.innerHTML = html);
                matches = [];
            };

            function doSearch(q) {
                clearHL();
                if (!q.trim()) {
                    searchCount.textContent = '';
                    return;
                }
                const re = new RegExp('(' + q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
                document.querySelectorAll('.bubble-text').forEach(el => {
                    const orig = origMap.get(el) || el.textContent;
                    const nh = orig.replace(re,
                        '<mark class="bg-yellow-200 rounded-sm px-px">$1</mark>'
                    );
                    if (nh !== orig) {
                        el.innerHTML = nh;
                        el.querySelectorAll('mark').forEach(m => matches.push(m));
                    }
                });
                matchIdx = 0;
                updateNav();
            }

            function updateNav() {
                matches.forEach(m => m.classList.remove('!bg-orange-400'));
                if (!matches.length) {
                    searchCount.textContent = searchInput.value.trim() ? '0 hasil' : '';
                    btnPrev.disabled = btnNext.disabled = true;
                    return;
                }
                matches[matchIdx].classList.add('!bg-orange-400');
                matches[matchIdx].scrollIntoView({
                    block: 'center',
                    behavior: 'smooth'
                });
                searchCount.textContent = `${matchIdx + 1} / ${matches.length}`;
                btnPrev.disabled = matchIdx === 0;
                btnNext.disabled = matchIdx === matches.length - 1;
            }

            btnToggle.addEventListener('click', () =>
                searchWrap.classList.contains('open') ? closeSearch() : openSearch());
            btnClose.addEventListener('click', closeSearch);

            let sdebounce;
            searchInput.addEventListener('input', function() {
                clearTimeout(sdebounce);
                sdebounce = setTimeout(() => doSearch(this.value), 220);
            });
            btnNext.addEventListener('click', () => {
                if (matchIdx < matches.length - 1) {
                    matchIdx++;
                    updateNav();
                }
            });
            btnPrev.addEventListener('click', () => {
                if (matchIdx > 0) {
                    matchIdx--;
                    updateNav();
                }
            });
            searchInput.addEventListener('keydown', e => {
                if (e.key === 'Enter') {
                    e.shiftKey ? btnPrev.click() : btnNext.click();
                }
                if (e.key === 'Escape') closeSearch();
            });
        });
    </script>
@endpush
