<?php
/**
 * Shared helper functions.
 */

require_once __DIR__ . '/config.php';

/** Escape a string for safe HTML output. */
function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/** Role -> friendly label map. */
function role_label(?string $role): string
{
    $map = [
        'admin'          => 'Administrator',
        'registrar'      => 'Registrar',
        'id_staff'       => 'ID Staff',
        'security_guard' => 'Security Guard',
        'student'        => 'Student',
    ];
    return $map[$role] ?? ucfirst((string)$role);
}

/** Generate a short unique token. */
function uid(string $prefix = ''): string
{
    return $prefix . strtoupper(bin2hex(random_bytes(6)));
}

/** Create a student ID number like YYYY-NNNN. */
function next_student_no(): string
{
    $year   = date('Y');
    $stmt   = db()->query("SELECT COUNT(*) AS c FROM students WHERE student_no LIKE '" . $year . "-%'");
    $count  = (int)$stmt->fetch()['c'];
    return sprintf('%s-%04d', $year, $count + 1);
}

/** Add a notification for a recipient. */
function notify(?int $recipient_id, string $message): void
{
    $stmt = db()->prepare('INSERT INTO notifications (recipient_id, message) VALUES (?, ?)');
    $stmt->execute([$recipient_id, $message]);
}

/** Get unread notification count for the current user. */
function unread_notifications(?int $user_id): int
{
    if (!$user_id) {
        return 0;
    }
    $stmt = db()->prepare('SELECT COUNT(*) AS c FROM notifications WHERE recipient_id = ? AND is_read = 0');
    $stmt->execute([$user_id]);
    return (int)$stmt->fetch()['c'];
}

/** Redirect with a flash message. */
function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/** Set a one-shot flash message in the session. */
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

/** Consume and return any pending flash messages. */
function take_flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}
