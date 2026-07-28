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

    const createMessageElement = (message) => {
        const article = document.createElement('article');
        addClasses(article, 'group flex items-start gap-3');
        article.dataset.chatMessage = '';
        article.dataset.messageId = String(message.id);

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
        content.textContent = message.content;

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

        if (shouldScroll) {
            messageList.scrollTop = messageList.scrollHeight;
            newMessagesIndicator.classList.add('hidden');
        } else if (messages.length > 0) {
            newMessagesIndicator.classList.remove('hidden');
        }
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
        submitButton.disabled = true;
        submitLabel.textContent = 'Sending...';

        try {
            const message = await requestJson(chatRoot.dataset.storeUrl, {
                method: 'POST',
                body: JSON.stringify({ content: input.value }),
            });
            appendMessages([message], true);
            input.value = '';
            await markLatestRead();
        } catch (error) {
            showError(error.message);
        } finally {
            submitButton.disabled = false;
            submitLabel.textContent = 'Send';
            input.focus();
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
            const contentElement = messageElement.querySelector('[data-chat-content]');
            const content = window.prompt('Edit message', contentElement.textContent);

            if (content === null) {
                return;
            }

            clearError();

            try {
                const message = await requestJson(
                    chatRoot.dataset.updateUrlTemplate.replace('__MESSAGE_ID__', messageId),
                    {
                        method: 'PATCH',
                        body: JSON.stringify({ content }),
                    },
                );
                contentElement.textContent = message.content;
                messageElement.querySelector('[data-chat-edited]').classList.remove('hidden');
            } catch (error) {
                showError(error.message);
            }
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
        pollingController?.abort();
    });

    messageList.scrollTop = messageList.scrollHeight;
    markLatestRead();
    schedulePoll();
}
