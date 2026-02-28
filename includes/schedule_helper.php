<?php
// ─── SCHEDULE HELPER FUNCTIONS ────────────────────────────────────────────────

/**
 * Fetch all schedules ordered by start time.
 */
function get_all_schedules($conn): array {
    $schedules = [];
    $result    = mysqli_query($conn, "SELECT * FROM schedules ORDER BY start_time ASC");
    while ($row = mysqli_fetch_assoc($result)) {
        $schedules[] = $row;
    }
    return $schedules;
}

/**
 * Format duration in minutes to human-readable string (e.g. "1h 30m").
 */
function format_duration(int $mins): string {
    if ($mins <= 0) return '0m';
    if ($mins < 60) return $mins . 'm';
    $h = floor($mins / 60);
    $m = $mins % 60;
    return $h . 'h' . ($m ? ' ' . $m . 'm' : '');
}

/**
 * Upload a PDF file and return its relative path, or empty string if no file.
 * Returns array: ['path' => string, 'error' => string|null]
 */
function handle_pdf_upload(array $file): array {
    if (empty($file['name']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['path' => '', 'error' => null];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['path' => '', 'error' => 'File upload error (code ' . $file['error'] . ').'];
    }

    if ($file['size'] > MAX_FILE_SIZE) {
        return ['path' => '', 'error' => 'File exceeds maximum size of 10MB.'];
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_EXT)) {
        return ['path' => '', 'error' => 'Only PDF files are allowed.'];
    }

    $fname = uniqid('pdf_') . '.pdf';
    $dest  = UPLOAD_DIR . $fname;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return ['path' => '', 'error' => 'Failed to save uploaded file.'];
    }

    return ['path' => UPLOAD_URL . $fname, 'error' => null];
}

/**
 * Delete a PDF file from disk if it exists.
 */
function delete_pdf_file(string $relative_path): void {
    if (empty($relative_path)) return;
    $full = dirname(__DIR__) . '/' . $relative_path;
    if (file_exists($full)) {
        unlink($full);
    }
}
