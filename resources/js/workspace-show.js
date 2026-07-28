const workspaceShow = document.querySelector('[data-workspace-show]');

if (workspaceShow) {
    let activeProjectStatusDropdown = null;
    const removeUrls = {
        workspaceOnly: '',
        cascade: '',
    };

    window.openModal = (id) => {
        const modal = document.getElementById(id);

        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
    };

    window.closeModal = (id) => {
        const modal = document.getElementById(id);

        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    };

    window.toggleMemberDropdown = (memberId, prefix) => {
        const dropdownId = `dd-${prefix}-${memberId}`;
        const dropdown = document.getElementById(dropdownId);

        document.querySelectorAll('[id^="dd-"]').forEach((element) => {
            if (element.id !== dropdownId) {
                element.classList.add('hidden');
            }
        });
        dropdown?.classList.toggle('hidden');
    };

    window.toggleSubRoles = (subId) => {
        document.getElementById(subId)?.classList.toggle('hidden');
    };

    window.confirmRemoveMember = (memberName, workspaceOnlyUrl, cascadeUrl) => {
        removeUrls.workspaceOnly = workspaceOnlyUrl;
        removeUrls.cascade = cascadeUrl;

        fetch(workspaceOnlyUrl, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                Accept: 'application/json',
            },
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.needs_confirmation) {
                    document.getElementById('modal-member-name').textContent = data.user_name;
                    document.getElementById('modal-project-info').textContent =
                        `Member ini masih terdaftar di ${data.project_count} project: ${data.project_names.join(', ')}. Pilih tindakan:`;
                    window.openModal('remove-member-modal');
                } else {
                    window.location.href = `${window.location.pathname}?tab=members`;
                }
            })
            .catch(() => {
                if (window.confirm(`Hapus ${memberName} dari workspace?`)) {
                    const form = document.getElementById('form-remove');
                    form.action = workspaceOnlyUrl;
                    form.submit();
                }
            });
    };

    window.submitRemove = (type) => {
        const form = document.getElementById('form-remove');
        form.action = type === 'cascade' ? removeUrls.cascade : removeUrls.workspaceOnly;
        form.submit();
    };

    window.closeRemoveMemberModal = () => {
        window.closeModal('remove-member-modal');
    };

    window.toggleProjectStatusDropdown = (projectId, button) => {
        const dropdown = document.getElementById(`status-dropdown-${projectId}`);

        document.querySelectorAll('[id^="status-dropdown-"]').forEach((element) => {
            if (element.id !== `status-dropdown-${projectId}`) {
                element.classList.add('hidden');
            }
        });

        if (dropdown.classList.contains('hidden')) {
            const bounds = button.getBoundingClientRect();
            dropdown.style.top = `${bounds.bottom + window.scrollY + 4}px`;
            dropdown.style.left = `${bounds.left + window.scrollX}px`;
            dropdown.classList.remove('hidden');
            activeProjectStatusDropdown = projectId;
        } else {
            dropdown.classList.add('hidden');
            activeProjectStatusDropdown = null;
        }
    };

    window.updateProjectStatus = (event) => {
        event.preventDefault();

        const button = event.target.closest('button');
        const form = button.closest('form');
        const originalContent = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i> Updating...';

        fetch(form.action, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': form.querySelector('input[name="_token"]').value,
                'X-HTTP-Method-Override': 'PATCH',
                Accept: 'application/json',
            },
            body: new FormData(form),
        })
            .then((response) => {
                if (!response.ok) {
                    throw new Error('Failed to update project status');
                }

                window.location.reload();
            })
            .catch(() => {
                button.disabled = false;
                button.innerHTML = originalContent;
                window.alert('Gagal mengubah status. Silakan coba lagi.');
            });
    };

    document.addEventListener('click', (event) => {
        if (!event.target.closest('[id^="dd-"]') &&
            !event.target.closest('button[onclick^="toggleMemberDropdown"]')) {
            document.querySelectorAll('[id^="dd-"]').forEach((element) => {
                element.classList.add('hidden');
            });
        }

        if (!event.target.closest('[id^="status-dropdown-"]') &&
            !event.target.closest('[onclick^="toggleProjectStatusDropdown"]')) {
            document.querySelectorAll('[id^="status-dropdown-"]').forEach((element) => {
                element.classList.add('hidden');
            });
            activeProjectStatusDropdown = null;
        }
    });

    window.addEventListener('scroll', () => {
        if (activeProjectStatusDropdown !== null) {
            document.querySelectorAll('[id^="status-dropdown-"]').forEach((element) => {
                element.classList.add('hidden');
            });
            activeProjectStatusDropdown = null;
        }
    }, { passive: true });
}
