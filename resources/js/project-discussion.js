import { createMentionComposer, renderMentionContent } from './mention-composer';

const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

const openModal = (modal) => {
    if (!modal) {
        return;
    }

    modal.classList.remove('hidden');
    modal.classList.add('flex');
};

const closeModal = (modal) => {
    if (!modal) {
        return;
    }

    modal.classList.add('hidden');
    modal.classList.remove('flex');
};

const initializeDiscussionModals = () => {
    document.addEventListener('click', (event) => {
        const openButton = event.target.closest('[data-discussion-modal-open]');
        if (openButton) {
            openModal(document.getElementById(openButton.dataset.discussionModalOpen));
            return;
        }

        const closeButton = event.target.closest('[data-discussion-modal-close]');
        if (closeButton) {
            closeModal(closeButton.closest('[data-discussion-modal]'));
            return;
        }

        const modal = event.target.closest('[data-discussion-modal]');
        if (modal && event.target === modal) {
            closeModal(modal);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            document.querySelectorAll('[data-discussion-modal]').forEach(closeModal);
        }
    });
};

const initializeThreadList = () => {
    const root = document.querySelector('[data-project-discussions]');
    if (!root) {
        return;
    }

    root.addEventListener('click', (event) => {
        const renameButton = event.target.closest('[data-discussion-rename]');
        if (renameButton) {
            const form = document.getElementById('rename-discussion-form');
            const title = document.getElementById('rename-discussion-title');
            if (form && title) {
                form.action = renameButton.dataset.action;
                title.value = renameButton.dataset.title;
                openModal(document.getElementById('rename-discussion-modal'));
                title.focus();
            }
            return;
        }

        const deleteButton = event.target.closest('[data-discussion-delete]');
        if (deleteButton) {
            const form = document.getElementById('delete-discussion-form');
            if (form) {
                form.action = deleteButton.dataset.action;
                openModal(document.getElementById('delete-discussion-modal'));
            }
        }
    });

    const refreshUnread = async () => {
        if (!root.dataset.unreadUrl || document.hidden) {
            return;
        }

        try {
            const response = await fetch(root.dataset.unreadUrl, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                return;
            }

            const threads = await response.json();
            threads.forEach((thread) => {
                const badge = document.getElementById(`badge-${thread.id}`);
                if (badge) {
                    badge.textContent = thread.unread_count > 99 ? '99+' : String(thread.unread_count);
                    badge.classList.toggle('hidden', thread.unread_count === 0);
                }

                if (thread.last_message) {
                    const preview = document.getElementById(`preview-${thread.id}`);
                    const time = document.getElementById(`time-${thread.id}`);
                    if (preview) {
                        preview.textContent = `${thread.last_message.user_name}: ${thread.last_message.content}`;
                    }
                    if (time) {
                        time.textContent = thread.last_message.time;
                    }
                }
            });
        } catch {
            // A failed poll must not interrupt the discussion list.
        }
    };

    window.setInterval(refreshUnread, 30000);
};

const renderMessageContent = (element, message) => {
    renderMentionContent(element, message.content_segments || [
        { type: 'text', text: message.plain_text || message.content },
    ]);
};

