// 1) Alpine
import Alpine from 'alpinejs'
window.Alpine = Alpine

// 2) CSS & Libraries
import '../css/app.css'
import AOS from 'aos'
import 'aos/dist/aos.css'
import ApexCharts from 'apexcharts'
import Sortable from 'sortablejs';

// ---------------- Alpine Global Store (UI) ----------------
Alpine.store('ui', {
  isMobileMenuOpen: false,
  isSearchOpen: false,
  sidebarHover: false,
})

// ---------------- Alpine Components (notif) ----------------
Alpine.data('notifBell', (el) => {
  const unreadInit = parseInt(el?.dataset?.unread || '0', 10) || 0
  let preview = []
  try { preview = JSON.parse(el?.dataset?.preview || '[]') } catch { preview = [] }
  const feedUrl = el?.dataset?.feed || ''
  const readBase = el?.dataset?.readBase || ''
  const markAllUrl = el?.dataset?.readAll || ''

  return {
    open: false,
    unread: unreadInit,
    items: (preview || []).map(n => ({ source: n.source ?? 'notification', ...n })),
    pollMs: 15000,
    timer: null,
    csrf() {
      return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
    },
    feedUrl() { return feedUrl },
    readUrl(id) { return readBase ? `${readBase}/${encodeURIComponent(id)}/read` : '' },
    markAllUrl() { return markAllUrl },
    init() {
      if (this.feedUrl()) {
        this.fetchFeed()
        this.timer = setInterval(() => this.fetchFeed(), this.pollMs)
      }
      document.addEventListener('close-all', () => this.open = false)
    },
    async fetchFeed() {
      try {
        const res = await fetch(this.feedUrl(), { headers: { 'Accept': 'application/json' } })
        if (!res.ok) return
        const json = await res.json()
        this.unread = json.unread_count ?? this.unread
        this.items = (json.notifications || []).map(n => ({ source: n.source ?? 'notification', ...n }))
      } catch (e) {
        console.error('notifBell fetch error:', e)
      }
    },
    async markReadIfNotification(n) {
      if (n.source !== 'notification' || n.read_at) return
      const url = this.readUrl(n.id)
      if (!url) return
      try {
        await fetch(url, {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': this.csrf(), 'Accept': 'application/json' }
        })
        n.read_at = new Date().toISOString()
        if (this.unread > 0) this.unread--
      } catch (e) {
        console.error('notifBell markRead error:', e)
      }
    },
    async markAll() {
      const url = this.markAllUrl()
      if (!url) return
      if (!this.items.some(x => x.source === 'notification' && !x.read_at)) return
      try {
        await fetch(url, {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': this.csrf(), 'Accept': 'application/json' }
        })
        this.items = this.items.map(n => n.source === 'notification' ? { ...n, read_at: new Date().toISOString() } : n)
        this.unread = 0
      } catch (e) {
        console.error('notifBell markAll error:', e)
      }
    },
    async go(n) {
      await this.markReadIfNotification(n)
      if (n.url) window.location.href = n.url
    }
  }
})

// ---------------- AOS Init ----------------
function initAOS() {
  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches
  AOS.init({
    once: true,
    duration: prefersReducedMotion ? 0 : 600,
    easing: 'ease-out-cubic',
    disable: prefersReducedMotion
  })
}

// ---------------- Demo Chart (opsional) ----------------
function initDemoChart() {
  const el = document.querySelector('#chart-demo')
  if (!el || typeof ApexCharts === 'undefined') return
  const chart = new ApexCharts(el, {
    chart: { type: 'area', height: 320, animations: { enabled: true }, toolbar: { show: false }, fontFamily: 'Inter Variable, Inter, sans-serif' },
    series: [
      { name: 'Sales', data: [31, 40, 28, 51, 42, 109, 100] },
      { name: 'Revenue', data: [11, 32, 45, 32, 34, 52, 41] },
    ],
    xaxis: { categories: ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] },
    colors: ['#3B82F6', '#F472B6'],
    stroke: { curve: 'smooth', width: 3 },
    fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05, stops: [0,90,100] } },
    dataLabels: { enabled: false },
    grid: { borderColor: 'rgba(226,232,240,.4)', strokeDashArray: 4 },
    tooltip: { theme: 'dark' },
    legend: { position:'top', horizontalAlign:'left' },
  })
  chart.render().then(() => document.getElementById('chart-skeleton')?.classList.add('hidden'))
}


