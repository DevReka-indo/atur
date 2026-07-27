const modal = document.querySelector('[data-workspace-invite-modal]');

if (modal) {
    const openButtons = document.querySelectorAll('[data-open-workspace-invite]');
    const closeButtons = modal.querySelectorAll('[data-close-workspace-invite]');
    const searchInput = modal.querySelector('[data-workspace-invite-search]');
    const results = modal.querySelector('[data-workspace-invite-results]');
    const feedback = modal.querySelector('[data-workspace-invite-feedback]');
    const userIdInput = modal.querySelector('[data-workspace-invite-user-id]');
    const emailInput = modal.querySelector('[data-workspace-invite-email]');
    const submitButton = modal.querySelector('[data-workspace-invite-submit]');
    const copyButton = modal.querySelector('[data-copy-workspace-invite-link]');
    const inviteLinkInput = modal.querySelector('[data-workspace-invite-link]');
    let debounceTimer;
    let requestController;
    let previouslyFocused;

    const setFeedback = (message, isError = false) => {
        feedback.textContent = message;
        feedback.classList.toggle('hidden', message === '');
        feedback.classList.toggle('text-red-600', isError);
        feedback.classList.toggle('text-gray-500', !isError);
    };

    const clearSelection = () => {
        userIdInput.value = '';
        emailInput.value = '';
        submitButton.disabled = true;
        submitButton.textContent = 'Add Member';
    };

    const selectRegisteredUser = (user) => {
        userIdInput.value = String(user.id);
        emailInput.value = '';
        searchInput.value = `${user.name} (${user.email})`;
        submitButton.textContent = 'Add Member';
        submitButton.disabled = false;
        results.classList.add('hidden');
        setFeedback(`${user.name} dipilih sebagai registered user.`);
    };

    const selectEmail = (email) => {
        userIdInput.value = '';
        emailInput.value = email;
        searchInput.value = email;
        submitButton.textContent = 'Send Invitation';
        submitButton.disabled = false;
        results.classList.add('hidden');
        setFeedback(`${email} akan menerima invitation email.`);
    };

    const avatar = (user) => {
        if (user.avatar_url) {
            const image = document.createElement('img');
            image.src = user.avatar_url;
            image.alt = '';
            image.className = 'h-9 w-9 rounded-full object-cover';

            return image;
        }

        const fallback = document.createElement('span');
        fallback.className = 'flex h-9 w-9 items-center justify-center rounded-full bg-indigo-100 text-sm font-bold text-indigo-700';
        fallback.textContent = user.name.charAt(0).toUpperCase();

        return fallback;
    };

    const candidateButton = (user) => {
        const button = document.createElement('button');
        const copy = document.createElement('span');
        const name = document.createElement('span');
        const email = document.createElement('span');
        const badge = document.createElement('span');
        const unavailable = user.membership_status === 'already_member';

        button.type = 'button';
        button.disabled = unavailable;
        button.className = 'flex w-full items-center gap-3 border-b border-gray-100 px-4 py-3 text-left last:border-b-0 hover:bg-indigo-50 disabled:cursor-not-allowed disabled:bg-gray-50 disabled:opacity-60';
        copy.className = 'min-w-0 flex-1';
        name.className = 'block truncate text-sm font-semibold text-gray-900';
        email.className = 'block truncate text-xs text-gray-500';
        badge.className = unavailable
            ? 'rounded-full bg-gray-200 px-2 py-1 text-[10px] font-semibold text-gray-600'
            : 'rounded-full bg-emerald-100 px-2 py-1 text-[10px] font-semibold text-emerald-700';

        name.textContent = user.name;
        email.textContent = user.email;
        badge.textContent = unavailable ? 'Already a member' : 'Registered User';
        copy.append(name, email);
        button.append(avatar(user), copy, badge);

        if (!unavailable) {
            button.addEventListener('click', () => selectRegisteredUser(user));
        }

        return button;
    };

    const emailButton = (email) => {
        const button = document.createElement('button');
        const icon = document.createElement('span');
        const copy = document.createElement('span');
        const title = document.createElement('span');
        const subtitle = document.createElement('span');

        button.type = 'button';
        button.className = 'flex w-full items-center gap-3 px-4 py-3 text-left hover:bg-indigo-50';
        icon.className = 'flex h-9 w-9 items-center justify-center rounded-full bg-indigo-100 text-indigo-700';
        icon.setAttribute('aria-hidden', 'true');
        icon.textContent = '@';
        copy.className = 'min-w-0 flex-1';
        title.className = 'block truncate text-sm font-semibold text-indigo-700';
        subtitle.className = 'block text-xs text-gray-500';
        title.textContent = `Invite ${email}`;
        subtitle.textContent = 'Pengguna belum terdaftar';
        copy.append(title, subtitle);
        button.append(icon, copy);
        button.addEventListener('click', () => selectEmail(email));

        return button;
    };

    const renderResults = (payload) => {
        results.replaceChildren();

        payload.data.forEach((user) => results.append(candidateButton(user)));

        const exactRegisteredUser = payload.data.some(
            (user) => user.email.toLowerCase() === payload.email.value,
        );

        if (payload.email.value && !exactRegisteredUser) {
            if (payload.email.has_pending_invitation) {
                const pending = document.createElement('p');
                pending.className = 'px-4 py-3 text-sm font-medium text-amber-700';
                pending.textContent = 'Email ini sudah memiliki pending invitation. Gunakan aksi Resend.';
                results.append(pending);
            } else {
                results.append(emailButton(payload.email.value));
            }
        }

        if (!results.childElementCount) {
            const empty = document.createElement('p');
            empty.className = 'px-4 py-3 text-sm text-gray-500';
            empty.textContent = 'Tidak ada pengguna yang ditemukan.';
            results.append(empty);
        }

        results.classList.remove('hidden');
        setFeedback('');
    };

    const search = async () => {
        const value = searchInput.value.trim();
        clearSelection();

        if (value.length < 2) {
            results.classList.add('hidden');
            setFeedback('Ketik minimal 2 karakter.');
            return;
        }

        requestController?.abort();
        requestController = new AbortController();
        setFeedback('Mencari pengguna...');

        try {
            const url = new URL(modal.dataset.searchUrl, window.location.origin);
            url.searchParams.set('search', value);
            const response = await fetch(url, {
                headers: { Accept: 'application/json' },
                signal: requestController.signal,
            });

            if (!response.ok) {
                throw new Error('Search request failed');
            }

            renderResults(await response.json());
        } catch (error) {
            if (error.name !== 'AbortError') {
                results.classList.add('hidden');
                setFeedback('Pencarian gagal. Silakan coba lagi.', true);
            }
        }
    };

    const focusableElements = () => [...modal.querySelectorAll(
        'button:not([disabled]), input:not([disabled]), select:not([disabled]), [href]',
    )].filter((element) => element.offsetParent !== null);

    const openModal = (button) => {
        previouslyFocused = button;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.classList.add('overflow-hidden');
        searchInput.focus();
    };

    const closeModal = () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
        results.classList.add('hidden');
        searchInput.value = '';
        clearSelection();
        setFeedback('');
        previouslyFocused?.focus();
    };

    openButtons.forEach((button) => button.addEventListener('click', () => openModal(button)));
    closeButtons.forEach((button) => button.addEventListener('click', closeModal));

    searchInput.addEventListener('input', () => {
        window.clearTimeout(debounceTimer);
        debounceTimer = window.setTimeout(search, 300);
    });

    modal.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeModal();
            return;
        }

        if (event.key !== 'Tab') {
            return;
        }

        const focusable = focusableElements();
        const first = focusable[0];
        const last = focusable[focusable.length - 1];

        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    });

    copyButton?.addEventListener('click', async () => {
        await navigator.clipboard.writeText(inviteLinkInput.value);
        copyButton.textContent = 'Copied';
        window.setTimeout(() => {
            copyButton.textContent = 'Copy';
        }, 2000);
    });
}
