<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Load environment config (BASE_URL, APP_ENV)
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/env.php';
}

function require_login() {
    if (empty($_SESSION['user'])) {
        header("Location: " . BASE_URL . "/login.php");
        exit;
    }
}

function require_role($roles) {
    require_login();
    $roles = (array)$roles;
    if (!in_array($_SESSION['user']['role'], $roles, true)) {
        http_response_code(403);
        exit("Akses ditolak.");
    }
}


function role_label($role) {
    switch($role) {
        case 'admin': return 'Admin';
        case 'guru': return 'Guru';
        case 'kepala_sekolah': return 'Kepala Sekolah';
        default: return ucfirst(str_replace('_', ' ', (string)$role));
    }
}

function has_role($roles) {
    $roles = (array)$roles;
    return !empty($_SESSION['user']) && in_array($_SESSION['user']['role'] ?? '', $roles, true);
}

function csrf_token() {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}

function check_csrf() {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(419);
        exit("Token CSRF tidak valid.");
    }
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
