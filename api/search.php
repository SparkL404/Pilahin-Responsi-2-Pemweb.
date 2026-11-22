<?php
/**
 * GET api/search.php?q=...
 * Mencari kategori sampah berdasarkan kata kunci (exact -> substring 2 arah -> terpanjang)
 * dan mencatat pencarian yang belum ditemukan ke tabel search_misses.
 * Mode baca-saja `mode=suggest` mengembalikan maksimal 6 saran autocomplete tanpa mencatat miss.
 */

define('PILAHIN_API', true);

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../config/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_error('Metode HTTP tidak diizinkan', 405);
}

$mode = isset($_GET['mode']) ? trim((string)$_GET['mode']) : '';
$rawQ = isset($_GET['q']) ? (string)$_GET['q'] : '';
$q = str_sub(str_lower(trim($rawQ)), 0, 80);

if ($mode === 'suggest') {
    if ($q === '') {
        json_response(array(
            'query' => '',
            'suggestions' => array(),
        ), 200);
    }

    try {
        $escapedQ = escape_like($q, '!');
        $likePattern = '%' . $escapedQ . '%';
        $prefixPattern = $escapedQ . '%';

        $sql = "SELECT i.keyword, c.slug, c.name, c.color, c.bin, c.examples
                FROM items i
                INNER JOIN categories c ON c.id = i.category_id
                WHERE LOWER(i.keyword) LIKE ? ESCAPE '!'
                ORDER BY (LOWER(i.keyword) LIKE ? ESCAPE '!') DESC,
                         (LOWER(i.keyword) = ?) DESC,
                         CHAR_LENGTH(i.keyword) ASC,
                         i.keyword ASC
                LIMIT 6";

        $stmt = mysqli_prepare($koneksi, $sql);
        mysqli_stmt_bind_param($stmt, 'sss', $likePattern, $prefixPattern, $q);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $suggestions = array();
        while ($row = mysqli_fetch_assoc($res)) {
            $suggestions[] = array(
                'keyword' => $row['keyword'],
                'slug' => $row['slug'],
                'name' => $row['name'],
                'color' => $row['color'],
                'bin' => $row['bin'],
                'examples' => $row['examples'],
            );
        }
        mysqli_stmt_close($stmt);

        json_response(array(
            'query' => $q,
            'suggestions' => $suggestions,
        ), 200);
    } catch (mysqli_sql_exception $e) {
        json_error('Terjadi kesalahan saat memuat saran pencarian', 500);
    }
}

if ($q === '') {
    json_error('Parameter q wajib diisi', 400);
}

try {
    $likePattern = '%' . escape_like($q, '!') . '%';

    $sql = "SELECT i.keyword, c.slug, c.name, c.color, c.bin, c.examples
            FROM items i
            INNER JOIN categories c ON c.id = i.category_id
            WHERE LOWER(i.keyword) = ?
               OR INSTR(?, LOWER(i.keyword)) > 0
               OR LOWER(i.keyword) LIKE ? ESCAPE '!'
            ORDER BY (LOWER(i.keyword) = ?) DESC, CHAR_LENGTH(i.keyword) DESC
            LIMIT 1";

    $stmt = mysqli_prepare($koneksi, $sql);
    mysqli_stmt_bind_param($stmt, 'ssss', $q, $q, $likePattern, $q);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);

    $match = null;
    if ($row) {
        $match = array(
            'keyword' => $row['keyword'],
            'slug' => $row['slug'],
            'name' => $row['name'],
            'color' => $row['color'],
            'bin' => $row['bin'],
            'examples' => $row['examples'],
        );
    } elseif (str_len($q) >= 2) {
        $missSql = "INSERT INTO search_misses (query_text, hit_count, status)
                    VALUES (?, 1, 'pending')
                    ON DUPLICATE KEY UPDATE
                        hit_count = hit_count + 1,
                        status = IF(status = 'ignored', 'ignored', 'pending'),
                        last_searched_at = CURRENT_TIMESTAMP";
        $missStmt = mysqli_prepare($koneksi, $missSql);
        mysqli_stmt_bind_param($missStmt, 's', $q);
        mysqli_stmt_execute($missStmt);
        mysqli_stmt_close($missStmt);
    }

    json_response(array(
        'query' => $q,
        'match' => $match,
    ), 200);
} catch (mysqli_sql_exception $e) {
    json_error('Terjadi kesalahan saat mencari data sampah', 500);
}
