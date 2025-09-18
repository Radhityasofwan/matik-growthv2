import Alpine from 'alpinejs'
window.Alpine = Alpine

import '../css/app.css'
import AOS from 'aos'
import 'aos/dist/aos.css'
import ApexCharts from 'apexcharts'
import Sortable from 'sortablejs';

// ... (Kode Alpine Store & Components lain tetap sama) ...
Alpine.store('ui', {
  isMobileMenuOpen: false,
  isSearchOpen: false,
  sidebarHover: false,
});
// ... (notifBell tetap sama) ...

// --- KANBAN LOGIC BARU (TERPUSAT) ---
window.Kanban = (() => {
  function csrf() { return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '' }
  
  function initSortable() {
    document.querySelectorAll('.kanban-column').forEach(column => {
      new Sortable(column, {
        group: 'tasks',
        animation: 150,
        ghostClass: 'sortable-ghost',
        chosenClass: 'sortable-chosen',
        onEnd: (evt) => {
          const itemEl = evt.item;
          const taskId = itemEl.id.replace('task-', '');
          const newStatus = evt.to.dataset.status;
          updateTaskStatus(taskId, newStatus, itemEl);
        },
      });
    });
  }

  async function updateTaskStatus(taskId, status, element) {
    try {
      const res = await fetch(`/tasks/${taskId}/update-status`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' },
        body: JSON.stringify({ status }),
      });
      if (!res.ok) throw new Error('Network response was not ok');
      // Aksi setelah berhasil (misal: toast)
    } catch (error) {
      console.error('Error updating task status:', error);
    }
  }

  window.openEditModal = async function(taskId) {
    try {
        const res = await fetch(`/tasks/${taskId}/edit`);
        if (!res.ok) throw new Error('Failed to fetch task data');
        const { task } = await res.json();
        
        const form = document.getElementById('edit_task_form');
        form.action = `/tasks/${task.id}`;
        
        // Isi semua field di modal edit
        Object.keys(task).forEach(key => {
            const el = document.getElementById(`edit_${key}`);
            if (el) {
                if (key.includes('_at') && task[key]) {
                    el.value = new Date(task[key]).toISOString().split('T')[0];
                } else {
                    el.value = task[key];
                }
            }
        });
        document.getElementById('edit_task_modal').showModal();
    } catch (error) {
        console.error('Error opening edit modal:', error);
    }
  };

  // --- Aksi Dropdown Baru ---
  window.togglePin = async function(taskId) {
    try {
      const res = await fetch(`/tasks/${taskId}/toggle-pin`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' },
      });
      const data = await res.json();
      document.getElementById(`task-${taskId}`).classList.toggle('is-pinned', data.is_pinned);
    } catch (error) {
      console.error('Error toggling pin:', error);
    }
  };
  
  window.copyLink = async function(taskId) {
    try {
      const res = await fetch(`/tasks/${taskId}/copy-link`);
      const { url } = await res.json();
      navigator.clipboard.writeText(url);
      // Ganti dengan toast yang lebih baik
      alert('Link disalin ke clipboard!');
    } catch (error) {
      console.error('Error copying link:', error);
    }
  };

  return { init: initSortable };
})();


// ---------------- Boot ----------------
document.addEventListener('DOMContentLoaded', () => {
  // ... (init AOS, DemoChart, dll. tetap sama) ...
  if (document.querySelector('.kanban-column')) {
    Kanban.init();
  }
});

Alpine.start();

