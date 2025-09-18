<script>
document.addEventListener('DOMContentLoaded', function() {
    // Logika untuk drag-and-drop SortableJS
    const columns = document.querySelectorAll('.kanban-column');
    columns.forEach(column => {
        new Sortable(column, {
            group: 'tasks',
            animation: 150,
            ghostClass: 'bg-blue-100',
            onEnd: function(evt) {
                const itemEl = evt.item;
                const toColumn = evt.to;
                const taskId = itemEl.id.replace('task-', '');
                const newStatus = toColumn.dataset.status;

                updateTaskStatus(taskId, newStatus, itemEl);
            },
        });
    });

    function updateTaskStatus(taskId, status, element) {
        const url = `/tasks/${taskId}/update-status`;
        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ status: status })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                element.style.borderColor = 'green';
                setTimeout(() => { element.style.borderColor = ''; }, 1000);
            } else {
                console.error('Failed to update task status');
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }
});

// --- INI PERBAIKANNYA ---
async function openEditModal(taskId) {
    try {
        const response = await fetch(`/tasks/${taskId}/edit`);
        if (!response.ok) throw new Error('Network response was not ok.');

        const task = await response.json(); // Langsung gunakan respons sebagai objek 'task'

        // Mengisi field-field di dalam modal
        const form = document.getElementById('edit_task_form');
        form.action = `/tasks/${taskId}`; // Set action form secara dinamis

        document.getElementById('edit_title').value = task.title;
        document.getElementById('edit_description').value = task.description || '';
        document.getElementById('edit_assignee_id').value = task.assignee_id || '';
        document.getElementById('edit_priority').value = task.priority;
        document.getElementById('edit_status').value = task.status;

        if (task.due_date) {
            // Format tanggal menjadi YYYY-MM-DD untuk input type="date"
            document.getElementById('edit_due_date').value = task.due_date.split('T')[0];
        } else {
            document.getElementById('edit_due_date').value = '';
        }

        // Menampilkan modal
        location.hash = 'edit_task_modal';

    } catch (error) {
        console.error('Gagal mengambil data tugas untuk diedit:', error);
        alert('Tidak dapat memuat data tugas. Silakan coba lagi.');
    }
}
</script>

