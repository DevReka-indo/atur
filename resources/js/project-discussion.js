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

const createMessageElement = (message, currentUserName) => {
    const article = document.createElement('article');
    article.dataset.discussionMessage = '';
    article.dataset.messageId = String(message.id);
    article.dataset.messageContent = message.content;
    article.className = 'flex justify-end';

    const wrapper = document.createElement('div');
    wrapper.className = 'max-w-[80%] sm:max-w-[65%]';

    const bubble = document.createElement('div');
    bubble.className = 'group relative rounded-2xl bg-green-100 px-4 py-2 shadow-sm';

    const text = document.createElement('p');
    text.dataset.messageText = '';
    text.className = 'whitespace-pre-wrap break-words text-sm text-gray-900';
    text.textContent = message.content;

    const meta = document.createElement('div');
    meta.className = 'mt-1 flex items-center justify-end gap-2';

    const time = document.createElement('time');
    time.className = 'text-[11px] text-gray-400';
    time.textContent = message.time;

    const edit = document.createElement('button');
    edit.type = 'button';
    edit.dataset.messageEdit = '';
    edit.className = 'text-[11px] font-medium text-gray-500 hover:text-indigo-600';
    edit.textContent = 'Edit';

    const remove = document.createElement('button');
    remove.type = 'button';
    remove.dataset.messageDelete = '';
    remove.className = 'text-[11px] font-medium text-gray-500 hover:text-red-600';
    remove.textContent = 'Delete';

    meta.append(time, edit, remove);
    bubble.append(text, meta);
    wrapper.append(bubble);
    article.append(wrapper);
    article.setAttribute('aria-label', `Message from ${currentUserName}`);

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

    const list = document.getElementById('discussion-message-list');
    const form = document.getElementById('discussion-message-form');
    const input = document.getElementById('discussion-message-input');
    const error = document.getElementById('discussion-message-error');
    let selectedMessage = null;

    const messageUrl = (template, id) => template.replace('__MESSAGE__', String(id));

    const showError = (message) => {
        if (error) {
            error.textContent = message;
            error.classList.remove('hidden');
        }
    };

    const request = async (url, options) => {
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
            throw new Error(payload.message ?? 'The request could not be completed.');
        }

        return response.json();
    };

    form?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const content = input?.value.trim() ?? '';
        if (!content) {
            return;
        }

        error?.classList.add('hidden');
        const submit = form.querySelector('button[type="submit"]');
        submit.disabled = true;

        try {
            const message = await request(root.dataset.messageStoreUrl, {
                method: 'POST',
                body: JSON.stringify({ content }),
            });
            document.getElementById('discussion-empty-state')?.remove();
            list.append(createMessageElement(message, root.dataset.currentUserName));
            input.value = '';
            list.scrollTop = list.scrollHeight;
        } catch (requestError) {
            showError(requestError.message);
        } finally {
            submit.disabled = false;
            input?.focus();
        }
    });

    list?.addEventListener('click', (event) => {
        const message = event.target.closest('[data-discussion-message]');
        if (!message) {
            return;
        }

        if (event.target.closest('[data-message-edit]')) {
            selectedMessage = message;
            const textarea = document.getElementById('edit-message-content');
            textarea.value = message.dataset.messageContent;
            openModal(document.getElementById('edit-message-modal'));
            textarea.focus();
        }

        if (event.target.closest('[data-message-delete]')) {
            selectedMessage = message;
            openModal(document.getElementById('delete-message-modal'));
        }
    });

    document.getElementById('edit-message-form')?.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!selectedMessage) {
            return;
        }

        const textarea = document.getElementById('edit-message-content');
        const content = textarea.value.trim();
        if (!content) {
            return;
        }

        try {
            const message = await request(
                messageUrl(root.dataset.messageUpdateUrl, selectedMessage.dataset.messageId),
                {
                    method: 'PATCH',
                    body: JSON.stringify({ content }),
                },
            );
            selectedMessage.dataset.messageContent = message.content;
            selectedMessage.querySelector('[data-message-text]').textContent = message.content;
            closeModal(document.getElementById('edit-message-modal'));
            selectedMessage = null;
        } catch (requestError) {
            window.alert(requestError.message);
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
            selectedMessage.remove();
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

    list.scrollTop = list.scrollHeight;
};

document.addEventListener('DOMContentLoaded', () => {
    initializeDiscussionModals();
    initializeThreadList();
    initializeDiscussionChat();
});
