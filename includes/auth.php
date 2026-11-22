<?php
/**
 * Autentikasi & proteksi PHP Session untuk Panel Admin Pilahin.
 */

require_once __DIR__ . '/helpers.php';

function ensure_default_admin($koneksi)
{
    $res = mysqli_query($koneksi, 'SELECT COUNT(*) AS cnt FROM admins');
    $row = mysqli_fetch_assoc($res);
    if ((int)$row['cnt'] === 0) {
        $username = 'admin';
        $fullName = 'Administrator Pilahin';
        $hash = password_hash('admin123', PASSWORD_DEFAULT);

        $stmt = mysqli_prepare($koneksi, 'INSERT INTO admins (username, full_name, password_hash) VALUES (?, ?, ?)');
        mysqli_stmt_bind_param($stmt, 'sss', $username, $fullName, $hash);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

function is_admin_logged_in()
{
    start_app_session();
    return !empty($_SESSION['admin_id']);
}

function require_admin()
{
    start_app_session();
    if (empty($_SESSION['admin_id'])) {
        set_flash('warning', 'Silakan login terlebih dahulu untuk mengakses Panel Admin.');
        header('Location: login.php');
        exit;
    }
}

function current_admin()
{
    start_app_session();
    if (empty($_SESSION['admin_id'])) {
        return null;
    }
    return array(
        'id' => (int)$_SESSION['admin_id'],
        'username' => isset($_SESSION['admin_username']) ? (string)$_SESSION['admin_username'] : '',
        'name' => isset($_SESSION['admin_name']) ? (string)$_SESSION['admin_name'] : 'Admin',
    );
}
