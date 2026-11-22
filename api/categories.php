<?php
/**
 * GET api/categories.php
 * Mengembalikan seluruh kategori tempat sampah dari database MySQL.
 */

define('PILAHIN_API', true);

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../config/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_error('Metode HTTP tidak diizinkan', 405);
}

try {
    $stmt = mysqli_prepare($koneksi, 'SELECT slug, name, color, bin, examples FROM categories ORDER BY sort_order ASC, id ASC');
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    $categories = array();
    while ($row = mysqli_fetch_assoc($res)) {
        $categories[] = array(
            'slug' => $row['slug'],
            'name' => $row['name'],
            'color' => $row['color'],
            'bin' => $row['bin'],
            'examples' => $row['examples'],
        );
    }
    mysqli_stmt_close($stmt);

    json_response($categories, 200);
} catch (mysqli_sql_exception $e) {
    json_error('Gagal memuat data kategori dari server', 500);
}
