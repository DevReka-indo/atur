document.addEventListener('DOMContentLoaded', () => {
    const toTechnicalName = (value, separator) => value
        .normalize('NFKD')
        .replace(/[\u0300-\u036f]/g, '')
        .trim()
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, separator)
        .replace(new RegExp(`^${separator}+|${separator}+$`, 'g'), '');

    document.querySelectorAll('[data-permission-group]').forEach((group) => {
        const selectAll = group.querySelector('[data-select-all]');
        const permissionCheckboxes = Array.from(group.querySelectorAll('[data-permission-checkbox]'));

        if (!selectAll || permissionCheckboxes.length === 0) {
            return;
        }

        const updateSelectAllState = () => {
            const selectedCount = permissionCheckboxes.filter((checkbox) => checkbox.checked).length;

            selectAll.checked = selectedCount === permissionCheckboxes.length;
            selectAll.indeterminate = selectedCount > 0 && selectedCount < permissionCheckboxes.length;
        };

        selectAll.addEventListener('change', () => {
            permissionCheckboxes.forEach((checkbox) => {
                if (!checkbox.disabled) {
                    checkbox.checked = selectAll.checked;
                }
            });

            updateSelectAllState();
        });

        permissionCheckboxes.forEach((checkbox) => {
            checkbox.addEventListener('change', updateSelectAllState);
        });

        updateSelectAllState();
    });

    const roleNameInput = document.querySelector('[data-role-name-input]');
    const roleNamePreview = document.querySelector('[data-role-name-preview]');

    if (roleNameInput && roleNamePreview) {
        const updateRolePreview = () => {
            roleNamePreview.textContent = toTechnicalName(roleNameInput.value, '_') || 'belum_diisi';
        };

        roleNameInput.addEventListener('input', updateRolePreview);
        updateRolePreview();
    }

    const moduleInput = document.querySelector('[data-permission-module]');
    const actionSelect = document.querySelector('[data-permission-action]');
    const customActionInput = document.querySelector('[data-permission-custom-action]');
    const customActionWrapper = document.querySelector('[data-custom-action-wrapper]');
    const permissionPreview = document.querySelector('[data-permission-name-preview]');

    if (moduleInput && actionSelect && customActionInput && customActionWrapper && permissionPreview) {
        const updatePermissionPreview = () => {
            const usesCustomAction = actionSelect.value === 'custom';
            const module = toTechnicalName(moduleInput.value, '-') || 'module';
            const action = usesCustomAction
                ? toTechnicalName(customActionInput.value, '-') || 'custom-action'
                : actionSelect.value;

            customActionWrapper.classList.toggle('hidden', !usesCustomAction);
            customActionInput.required = usesCustomAction;
            permissionPreview.textContent = `${module}.${action}`;
        };

        moduleInput.addEventListener('blur', () => {
            moduleInput.value = toTechnicalName(moduleInput.value, '-');
            updatePermissionPreview();
        });
        moduleInput.addEventListener('input', updatePermissionPreview);
        actionSelect.addEventListener('change', updatePermissionPreview);
        customActionInput.addEventListener('input', updatePermissionPreview);
        customActionInput.addEventListener('blur', () => {
            customActionInput.value = toTechnicalName(customActionInput.value, '-');
            updatePermissionPreview();
        });
        updatePermissionPreview();
    }
});
