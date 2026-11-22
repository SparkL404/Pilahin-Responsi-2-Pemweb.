<?php
/**
 * POST api/reports.php
 * Memvalidasi dan menyimpan laporan tumpukan sampah liar dari warga ke MySQL.
 */

define('PILAHIN_API', true);

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../config/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Metode HTTP tidak diizinkan', 405);
}

$input = get_json_input();
if ($input === null && !empty($_POST)) {
    $input = $_POST;
}
if (!is_array($input)) {
    json_error('Format payload JSON tidak valid', 400);
}

$type = trim((string)(isset($input['type']) ? $input['type'] : ''));
$location = trim((string)(isset($input['location']) ? $input['location'] : ''));
$notes = trim((string)(isset($input['notes']) ? $input['notes'] : ''));

try {
    $allowedTypes = array('Campuran');
    $catRes = mysqli_query($koneksi, 'SELECT name FROM categories');
    while ($catRow = mysqli_fetch_assoc($catRes)) {
        $allowedTypes[] = $catRow['name'];
    }

    if (!in_array($type, $allowedTypes, true)) {
        json_error('Jenis sampah tidak valid', 400);
    }

    if ($location === '' || str_len($location) > 200) {
        json_error('Lokasi wajib diisi (maks. 200 karakter)', 400);
    }

    if (str_len($notes) > 1000) {
        json_error('Keterangan maksimal 1000 karakter', 400);
    }

    $stmt = mysqli_prepare($koneksi, "INSERT INTO reports (waste_type, location, notes, status) VALUES (?, ?, ?, 'baru')");
    mysqli_stmt_bind_param($stmt, 'sss', $type, $location, $notes);
    mysqli_stmt_execute($stmt);
    $reportId = (int)mysqli_insert_id($koneksi);
    mysqli_stmt_close($stmt);

    json_response(array(
        'id' => $reportId,
        'status' => 'baru',
    ), 201);
} catch (mysqli_sql_exception $e) {
    json_error('Gagal menyimpan laporan ke server', 500);
}
