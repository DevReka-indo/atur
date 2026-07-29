import { createMentionComposer, renderMentionContent } from './mention-composer';

const chatRoot = document.querySelector('[data-workspace-chat]');

if (chatRoot) {
    const messageList = chatRoot.querySelector('[data-chat-message-list]');
    const emptyState = chatRoot.querySelector('[data-chat-empty]');
    const composer = chatRoot.querySelector('[data-chat-composer]');
    const input = chatRoot.querySelector('[data-chat-input]');
    const submitButton = chatRoot.querySelector('[data-chat-submit]');
    const submitLabel = chatRoot.querySelector('[data-chat-submit-label]');
    const errorMessage = chatRoot.querySelector('[data-chat-error]');
    const loadOlderButton = chatRoot.querySelector('[data-chat-load-older]');
    const newMessagesIndicator = chatRoot.querySelector('[data-chat-new-indicator]');
    const mentionSuggestions = chatRoot.querySelector('[data-chat-mention-suggestions]');
    const targetStatus = chatRoot.querySelector('[data-chat-target-status]');
    const composerPlaceholder = chatRoot.querySelector('[data-chat-placeholder]');
    const composerFallback = chatRoot.querySelector('[data-chat-composer-fallback]');
    const editModal = document.querySelector('[data-chat-edit-modal]');
    const editForm = document.querySelector('[data-chat-edit-form]');
    const editInput = document.querySelector('[data-chat-edit-input]');
    const editPlaceholder = document.querySelector('[data-chat-edit-placeholder]');
    const editFallback = document.querySelector('[data-chat-edit-fallback]');
    const editSubmit = document.querySelector('[data-chat-edit-submit]');
    const csrfToken = composer.querySelector('input[name="_token"]').value;
    const knownMessageIds = new Set(
        [...messageList.querySelectorAll('[data-chat-message]')]
            .map((element) => Number(element.dataset.messageId)),
    );
    const pollingIntervals = [5000, 10000, 20000, 30000];
    let pollingFailureCount = 0;
    let pollingTimer = null;
    let pollingController = null;
    let markingRead = false;
    let lastMarkedReadId = Math.max(0, ...knownMessageIds);
    let mentionSearchTimer = null;
    let mentionSearchController = null;
    let mentionOptions = [];
    let activeMentionIndex = -1;
    let selectedMessageElement = null;
    let mentionComposer = null;
    let editComposer = null;

    const messageElements = () => [...messageList.querySelectorAll('[data-chat-message]')];
    const firstMessageId = () => Number(messageElements()[0]?.dataset.messageId || 0);
    const lastMessageId = () => Number(messageElements().at(-1)?.dataset.messageId || 0);
    const isNearBottom = () => (
        messageList.scrollHeight - messageList.scrollTop - messageList.clientHeight < 120
    );

    const showError = (message) => {
        errorMessage.textContent = message;
        errorMessage.classList.remove('hidden');
    };

    const clearError = () => {
        errorMessage.textContent = '';
        errorMessage.classList.add('hidden');
    };

    const updateEmptyState = () => {
        emptyState.classList.toggle('hidden', knownMessageIds.size > 0);
    };

    const formatTime = (value) => {
        const date = new Date(value);

        return Number.isNaN(date.getTime())
            ? ''
            : date.toLocaleString([], {
                day: '2-digit',
                month: 'short',
                hour: '2-digit',
                minute: '2-digit',
            });
    };

    const addClasses = (element, classes) => {
        element.classList.add(...classes.split(' '));
    };

    const renderMessageContent = (element, message) => {
        renderMentionContent(element, message.content_segments || [
            { type: 'text', text: message.plain_text || message.content },
        ]);
    };

    const createMessageElement = (message) => {
        const article = document.createElement('article');
        addClasses(article, 'group flex items-start gap-3');
        article.dataset.chatMessage = '';
        article.dataset.messageId = String(message.id);
        article.dataset.messageContent = message.content;

        if (message.sender.avatar) {
            const avatar = document.createElement('img');
            avatar.src = message.sender.avatar;
            avatar.alt = message.sender.name;
            addClasses(avatar, 'h-9 w-9 shrink-0 rounded-full object-cover');
            article.append(avatar);
        } else {
            const avatar = document.createElement('span');
            addClasses(
                avatar,
                'flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-sm font-semibold text-indigo-700',
            );
            avatar.setAttribute('aria-hidden', 'true');
            avatar.textContent = message.sender.name.slice(0, 1).toUpperCase() || '?';
            article.append(avatar);
        }

        const body = document.createElement('div');
        addClasses(body, 'min-w-0 flex-1');
        const meta = document.createElement('div');
        addClasses(meta, 'flex flex-wrap items-center gap-x-2 gap-y-1');

        const sender = document.createElement('span');
        addClasses(sender, 'text-sm font-semibold text-gray-900');
        sender.dataset.chatSender = '';
        sender.textContent = message.sender.name;
        meta.append(sender);

        const time = document.createElement('time');
        addClasses(time, 'text-xs text-gray-400');
        time.dateTime = message.created_at;
        time.textContent = formatTime(message.created_at);
        meta.append(time);

        const edited = document.createElement('span');
        addClasses(edited, 'text-xs italic text-gray-400');
        edited.dataset.chatEdited = '';
        edited.textContent = 'edited';
        edited.classList.toggle('hidden', !message.edited_at);
        meta.append(edited);

        const content = document.createElement('p');
        addClasses(content, 'mt-1 whitespace-pre-wrap break-words text-sm leading-relaxed text-gray-700');
        content.dataset.chatContent = '';
        renderMessageContent(content, message);

        body.append(meta, content);

        if (message.can_edit || message.can_delete) {
            const actions = document.createElement('div');
            addClasses(actions, 'mt-1 flex items-center gap-3 text-xs');

            if (message.can_edit) {
                const editButton = document.createElement('button');
                editButton.type = 'button';
                addClasses(editButton, 'font-medium text-indigo-600 hover:text-indigo-800');
                editButton.dataset.chatEdit = '';
                editButton.textContent = 'Edit';
                actions.append(editButton);
            }

            if (message.can_delete) {
                const deleteButton = document.createElement('button');
                deleteButton.type = 'button';
                addClasses(deleteButton, 'font-medium text-red-600 hover:text-red-800');
                deleteButton.dataset.chatDelete = '';
                deleteButton.textContent = 'Delete';
                actions.append(deleteButton);
            }

            body.append(actions);
        }

        article.append(body);

        return article;
    };

    const requestJson = async (url, options = {}) => {
        const response = await fetch(url, {
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                ...options.headers,
            },
            ...options,
        });
        const payload = await response.json().catch(() => ({}));

        if (!response.ok) {
            const validationMessage = payload.errors
                ? Object.values(payload.errors).flat()[0]
                : null;
            throw new Error(validationMessage || payload.message || 'The request could not be completed.');
        }

        return payload;
    };

    const appendMessages = (messages, shouldScroll) => {
        messages.forEach((message) => {
            if (knownMessageIds.has(message.id)) {
                return;
            }

            knownMessageIds.add(message.id);
            messageList.append(createMessageElement(message));
        });
        updateEmptyState();
        highlightTargetMessage();

        if (shouldScroll) {
            messageList.scrollTop = messageList.scrollHeight;
            newMessagesIndicator.classList.add('hidden');
        } else if (messages.length > 0) {
            newMessagesIndicator.classList.remove('hidden');
        }
    };

    const highlightTargetMessage = () => {
        const targetMessageId = Number(chatRoot.dataset.targetMessageId || 0);

        if (!targetMessageId) {
            return false;
        }

        const target = messageList.querySelector(
            `[data-chat-message][data-message-id="${targetMessageId}"]`,
        );

        if (!target) {
            return false;
        }

        addClasses(target, 'rounded-xl bg-sky-50/60 p-2 ring-2 ring-sky-400');
        targetStatus?.classList.add('hidden');
        target.scrollIntoView({ behavior: 'smooth', block: 'center' });

        return true;
    };

    const closeMentionSuggestions = () => {
        mentionSuggestions.classList.add('hidden');
        input.setAttribute('aria-expanded', 'false');
        mentionOptions = [];
        activeMentionIndex = -1;
    };

    const setActiveMention = (index) => {
        const options = [...mentionSuggestions.querySelectorAll('[data-chat-mention-option]')];

        if (options.length === 0) {
            return;
        }

        activeMentionIndex = (index + options.length) % options.length;
        options.forEach((option, optionIndex) => {
            const isActive = optionIndex === activeMentionIndex;
            option.classList.toggle('bg-sky-50', isActive);
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
        mentionSuggestions.replaceChildren();
        mentionOptions = members;
        activeMentionIndex = -1;

        if (members.length === 0) {
            const status = document.createElement('p');
            addClasses(status, 'px-3 py-2 text-sm text-gray-500');
            status.textContent = 'No matching workspace members.';
            mentionSuggestions.append(status);
        } else {
            members.forEach((member, index) => {
                const option = document.createElement('button');
                option.type = 'button';
                option.dataset.chatMentionOption = String(index);
                option.setAttribute('role', 'option');
                option.setAttribute('aria-selected', 'false');
                addClasses(
                    option,
                    'flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left hover:bg-sky-50',
                );

                if (member.avatar) {
                    const avatar = document.createElement('img');
                    avatar.src = member.avatar;
                    avatar.alt = '';
                    addClasses(avatar, 'h-8 w-8 rounded-full object-cover');
                    option.append(avatar);
                } else {
                    const avatar = document.createElement('span');
                    addClasses(
                        avatar,
                        'flex h-8 w-8 items-center justify-center rounded-full bg-sky-100 text-xs font-semibold text-sky-700',
                    );
                    avatar.textContent = member.name.slice(0, 1).toUpperCase() || '?';
                    avatar.setAttribute('aria-hidden', 'true');
                    option.append(avatar);
                }

                const identity = document.createElement('span');
                addClasses(identity, 'min-w-0 flex-1');
                const name = document.createElement('span');
                addClasses(name, 'block truncate text-sm font-medium text-gray-900');
                name.textContent = member.name;
                const emailHint = document.createElement('span');
                addClasses(emailHint, 'block truncate text-xs text-gray-500');
                emailHint.textContent = member.email_hint;
                identity.append(name, emailHint);
                option.append(identity);
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
        const url = new URL(chatRoot.dataset.mentionsUrl, window.location.origin);
        url.searchParams.set('search', context.search);

        try {
            const payload = await requestJson(url, {
                signal: mentionSearchController.signal,
            });
            renderMentionSuggestions(payload.members);
        } catch (error) {
            if (error.name !== 'AbortError') {
                closeMentionSuggestions();
                showError(error.message);
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

    const markLatestRead = async () => {
        const messageId = lastMessageId();

        if (!messageId
            || messageId <= lastMarkedReadId
            || document.hidden
            || !isNearBottom()
            || markingRead) {
            return;
        }

        markingRead = true;

        try {
            await requestJson(chatRoot.dataset.readUrl, {
                method: 'POST',
                body: JSON.stringify({ message_id: messageId }),
            });
            lastMarkedReadId = messageId;
        } catch {
            // Polling will retry read state when the user remains at the bottom.
        } finally {
            markingRead = false;
        }
    };

    const schedulePoll = (delay = pollingIntervals[Math.min(
        pollingFailureCount,
        pollingIntervals.length - 1,
    )]) => {
        window.clearTimeout(pollingTimer);

        if (!document.hidden) {
            pollingTimer = window.setTimeout(pollMessages, delay);
        }
    };

    const pollMessages = async () => {
        if (document.hidden || pollingController) {
            return;
        }

        pollingController = new AbortController();

        try {
            const wasNearBottom = isNearBottom();
            const url = new URL(chatRoot.dataset.indexUrl, window.location.origin);
            const afterId = lastMessageId();

            if (afterId) {
                url.searchParams.set('after_id', String(afterId));
            }

            const payload = await requestJson(url, {
                signal: pollingController.signal,
            });
            appendMessages(payload.messages, wasNearBottom);
            pollingFailureCount = 0;

            if (wasNearBottom && payload.messages.length > 0) {
                await markLatestRead();
            }

            schedulePoll(payload.has_more ? 0 : pollingIntervals[0]);
        } catch (error) {
            if (error.name !== 'AbortError') {
                pollingFailureCount += 1;
                schedulePoll();
            }
        } finally {
            pollingController = null;
        }
    };

    composer.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearError();
        const content = mentionComposer.serialize();
        if (!content) {
            showError('Message is required.');
            return;
        }
        if (content.length > 1000) {
            showError('Message cannot exceed 1000 characters.');
            return;
        }

        submitButton.disabled = true;
        submitLabel.textContent = 'Sending...';
        mentionComposer.setDisabled(true);
        closeMentionSuggestions();

        try {
            const message = await requestJson(chatRoot.dataset.storeUrl, {
                method: 'POST',
                body: JSON.stringify({ content }),
            });
            appendMessages([message], true);
            mentionComposer.clear();
            await markLatestRead();
        } catch (error) {
            showError(error.message);
        } finally {
            submitButton.disabled = false;
            submitLabel.textContent = 'Send';
            mentionComposer.setDisabled(false);
            mentionComposer.focus();
        }
    });

    loadOlderButton.addEventListener('click', async () => {
        const beforeId = firstMessageId();

        if (!beforeId) {
            return;
        }

        clearError();
        loadOlderButton.disabled = true;
        const previousHeight = messageList.scrollHeight;

        try {
            const url = new URL(chatRoot.dataset.indexUrl, window.location.origin);
            url.searchParams.set('before_id', String(beforeId));
            const payload = await requestJson(url);
            const fragment = document.createDocumentFragment();

            payload.messages.forEach((message) => {
                if (!knownMessageIds.has(message.id)) {
                    knownMessageIds.add(message.id);
                    fragment.append(createMessageElement(message));
                }
            });
            messageList.prepend(fragment);
            messageList.scrollTop += messageList.scrollHeight - previousHeight;
            loadOlderButton.classList.toggle('hidden', !payload.has_more);
            updateEmptyState();
        } catch (error) {
            showError(error.message);
        } finally {
            loadOlderButton.disabled = false;
        }
    });

    messageList.addEventListener('click', async (event) => {
        const messageElement = event.target.closest('[data-chat-message]');

        if (!messageElement) {
            return;
        }

        const messageId = messageElement.dataset.messageId;

        if (event.target.closest('[data-chat-edit]')) {
            selectedMessageElement = messageElement;
            editComposer.deserialize(messageElement.dataset.messageContent);
            editModal.classList.remove('hidden');
            editModal.classList.add('flex');
            editComposer.focus();
        }

        if (event.target.closest('[data-chat-delete]')
            && window.confirm('Delete this message?')) {
            clearError();

            try {
                await requestJson(
                    chatRoot.dataset.deleteUrlTemplate.replace('__MESSAGE_ID__', messageId),
                    { method: 'DELETE' },
                );
                knownMessageIds.delete(Number(messageId));
                messageElement.remove();
                updateEmptyState();
            } catch (error) {
                showError(error.message);
            }
        }
    });

    newMessagesIndicator.addEventListener('click', () => {
        messageList.scrollTop = messageList.scrollHeight;
        newMessagesIndicator.classList.add('hidden');
        markLatestRead();
    });

    input.addEventListener('click', queueMentionSearch);
    input.addEventListener('blur', () => {
        window.setTimeout(closeMentionSuggestions, 150);
    });
    input.addEventListener('keydown', (event) => {
        if (mentionSuggestions.classList.contains('hidden')) {
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

    const closeEditModal = () => {
        editModal.classList.add('hidden');
        editModal.classList.remove('flex');
        selectedMessageElement = null;
    };

    chatRoot.querySelector('[data-chat-edit-cancel]')?.addEventListener('click', closeEditModal);
    editModal?.addEventListener('click', (event) => {
        if (event.target === editModal) {
            closeEditModal();
        }
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !editModal.classList.contains('hidden')) {
            closeEditModal();
        }
    });
    editForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const content = editComposer.serialize();
        if (!selectedMessageElement || !content) {
            return;
        }
        if (content.length > 1000) {
            showError('Message cannot exceed 1000 characters.');
            return;
        }

        clearError();
        editSubmit.disabled = true;
        editComposer.setDisabled(true);

        try {
            const message = await requestJson(
                chatRoot.dataset.updateUrlTemplate.replace(
                    '__MESSAGE_ID__',
                    selectedMessageElement.dataset.messageId,
                ),
                {
                    method: 'PATCH',
                    body: JSON.stringify({ content }),
                },
            );
            selectedMessageElement.dataset.messageContent = message.content;
            renderMessageContent(
                selectedMessageElement.querySelector('[data-chat-content]'),
                message,
            );
            selectedMessageElement.querySelector('[data-chat-edited]').classList.remove('hidden');
            closeEditModal();
        } catch (error) {
            showError(error.message);
        } finally {
            editSubmit.disabled = false;
            editComposer.setDisabled(false);
        }
    });

    messageList.addEventListener('scroll', () => {
        if (isNearBottom()) {
            newMessagesIndicator.classList.add('hidden');
            markLatestRead();
        }
    }, { passive: true });

    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            window.clearTimeout(pollingTimer);
            pollingController?.abort();

            return;
        }

        pollMessages();
        markLatestRead();
    });

    window.addEventListener('beforeunload', () => {
        window.clearTimeout(pollingTimer);
        window.clearTimeout(mentionSearchTimer);
        pollingController?.abort();
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
    submitButton.disabled = false;
    editSubmit.disabled = false;

    if (!highlightTargetMessage()) {
        messageList.scrollTop = messageList.scrollHeight;
    }
    markLatestRead();
    schedulePoll();
}
