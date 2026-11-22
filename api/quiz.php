<?php
/**
 * GET api/quiz.php
 * Mengembalikan daftar soal kuis aktif dari database.
 */

define('PILAHIN_API', true);

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../config/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_error('Metode HTTP tidak diizinkan', 405);
}

try {
    $sql = 'SELECT id, question, option_a, option_b, option_c, answer_index, explanation
            FROM quiz_questions
            WHERE is_active = 1
            ORDER BY sort_order ASC, id ASC';
    $stmt = mysqli_prepare($koneksi, $sql);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    $quiz = array();
    while ($row = mysqli_fetch_assoc($res)) {
        $quiz[] = array(
            'id' => (int)$row['id'],
            'question' => $row['question'],
            'options' => array($row['option_a'], $row['option_b'], $row['option_c']),
            'answer' => (int)$row['answer_index'],
            'explanation' => $row['explanation'],
        );
    }
    mysqli_stmt_close($stmt);

    json_response($quiz, 200);
} catch (mysqli_sql_exception $e) {
    json_error('Gagal memuat soal kuis dari server', 500);
}
