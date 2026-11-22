<?php
/**
 * Helper umum: session, sanitasi output, string UTF-8 aman, JSON response, CSRF, flash message, dan CSV.
 */

function start_app_session()
{
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.use_strict_mode', '1');
        $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        session_set_cookie_params(array(
            'lifetime' => 0,
            'path' => '/',
            'secure' => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ));
        session_start();
    }
}

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function str_lower($s)
{
    $s = (string)$s;
    if (function_exists('mb_strtolower')) {
        return mb_strtolower($s, 'UTF-8');
    }
    return strtolower($s);
}

function str_len($s)
{
    $s = (string)$s;
    if (function_exists('mb_strlen')) {
        return mb_strlen($s, 'UTF-8');
    }
    return strlen($s);
}

function str_sub($s, $start, $len)
{
    $s = (string)$s;
    if (function_exists('mb_substr')) {
        return mb_substr($s, $start, $len, 'UTF-8');
    }
    return substr($s, $start, $len);
}

function escape_like($s, $escapeChar = '!')
{
    return str_replace(
        array($escapeChar, '%', '_'),
        array($escapeChar . $escapeChar, $escapeChar . '%', $escapeChar . '_'),
        (string)$s
    );
}

function slugify($text)
{
    $slug = str_lower(trim((string)$text));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim((string)$slug, '-');
    return $slug;
}

function json_response($data, $status = 200)
{
    http_response_code((int)$status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function json_error($message, $status = 400)
{
    json_response(array('error' => (string)$message), $status);
}

function get_json_input()
{
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        return null;
    }
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return null;
    }
    return $decoded;
}

function csrf_token()
{
    start_app_session();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf($token)
{
    start_app_session();
    if (empty($_SESSION['csrf_token']) || !is_string($token) || $token === '') {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function set_flash($type, $message)
{
    start_app_session();
    $_SESSION['flash'] = array(
        'type' => (string)$type,
        'message' => (string)$message,
    );
}

function get_flash()
{
    start_app_session();
    if (empty($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function format_datetime($ts)
{
    if (empty($ts)) {
        return '-';
    }
    $time = strtotime((string)$ts);
    if ($time === false) {
        return (string)$ts;
    }
    $months = array(
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
        5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
        9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
    );
    $m = (int)date('n', $time);
    $monthLabel = isset($months[$m]) ? $months[$m] : date('M', $time);
    return date('d', $time) . ' ' . $monthLabel . ' ' . date('Y, H:i', $time);
}

function csv_safe($val)
{
    $str = (string)$val;
    if ($str !== '' && preg_match('/^[=+\-@\t\r]/', $str)) {
        return "'" . $str;
    }
    return $str;
}

function stmt_bind_params($stmt, $types, $params)
{
    if ($types === '' || empty($params)) {
        return true;
    }
    $refs = array($stmt, $types);
    foreach ($params as $key => $val) {
        $refs[] = &$params[$key];
    }
    return call_user_func_array('mysqli_stmt_bind_param', $refs);
}
