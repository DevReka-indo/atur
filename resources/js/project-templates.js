document.addEventListener('DOMContentLoaded', () => {
    const templateSelect = document.getElementById('project_template_id');
    const preview = document.getElementById('project-template-preview');

    const renderTemplatePreview = () => {
        if (!templateSelect || !preview) {
            return;
        }

        const option = templateSelect.options[templateSelect.selectedIndex];
        if (!option?.dataset.template) {
            preview.innerHTML = '<p class="font-semibold text-slate-800">Tanpa Template</p><p class="mt-1">Enam default task tetap dibuat seperti flow sebelumnya.</p>';
            return;
        }

        const template = JSON.parse(option.dataset.template);
        preview.innerHTML = `
            <div class="flex flex-wrap items-center gap-2">
                <p class="js-template-name font-semibold text-slate-900"></p>
                <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-700">Aktif</span>
            </div>
            <p class="js-template-meta mt-1 text-xs text-slate-500"></p>
            <p class="js-template-description mt-2"></p>
            <p class="js-template-stats mt-2 text-xs font-medium text-indigo-600"></p>
        `;
        preview.querySelector('.js-template-name').textContent = template.name;
        preview.querySelector('.js-template-meta').textContent = `${template.category} · Version ${template.version}`;
        preview.querySelector('.js-template-description').textContent = template.description || 'Tanpa deskripsi';
        preview.querySelector('.js-template-stats').textContent = `${template.tasks_count} task · total beban template ${template.leaf_weight} · estimasi selesai hari ke-${template.estimated_end_offset + 1}`;
    };

    templateSelect?.addEventListener('change', renderTemplatePreview);
    renderTemplatePreview();

    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.dataset.confirm)) {
                event.preventDefault();
            }
        });
    });
});
