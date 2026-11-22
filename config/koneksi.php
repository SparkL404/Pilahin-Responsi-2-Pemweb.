<?php
/**
 * Koneksi database MySQL menggunakan mysqli prosedural (sesuai Modul 6 PHP CRUD).
 */

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'pilahin_db';
$db_port = 3306;

$localConfig = __DIR__ . '/config.local.php';
if (file_exists($localConfig)) {
    require $localConfig;
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $koneksi = mysqli_connect($db_host, $db_user, $db_pass, $db_name, (int)$db_port);
    mysqli_set_charset($koneksi, 'utf8mb4');
} catch (mysqli_sql_exception $e) {
    $errMsg = 'Koneksi database gagal. Pastikan MySQL di XAMPP aktif dan database pilahin_db sudah di-import.';
    if (defined('PILAHIN_API') && PILAHIN_API) {
        if (function_exists('json_error')) {
            json_error($errMsg, 500);
        }
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array('error' => $errMsg), JSON_UNESCAPED_UNICODE);
        exit;
    }

    http_response_code(500);
    echo '<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>Koneksi Database Gagal - Pilahin</title>';
    echo '<style>body{font-family:system-ui,sans-serif;background:#DDEB9D;color:#143D60;margin:0;padding:40px 20px}';
    echo '.box{max-width:560px;margin:40px auto;background:#F3F8D6;border:2px solid #A0C878;border-radius:16px;padding:28px}';
    echo 'h1{margin:0 0 12px;font-size:24px}code{background:#E6EFBB;padding:2px 6px;border-radius:6px}</style></head><body>';
    echo '<div class="box"><h1>Koneksi Database Gagal</h1>';
    echo '<p>Pastikan layanan <strong>MySQL</strong> pada XAMPP Control Panel sudah berjalan dan file <code>database/pilahin_db.sql</code> sudah di-import melalui <strong>phpMyAdmin</strong>.</p>';
    echo '<p style="font-size:14px;color:#27667B">Konfigurasi default: host <code>localhost</code>, database <code>pilahin_db</code>, user <code>root</code>.</p>';
    echo '</div></body></html>';
    exit;
}
