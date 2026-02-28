<?php
// ─── TO-DO HELPER FUNCTIONS ───────────────────────────────────────────────────

/**
 * Fetch all todo items, with optional filter.
 * $filter: 'all' | 'active' | 'done'
 */
function get_todos($conn, string $filter = 'all'): array {
    $where = '';
    if ($filter === 'active') $where = 'WHERE is_done = 0';
    if ($filter === 'done')   $where = 'WHERE is_done = 1';

    $result = mysqli_query($conn,
        "SELECT * FROM todo_items $where ORDER BY is_done ASC, sort_order DESC, created_at DESC"
    );
    $items = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $items[] = $row;
    }
    return $items;
}

/**
 * Get todo summary counts.
 * Returns ['total' => int, 'done' => int, 'active' => int]
 */
function get_todo_counts($conn): array {
    $row = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT
            COUNT(*) as total,
            SUM(is_done = 1) as done,
            SUM(is_done = 0) as active
         FROM todo_items"
    ));
    return [
        'total'  => (int)($row['total']  ?? 0),
        'done'   => (int)($row['done']   ?? 0),
        'active' => (int)($row['active'] ?? 0),
    ];
}

/**
 * Format a due date string for display.
 * Returns ['label' => string, 'overdue' => bool]
 */
function format_due_date(?string $date): array {
    if (empty($date)) return ['label' => '', 'overdue' => false];

    $ts    = strtotime($date);
    $today = strtotime(date('Y-m-d'));
    $diff  = $ts - $today;
    $days  = (int)round($diff / 86400);

    $overdue = $days < 0;

    if ($days === 0)      $label = 'Due today';
    elseif ($days === 1)  $label = 'Due tomorrow';
    elseif ($days === -1) $label = 'Due yesterday';
    elseif ($days > 1)    $label = 'Due in ' . $days . ' days';
    else                  $label = abs($days) . ' days overdue';

    return ['label' => $label, 'overdue' => $overdue];
}