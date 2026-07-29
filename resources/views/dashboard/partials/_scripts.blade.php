@once
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
@endonce

<script>
    document.addEventListener('DOMContentLoaded', function() {
        initProjectPieChart();
        initFloatingActionButton();
        initSortableDashboard();
        initDismissedAlerts();
    });

    function initProjectPieChart() {
        const chartElement = document.getElementById('projectPieChart');

        if (!chartElement || typeof Chart === 'undefined') {
            return;
        }

        const statusMapping = [
            'planning',
            'active',
            'on_hold',
            'completed',
            'cancelled',
            'urgent'
        ];

        new Chart(chartElement, {
            type: 'pie',
            data: {
                labels: ['Planning', 'Active', 'On Hold', 'Completed', 'Cancelled', 'Urgent'],
                datasets: [{
                    data: [
                        {{ $projectStats['planning'] ?? 0 }},
                        {{ $projectStats['active'] ?? 0 }},
                        {{ $projectStats['on_hold'] ?? 0 }},
                        {{ $projectStats['completed'] ?? 0 }},
                        {{ $projectStats['cancelled'] ?? 0 }},
                        {{ $projectStats['urgent'] ?? 0 }}
                    ],
                    backgroundColor: [
                        '#94a3b8',
                        '#10b981',
                        '#f59e0b',
                        '#3b82f6',
                        '#ef4444',
                        '#D50000'
                    ],
                    borderWidth: 2,
                    borderColor: '#ffffff',
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: '#111827',
                        padding: 10,
                        cornerRadius: 6,
                        titleFont: {
                            size: 13
                        },
                        bodyFont: {
                            size: 12
                        },
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ' + context.raw + ' Projects';
                            }
                        }
                    }
                },
                animation: {
                    animateRotate: true,
                    duration: 1000
                },
                onClick: function(event, activeElements) {
                    if (activeElements.length === 0) {
                        return;
                    }

                    const index = activeElements[0].index;
                    const status = statusMapping[index];

                    if (status) {
                        window.location.href = "{{ route('projects.index') }}?status=" + status;
                    }
                },
                onHover: function(event, activeElements) {
                    event.chart.canvas.style.cursor = activeElements.length > 0 ? 'pointer' : 'default';
                }
            }
        });
    }

    function initFloatingActionButton() {
        const fabBtn = document.getElementById('fab-btn');
        const fabMenu = document.getElementById('fab-menu');
        const fabIcon = document.getElementById('fab-icon');
        const fabContainer = document.getElementById('fab-container');
        const fabItems = document.querySelectorAll('.fab-item');

        if (!fabBtn || !fabMenu || !fabIcon || !fabContainer) {
            return;
        }

        let isOpen = false;
        let startX = 0;
        let startY = 0;
        let initialLeft = 0;
        let initialTop = 0;

        function updateMenuDirection() {
            const rect = fabContainer.getBoundingClientRect();
            const nearTop = rect.top < 150;
            const nearLeft = rect.left < 150;

            fabMenu.style.top = '';
            fabMenu.style.bottom = '';
            fabMenu.style.left = '';
            fabMenu.style.right = '';
            fabMenu.style.transform = '';

            if (nearTop) {
                fabMenu.style.top = '64px';
            } else {
                fabMenu.style.bottom = '64px';
            }

            if (nearLeft) {
                fabMenu.style.left = '0';
                fabMenu.style.transformOrigin = 'bottom left';

                fabItems.forEach(function(item) {
                    item.classList.remove('-translate-x-2');
                    item.classList.add('translate-x-2');
                });
            } else {
                fabMenu.style.right = '0';
                fabMenu.style.transformOrigin = 'bottom right';

                fabItems.forEach(function(item) {
                    item.classList.remove('translate-x-2');
                    item.classList.add('-translate-x-2');
                });
            }
        }

        function openMenu() {
            fabMenu.classList.remove('opacity-0', 'scale-90', 'pointer-events-none');
            fabMenu.classList.add('opacity-100', 'scale-100', 'pointer-events-auto');
            fabIcon.classList.add('rotate-45');

            fabItems.forEach(function(item, index) {
                setTimeout(function() {
                    item.classList.remove('opacity-0', '-translate-x-2');
                    item.classList.add('opacity-100', 'translate-x-0');
                }, 50 * (index + 1));
            });
        }

        function closeMenu() {
            fabMenu.classList.remove('opacity-100', 'scale-100', 'pointer-events-auto');
            fabMenu.classList.add('opacity-0', 'scale-90', 'pointer-events-none');
            fabIcon.classList.remove('rotate-45');

            fabItems.forEach(function(item) {
                item.classList.remove('opacity-100', 'translate-x-0');
                item.classList.add('opacity-0', '-translate-x-2');
            });
        }

        fabBtn.addEventListener('click', function() {
            updateMenuDirection();

            if (fabContainer.classList.contains('dragging')) {
                return;
            }

            isOpen = !isOpen;

            if (isOpen) {
                openMenu();
            } else {
                closeMenu();
            }
        });

        fabBtn.addEventListener('mousedown', function(event) {
            startX = event.clientX;
            startY = event.clientY;

            const rect = fabContainer.getBoundingClientRect();

            initialLeft = rect.left;
            initialTop = rect.top;

            fabContainer.classList.add('dragging');
            fabContainer.style.transition = 'none';
        });

        document.addEventListener('mousemove', function(event) {
            if (!fabContainer.classList.contains('dragging')) {
                return;
            }

            const dx = event.clientX - startX;
            const dy = event.clientY - startY;

            let newLeft = initialLeft + dx;
            let newTop = initialTop + dy;

            const rect = fabContainer.getBoundingClientRect();
            const maxLeft = window.innerWidth - rect.width;
            const maxTop = window.innerHeight - rect.height;

            newLeft = Math.max(0, Math.min(newLeft, maxLeft));
            newTop = Math.max(0, Math.min(newTop, maxTop));

            fabContainer.style.left = newLeft + 'px';
            fabContainer.style.top = newTop + 'px';
            fabContainer.style.bottom = 'auto';
            fabContainer.style.right = 'auto';
        });

        document.addEventListener('mouseup', function() {
            if (!fabContainer.classList.contains('dragging')) {
                return;
            }

            fabContainer.classList.remove('dragging');
            fabContainer.style.transition = '';
        });
    }

    function initSortableDashboard() {
        const grid = document.getElementById('dashboard-grid');

        if (!grid || typeof Sortable === 'undefined') {
            return;
        }

        const savedOrder = localStorage.getItem('dashboard-widget-order');

        if (savedOrder) {
            try {
                const order = JSON.parse(savedOrder);

                order.forEach(function(id) {
                    const element = grid.querySelector('[data-id="' + id + '"]');

                    if (element) {
                        grid.appendChild(element);
                    }
                });
            } catch (error) {
                localStorage.removeItem('dashboard-widget-order');
            }
        }

        Sortable.create(grid, {
            animation: 200,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            dragClass: 'sortable-drag',
            handle: '.widget-header, .widget-card',
            filter: '.no-drag, a, button, input, select, textarea, canvas, [onclick]',
            preventOnFilter: true,
            onMove: function(event) {
                const target = event.related;

                if (target.closest('a, button, input, select, textarea, canvas, [onclick]')) {
                    return false;
                }

                return true;
            },
            onEnd: function() {
                const order = Array.from(grid.querySelectorAll('.widget-card'))
                    .map(function(element) {
                        return element.dataset.id;
                    });

                localStorage.setItem('dashboard-widget-order', JSON.stringify(order));
            }
        });
    }

    function initDismissedAlerts() {
        const urgentCount = {{ $projectStats['urgent'] ?? 0 }};
        const urgentDismissedData = localStorage.getItem('urgent-alert-dismissed');

        if (urgentDismissedData) {
            try {
                const parsed = JSON.parse(urgentDismissedData);

                if (parsed.count === urgentCount) {
                    const alertElement = document.getElementById('urgent-projects-alert');

                    if (alertElement) {
                        alertElement.style.display = 'none';
                    }
                } else {
                    localStorage.removeItem('urgent-alert-dismissed');
                }
            } catch (error) {
                localStorage.removeItem('urgent-alert-dismissed');
            }
        }

    }

    function closeUrgentAlert() {
        const alert = document.getElementById('urgent-projects-alert');
        const currentCount = {{ $projectStats['urgent'] ?? 0 }};

        if (!alert) {
            return;
        }

        localStorage.setItem('urgent-alert-dismissed', JSON.stringify({
            count: currentCount,
            dismissedAt: new Date().toISOString()
        }));

        closeAlertElement(alert);
    }

    function closeAlertElement(alert) {
        alert.style.transition = 'all 0.3s ease';
        alert.style.opacity = '0';
        alert.style.maxHeight = '0';
        alert.style.overflow = 'hidden';
        alert.style.padding = '0';
        alert.style.margin = '0';

        setTimeout(function() {
            alert.style.display = 'none';
        }, 300);
    }
</script>
