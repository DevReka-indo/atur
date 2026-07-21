@pushOnce('scripts', 'task-kanban-board')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.js-kanban-board').forEach(function(board) {
                let draggingCard = null;
                let sourceStatus = null;
                let sourceZone = null;
                let sourceNextSibling = null;
                let placeholder = null;

                function showToast(message, type) {
                    const toast = board.querySelector('.js-kanban-toast');
                    const icon = toast.querySelector('.js-kanban-toast-icon');
                    const messageElement = toast.querySelector('.js-kanban-toast-message');

                    icon.innerHTML = type === 'error'
                        ? '<i class="fa-solid fa-circle-xmark text-red-400"></i>'
                        : '<i class="fa-solid fa-circle-check text-emerald-400"></i>';
                    messageElement.textContent = message;
                    toast.classList.remove('hidden');
                    toast.classList.add('flex');
                    clearTimeout(toast.hideTimeout);
                    toast.hideTimeout = setTimeout(function() {
                        toast.classList.add('hidden');
                        toast.classList.remove('flex');
                    }, 3500);
                }

                function makePlaceholder() {
                    const element = document.createElement('div');
                    element.className = 'kanban-placeholder h-20 flex-shrink-0 rounded-lg border-2 border-dashed border-indigo-400 bg-indigo-50/70 pointer-events-none';

                    return element;
                }

                function refreshColumn(zone) {
                    if (!zone) {
                        return;
                    }

                    const column = zone.closest('.kanban-column');
                    const count = zone.querySelectorAll('.kanban-card').length;
                    const badge = column?.querySelector('.kanban-count');
                    const emptyState = zone.querySelector('.kanban-empty');

                    if (badge) {
                        badge.textContent = count;
                    }
                    if (emptyState) {
                        emptyState.classList.toggle('hidden', count > 0);
                    }
                }

                function nextCardAt(zone, pointerY) {
                    return [...zone.querySelectorAll('.kanban-card')]
                        .filter(function(card) {
                            return card !== draggingCard;
                        })
                        .reduce(function(closest, card) {
                            const box = card.getBoundingClientRect();
                            const offset = pointerY - box.top - box.height / 2;

                            return offset < 0 && offset > closest.offset
                                ? { offset: offset, element: card }
                                : closest;
                        }, { offset: Number.NEGATIVE_INFINITY }).element;
                }

                function resetDropZones() {
                    board.querySelectorAll('.kanban-drop-zone').forEach(function(zone) {
                        zone.classList.remove(
                            'bg-indigo-50/50',
                            'outline',
                            'outline-2',
                            'outline-dashed',
                            'outline-indigo-300'
                        );
                    });
                }

                function restoreCard(card) {
                    if (!sourceZone) {
                        return;
                    }

                    if (sourceNextSibling && sourceNextSibling.parentElement === sourceZone) {
                        sourceZone.insertBefore(card, sourceNextSibling);
                    } else {
                        sourceZone.appendChild(card);
                    }
                }

                function setProcessing(card, processing) {
                    card.dataset.processing = processing ? '1' : '0';
                    card.setAttribute('aria-busy', processing ? 'true' : 'false');
                    card.draggable = !processing;
                    card.classList.toggle('pointer-events-none', processing);
                    card.classList.toggle('opacity-60', processing);
                }

                async function responseErrorMessage(response) {
                    try {
                        const payload = await response.json();
                        const validationMessage = payload.errors
                            ? Object.values(payload.errors).flat().find(Boolean)
                            : null;

                        return validationMessage || payload.message || 'Request gagal (' + response.status + ').';
                    } catch (error) {
                        return 'Request gagal (' + response.status + ').';
                    }
                }

                function onDragStart(event) {
                    if (event.target.closest('a, button') || this.dataset.processing === '1') {
                        event.preventDefault();

                        return;
                    }

                    draggingCard = this;
                    sourceStatus = this.dataset.status;
                    sourceZone = this.closest('.kanban-drop-zone');
                    sourceNextSibling = this.nextElementSibling;
                    placeholder = makePlaceholder();

                    event.dataTransfer.effectAllowed = 'move';
                    event.dataTransfer.setData('text/plain', this.dataset.taskToken);
                    requestAnimationFrame(function() {
                        draggingCard?.classList.add('opacity-40', 'rotate-1', 'scale-[1.02]');
                    });
                }

                function onDragEnd() {
                    draggingCard?.classList.remove('opacity-40', 'rotate-1', 'scale-[1.02]');
                    placeholder?.remove();
                    placeholder = null;
                    resetDropZones();
                }

                function onDragOver(event) {
                    event.preventDefault();
                    if (!draggingCard || draggingCard.dataset.processing === '1') {
                        return;
                    }

                    this.classList.add(
                        'bg-indigo-50/50',
                        'outline',
                        'outline-2',
                        'outline-dashed',
                        'outline-indigo-300'
                    );
                    const nextCard = nextCardAt(this, event.clientY);
                    nextCard ? this.insertBefore(placeholder, nextCard) : this.appendChild(placeholder);
                }

                function onDragLeave(event) {
                    if (!this.contains(event.relatedTarget)) {
                        this.classList.remove(
                            'bg-indigo-50/50',
                            'outline',
                            'outline-2',
                            'outline-dashed',
                            'outline-indigo-300'
                        );
                    }
                }

                async function onDrop(event) {
                    event.preventDefault();
                    if (!draggingCard || draggingCard.dataset.processing === '1') {
                        return;
                    }

                    const card = draggingCard;
                    const destinationZone = this;
                    const newStatus = destinationZone.dataset.status;
                    const oldStatus = sourceStatus;
                    const insertionTarget = nextCardAt(destinationZone, event.clientY);

                    placeholder?.remove();
                    resetDropZones();

                    if (newStatus === oldStatus) {
                        insertionTarget
                            ? destinationZone.insertBefore(card, insertionTarget)
                            : destinationZone.appendChild(card);
                        refreshColumn(destinationZone);

                        return;
                    }

                    setProcessing(card, true);

                    try {
                        const response = await fetch(card.dataset.statusUrl, {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify({ status: newStatus }),
                        });

                        if (!response.ok) {
                            throw new Error(await responseErrorMessage(response));
                        }

                        insertionTarget
                            ? destinationZone.insertBefore(card, insertionTarget)
                            : destinationZone.appendChild(card);
                        card.dataset.status = newStatus;
                        const title = card.querySelector('a.mt-2.block.text-sm.font-semibold');
                        if (title) {
                            title.classList.toggle('line-through', newStatus === 'completed');
                            title.classList.toggle('text-gray-400', newStatus === 'completed');
                            title.classList.toggle('text-gray-800', newStatus !== 'completed');
                        }

                        refreshColumn(sourceZone);
                        refreshColumn(destinationZone);
                        showToast('Task dipindahkan ke ' + newStatus.replaceAll('_', ' ') + '.', 'success');
                    } catch (error) {
                        restoreCard(card);
                        refreshColumn(sourceZone);
                        refreshColumn(destinationZone);
                        showToast(error.message || 'Status task gagal diperbarui.', 'error');
                    } finally {
                        setProcessing(card, false);
                    }
                }

                board.querySelectorAll('.kanban-card[draggable="true"]').forEach(function(card) {
                    card.addEventListener('dragstart', onDragStart);
                    card.addEventListener('dragend', onDragEnd);
                });

                board.querySelectorAll('.kanban-drop-zone').forEach(function(zone) {
                    zone.addEventListener('dragover', onDragOver);
                    zone.addEventListener('dragleave', onDragLeave);
                    zone.addEventListener('drop', onDrop);
                });
            });
        });
    </script>
@endPushOnce
