<?php
/**
 * Authentication & role-based access control.
 */

require_once __DIR__ . '/config.php';

/** Start / resume login for a user. */
function login_user(int $id): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = $id;
}

/** Log the current user out. */
function logout_user(): void
{
    $_SESSION = [];
    session_destroy();
}

/** Whether a user is currently authenticated. */
function is_logged_in(): bool
{
    return isset($_SESSION['user_id']);
}

/** Fetch the currently logged-in user or null. */
function current_user(): ?array
{
    if (!is_logged_in()) {
        return null;
    }
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }
    $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $cached = $stmt->fetch() ?: null;
    return $cached;
}

/** Require authentication; redirect to login otherwise. */
function require_login(): array
{
    if (!is_logged_in()) {
        header('Location: /index.php');
        exit;
    }
    return current_user();
}

/**
 * Require the current user to hold ALL the given roles.
 * Pass an array of allowed roles; redirects with 403 otherwise.
 */
function require_role(array $roles): array
{
    $user = require_login();
    if (!in_array($user['role'], $roles, true)) {
        http_response_code(403);
        die('<div style="font-family:sans-serif;background:#7b1f2a;color:#fff;padding:2rem;border-radius:8px;max-width:400px;margin:6rem auto;">Access Denied. Your role (' . htmlspecialchars($user['role']) . ') is not allowed here.</div>');
    }
    return $user;
}
