<?php
// ─── BOOTSTRAP ────────────────────────────────────────────────────────────────
require_once 'config/config.php';
require_once 'config/db.php';
require_once 'includes/todo_helper.php';

// ─── DATA ─────────────────────────────────────────────────────────────────────
$todos  = get_todos($conn, 'all');
$counts = get_todo_counts($conn);
$pct    = $counts['total'] > 0 ? round(($counts['done'] / $counts['total']) * 100) : 0;

// ─── VIEW ─────────────────────────────────────────────────────────────────────
require_once 'includes/header.php';
?>

<div class="todo-app">

    <!-- ── HEADER ─────────────────────────────────────────────────────────── -->
    <div class="todo-header">
        <a href="index.php" class="todo-back">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                <path d="M10 3L5 8l5 5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Back to Dashboard
        </a>

        <div class="todo-title-block">
            <h1>To-Do List</h1>
            <p>Stay on track, one task at a time</p>
        </div>

        <div class="todo-stats">
            <div>
                <span class="todo-stat-val" id="stat-active"><?= $counts['active'] ?></span>
                Pending
            </div>
            <div>
                <span class="todo-stat-val" id="stat-done"><?= $counts['done'] ?></span>
                Done
            </div>
            <div>
                <span class="todo-stat-val" id="stat-total"><?= $counts['total'] ?></span>
                Total
            </div>
        </div>
    </div>

    <!-- ── PROGRESS BAR ───────────────────────────────────────────────────── -->
    <div class="progress-wrap">
        <div class="progress-label">
            <span id="progress-lbl">
                <?= $counts['total'] === 0
                    ? 'No tasks yet'
                    : "{$counts['done']} of {$counts['total']} completed" ?>
            </span>
            <span id="progress-pct"><?= $pct ?>%</span>
        </div>
        <div class="progress-bar">
            <div class="progress-fill" id="progress-fill" style="width: <?= $pct ?>%"></div>
        </div>
    </div>

    <!-- ── FILTER TABS ────────────────────────────────────────────────────── -->
    <div class="todo-filters">
        <button class="filter-btn active" data-filter="all"    onclick="setFilter('all')">All Tasks</button>
        <button class="filter-btn"        data-filter="active" onclick="setFilter('active')">Pending</button>
        <button class="filter-btn"        data-filter="done"   onclick="setFilter('done')">Completed</button>
    </div>

    <!-- ── ADD TASK INPUT ─────────────────────────────────────────────────── -->
    <div class="todo-input-wrap">
        <input type="text"
               id="task-input"
               placeholder="What do you need to do? Press Enter to add…"
               autocomplete="off">
        <button class="btn-add-task" id="btn-add-task" onclick="addTask()">+ Add Task</button>
    </div>

    <!-- ── BULK ACTIONS ───────────────────────────────────────────────────── -->
    <?php if ($counts['done'] > 0 || $counts['active'] > 0): ?>
    <div class="bulk-bar">
        <span><?= $counts['active'] ?> task<?= $counts['active'] !== 1 ? 's' : '' ?> remaining</span>
        <div class="bulk-actions">
            <?php if ($counts['active'] > 0): ?>
            <button class="bulk-btn" onclick="markAllDone()">✓ Mark all done</button>
            <?php endif; ?>
            <?php if ($counts['done'] > 0): ?>
            <button class="bulk-btn danger" onclick="clearDone()">✕ Clear completed</button>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── TASK LIST ──────────────────────────────────────────────────────── -->
    <div class="todo-list" id="todo-list">
        <?php if (empty($todos)): ?>
            <div class="todo-empty">
                <svg class="todo-empty-icon" width="56" height="56" viewBox="0 0 56 56" fill="none">
                    <circle cx="28" cy="28" r="26" stroke="#1A1814" stroke-width="2"/>
                    <path d="M18 28l7 7 13-13" stroke="#1A1814" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <h3>Nothing here yet</h3>
                <p>Add your first task above to get started.</p>
            </div>
        <?php else: foreach ($todos as $t):
            $doneClass = $t['is_done'] ? 'done' : '';
        ?>
            <div class="task-item <?= $doneClass ?>"
                 data-id="<?= $t['id'] ?>"
                 id="task-<?= $t['id'] ?>">

                <!-- Checkbox -->
                <div class="task-check"
                     onclick="toggleDone(<?= $t['id'] ?>)"
                     title="<?= $t['is_done'] ? 'Mark as pending' : 'Mark as done' ?>">
                    <svg class="task-check-icon" width="11" height="9" viewBox="0 0 11 9" fill="none">
                        <path d="M1 4.5l3 3 6-6" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>

                <!-- Task text -->
                <div class="task-body">
                    <div class="task-text"><?= htmlspecialchars($t['task']) ?></div>
                    <div class="task-status-badge <?= $t['is_done'] ? 'badge-done' : 'badge-pending' ?>">
                        <?= $t['is_done'] ? 'Completed' : 'Pending' ?>
                    </div>
                </div>

                <!-- Delete button -->
                <div class="task-actions">
                    <button class="task-btn del"
                            onclick="deleteTask(<?= $t['id'] ?>)"
                            title="Delete task">
                        <svg width="13" height="13" viewBox="0 0 13 13" fill="none">
                            <path d="M2 2l9 9M11 2l-9 9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        </svg>
                    </button>
                </div>

            </div>
        <?php endforeach; endif; ?>
    </div>

</div><!-- /todo-app -->

<script>
window.__TASKS__ = <?= json_encode(array_map(function($t) {
    return [
        'id'      => (int)$t['id'],
        'task'    => $t['task'],
        'is_done' => (int)$t['is_done'],
    ];
}, $todos)) ?>;
</script>
<script src="assets/js/todo.js"></script>

</body>
</html>