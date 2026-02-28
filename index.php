<?php
// ─── BOOTSTRAP ────────────────────────────────────────────────────────────────
require_once 'config/config.php';
require_once 'config/db.php';
require_once 'includes/schedule_helper.php';

// ─── DATA ─────────────────────────────────────────────────────────────────────
$schedules = get_all_schedules($conn);

// ─── VIEW ─────────────────────────────────────────────────────────────────────
require_once 'includes/header.php';
?>

<div class="app">

    <!-- ── HEADER ─────────────────────────────────────────────────────────── -->
    <header class="header">
        <div class="logo">
            <h1><?= APP_NAME ?></h1>
            <em><?= APP_TAGLINE ?></em>
        </div>
        <div class="header-actions">
            <a href="todo.php" class="btn-todo">
                <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                    <rect x="1" y="2" width="9" height="1.5" rx=".75" fill="currentColor"/>
                    <rect x="1" y="6" width="9" height="1.5" rx=".75" fill="currentColor"/>
                    <rect x="1" y="10" width="6" height="1.5" rx=".75" fill="currentColor"/>
                    <path d="M11.5 7.5l1.2 1.3L15 6" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                To-Do List
            </a>
            <button class="btn-add" onclick="openModal()">
                <svg width="13" height="13" viewBox="0 0 13 13" fill="none">
                    <path d="M6.5 1v11M1 6.5h11" stroke="#fff" stroke-width="2" stroke-linecap="round"/>
                </svg>
                Add Schedule
            </button>
        </div>
    </header>

    <!-- ── SCHEDULE BAR (TOP) ─────────────────────────────────────────────── -->
    <section class="bar">
        <div class="bar-label">Today's Subjects</div>
        <div class="cards" id="cards">

            <?php if (empty($schedules)): ?>
                <span class="bar-empty">No schedules yet — click Add Schedule to get started.</span>

            <?php else: foreach ($schedules as $s):
                $end_ts   = strtotime($s['end_time']);
                $start_ts = strtotime($s['start_time']);
                $mins     = max(0, round(($end_ts - $start_ts) / 60));
                $dur      = format_duration($mins);
            ?>
                <div class="card"
                     data-id="<?= $s['id'] ?>"
                     data-pdf="<?= htmlspecialchars($s['pdf_file']) ?>"
                     data-notes="<?= htmlspecialchars($s['notes']) ?>"
                     data-end="<?= htmlspecialchars($s['end_time']) ?>"
                     data-subject="<?= htmlspecialchars($s['subject_name']) ?>"
                     onclick="selectCard(this)">
                    <button class="card-del"
                            onclick="delSchedule(event, <?= $s['id'] ?>)"
                            title="Delete">×</button>
                    <span class="card-name"><?= htmlspecialchars($s['subject_name']) ?></span>
                    <span class="card-meta">
                        <?= $dur ?> &middot; ends <?= date('g:i A', $end_ts) ?>
                    </span>
                </div>
            <?php endforeach; endif; ?>

        </div>
    </section>

    <!-- ── MIDDLE: PDF VIEWER + NOTEPAD ───────────────────────────────────── -->
    <div class="middle">

        <!-- PDF VIEWER (LEFT) -->
        <div class="pdf-panel">
            <div class="panel-head">
                <span class="panel-tag">PDF Viewer</span>
                <span class="panel-sub" id="pdf-sub">—</span>
            </div>
            <div class="pdf-wrap">
                <iframe id="pdf-frame"></iframe>
                <div class="pdf-empty" id="pdf-empty">
                    <svg class="pdf-empty-icon" width="48" height="58" viewBox="0 0 48 58" fill="none">
                        <rect x="3" y="3" width="42" height="52" rx="5" stroke="#1A1814" stroke-width="2.5"/>
                        <path d="M13 19h22M13 27h22M13 35h14" stroke="#1A1814" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    <p id="pdf-empty-msg">Select a subject to view its PDF</p>
                </div>
            </div>
        </div>

        <!-- NOTEPAD (RIGHT) -->
        <div class="note-panel">
            <div class="panel-head">
                <span class="panel-tag">Notes</span>
                <span class="panel-sub" id="note-sub">—</span>
            </div>
            <div class="note-body">
                <textarea id="notes-ta"
                          placeholder="Select a subject to start writing notes…"
                          disabled></textarea>
                <div class="note-foot">
                    <span class="save-msg" id="save-msg"></span>
                    <button class="btn-save" id="btn-save" disabled onclick="saveNotes()">
                        Save Notes
                    </button>
                </div>
            </div>
        </div>

    </div>

    <!-- ── TIMER (BOTTOM) ─────────────────────────────────────────────────── -->
    <div class="timer-wrap timer-idle" id="timer-wrap">
        <div class="timer-left">
            <span class="t-eyebrow">Currently Studying</span>
            <span class="t-subject" id="t-subject">—</span>
        </div>
        <div class="timer-center">
            <div class="t-time" id="t-time">--:--:--</div>
            <div class="t-status s-idle" id="t-status">No subject selected</div>
        </div>
        <div class="timer-right">
            <div class="t-end-label">Ends at</div>
            <div class="t-end-val" id="t-end">—</div>
        </div>
    </div>

</div><!-- /app -->

<!-- ── ADD SCHEDULE MODAL ──────────────────────────────────────────────────── -->
<div class="overlay" id="overlay">
    <div class="modal">
        <h2>New Schedule</h2>
        <form id="add-form" enctype="multipart/form-data">

            <div class="fg">
                <label for="f-name">Subject Name</label>
                <input type="text" id="f-name" name="subject_name"
                       placeholder="e.g. Mathematics" required>
            </div>

            <div class="fg">
                <label for="f-start">Start Time</label>
                <input type="datetime-local" id="f-start" name="start_time" required>
            </div>

            <div class="fg">
                <label for="f-end">End Time</label>
                <input type="datetime-local" id="f-end" name="end_time" required>
            </div>

            <div class="fg">
                <label for="f-pdf">
                    PDF File
                    <span class="field-hint">(optional, max 10MB)</span>
                </label>
                <input type="file" id="f-pdf" name="pdf_file" accept=".pdf">
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn-submit" id="btn-submit">Add Schedule</button>
            </div>

        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>