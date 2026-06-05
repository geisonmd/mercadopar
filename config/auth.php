<?php
require_once __DIR__ . '/database.php';

function session_start_safe(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function is_logged_in(): bool {
    session_start_safe();
    return !empty($_SESSION['user_id']);
}

function require_login(): void {
    if (!is_logged_in()) {
        header('Location: /login');
        exit;
    }
}

function login(string $email, string $password): bool {
    $stmt = db()->prepare('SELECT id, password_hash FROM users WHERE email = ? AND active = 1 LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password_hash'])) {
        session_start_safe();
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        return true;
    }
    return false;
}

function logout(): void {
    session_start_safe();
    session_destroy();
    header('Location: /login');
    exit;
}

function current_user(): ?array {
    if (!is_logged_in()) return null;
    $stmt = db()->prepare('SELECT id, name, email FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}
