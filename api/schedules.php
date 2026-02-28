<?php
// ─── API: SCHEDULE ENDPOINTS ──────────────────────────────────────────────────
// Handles AJAX POST requests from the frontend.
// All responses are JSON.

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/includes/schedule_helper.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['action'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid request.']);
    exit;
}

$action = $_POST['action'];

// ── SAVE NOTES ────────────────────────────────────────────────────────────────
if ($action === 'save_notes') {
    $id    = (int)($_POST['id'] ?? 0);
    $notes = mysqli_real_escape_string($conn, $_POST['notes'] ?? '');

    if ($id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid schedule ID.']);
        exit;
    }

    $ok = mysqli_query($conn, "UPDATE schedules SET notes='$notes' WHERE id=$id");
    echo json_encode(['success' => (bool)$ok]);
    exit;
}

// ── ADD SCHEDULE ──────────────────────────────────────────────────────────────
if ($action === 'add_schedule') {
    $subject = trim($_POST['subject_name'] ?? '');
    $start   = $_POST['start_time']   ?? '';
    $end     = $_POST['end_time']     ?? '';

    if (empty($subject) || empty($start) || empty($end)) {
        echo json_encode(['success' => false, 'error' => 'All fields are required.']);
        exit;
    }

    // Handle optional PDF upload
    $upload = handle_pdf_upload($_FILES['pdf_file'] ?? []);
    if ($upload['error']) {
        echo json_encode(['success' => false, 'error' => $upload['error']]);
        exit;
    }

    $subject_esc  = mysqli_real_escape_string($conn, $subject);
    $start_esc    = mysqli_real_escape_string($conn, $start);
    $end_esc      = mysqli_real_escape_string($conn, $end);
    $pdf_esc      = mysqli_real_escape_string($conn, $upload['path']);

    $ok = mysqli_query($conn,
        "INSERT INTO schedules (subject_name, start_time, end_time, pdf_file)
         VALUES ('$subject_esc', '$start_esc', '$end_esc', '$pdf_esc')"
    );

    if (!$ok) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . mysqli_error($conn)]);
        exit;
    }

    echo json_encode(['success' => true, 'id' => mysqli_insert_id($conn)]);
    exit;
}

// ── DELETE SCHEDULE ───────────────────────────────────────────────────────────
if ($action === 'delete_schedule') {
    $id = (int)($_POST['id'] ?? 0);

    if ($id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid schedule ID.']);
        exit;
    }

    // Get PDF path before deleting row
    $row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT pdf_file FROM schedules WHERE id=$id"));
    if ($row) {
        delete_pdf_file($row['pdf_file']);
    }

    $ok = mysqli_query($conn, "DELETE FROM schedules WHERE id=$id");
    echo json_encode(['success' => (bool)$ok]);
    exit;
}

// ── UNKNOWN ACTION ────────────────────────────────────────────────────────────
http_response_code(400);
echo json_encode(['success' => false, 'error' => 'Unknown action: ' . htmlspecialchars($action)]);
