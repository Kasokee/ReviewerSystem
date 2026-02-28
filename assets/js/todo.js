// ── CONFIG ────────────────────────────────────────────────────────────────────
const API = 'api/todos.php';

// ── STATE ─────────────────────────────────────────────────────────────────────
let currentFilter = 'all';
let tasks = [];

// ── INIT ──────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    tasks = window.__TASKS__ || [];
    renderList();
    updateStats();
    updateProgress();

    document.getElementById('task-input').focus();

    document.getElementById('task-input').addEventListener('keydown', e => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            addTask();
        }
    });
});

// ── RENDER LIST ───────────────────────────────────────────────────────────────
function renderList() {
    const list = document.getElementById('todo-list');

    const filtered = tasks.filter(t => {
        if (currentFilter === 'active') return !t.is_done;
        if (currentFilter === 'done')   return  t.is_done;
        return true;
    });

    if (filtered.length === 0) {
        const msgs = {
            all:    ['Nothing here yet',    'Add your first task above to get started.'],
            active: ['All caught up!',       'No pending tasks — great work.'],
            done:   ['No completed tasks',   'Finish some tasks and they\'ll appear here.'],
        };
        const [h, p] = msgs[currentFilter];
        list.innerHTML = `
            <div class="todo-empty">
                <svg class="todo-empty-icon" width="56" height="56" viewBox="0 0 56 56" fill="none">
                    <circle cx="28" cy="28" r="26" stroke="#1A1814" stroke-width="2"/>
                    <path d="M18 28l7 7 13-13" stroke="#1A1814" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <h3>${h}</h3>
                <p>${p}</p>
            </div>`;
        return;
    }

    list.innerHTML = filtered.map(t => renderTask(t)).join('');
}

// ── RENDER SINGLE TASK ────────────────────────────────────────────────────────
function renderTask(t) {
    const doneClass  = t.is_done ? 'done' : '';
    const badgeClass = t.is_done ? 'badge-done' : 'badge-pending';
    const badgeText  = t.is_done ? 'Completed' : 'Pending';
    const checkTitle = t.is_done ? 'Mark as pending' : 'Mark as done';

    return `
    <div class="task-item ${doneClass}" data-id="${t.id}" id="task-${t.id}">
        <div class="task-check" onclick="toggleDone(${t.id})" title="${checkTitle}">
            <svg class="task-check-icon" width="11" height="9" viewBox="0 0 11 9" fill="none">
                <path d="M1 4.5l3 3 6-6" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <div class="task-body">
            <div class="task-text">${esc(t.task)}</div>
            <div class="task-status-badge ${badgeClass}">${badgeText}</div>
        </div>
        <div class="task-actions">
            <button class="task-btn del" onclick="deleteTask(${t.id})" title="Delete task">
                <svg width="13" height="13" viewBox="0 0 13 13" fill="none">
                    <path d="M2 2l9 9M11 2l-9 9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                </svg>
            </button>
        </div>
    </div>`;
}

