<?php

function ensureNotificationsTable(mysqli $conn): void {
    static $checked = false;

    if ($checked) {
        return;
    }

    $sql = "CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(150) NOT NULL,
        message TEXT NOT NULL,
        type ENUM('info','success','warning','error') DEFAULT 'info',
        related_url VARCHAR(255) DEFAULT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_notifications_user_read (user_id, is_read, created_at),
        CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

    mysqli_query($conn, $sql);
    $checked = true;
}

function createNotification(mysqli $conn, int $userId, string $title, string $message, string $type = 'info', ?string $relatedUrl = null): bool {
    ensureNotificationsTable($conn);

    $safeType = in_array($type, ['info', 'success', 'warning', 'error'], true) ? $type : 'info';
    $stmt = mysqli_prepare($conn, 'INSERT INTO notifications (user_id, title, message, type, related_url) VALUES (?, ?, ?, ?, ?)');

    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, 'issss', $userId, $title, $message, $safeType, $relatedUrl);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return $ok;
}

function notifyAllAdmins(mysqli $conn, string $title, string $message, string $type = 'info', ?string $relatedUrl = null): void {
    $result = mysqli_query($conn, "SELECT id FROM users WHERE role = 'Admin'");

    if (!$result) {
        return;
    }

    while ($row = mysqli_fetch_assoc($result)) {
        createNotification($conn, (int)$row['id'], $title, $message, $type, $relatedUrl);
    }
}

function getUnreadNotificationCount(mysqli $conn, int $userId): int {
    ensureNotificationsTable($conn);

    $stmt = mysqli_prepare($conn, 'SELECT COUNT(*) AS total FROM notifications WHERE user_id = ? AND is_read = 0');
    if (!$stmt) {
        return 0;
    }

    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $count = (int) (($result && ($row = mysqli_fetch_assoc($result))) ? $row['total'] : 0);
    mysqli_stmt_close($stmt);

    return $count;
}

function fetchNotifications(mysqli $conn, int $userId, int $limit = 20): array {
    ensureNotificationsTable($conn);

    $limit = max(1, min(100, $limit));
    $stmt = mysqli_prepare($conn, 'SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?');

    if (!$stmt) {
        return [];
    }

    mysqli_stmt_bind_param($stmt, 'ii', $userId, $limit);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $rows = [];

    while ($result && ($row = mysqli_fetch_assoc($result))) {
        $rows[] = $row;
    }

    mysqli_stmt_close($stmt);
    return $rows;
}

function markNotificationsRead(mysqli $conn, int $userId): void {
    ensureNotificationsTable($conn);

    $stmt = mysqli_prepare($conn, 'UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0');
    if (!$stmt) {
        return;
    }

    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}
