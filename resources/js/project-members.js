const initializeProjectMembers = () => {
    const root = document.querySelector('[data-project-members]');

    if (!root) {
        return;
    }

    const memberMenus = [...root.querySelectorAll('[data-member-menu]')];

    const closeMemberMenus = (exceptId = null) => {
        memberMenus.forEach((menu) => {
            if (menu.id === exceptId) {
                return;
            }

            menu.classList.add('hidden');
            root.querySelector(`[data-member-menu-trigger="${menu.id}"]`)?.setAttribute('aria-expanded', 'false');
        });
    };

    root.querySelectorAll('[data-member-menu-trigger]').forEach((trigger) => {
        trigger.addEventListener('click', (event) => {
            event.stopPropagation();
            const menu = document.getElementById(trigger.dataset.memberMenuTrigger);
            const willOpen = menu?.classList.contains('hidden') ?? false;

            closeMemberMenus(willOpen ? menu?.id : null);
            menu?.classList.toggle('hidden', !willOpen);
            trigger.setAttribute('aria-expanded', String(willOpen));
        });
    });

    root.querySelectorAll('[data-role-menu-trigger]').forEach((trigger) => {
        trigger.addEventListener('click', (event) => {
            event.stopPropagation();
            const menu = document.getElementById(trigger.dataset.roleMenuTrigger);
            const willOpen = menu?.classList.contains('hidden') ?? false;

            menu?.classList.toggle('hidden', !willOpen);
            trigger.setAttribute('aria-expanded', String(willOpen));
        });
    });

    document.addEventListener('click', (event) => {
        if (!event.target.closest('[data-member-menu]') && !event.target.closest('[data-member-menu-trigger]')) {
            closeMemberMenus();
        }
    });

    root.querySelectorAll('[data-remove-project-member]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.dataset.confirmMessage)) {
                event.preventDefault();
            }
        });
    });

    const modal = root.querySelector('[data-member-invite-modal]');

    if (!modal) {
        return;
    }

    const dialog = modal.querySelector('[data-member-invite-dialog]');
    const searchInput = modal.querySelector('[data-member-search]');
    const selectedMemberInput = modal.querySelector('[data-selected-member-id]');
    const submitButton = modal.querySelector('[data-submit-member-invite]');
    const candidates = [...modal.querySelectorAll('[data-member-candidate]')];
    const noResults = modal.querySelector('[data-member-no-results]');
    let opener = null;

    const updateCandidateSelection = () => {
        if (!candidates.some((candidate) => candidate.dataset.memberId === selectedMemberInput.value)) {
            selectedMemberInput.value = '';
        }

        candidates.forEach((candidate) => {
            const isSelected = candidate.dataset.memberId === selectedMemberInput.value;
            const indicator = candidate.querySelector('[data-member-selected-indicator]');

            candidate.setAttribute('aria-pressed', String(isSelected));
            candidate.classList.toggle('border-indigo-600', isSelected);
            candidate.classList.toggle('bg-indigo-50/60', isSelected);
            candidate.classList.toggle('border-slate-200', !isSelected);
            indicator.classList.toggle('hidden', !isSelected);
            indicator.classList.toggle('flex', isSelected);
        });

        submitButton.disabled = selectedMemberInput.value === '';
    };

    const filterCandidates = () => {
        const query = searchInput.value.trim().toLocaleLowerCase('id-ID');
        let visibleCount = 0;

        candidates.forEach((candidate) => {
            const isVisible = candidate.dataset.searchText.includes(query);

            candidate.classList.toggle('hidden', !isVisible);
            visibleCount += isVisible ? 1 : 0;
        });

        noResults.classList.toggle('hidden', visibleCount > 0 || candidates.length === 0);
    };

    const closeModal = () => {
        modal.classList.add('hidden');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('overflow-hidden');
        opener?.focus();
    };

    const openModal = (trigger = null) => {
        opener = trigger;
        searchInput.value = '';
        filterCandidates();
        updateCandidateSelection();
        modal.classList.remove('hidden');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('overflow-hidden');
        window.requestAnimationFrame(() => searchInput.focus());
    };

    root.querySelectorAll('[data-open-member-invite]').forEach((trigger) => {
        trigger.addEventListener('click', () => openModal(trigger));
    });

    modal.querySelectorAll('[data-close-member-invite]').forEach((trigger) => {
        trigger.addEventListener('click', closeModal);
    });

    modal.querySelector('[data-member-invite-backdrop]').addEventListener('click', closeModal);
    searchInput.addEventListener('input', filterCandidates);

    candidates.forEach((candidate) => {
        candidate.addEventListener('click', () => {
            selectedMemberInput.value = candidate.dataset.memberId;
            updateCandidateSelection();
        });
    });

    modal.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeModal();
            return;
        }

        if (event.key !== 'Tab') {
            return;
        }

        const focusableElements = [...dialog.querySelectorAll(
            'button:not([disabled]):not(.hidden), input:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])',
        )].filter((element) => element.offsetParent !== null);
        const firstElement = focusableElements[0];
        const lastElement = focusableElements.at(-1);

        if (event.shiftKey && document.activeElement === firstElement) {
            event.preventDefault();
            lastElement.focus();
        } else if (!event.shiftKey && document.activeElement === lastElement) {
            event.preventDefault();
            firstElement.focus();
        }
    });

    updateCandidateSelection();

    if (modal.dataset.openOnError === 'true') {
        openModal();
    }
};

document.addEventListener('DOMContentLoaded', initializeProjectMembers);
