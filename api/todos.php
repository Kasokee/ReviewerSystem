<?php
// ─── API: TO-DO ENDPOINTS ─────────────────────────────────────────────────────
// Handles all AJAX POST requests for the To-Do feature.
// All responses are JSON.

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['action'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid request.']);
    exit;
}

$action = $_POST['action'];

// ── ADD TASK ──────────────────────────────────────────────────────────────────
if ($action === 'add_task') {
    $task     = trim($_POST['task'] ?? '');
    $priority = $_POST['priority']  ?? 'medium';
    $category = trim($_POST['category'] ?? '');
    $due_date = $_POST['due_date']  ?? '';

    if (empty($task)) {
        echo json_encode(['success' => false, 'error' => 'Task cannot be empty.']);
        exit;
    }

    // Validate priority
    $valid_priorities = ['high', 'medium', 'low'];
    if (!in_array($priority, $valid_priorities)) $priority = 'medium';

    $task_esc     = mysqli_real_escape_string($conn, $task);
    $priority_esc = mysqli_real_escape_string($conn, $priority);
    $category_esc = mysqli_real_escape_string($conn, $category);
    $due_esc      = !empty($due_date) ? "'" . mysqli_real_escape_string($conn, $due_date) . "'" : 'NULL';

    // Get current max sort_order
    $max = mysqli_fetch_assoc(mysqli_query($conn, "SELECT MAX(sort_order) as m FROM todo_items"));
    $sort = (int)($max['m'] ?? 0) + 1;

    $ok = mysqli_query($conn,
        "INSERT INTO todo_items (task, priority, category, due_date, sort_order)
         VALUES ('$task_esc', '$priority_esc', '$category_esc', $due_esc, $sort)"
    );

    if (!$ok) {
        echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
        exit;
    }

    $id = mysqli_insert_id($conn);

    // Return the full new task row for JS to render immediately
    $row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM todo_items WHERE id=$id"));
    echo json_encode(['success' => true, 'task' => $row]);
    exit;
}

// ── TOGGLE DONE ───────────────────────────────────────────────────────────────
if ($action === 'toggle_done') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid ID.']);
        exit;
    }
    $ok = mysqli_query($conn, "UPDATE todo_items SET is_done = NOT is_done WHERE id=$id");
    $row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT is_done FROM todo_items WHERE id=$id"));
    echo json_encode(['success' => (bool)$ok, 'is_done' => (bool)$row['is_done']]);
    exit;
}

// ── DELETE TASK ───────────────────────────────────────────────────────────────
if ($action === 'delete_task') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid ID.']);
        exit;
    }
    $ok = mysqli_query($conn, "DELETE FROM todo_items WHERE id=$id");
    echo json_encode(['success' => (bool)$ok]);
    exit;
}

// ── CLEAR DONE ────────────────────────────────────────────────────────────────
if ($action === 'clear_done') {
    $ok = mysqli_query($conn, "DELETE FROM todo_items WHERE is_done=1");
    echo json_encode(['success' => (bool)$ok, 'deleted' => mysqli_affected_rows($conn)]);
    exit;
}

// ── MARK ALL DONE ─────────────────────────────────────────────────────────────
if ($action === 'mark_all_done') {
    $ok = mysqli_query($conn, "UPDATE todo_items SET is_done=1 WHERE is_done=0");
    echo json_encode(['success' => (bool)$ok]);
    exit;
}

// ── UPDATE PRIORITY ───────────────────────────────────────────────────────────
if ($action === 'update_priority') {
    $id       = (int)($_POST['id'] ?? 0);
    $priority = $_POST['priority'] ?? 'medium';
    $valid    = ['high', 'medium', 'low'];
    if ($id <= 0 || !in_array($priority, $valid)) {
        echo json_encode(['success' => false, 'error' => 'Invalid input.']);
        exit;
    }
    $p_esc = mysqli_real_escape_string($conn, $priority);
    $ok    = mysqli_query($conn, "UPDATE todo_items SET priority='$p_esc' WHERE id=$id");
    echo json_encode(['success' => (bool)$ok]);
    exit;
}

// ── UNKNOWN ───────────────────────────────────────────────────────────────────
http_response_code(400);
echo json_encode(['success' => false, 'error' => 'Unknown action.']);