const createMessageElement = (message, currentUserId) => {
    const isOwnMessage = Number(message.sender.id) === currentUserId;
    const article = document.createElement('article');
    article.dataset.discussionMessage = '';
    article.dataset.messageId = String(message.id);
    article.dataset.messageContent = message.content;
    article.className = `flex ${isOwnMessage ? 'justify-end' : 'justify-start'}`;

    const wrapper = document.createElement('div');
    wrapper.className = 'flex max-w-[85%] items-end gap-2 sm:max-w-[70%]';

    if (!isOwnMessage) {
        if (message.sender.avatar) {
            const avatar = document.createElement('img');
            avatar.src = message.sender.avatar;
            avatar.alt = '';
            avatar.className = 'h-8 w-8 flex-shrink-0 rounded-full object-cover';
            wrapper.append(avatar);
        } else {
            const avatarFallback = document.createElement('span');
            avatarFallback.className = 'flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-indigo-100 text-xs font-semibold text-indigo-700';
            avatarFallback.textContent = message.sender.name.charAt(0).toLocaleUpperCase();
            wrapper.append(avatarFallback);
        }
    }

    const contentWrapper = document.createElement('div');
    if (!isOwnMessage) {
        const sender = document.createElement('p');
        sender.className = 'mb-1 px-2 text-xs font-semibold text-indigo-700';
        sender.textContent = message.sender.name;
        contentWrapper.append(sender);
    }

    const bubble = document.createElement('div');
    bubble.className = `group relative rounded-2xl px-4 py-2 shadow-sm ${isOwnMessage ? 'bg-green-100' : 'bg-white'}`;

    const text = document.createElement('p');
    text.dataset.messageText = '';
    text.className = 'whitespace-pre-wrap break-words text-sm text-gray-900';
    renderMessageContent(text, message);

    const meta = document.createElement('div');
    meta.className = 'mt-1 flex items-center justify-end gap-2';

    const edited = document.createElement('span');
    edited.dataset.messageEdited = '';
    edited.className = `${message.edited_at ? '' : 'hidden'} text-[11px] text-gray-400`;
    edited.textContent = 'edited';

    const time = document.createElement('time');
    time.className = 'text-[11px] text-gray-400';
    time.dateTime = message.created_at;
    time.textContent = message.created_at_human;

    meta.append(edited, time);
    if (message.can_edit) {
        const edit = document.createElement('button');
        edit.type = 'button';
        edit.dataset.messageEdit = '';
        edit.className = 'text-[11px] font-medium text-gray-500 hover:text-indigo-600';
        edit.textContent = 'Edit';
        meta.append(edit);
    }
    if (message.can_delete) {
        const remove = document.createElement('button');
        remove.type = 'button';
        remove.dataset.messageDelete = '';
        remove.className = 'text-[11px] font-medium text-gray-500 hover:text-red-600';
        remove.textContent = 'Delete';
        meta.append(remove);
    }

    bubble.append(text, meta);
    contentWrapper.append(bubble);
    wrapper.append(contentWrapper);
    article.append(wrapper);
    article.setAttribute('aria-label', `Message from ${message.sender.name}`);

    return article;
};