// ── ADD TASK ──────────────────────────────────────────────────────────────────
async function addTask() {
    const input = document.getElementById('task-input');
    const task  = input.value.trim();

    if (!task) {
        input.focus();
        input.style.borderColor = '#E05C3A';
        setTimeout(() => input.style.borderColor = '', 800);
        return;
    }

    const btn = document.getElementById('btn-add-task');
    btn.textContent = 'Adding…';
    btn.disabled    = true;

    const fd = new FormData();
    fd.append('action', 'add_task');
    fd.append('task',   task);

    try {
        const res  = await fetch(API, { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            // New task is always pending (is_done = 0)
            tasks.unshift({ id: data.task.id, task: data.task.task, is_done: 0 });
            input.value = '';
            renderList();
            updateStats();
            updateProgress();
            updateBulkBar();
        } else {
            alert(data.error || 'Failed to add task.');
        }
    } catch (err) { alert('Error: ' + err.message); }

    btn.textContent = '+ Add Task';
    btn.disabled    = false;
    input.focus();
}

// ── TOGGLE DONE ───────────────────────────────────────────────────────────────
async function toggleDone(id) {
    const el = document.getElementById('task-' + id);
    if (el) el.style.opacity = '.5';

    const fd = new FormData();
    fd.append('action', 'toggle_done');
    fd.append('id', id);

    try {
        const res  = await fetch(API, { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            const t = tasks.find(t => t.id == id);
            if (t) t.is_done = data.is_done ? 1 : 0;
            renderList();
            updateStats();
            updateProgress();
            updateBulkBar();
        } else {
            if (el) el.style.opacity = '1';
        }
    } catch { if (el) el.style.opacity = '1'; }
}

// ── DELETE TASK ───────────────────────────────────────────────────────────────
async function deleteTask(id) {
    const el = document.getElementById('task-' + id);
    if (el) { el.style.opacity = '.3'; el.style.transform = 'translateX(16px)'; }

    const fd = new FormData();
    fd.append('action', 'delete_task');
    fd.append('id', id);

    try {
        const res  = await fetch(API, { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            tasks = tasks.filter(t => t.id != id);
            renderList();
            updateStats();
            updateProgress();
            updateBulkBar();
        } else {
            if (el) { el.style.opacity = '1'; el.style.transform = ''; }
            alert('Failed to delete.');
        }
    } catch { if (el) { el.style.opacity = '1'; el.style.transform = ''; } }
}

// ── BULK ACTIONS ──────────────────────────────────────────────────────────────
async function markAllDone() {
    if (!tasks.some(t => !t.is_done)) return;

    const fd = new FormData();
    fd.append('action', 'mark_all_done');
    const res  = await fetch(API, { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) {
        tasks.forEach(t => t.is_done = 1);
        renderList();
        updateStats();
        updateProgress();
        updateBulkBar();
    }
}

async function clearDone() {
    const doneTasks = tasks.filter(t => t.is_done);
    if (!doneTasks.length) return;
    if (!confirm(`Remove ${doneTasks.length} completed task${doneTasks.length > 1 ? 's' : ''}?`)) return;

    const fd = new FormData();
    fd.append('action', 'clear_done');
    const res  = await fetch(API, { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) {
        tasks = tasks.filter(t => !t.is_done);
        renderList();
        updateStats();
        updateProgress();
        updateBulkBar();
    }
}

// ── FILTER ────────────────────────────────────────────────────────────────────
function setFilter(filter) {
    currentFilter = filter;
    document.querySelectorAll('.filter-btn').forEach(b => {
        b.classList.toggle('active', b.dataset.filter === filter);
    });
    renderList();
}

// ── STATS & PROGRESS ──────────────────────────────────────────────────────────
function updateStats() {
    const total  = tasks.length;
    const done   = tasks.filter(t => t.is_done).length;
    const active = total - done;
    document.getElementById('stat-total').textContent  = total;
    document.getElementById('stat-done').textContent   = done;
    document.getElementById('stat-active').textContent = active;
}

function updateProgress() {
    const total = tasks.length;
    const done  = tasks.filter(t => t.is_done).length;
    const pct   = total > 0 ? Math.round((done / total) * 100) : 0;
    document.getElementById('progress-fill').style.width = pct + '%';
    document.getElementById('progress-pct').textContent  = pct + '%';
    document.getElementById('progress-lbl').textContent  =
        total === 0 ? 'No tasks yet' : `${done} of ${total} completed`;
}

// Dynamically update the bulk action bar without full page reload
function updateBulkBar() {
    const active = tasks.filter(t => !t.is_done).length;
    const done   = tasks.filter(t =>  t.is_done).length;
    let bar      = document.querySelector('.bulk-bar');

    if (tasks.length === 0) {
        if (bar) bar.remove();
        return;
    }

    const html = `
        <span>${active} task${active !== 1 ? 's' : ''} remaining</span>
        <div class="bulk-actions">
            ${active > 0 ? `<button class="bulk-btn" onclick="markAllDone()">✓ Mark all done</button>` : ''}
            ${done  > 0 ? `<button class="bulk-btn danger" onclick="clearDone()">✕ Clear completed</button>` : ''}
        </div>`;

    if (bar) {
        bar.innerHTML = html;
    } else {
        bar = document.createElement('div');
        bar.className   = 'bulk-bar';
        bar.innerHTML   = html;
        const list = document.getElementById('todo-list');
        list.parentNode.insertBefore(bar, list);
    }
}

// ── UTILS ─────────────────────────────────────────────────────────────────────
function esc(str) {
    return String(str)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}