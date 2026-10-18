<?php
require_once __DIR__ . '/sesi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$token = isset($_POST['csrf']) && is_string($_POST['csrf'])
    ? $_POST['csrf']
    : '';

if (!hash_equals($_SESSION['csrf_logout'], $token)) {
    http_response_code(403);
    exit('Sesi tidak valid. Kembali ke beranda dan coba lagi.');
}

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $pengaturan = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $pengaturan['path'],
        $pengaturan['domain'],
        $pengaturan['secure'],
        $pengaturan['httponly']
    );
}

session_destroy();

header('Location: index.php');
exit;