const initializeDiscussionChat = () => {
    const root = document.getElementById('project-discussion-chat');
    if (!root) {
        return;
    }

    const mainScroll = document.getElementById('main-scroll');
    if (mainScroll) {
        mainScroll.style.padding = '0';
        mainScroll.style.overflow = 'hidden';
        mainScroll.style.display = 'flex';
        mainScroll.style.flexDirection = 'column';
        mainScroll.style.height = '0';
        mainScroll.style.flex = '1 1 0%';
    }

    const scroll = document.getElementById('discussion-message-scroll');
    const list = document.getElementById('discussion-message-list');
    const form = document.getElementById('discussion-message-form');
    const input = document.getElementById('discussion-message-input');
    const error = document.getElementById('discussion-message-error');
    const loadOlderContainer = document.getElementById('discussion-load-older-container');
    const loadOlderButton = document.getElementById('discussion-load-older');
    const loadOlderLabel = loadOlderButton?.querySelector('[data-load-older-label]');
    const loadOlderError = document.getElementById('discussion-load-older-error');
    const newMessagesIndicator = document.getElementById('discussion-new-messages');
    const mentionSuggestions = document.getElementById('project-discussion-mention-list');
    const composerPlaceholder = document.getElementById('discussion-message-placeholder');
    const composerFallback = document.getElementById('discussion-composer-fallback');
    const targetMessageState = document.getElementById('discussion-target-message-state');
    const editInput = document.getElementById('edit-message-content');
    const editPlaceholder = document.getElementById('edit-message-placeholder');
    const editFallback = document.getElementById('edit-message-fallback');
    const editForm = document.getElementById('edit-message-form');
    const editSubmit = editForm?.querySelector('button[type="submit"]');
    const currentUserId = Number(root.dataset.currentUserId);
    const knownMessageIds = new Set(
        [...list.querySelectorAll('[data-discussion-message]')]
            .map((element) => Number(element.dataset.messageId)),
    );
    let oldestMessageId = Number(root.dataset.oldestMessageId) || null;
    let latestMessageId = Number(root.dataset.latestMessageId) || null;
    let lastMarkedReadId = latestMessageId;
    let selectedMessage = null;
    let pollTimer = null;
    let pollController = null;
    let pollFailureCount = 0;
    let mentionSearchTimer = null;
    let mentionSearchController = null;
    let mentionOptions = [];
    let activeMentionIndex = -1;
    let mentionComposer = null;
    let editComposer = null;
    let targetModeActive = Boolean(Number(root.dataset.targetMessageId));
    const pollingDelays = [5000, 10000, 20000, 30000];

    const messageUrl = (template, id) => template.replace('__MESSAGE__', String(id));

    const showError = (message) => {
        if (error) {
            error.textContent = message;
            error.classList.remove('hidden');
        }
    };

    const request = async (url, options = {}) => {
        const response = await fetch(url, {
            ...options,
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
                ...options.headers,
            },
        });

        if (!response.ok) {
            const payload = await response.json().catch(() => ({}));
            const validationMessage = payload.errors
                ? Object.values(payload.errors).flat()[0]
                : null;
            throw new Error(validationMessage ?? payload.message ?? 'The request could not be completed.');
        }

        return response.json();
    };

    const isNearBottom = () => scroll.scrollHeight - scroll.scrollTop - scroll.clientHeight < 100;

    const updateCursorsFromDom = () => {
        const ids = [...list.querySelectorAll('[data-discussion-message]')]
            .map((element) => Number(element.dataset.messageId));
        oldestMessageId = ids.length ? Math.min(...ids) : null;
        latestMessageId = ids.length ? Math.max(...ids) : null;
        root.dataset.oldestMessageId = oldestMessageId ?? '';
        root.dataset.latestMessageId = latestMessageId ?? '';
    };

    const markRead = async () => {
        if (!latestMessageId || latestMessageId === lastMarkedReadId) {
            return;
        }

        try {
            const payload = await request(root.dataset.messageReadUrl, {
                method: 'POST',
                body: JSON.stringify({ last_read_message_id: latestMessageId }),
            });
            lastMarkedReadId = Number(payload.last_read_message_id);
        } catch {
            // The next successful poll or scroll event will retry.
        }
    };

    const scrollToBottom = () => {
        targetModeActive = false;
        scroll.scrollTop = scroll.scrollHeight;
        newMessagesIndicator?.classList.add('hidden');
        markRead();
    };

    const addMessages = (messages, position) => {
        const freshMessages = messages
            .filter((message) => !knownMessageIds.has(Number(message.id)))
            .sort((left, right) => Number(left.id) - Number(right.id));

        if (!freshMessages.length) {
            return 0;
        }

        document.getElementById('discussion-empty-state')?.remove();
        const fragment = document.createDocumentFragment();
        freshMessages.forEach((message) => {
            knownMessageIds.add(Number(message.id));
            fragment.append(createMessageElement(message, currentUserId));
        });

        if (position === 'prepend') {
            list.prepend(fragment);
        } else {
            list.append(fragment);
        }
        updateCursorsFromDom();

        return freshMessages.length;
    };

    const highlightTargetMessage = () => {
        const targetMessageId = Number(root.dataset.targetMessageId || 0);

        if (!targetMessageId) {
            return false;
        }

        const target = list.querySelector(
            `[data-discussion-message][data-message-id="${targetMessageId}"]`,
        );

        if (!target) {
            return false;
        }

        target.classList.add('rounded-xl', 'bg-indigo-50/60', 'p-2', 'ring-2', 'ring-indigo-400');
        targetMessageState?.classList.add('hidden');
        target.scrollIntoView({ behavior: 'smooth', block: 'center' });

        return true;
    };

    const loadOlder = async () => {
        if (!oldestMessageId || loadOlderButton?.disabled) {
            return;
        }

        loadOlderButton.disabled = true;
        loadOlderLabel.textContent = 'Loading...';
        loadOlderError?.classList.add('hidden');
        const previousHeight = scroll.scrollHeight;
        const previousTop = scroll.scrollTop;

        try {
            const url = new URL(root.dataset.messageIndexUrl, window.location.origin);
            url.searchParams.set('before_id', String(oldestMessageId));
            const payload = await request(url.toString(), { method: 'GET' });
            addMessages(payload.messages, 'prepend');
            scroll.scrollTop = previousTop + (scroll.scrollHeight - previousHeight);
            loadOlderContainer?.classList.toggle('hidden', !payload.has_more_older);
        } catch (requestError) {
            if (loadOlderError) {
                loadOlderError.textContent = requestError.message;
                loadOlderError.classList.remove('hidden');
            }
        } finally {
            loadOlderButton.disabled = false;
            loadOlderLabel.textContent = 'Load Older Messages';
        }
    };

    const schedulePoll = (delay = pollingDelays[0]) => {
        window.clearTimeout(pollTimer);
        if (!document.hidden) {
            pollTimer = window.setTimeout(pollNewer, delay);
        }
    };

    const pollNewer = async () => {
        if (document.hidden) {
            return;
        }

        pollController?.abort();
        pollController = new AbortController();
        const wasNearBottom = !targetModeActive && isNearBottom();

        try {
            const url = new URL(root.dataset.messageIndexUrl, window.location.origin);
            url.searchParams.set('after_id', String(latestMessageId ?? 0));
            const payload = await request(url.toString(), {
                method: 'GET',
                signal: pollController.signal,
            });
            const added = addMessages(payload.messages, 'append');
            pollFailureCount = 0;

            if (added && wasNearBottom) {
                scrollToBottom();
            } else if (added) {
                newMessagesIndicator?.classList.remove('hidden');
            }

            schedulePoll(payload.has_more_newer ? 0 : pollingDelays[0]);
        } catch (requestError) {
            if (requestError.name === 'AbortError') {
                return;
            }
            pollFailureCount = Math.min(pollFailureCount + 1, pollingDelays.length);
            schedulePoll(pollingDelays[pollFailureCount - 1]);
        }
    };

    loadOlderButton?.addEventListener('click', loadOlder);
    newMessagesIndicator?.addEventListener('click', scrollToBottom);
    scroll?.addEventListener('scroll', () => {
        if (!targetModeActive && isNearBottom()) {
            newMessagesIndicator?.classList.add('hidden');
            markRead();
        }
    });
    scroll?.addEventListener('wheel', () => {
        targetModeActive = false;
    }, { passive: true });
    scroll?.addEventListener('touchstart', () => {
        targetModeActive = false;
    }, { passive: true });

    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            window.clearTimeout(pollTimer);
            pollController?.abort();
            return;
        }

        pollFailureCount = 0;
        schedulePoll(0);
    });

    form?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const content = mentionComposer?.serialize() ?? '';
        if (!content) {
            showError('Message is required.');
            return;
        }
        if (content.length > 1000) {
            showError('Message cannot exceed 1000 characters.');
            return;
        }

        error?.classList.add('hidden');
        const submit = form.querySelector('button[type="submit"]');
        submit.disabled = true;
        mentionComposer.setDisabled(true);

        try {
            const message = await request(root.dataset.messageStoreUrl, {
                method: 'POST',
                body: JSON.stringify({ content }),
            });
            addMessages([message], 'append');
            mentionComposer.clear();
            closeMentionSuggestions();
            scrollToBottom();
        } catch (requestError) {
            showError(requestError.message);
        } finally {
            submit.disabled = false;
            mentionComposer.setDisabled(false);
            mentionComposer.focus();
        }
    });

    list?.addEventListener('click', (event) => {
        const message = event.target.closest('[data-discussion-message]');
        if (!message) {
            return;
        }

        if (event.target.closest('[data-message-edit]')) {
            selectedMessage = message;
            editComposer.deserialize(message.dataset.messageContent);
            openModal(document.getElementById('edit-message-modal'));
            editComposer.focus();
        }

        if (event.target.closest('[data-message-delete]')) {
            selectedMessage = message;
            openModal(document.getElementById('delete-message-modal'));
        }
    });

    editForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!selectedMessage) {
            return;
        }

        const content = editComposer.serialize();
        if (!content) {
            return;
        }
        if (content.length > 1000) {
            window.alert('Message cannot exceed 1000 characters.');
            return;
        }

        editSubmit.disabled = true;
        editComposer.setDisabled(true);

        try {
            const message = await request(
                messageUrl(root.dataset.messageUpdateUrl, selectedMessage.dataset.messageId),
                {
                    method: 'PATCH',
                    body: JSON.stringify({ content }),
                },
            );
            selectedMessage.dataset.messageContent = message.content;
            renderMessageContent(
                selectedMessage.querySelector('[data-message-text]'),
                message,
            );
            selectedMessage.querySelector('[data-message-edited]')?.classList.remove('hidden');
            closeModal(document.getElementById('edit-message-modal'));
            selectedMessage = null;
        } catch (requestError) {
            window.alert(requestError.message);
        } finally {
            editSubmit.disabled = false;
            editComposer.setDisabled(false);
        }
    });

    document.getElementById('delete-message-confirm')?.addEventListener('click', async () => {
        if (!selectedMessage) {
            return;
        }

        try {
            await request(
                messageUrl(root.dataset.messageDeleteUrl, selectedMessage.dataset.messageId),
                { method: 'DELETE' },
            );
            knownMessageIds.delete(Number(selectedMessage.dataset.messageId));
            selectedMessage.remove();
            updateCursorsFromDom();
            closeModal(document.getElementById('delete-message-modal'));
            selectedMessage = null;
        } catch (requestError) {
            window.alert(requestError.message);
        }
    });

    const search = document.getElementById('discussion-search');
    const searchInput = document.getElementById('discussion-search-input');
    const searchCount = document.getElementById('discussion-search-count');

    document.getElementById('discussion-search-toggle')?.addEventListener('click', () => {
        search?.classList.toggle('hidden');
        searchInput?.focus();
    });

    searchInput?.addEventListener('input', () => {
        const query = searchInput.value.trim().toLocaleLowerCase();
        let matches = 0;
        list.querySelectorAll('[data-discussion-message]').forEach((message) => {
            const matchesQuery = !query
                || message.dataset.messageContent.toLocaleLowerCase().includes(query);
            message.classList.toggle('hidden', !matchesQuery);
            matches += matchesQuery ? 1 : 0;
        });
        searchCount.textContent = query ? `${matches} found` : '';
    });

    const closeMentionSuggestions = () => {
        mentionSuggestions?.classList.add('hidden');
        input?.setAttribute('aria-expanded', 'false');
        mentionOptions = [];
        activeMentionIndex = -1;
    };

    const setActiveMention = (index) => {
        const options = [...mentionSuggestions.querySelectorAll('[data-discussion-mention-option]')];
        if (!options.length) {
            return;
        }

        activeMentionIndex = (index + options.length) % options.length;
        options.forEach((option, optionIndex) => {
            const isActive = optionIndex === activeMentionIndex;
            option.classList.toggle('bg-indigo-50', isActive);
            option.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });
        options[activeMentionIndex].scrollIntoView({ block: 'nearest' });
    };

    const insertMention = (member) => {
        if (!mentionComposer?.insertMention(member)) {
            showError('Message cannot exceed 1000 characters.');

            return;
        }

        closeMentionSuggestions();
        mentionComposer.focus();
    };

    const renderMentionSuggestions = (members) => {
        if (!mentionSuggestions || !input) {
            return;
        }

        mentionSuggestions.replaceChildren();
        mentionOptions = members;
        activeMentionIndex = -1;

        if (!members.length) {
            const status = document.createElement('p');
            status.className = 'px-3 py-2 text-sm text-gray-500';
            status.textContent = 'No matching project members.';
            mentionSuggestions.append(status);
        } else {
            members.forEach((member, index) => {
                const option = document.createElement('button');
                option.type = 'button';
                option.dataset.discussionMentionOption = String(index);
                option.setAttribute('role', 'option');
                option.setAttribute('aria-selected', 'false');
                option.className = 'flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left hover:bg-indigo-50';

                const avatar = member.avatar
                    ? document.createElement('img')
                    : document.createElement('span');
                if (member.avatar) {
                    avatar.src = member.avatar;
                    avatar.alt = '';
                    avatar.className = 'h-8 w-8 rounded-full object-cover';
                } else {
                    avatar.className = 'flex h-8 w-8 items-center justify-center rounded-full bg-indigo-100 text-xs font-semibold text-indigo-700';
                    avatar.textContent = member.name.slice(0, 1).toLocaleUpperCase() || '?';
                    avatar.setAttribute('aria-hidden', 'true');
                }

                const identity = document.createElement('span');
                identity.className = 'min-w-0 flex-1';
                const name = document.createElement('span');
                name.className = 'block truncate text-sm font-medium text-gray-900';
                name.textContent = member.name;
                const detail = document.createElement('span');
                detail.className = 'block truncate text-xs text-gray-500';
                detail.textContent = `${member.role} · ${member.email_hint}`;
                identity.append(name, detail);
                option.append(avatar, identity);
                option.addEventListener('mousedown', (event) => {
                    event.preventDefault();
                    insertMention(member);
                });
                mentionSuggestions.append(option);
            });
            setActiveMention(0);
        }

        mentionSuggestions.classList.remove('hidden');
        input.setAttribute('aria-expanded', 'true');
    };

    const mentionContext = () => {
        return mentionComposer?.mentionQuery() ?? null;
    };

    const searchMentions = async (context) => {
        mentionSearchController?.abort();
        mentionSearchController = new AbortController();
        const url = new URL(root.dataset.mentionCandidatesUrl, window.location.origin);
        url.searchParams.set('search', context.search);

        try {
            const payload = await request(url.toString(), {
                method: 'GET',
                signal: mentionSearchController.signal,
            });
            renderMentionSuggestions(payload.members);
        } catch (requestError) {
            if (requestError.name !== 'AbortError') {
                closeMentionSuggestions();
                showError(requestError.message);
            }
        } finally {
            mentionSearchController = null;
        }
    };

    const queueMentionSearch = () => {
        window.clearTimeout(mentionSearchTimer);
        const context = mentionContext();
        if (!context) {
            closeMentionSuggestions();

            return;
        }

        mentionSearchTimer = window.setTimeout(() => searchMentions(context), 200);
    };

    input?.addEventListener('click', queueMentionSearch);
    input?.addEventListener('blur', () => {
        window.setTimeout(closeMentionSuggestions, 150);
    });
    input?.addEventListener('keydown', (event) => {
        if (mentionSuggestions?.classList.contains('hidden')) {
            return;
        }

        if (event.key === 'ArrowDown') {
            event.preventDefault();
            setActiveMention(activeMentionIndex + 1);
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            setActiveMention(activeMentionIndex - 1);
        } else if (event.key === 'Enter' && activeMentionIndex >= 0) {
            event.preventDefault();
            insertMention(mentionOptions[activeMentionIndex]);
        } else if (event.key === 'Escape') {
            event.preventDefault();
            closeMentionSuggestions();
        }
    });

    window.addEventListener('beforeunload', () => {
        window.clearTimeout(pollTimer);
        window.clearTimeout(mentionSearchTimer);
        pollController?.abort();
        mentionSearchController?.abort();
    });

    mentionComposer = createMentionComposer(input, {
        maxLength: 1000,
        placeholder: composerPlaceholder,
        fallback: composerFallback,
        onInput: queueMentionSearch,
    });
    editComposer = createMentionComposer(editInput, {
        maxLength: 1000,
        placeholder: editPlaceholder,
        fallback: editFallback,
    });
    form.querySelector('button[type="submit"]').disabled = false;
    editSubmit.disabled = false;

    if (!highlightTargetMessage()) {
        scrollToBottom();
    }
    schedulePoll();
};

document.addEventListener('DOMContentLoaded', () => {
    initializeDiscussionModals();
    initializeThreadList();
    initializeDiscussionChat();
});
