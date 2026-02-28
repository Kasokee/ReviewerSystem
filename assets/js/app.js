// ── STATE ─────────────────────────────────────────────────────────────────────
let activeId  = null;
let timerInt  = null;
const API_URL = 'api/schedules.php';

// ── UTILITIES ─────────────────────────────────────────────────────────────────
function fmt2(n) {
    return String(n).padStart(2, '0');
}

function fmt12(date) {
    let h  = date.getHours();
    let m  = date.getMinutes();
    const ap = h >= 12 ? 'PM' : 'AM';
    h = h % 12 || 12;
    return h + ':' + fmt2(m) + ' ' + ap;
}

function localDatetimeValue(date) {
    return new Date(date - date.getTimezoneOffset() * 60000)
        .toISOString()
        .slice(0, 16);
}

// ── MODAL ─────────────────────────────────────────────────────────────────────
function openModal() {
    const now = new Date();
    now.setSeconds(0, 0);
    document.getElementById('f-start').value = localDatetimeValue(now);
    document.getElementById('f-end').value   = localDatetimeValue(new Date(now.getTime() + 3600000));
    document.getElementById('overlay').classList.add('open');
}

function closeModal() {
    document.getElementById('overlay').classList.remove('open');
    document.getElementById('add-form').reset();
}

document.getElementById('overlay').addEventListener('click', e => {
    if (e.target === e.currentTarget) closeModal();
});

// ── ADD SCHEDULE ──────────────────────────────────────────────────────────────
document.getElementById('add-form').addEventListener('submit', async function (e) {
    e.preventDefault();
    const btn = document.getElementById('btn-submit');
    btn.textContent = 'Adding…';
    btn.disabled = true;

    const fd = new FormData(this);
    fd.append('action', 'add_schedule');

    try {
        const res  = await fetch(API_URL, { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            closeModal();
            location.reload();
        } else {
            alert(data.error || 'Failed to add schedule.');
        }
    } catch (err) {
        alert('Network error: ' + err.message);
    }

    btn.textContent = 'Add Schedule';
    btn.disabled = false;
});

// ── SELECT CARD ───────────────────────────────────────────────────────────────
function selectCard(card) {
    document.querySelectorAll('.card').forEach(c => c.classList.remove('active'));
    card.classList.add('active');

    activeId = +card.dataset.id;
    const { pdf, notes, end: endStr, subject } = card.dataset;

    loadPdf(pdf, subject);
    loadNotes(notes, subject);
    startTimer(subject, endStr);
}

// ── PDF ───────────────────────────────────────────────────────────────────────
function loadPdf(pdfPath, subject) {
    const frame = document.getElementById('pdf-frame');
    const empty = document.getElementById('pdf-empty');

    document.getElementById('pdf-sub').textContent = subject;

    if (pdfPath) {
        frame.src             = pdfPath;
        frame.style.display   = 'block';
        empty.style.display   = 'none';
    } else {
        frame.src             = '';
        frame.style.display   = 'none';
        empty.style.display   = 'flex';
        document.getElementById('pdf-empty-msg').textContent = 'No PDF uploaded for this subject.';
    }
}

// ── NOTES ─────────────────────────────────────────────────────────────────────
function loadNotes(notes, subject) {
    const ta = document.getElementById('notes-ta');
    ta.value    = notes;
    ta.disabled = false;
    document.getElementById('note-sub').textContent  = subject;
    document.getElementById('btn-save').disabled     = false;
    document.getElementById('save-msg').textContent  = '';
}

async function saveNotes() {
    if (!activeId) return;

    const btn = document.getElementById('btn-save');
    const msg = document.getElementById('save-msg');
    btn.disabled    = true;
    btn.textContent = 'Saving…';
    msg.textContent = '';

    const fd = new FormData();
    fd.append('action', 'save_notes');
    fd.append('id',     activeId);
    fd.append('notes',  document.getElementById('notes-ta').value);

    try {
        const res  = await fetch(API_URL, { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            msg.textContent = 'Saved ✓';
            // Sync notes back into card dataset so it persists on re-select
            const card = document.querySelector(`.card[data-id="${activeId}"]`);
            if (card) card.dataset.notes = document.getElementById('notes-ta').value;
            setTimeout(() => {
                if (msg.textContent === 'Saved ✓') msg.textContent = '';
            }, 3000);
        } else {
            msg.textContent = 'Save failed.';
        }
    } catch {
        msg.textContent = 'Error saving.';
    }

    btn.disabled    = false;
    btn.textContent = 'Save Notes';
}

// ── TIMER ─────────────────────────────────────────────────────────────────────
function startTimer(subject, endStr) {
    if (timerInt) clearInterval(timerInt);
    const endMs = new Date(endStr).getTime();

    document.getElementById('t-subject').textContent = subject;
    document.getElementById('t-end').textContent     = fmt12(new Date(endStr));
    document.getElementById('timer-wrap').classList.remove('timer-idle');

    function tick() {
        const diff = endMs - Date.now();

        if (diff <= 0) {
            clearInterval(timerInt);
            document.getElementById('t-time').textContent   = '00:00:00';
            document.getElementById('t-status').textContent = 'Session Finished';
            document.getElementById('t-status').className   = 't-status s-finished';
            return;
        }

        const h = Math.floor(diff / 3600000);
        const m = Math.floor((diff % 3600000) / 60000);
        const s = Math.floor((diff % 60000) / 1000);

        document.getElementById('t-time').textContent   = fmt2(h) + ':' + fmt2(m) + ':' + fmt2(s);
        document.getElementById('t-status').textContent = 'Ongoing';
        document.getElementById('t-status').className   = 't-status s-ongoing';
    }

    tick();
    timerInt = setInterval(tick, 1000);
}

// ── DELETE SCHEDULE ───────────────────────────────────────────────────────────
async function delSchedule(e, id) {
    e.stopPropagation();
    if (!confirm('Delete this schedule?')) return;

    const fd = new FormData();
    fd.append('action', 'delete_schedule');
    fd.append('id', id);

    try {
        const res  = await fetch(API_URL, { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            document.querySelector(`.card[data-id="${id}"]`)?.remove();
            if (activeId === id) resetDashboard();
        } else {
            alert('Failed to delete schedule.');
        }
    } catch (err) {
        alert('Network error: ' + err.message);
    }
}

// ── RESET DASHBOARD ───────────────────────────────────────────────────────────
function resetDashboard() {
    activeId = null;
    clearInterval(timerInt);

    // Timer
    document.getElementById('t-time').textContent    = '--:--:--';
    document.getElementById('t-status').textContent  = 'No subject selected';
    document.getElementById('t-status').className    = 't-status s-idle';
    document.getElementById('t-subject').textContent = '—';
    document.getElementById('t-end').textContent     = '—';
    document.getElementById('timer-wrap').classList.add('timer-idle');

    // Notes
    document.getElementById('notes-ta').value        = '';
    document.getElementById('notes-ta').disabled     = true;
    document.getElementById('btn-save').disabled     = true;
    document.getElementById('note-sub').textContent  = '—';

    // PDF
    document.getElementById('pdf-frame').src         = '';
    document.getElementById('pdf-frame').style.display  = 'none';
    document.getElementById('pdf-empty').style.display  = 'flex';
    document.getElementById('pdf-empty-msg').textContent = 'Select a subject to view its PDF';
    document.getElementById('pdf-sub').textContent   = '—';
}

// ── KEYBOARD SHORTCUT: Ctrl+S ─────────────────────────────────────────────────
document.addEventListener('keydown', e => {
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        if (activeId) saveNotes();
    }
});
