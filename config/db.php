<?php
require_once __DIR__ . '/config.php';

// ─── DATABASE CONNECTION ──────────────────────────────────────────────────────
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$conn) {
    http_response_code(500);
    die(json_encode([
        'success' => false,
        'error'   => 'Database connection failed: ' . mysqli_connect_error()
    ]));
}

mysqli_set_charset($conn, 'utf8mb4');
