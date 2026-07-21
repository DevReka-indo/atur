@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const panel = document.querySelector('.js-subtask-weight-panel');
            const input = document.getElementById('subtask_weight_percentage');

            if (!panel || !input) {
                return;
            }

            const baseWeight = Number(panel.dataset.baseWeight || 0);
            const capacity = Number(panel.dataset.capacity || 0);
            const statusUnlocked = panel.dataset.statusUnlocked === '1';
            const remainingLabel = panel.querySelector('.js-remaining-weight');
            const warning = panel.querySelector('.js-weight-warning');
            const statusOptions = document.querySelectorAll('.js-subtask-status-option');

            function updateWeightReadiness() {
                const value = Number(input.value || 0);
                const total = baseWeight + value;
                const remaining = Math.max(0, 100 - total);
                const exceedsCapacity = value > capacity;
                const isReady = Math.abs(total - 100) < 0.001;

                remainingLabel.textContent = `${remaining.toFixed(2)}%`;
                warning.classList.toggle('hidden', !exceedsCapacity && isReady);
                warning.textContent = exceedsCapacity
                    ? `Bobot melebihi sisa yang tersedia (${capacity.toFixed(2)}%).`
                    : 'Total bobot sibling belum 100%. Status awal harus tetap To Do.';

                statusOptions.forEach(function(option) {
                    option.disabled = !statusUnlocked && !isReady;
                });
            }

            input.addEventListener('input', updateWeightReadiness);
            updateWeightReadiness();
        });
    </script>
@endpush
