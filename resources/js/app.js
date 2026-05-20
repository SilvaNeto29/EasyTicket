import './bootstrap';
import Sortable from 'sortablejs';

function initKanban() {
    const columns = document.querySelectorAll('.sortable-column');
    if (columns.length === 0) return;

    columns.forEach((column) => {
        if (column.__sortable) return;

        column.__sortable = Sortable.create(column, {
            group: 'kanban',
            animation: 150,
            ghostClass: 'opacity-30',
            chosenClass: 'shadow-lg',
            dragClass: 'rotate-1',
            touchStartThreshold: 5,
            onEnd(event) {
                const ticketId = event.item.dataset.ticketId;
                const newStatus = event.to.dataset.status;

                if (! ticketId || ! newStatus) return;

                // Dispatch Livewire event to the projects.show component
                const component = document.querySelector('[wire\\:id]');
                if (component) {
                    Livewire.find(component.getAttribute('wire:id'))
                            .call('updateTicketStatus', parseInt(ticketId, 10), newStatus);
                }
            },
        });
    });
}

// Initialize on page load and after Livewire navigates
document.addEventListener('DOMContentLoaded', initKanban);
document.addEventListener('livewire:navigated', initKanban);
document.addEventListener('livewire:updated', initKanban);
