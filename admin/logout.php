<?php
require_once __DIR__ . '/../includes/helpers.php';

start_app_session();

$token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf($token)) {
    if (!empty($_SESSION['admin_id'])) {
        header('Location: index.php');
    } else {
        header('Location: login.php');
    }
    exit;
}

$_SESSION = array();

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

session_destroy();

start_app_session();
set_flash('success', 'Anda telah keluar dari Panel Admin.');
header('Location: login.php');
exit;
