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
  function addOver(col, on){ col.classList.toggle('is-over', !!on) }
  function flash(el){ if(!el) return; el.classList.add('flash-ring'); setTimeout(()=>el.classList.remove('flash-ring'),700) }

  // --- Main Functions ---
  function initSortable() {
    const columns = document.querySelectorAll('.kanban-column');
    if (columns.length === 0) return; // Only run on pages with a kanban board

    columns.forEach(column => {
      new Sortable(column, {
        group: 'tasks',
        animation: 150,
        ghostClass: 'bg-primary/20',
        onChoose: () => addOver(column, true),
        onUnchoose: () => addOver(column, false),
        onAdd: () => addOver(column, false),
        onEnd: function(evt) {
          const itemEl    = evt.item;
          const toColumn  = evt.to;
          const taskId    = (itemEl.id || '').replace('task-', '');
          const newStatus = toColumn?.dataset?.status;
          if (!taskId || !newStatus) return;
          promptAndUpdateStatus(taskId, newStatus, itemEl);
        },
      });
    });
  }

  function promptAndUpdateStatus(taskId, status, element) {
    let notify_wa = 0;
    if (['in_progress','review','done'].includes(status)) {
      if (confirm('Kirim notifikasi WA ke PIC untuk perubahan status ini?')) {
        notify_wa = 1;
      }
    }
    updateTaskStatus(taskId, status, element, notify_wa);
  }

  function updateTaskStatus(taskId, status, element, notify_wa = 0) {
    fetch(`/tasks/${encodeURIComponent(taskId)}/update-status`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' },
      body: JSON.stringify({ status, notify_wa })
    })
    .then(r => r.ok ? r.json() : Promise.reject(r))
    .then(() => flash(element))
    .catch(err => console.error('Error updating status:', err));
  }

  async function openEditModal(taskId) {
    try {
      const res = await fetch(`/tasks/${encodeURIComponent(taskId)}/edit`, { headers: { 'Accept': 'application/json' } });
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const payload = await res.json();
      const task = payload.task || payload;

      const f = document.getElementById('edit_task_form');
      f.action = `/tasks/${encodeURIComponent(taskId)}`;
      document.getElementById('edit_task_id').value = task.id ?? taskId;

      document.getElementById('edit_title').value       = task.title || '';
      document.getElementById('edit_description').value = task.description || '';
      document.getElementById('edit_assignee_id').value = task.assignee_id ?? '';
      document.getElementById('edit_priority').value    = task.priority || 'medium';
      document.getElementById('edit_status').value      = task.status || 'open';
      document.getElementById('edit_due_date').value    = task.due_date ? new Date(task.due_date).toISOString().slice(0,10) : '';
      document.getElementById('edit_link').value        = task.link || '';

      const colVal = (task.color || '#3b82f6').trim();
      document.getElementById('edit_color_text').value = colVal;
      document.getElementById('edit_color_picker').value = colVal.match(/^#([0-9a-f]{6}|[0-9a-f]{3})$/i) ? colVal : '#3b82f6';

      document.getElementById('edit_task_modal').showModal();
    } catch (e) {
      console.error('Edit modal error:', e);
      alert('Tidak dapat memuat data tugas. Silakan coba lagi.');
    }
  }

  function promptMoveStatus(taskId, newStatus) {
    const itemEl = document.getElementById('task-' + taskId);
    const newCol = document.getElementById(newStatus);
    if (itemEl && newCol) newCol.appendChild(itemEl);
    promptAndUpdateStatus(taskId, newStatus, itemEl);
  }
  
  // Public API
  return {
    initSortable,
    openEditModal,
    promptMoveStatus
  };
})();

// Expose functions to global scope for onclick attributes in Blade
window.openEditModal = TaskBoard.openEditModal;
window.promptMoveStatus = TaskBoard.promptMoveStatus;

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

  // Color picker sync logic (for modals)
  const syncColorPickers = (pickerId, textId) => {
    const picker = document.getElementById(pickerId);
    const text = document.getElementById(textId);
    if(picker && text) {
        picker.addEventListener('input', () => text.value = picker.value);
    }
  };
  syncColorPickers('create_color_picker', 'create_color_text');
  syncColorPickers('edit_color_picker', 'edit_color_text');
});

Alpine.start();

