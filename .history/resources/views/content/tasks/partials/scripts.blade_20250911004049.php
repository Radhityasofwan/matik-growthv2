<script>
document.addEventListener('DOMContentLoaded', function() {
    const columns = document.querySelectorAll('.kanban-column');
    columns.forEach(column => {
        new Sortable(column, {
            group: 'tasks',
            animation: 150,
            ghostClass: 'bg-blue-100',
            onEnd: function(evt) {
                const itemEl = evt.item; // dragged HTMLElement
                const toColumn = evt.to;   // target list
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
                // Beri feedback visual, misal border hijau sesaat
                element.style.borderColor = 'green';
                setTimeout(() => { element.style.borderColor = ''; }, 1000);
            } else {
                // Kembalikan card jika gagal
                console.error('Failed to update task status');
                // Logika untuk mengembalikan card bisa ditambahkan di sini
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }
});

async function openEditModal(taskId) {
    try {
        const response = await fetch(`/tasks/${taskId}/edit`);
        if (!response.ok) throw new Error('Network response was not ok.');
        const data = await response.json();

        // Populate the modal fields
        const form = document.getElementById('edit_task_form');
        form.action = `/tasks/${taskId}`;
        document.getElementById('edit_title').value = data.title;
        document.getElementById('edit_description').value = data.description || '';
        document.getElementById('edit_assignee_id').value = data.assignee_id || '';
        document.getElementById('edit_priority').value = data.priority;
        document.getElementById('edit_status').value = data.status;
        if (data.due_date) {
            document.getElementById('edit_due_date').value = data.due_date.split('T')[0]; // Format to YYYY-MM-DD
        } else {
            document.getElementById('edit_due_date').value = '';
        }

        // Show the modal
        location.hash = 'edit_task_modal';

    } catch (error) {
        console.error('Failed to fetch task data for editing:', error);
        alert('Could not load task data. Please try again.');
    }
}
</script>
