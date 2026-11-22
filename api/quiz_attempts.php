<?php
/**
 * GET / POST api/quiz_attempts.php
 * Menyimpan hasil kuis warga dan mengembalikan statistik agregat skor.
 */

define('PILAHIN_API', true);

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../config/koneksi.php';

function fetch_quiz_stats($koneksi)
{
    $sql = 'SELECT
                COUNT(*) AS total_attempts,
                COALESCE(ROUND(AVG(score), 1), 0) AS avg_score,
                COALESCE(ROUND(AVG(score / NULLIF(total_questions, 0)) * 100, 0), 0) AS avg_percent
            FROM quiz_attempts';
    $stmt = mysqli_prepare($koneksi, $sql);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);

    return array(
        'total_attempts' => (int)$row['total_attempts'],
        'avg_score' => (float)$row['avg_score'],
        'avg_percent' => (int)$row['avg_percent'],
    );
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        json_response(fetch_quiz_stats($koneksi), 200);
    } catch (mysqli_sql_exception $e) {
        json_error('Gagal memuat statistik kuis', 500);
    }
}

if ($method !== 'POST') {
    json_error('Metode HTTP tidak diizinkan', 405);
}

$input = get_json_input();
if ($input === null && !empty($_POST)) {
    $input = $_POST;
}
if (!is_array($input)) {
    json_error('Format data tidak valid', 400);
}

$scoreRaw = isset($input['score']) ? $input['score'] : null;
$totalRaw = isset($input['total']) ? $input['total'] : null;

$score = filter_var($scoreRaw, FILTER_VALIDATE_INT);
$total = filter_var($totalRaw, FILTER_VALIDATE_INT);

if ($score === false || $total === false || $total < 1 || $total > 50 || $score < 0 || $score > $total) {
    json_error('Skor atau jumlah soal tidak valid', 400);
}

try {
    $stmt = mysqli_prepare($koneksi, 'INSERT INTO quiz_attempts (score, total_questions) VALUES (?, ?)');
    mysqli_stmt_bind_param($stmt, 'ii', $score, $total);
    mysqli_stmt_execute($stmt);
    $attemptId = (int)mysqli_insert_id($koneksi);
    mysqli_stmt_close($stmt);

    $stats = fetch_quiz_stats($koneksi);
    $stats['id'] = $attemptId;

    json_response($stats, 201);
} catch (mysqli_sql_exception $e) {
    json_error('Gagal menyimpan hasil kuis', 500);
}
