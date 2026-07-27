const initializeProjectEdit = () => {
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');

    if (!startDateInput || !endDateInput) {
        return;
    }

    const synchronizeTimeline = () => {
        if (!startDateInput.value) {
            endDateInput.removeAttribute('min');

            return;
        }

        endDateInput.min = startDateInput.value;

        if (endDateInput.value && endDateInput.value < startDateInput.value) {
            endDateInput.value = '';
        }
    };

    startDateInput.addEventListener('change', synchronizeTimeline);
    synchronizeTimeline();
};

document.addEventListener('DOMContentLoaded', initializeProjectEdit);