// ---------------- TaskBoard (Kanban) ----------------
const TaskBoard = (() => {
  // --- Utils ---
  function csrf(){ return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '' }
  function flash(el){ if(!el) return; el.classList.add('flash-ring'); setTimeout(()=>el.classList.remove('flash-ring'),700) }

  // --- API Calls ---
  async function callTaskApi(url, method = 'POST', body = {}) {
    try {
      const res = await fetch(url, {
        method: method,
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' },
        body: Object.keys(body).length > 0 ? JSON.stringify(body) : null
      });
      if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
      return await res.json();
    } catch (err) {
      console.error(`Error calling API [${method} ${url}]:`, err);
      // Fallback ke reload jika API gagal untuk operasi penting
      // location.reload();
      return null;
    }
  }

  // --- Main Functions ---
  function initSortable() {
    const columns = document.querySelectorAll('.kanban-column');
    if (columns.length === 0) return;

    columns.forEach(column => {
      new Sortable(column, {
        group: 'tasks',
        animation: 150,
        ghostClass: 'opacity-50 bg-base-300',
        onEnd: function(evt) {
          const itemEl = evt.item;
          const toColumn = evt.to;
          const taskId = (itemEl.id || '').replace('task-', '');
          const newStatus = toColumn?.dataset?.status;
          if (!taskId || !newStatus) return;
          updateTaskStatus(taskId, newStatus, itemEl);
        },
      });
    });
  }

  function updateTaskStatus(taskId, status, element, notify_wa = 0) {
    callTaskApi(`/tasks/${taskId}/update-status`, 'POST', { status, notify_wa })
      .then(data => {
        if (data?.success) flash(element);
      });
  }

  async function openEditModal(taskId) {
      const payload = await callTaskApi(`/tasks/${taskId}/edit`, 'GET');
      if (!payload || !payload.task) {
          alert('Gagal memuat data tugas.');
          return;
      }
      const task = payload.task;
      const modal = document.getElementById('edit_task_modal');
      if (!modal) return;
      
      const form = modal.querySelector('form');
      form.action = `/tasks/${taskId}`;
      
      // Mengisi semua field di modal
      form.querySelector('#edit_title').value = task.title || '';
      form.querySelector('#edit_progress').value = task.progress || 0;
      form.querySelector('#edit_start_at').value = task.start_at ? task.start_at.split(' ')[0] : '';
      form.querySelector('#edit_due_date').value = task.due_date ? task.due_date.split(' ')[0] : '';
      form.querySelector('#edit_priority').value = task.priority || 'medium';
      form.querySelector('#edit_assignee_id').value = task.assignee_id || '';
      form.querySelector('#edit_icon').value = task.icon || '';

      modal.showModal();
  }

  async function togglePin(taskId) {
      const data = await callTaskApi(`/tasks/${taskId}/toggle-pin`, 'POST');
      if (data?.success) {
        // Cukup reload untuk menyusun ulang board
        location.reload(); 
      }
  }

  async function duplicateTask(taskId) {
      if (!confirm('Anda yakin ingin menduplikasi tugas ini?')) return;
      const data = await callTaskApi(`/tasks/${taskId}/duplicate`, 'POST');
      if (data?.success) {
          location.reload();
      }
  }

  function copyLink(taskId) {
      const url = `${window.location.origin}/tasks#task-${taskId}`;
      navigator.clipboard.writeText(url).then(() => {
          alert('Link tugas berhasil disalin!');
      }).catch(err => {
          console.error('Gagal menyalin link:', err);
          alert('Gagal menyalin link.');
      });
  }

  // Public API
  return {
    initSortable,
    openEditModal,
    togglePin,
    duplicateTask,
    copyLink
  };
})();

// Expose functions to global scope for onclick attributes in Blade
window.openEditModal = TaskBoard.openEditModal;
window.togglePin = TaskBoard.togglePin;
window.duplicateTask = TaskBoard.duplicateTask;
window.copyLink = TaskBoard.copyLink;

// ---------------- Boot ----------------
document.addEventListener('DOMContentLoaded', () => {
  initAOS();
  initDemoChart();

  // Esc to close mobile drawer/search
  window.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      Alpine.store('ui').isMobileMenuOpen = false;
      Alpine.store('ui').isSearchOpen = false;
    }
  });

  // Initialize Kanban board
  TaskBoard.initSortable();
});

Alpine.start();

