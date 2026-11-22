<?php
/**
 * GET api/search.php?q=...
 * Mencari kategori sampah berdasarkan kata kunci (exact -> substring 2 arah -> terpanjang)
 * dan mencatat pencarian yang belum ditemukan ke tabel search_misses.
 * Mode baca-saja:
 * - mode=suggest : maksimal 6 saran autocomplete tanpa mencatat miss
 * - mode=random  : sampel kata kunci acak lintas kategori untuk tombol Acak Coba cepat
 * - mode=stats   : ringkasan jumlah kata kunci, kategori, dan laporan warga live
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

if ($mode === 'stats') {
    try {
        $sql = 'SELECT
                    (SELECT COUNT(*) FROM items) AS items_count,
                    (SELECT COUNT(*) FROM categories) AS categories_count,
                    (SELECT COUNT(*) FROM reports) AS reports_count';
        $stmt = mysqli_prepare($koneksi, $sql);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);

        json_response(array(
            'items' => (int)$row['items_count'],
            'categories' => (int)$row['categories_count'],
            'reports' => (int)$row['reports_count'],
        ), 200);
    } catch (mysqli_sql_exception $e) {
        json_error('Gagal memuat statistik ringkas', 500);
    }
}

if ($mode === 'random') {
    $limitRaw = filter_var(isset($_GET['limit']) ? $_GET['limit'] : 4, FILTER_VALIDATE_INT);
    $limit = ($limitRaw !== false) ? max(1, min(8, (int)$limitRaw)) : 4;

    $excludeRaw = isset($_GET['exclude']) ? (string)$_GET['exclude'] : '';
    $excludeMap = array();
    if ($excludeRaw !== '') {
        foreach (explode(',', $excludeRaw) as $ex) {
            $norm = str_lower(trim($ex));
            if ($norm !== '') {
                $excludeMap[$norm] = true;
            }
        }
    }

    try {
        $sql = 'SELECT i.keyword, c.slug, c.name, c.color, c.bin, c.examples
                FROM items i
                INNER JOIN categories c ON c.id = i.category_id';
        $stmt = mysqli_prepare($koneksi, $sql);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $rows = array();
        while ($row = mysqli_fetch_assoc($res)) {
            $rows[] = array(
                'keyword' => $row['keyword'],
                'slug' => $row['slug'],
                'name' => $row['name'],
                'color' => $row['color'],
                'bin' => $row['bin'],
                'examples' => $row['examples'],
            );
        }
        mysqli_stmt_close($stmt);

        shuffle($rows);

        $picked = array();
        $usedKeys = array();
        $usedSlugs = array();

        // Pass 1: distinct categories AND not in exclude
        foreach ($rows as $row) {
            if (count($picked) >= $limit) {
                break;
            }
            $kwLow = str_lower($row['keyword']);
            if (isset($excludeMap[$kwLow]) || isset($usedSlugs[$row['slug']])) {
                continue;
            }
            $picked[] = $row;
            $usedKeys[$kwLow] = true;
            $usedSlugs[$row['slug']] = true;
        }

        // Pass 2: distinct categories even if excluded
        if (count($picked) < $limit) {
            foreach ($rows as $row) {
                if (count($picked) >= $limit) {
                    break;
                }
                $kwLow = str_lower($row['keyword']);
                if (isset($usedKeys[$kwLow]) || isset($usedSlugs[$row['slug']])) {
                    continue;
                }
                $picked[] = $row;
                $usedKeys[$kwLow] = true;
                $usedSlugs[$row['slug']] = true;
            }
        }

        // Pass 3: any remaining unpicked items
        if (count($picked) < $limit) {
            foreach ($rows as $row) {
                if (count($picked) >= $limit) {
                    break;
                }
                $kwLow = str_lower($row['keyword']);
                if (isset($usedKeys[$kwLow])) {
                    continue;
                }
                $picked[] = $row;
                $usedKeys[$kwLow] = true;
            }
        }

        json_response(array('items' => $picked), 200);
    } catch (mysqli_sql_exception $e) {
        json_error('Gagal mengacak kata kunci contoh', 500);
    }
}

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
    $match = null;

    if (str_len($q) < 2) {
        $sql = 'SELECT i.keyword, c.slug, c.name, c.color, c.bin, c.examples
                FROM items i
                INNER JOIN categories c ON c.id = i.category_id
                WHERE LOWER(i.keyword) = ?
                LIMIT 1';
        $stmt = mysqli_prepare($koneksi, $sql);
        mysqli_stmt_bind_param($stmt, 's', $q);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);
    } else {
        $escapedQ = escape_like($q, '!');
        $likePattern = '%' . $escapedQ . '%';
        $prefixPattern = $escapedQ . '%';

        $sql = "SELECT i.keyword, c.slug, c.name, c.color, c.bin, c.examples
                FROM items i
                INNER JOIN categories c ON c.id = i.category_id
                WHERE LOWER(i.keyword) = ?
                   OR INSTR(?, LOWER(i.keyword)) > 0
                   OR LOWER(i.keyword) LIKE ? ESCAPE '!'
                ORDER BY (LOWER(i.keyword) = ?) DESC,
                         (INSTR(?, LOWER(i.keyword)) > 0) DESC,
                         CASE c.slug
                             WHEN 'b3' THEN 4
                             WHEN 'elektronik' THEN 3
                             WHEN 'residu' THEN 2
                             ELSE 1
                         END DESC,
                         (LOWER(i.keyword) LIKE ? ESCAPE '!') DESC,
                         CHAR_LENGTH(i.keyword) DESC
                LIMIT 1";

        $stmt = mysqli_prepare($koneksi, $sql);
        mysqli_stmt_bind_param($stmt, 'ssssss', $q, $q, $likePattern, $q, $q, $prefixPattern);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);
    }

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
