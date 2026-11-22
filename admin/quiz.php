<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/koneksi.php';

require_admin();

$formError = '';
$fieldErrors = array();
$form = array(
    'id' => 0,
    'question' => '',
    'option_a' => '',
    'option_b' => '',
    'option_c' => '',
    'answer_index' => 0,
    'explanation' => '',
    'sort_order' => 0,
    'is_active' => 1,
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (!verify_csrf($token)) {
        set_flash('error', 'Token keamanan tidak valid.');
        header('Location: quiz.php');
        exit;
    }

    $action = isset($_POST['action']) ? $_POST['action'] : '';

    if ($action === 'toggle_active') {
        $qid = filter_var(isset($_POST['id']) ? $_POST['id'] : 0, FILTER_VALIDATE_INT);
        if ($qid > 0) {
            $stmt = mysqli_prepare($koneksi, 'UPDATE quiz_questions SET is_active = IF(is_active = 1, 0, 1) WHERE id = ?');
            mysqli_stmt_bind_param($stmt, 'i', $qid);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            $cntRow = mysqli_fetch_assoc(mysqli_query($koneksi, 'SELECT COUNT(*) AS cnt FROM quiz_questions WHERE is_active = 1'));
            if ((int)$cntRow['cnt'] === 0) {
                set_flash('warning', 'Status aktif soal #' . $qid . ' diubah. Perhatian: tidak ada soal aktif sehingga kuis publik sementara kosong.');
            } else {
                set_flash('success', 'Status aktif soal #' . $qid . ' berhasil diubah.');
            }
        }
        header('Location: quiz.php');
        exit;
    }

    if ($action === 'delete_question') {
        $qid = filter_var(isset($_POST['id']) ? $_POST['id'] : 0, FILTER_VALIDATE_INT);
        if ($qid > 0) {
            $stmt = mysqli_prepare($koneksi, 'DELETE FROM quiz_questions WHERE id = ?');
            mysqli_stmt_bind_param($stmt, 'i', $qid);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            $cntRow = mysqli_fetch_assoc(mysqli_query($koneksi, 'SELECT COUNT(*) AS cnt FROM quiz_questions WHERE is_active = 1'));
            if ((int)$cntRow['cnt'] === 0) {
                set_flash('warning', 'Soal kuis #' . $qid . ' dihapus. Perhatian: tidak ada soal aktif tersisa di halaman publik.');
            } else {
                set_flash('success', 'Soal kuis #' . $qid . ' berhasil dihapus.');
            }
        }
        header('Location: quiz.php');
        exit;
    }

    if ($action === 'save_question') {
        $form['id'] = (int)(filter_var(isset($_POST['id']) ? $_POST['id'] : 0, FILTER_VALIDATE_INT) ?: 0);
        $form['question'] = trim((string)(isset($_POST['question']) ? $_POST['question'] : ''));
        $form['option_a'] = trim((string)(isset($_POST['option_a']) ? $_POST['option_a'] : ''));
        $form['option_b'] = trim((string)(isset($_POST['option_b']) ? $_POST['option_b'] : ''));
        $form['option_c'] = trim((string)(isset($_POST['option_c']) ? $_POST['option_c'] : ''));
        $ansRaw = isset($_POST['answer_index']) ? $_POST['answer_index'] : '';
        $form['answer_index'] = (int)$ansRaw;
        $form['explanation'] = trim((string)(isset($_POST['explanation']) ? $_POST['explanation'] : ''));
        $form['sort_order'] = (int)(isset($_POST['sort_order']) ? $_POST['sort_order'] : 0);
        $form['is_active'] = isset($_POST['is_active']) ? 1 : 0;

        if ($form['question'] === '' || str_len($form['question']) > 255) {
            $fieldErrors['question'] = 'Pertanyaan wajib diisi (maks. 255 karakter).';
        }
        if ($form['option_a'] === '' || str_len($form['option_a']) > 150) {
            $fieldErrors['option_a'] = 'Pilihan A wajib diisi (maks. 150 karakter).';
        }
        if ($form['option_b'] === '' || str_len($form['option_b']) > 150) {
            $fieldErrors['option_b'] = 'Pilihan B wajib diisi (maks. 150 karakter).';
        }
        if ($form['option_c'] === '' || str_len($form['option_c']) > 150) {
            $fieldErrors['option_c'] = 'Pilihan C wajib diisi (maks. 150 karakter).';
        }
        if (!in_array($form['answer_index'], array(0, 1, 2), true)) {
            $fieldErrors['answer_index'] = 'Kunci jawaban harus salah satu dari Pilihan A, B, atau C.';
        }
        if ($form['explanation'] === '' || str_len($form['explanation']) > 1000) {
            $fieldErrors['explanation'] = 'Penjelasan jawaban wajib diisi (maks. 1000 karakter).';
        }

        if (!empty($fieldErrors)) {
            $formError = 'Periksa kembali kolom yang ditandai merah di bawah.';
        } else {
            if ($form['id'] > 0) {
                $stmt = mysqli_prepare(
                    $koneksi,
                    'UPDATE quiz_questions
                     SET question = ?, option_a = ?, option_b = ?, option_c = ?, answer_index = ?, explanation = ?, sort_order = ?, is_active = ?
                     WHERE id = ?'
                );
                mysqli_stmt_bind_param(
                    $stmt,
                    'ssssisiii',
                    $form['question'],
                    $form['option_a'],
                    $form['option_b'],
                    $form['option_c'],
                    $form['answer_index'],
                    $form['explanation'],
                    $form['sort_order'],
                    $form['is_active'],
                    $form['id']
                );
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                set_flash('success', 'Soal kuis #' . $form['id'] . ' berhasil diperbarui.');
            } else {
                $stmt = mysqli_prepare(
                    $koneksi,
                    'INSERT INTO quiz_questions (question, option_a, option_b, option_c, answer_index, explanation, sort_order, is_active)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
                );
                mysqli_stmt_bind_param(
                    $stmt,
                    'ssssisii',
                    $form['question'],
                    $form['option_a'],
                    $form['option_b'],
                    $form['option_c'],
                    $form['answer_index'],
                    $form['explanation'],
                    $form['sort_order'],
                    $form['is_active']
                );
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                set_flash('success', 'Soal kuis baru berhasil ditambahkan.');
            }
            header('Location: quiz.php');
            exit;
        }
    }
} elseif (isset($_GET['edit'])) {
    $editId = filter_var($_GET['edit'], FILTER_VALIDATE_INT);
    if ($editId > 0) {
        $stmt = mysqli_prepare($koneksi, 'SELECT id, question, option_a, option_b, option_c, answer_index, explanation, sort_order, is_active FROM quiz_questions WHERE id = ? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 'i', $editId);
        mysqli_stmt_execute($stmt);
        $editRow = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
        if ($editRow) {
            $form = array(
                'id' => (int)$editRow['id'],
                'question' => $editRow['question'],
                'option_a' => $editRow['option_a'],
                'option_b' => $editRow['option_b'],
                'option_c' => $editRow['option_c'],
                'answer_index' => (int)$editRow['answer_index'],
                'explanation' => $editRow['explanation'],
                'sort_order' => (int)$editRow['sort_order'],
                'is_active' => (int)$editRow['is_active'],
            );
        }
    }
}

$questions = array();
$activeCount = 0;
$qRes = mysqli_query($koneksi, 'SELECT id, question, option_a, option_b, option_c, answer_index, explanation, sort_order, is_active FROM quiz_questions ORDER BY sort_order ASC, id ASC');
while ($row = mysqli_fetch_assoc($qRes)) {
    if ((int)$row['is_active'] === 1) {
        $activeCount++;
    }
    $questions[] = $row;
}

$attemptStats = mysqli_fetch_assoc(
    mysqli_query(
        $koneksi,
        'SELECT COUNT(*) AS total_attempts, COALESCE(ROUND(AVG(score), 1), 0) AS avg_score, COALESCE(ROUND(AVG(score / NULLIF(total_questions, 0)) * 100, 0), 0) AS avg_percent FROM quiz_attempts'
    )
);

$isEdit = $form['id'] > 0;
$openForm = ($isEdit || $formError !== '');
$pageTitle = 'Manajemen Bank Soal Kuis';
$activeNav = 'quiz';
include __DIR__ . '/_layout_top.php';
?>

<div class="page-head">
  <div>
    <h1>Manajemen Bank Soal Kuis</h1>
    <p class="page-sub">Kelola pertanyaan, pilihan jawaban, kunci, dan penjelasan edukasi pemilahan sampah.</p>
  </div>
</div>

<div class="kpi-grid">
  <div class="kpi-card">
    <div class="kpi-label">Soal Aktif di Beranda</div>
    <div class="kpi-val"><?php echo $activeCount; ?></div>
    <div class="kpi-sub">Dari total <?php echo count($questions); ?> soal tersimpan</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-label">Total Percobaan Kuis</div>
    <div class="kpi-val"><?php echo (int)$attemptStats['total_attempts']; ?></div>
    <div class="kpi-sub">Tercatat otomatis dari halaman utama</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-label">Rata-rata Skor Warga</div>
    <div class="kpi-val"><?php echo (int)$attemptStats['avg_percent']; ?>%</div>
    <div class="kpi-sub">Rata-rata skor: <?php echo e($attemptStats['avg_score']); ?> jawaban benar</div>
  </div>
</div>

<details class="form-disclosure" id="quiz-form"<?php echo $openForm ? ' open' : ''; ?>>
  <summary class="btn"><?php echo $isEdit ? 'Edit Soal #' . (int)$form['id'] : '+ Tambah Soal Kuis Baru'; ?></summary>
  <section class="panel-card">
    <div class="panel-head">
      <h2><?php echo $isEdit ? 'Edit Soal #' . (int)$form['id'] : 'Tambah Soal Kuis Baru'; ?></h2>
      <?php if ($isEdit): ?>
        <a href="quiz.php" class="btn btn-sm btn-ghost">Batal Edit</a>
      <?php endif; ?>
    </div>

    <?php if ($formError !== ''): ?>
      <div class="flash flash-error" role="alert"><?php echo e($formError); ?></div>
    <?php endif; ?>

    <form method="post" action="quiz.php" class="form-grid">
      <input type="hidden" name="csrf_token" value="<?php echo e($csrf); ?>">
      <input type="hidden" name="action" value="save_question">
      <input type="hidden" name="id" value="<?php echo (int)$form['id']; ?>">

      <label class="field form-full">
        Pertanyaan
        <input
          type="text"
          name="question"
          maxlength="255"
          required
          value="<?php echo e($form['question']); ?>"
          placeholder="Contoh: Minyak jelantah sebaiknya dibuang ke mana?"
          <?php if (isset($fieldErrors['question'])): ?>aria-invalid="true" aria-describedby="err-quiz-question"<?php endif; ?>
        >
        <?php if (isset($fieldErrors['question'])): ?>
          <small class="field-error" id="err-quiz-question"><?php echo e($fieldErrors['question']); ?></small>
        <?php endif; ?>
      </label>

      <label class="field">
        Pilihan A
        <input
          type="text"
          name="option_a"
          maxlength="150"
          required
          value="<?php echo e($form['option_a']); ?>"
          <?php if (isset($fieldErrors['option_a'])): ?>aria-invalid="true" aria-describedby="err-quiz-a"<?php endif; ?>
        >
        <?php if (isset($fieldErrors['option_a'])): ?>
          <small class="field-error" id="err-quiz-a"><?php echo e($fieldErrors['option_a']); ?></small>
        <?php endif; ?>
      </label>

      <label class="field">
        Pilihan B
        <input
          type="text"
          name="option_b"
          maxlength="150"
          required
          value="<?php echo e($form['option_b']); ?>"
          <?php if (isset($fieldErrors['option_b'])): ?>aria-invalid="true" aria-describedby="err-quiz-b"<?php endif; ?>
        >
        <?php if (isset($fieldErrors['option_b'])): ?>
          <small class="field-error" id="err-quiz-b"><?php echo e($fieldErrors['option_b']); ?></small>
        <?php endif; ?>
      </label>

      <label class="field">
        Pilihan C
        <input
          type="text"
          name="option_c"
          maxlength="150"
          required
          value="<?php echo e($form['option_c']); ?>"
          <?php if (isset($fieldErrors['option_c'])): ?>aria-invalid="true" aria-describedby="err-quiz-c"<?php endif; ?>
        >
        <?php if (isset($fieldErrors['option_c'])): ?>
          <small class="field-error" id="err-quiz-c"><?php echo e($fieldErrors['option_c']); ?></small>
        <?php endif; ?>
      </label>

      <label class="field">
        Kunci Jawaban Benar
        <select
          name="answer_index"
          required
          <?php if (isset($fieldErrors['answer_index'])): ?>aria-invalid="true" aria-describedby="err-quiz-answer"<?php endif; ?>
        >
          <option value="0" <?php echo $form['answer_index'] === 0 ? 'selected' : ''; ?>>Pilihan A</option>
          <option value="1" <?php echo $form['answer_index'] === 1 ? 'selected' : ''; ?>>Pilihan B</option>
          <option value="2" <?php echo $form['answer_index'] === 2 ? 'selected' : ''; ?>>Pilihan C</option>
        </select>
        <?php if (isset($fieldErrors['answer_index'])): ?>
          <small class="field-error" id="err-quiz-answer"><?php echo e($fieldErrors['answer_index']); ?></small>
        <?php endif; ?>
      </label>

      <label class="field">
        Urutan Tampil
        <input type="number" name="sort_order" min="0" max="999" value="<?php echo (int)$form['sort_order']; ?>">
      </label>

      <div class="checkbox-field">
        <label class="checkbox-label">
          <input type="checkbox" name="is_active" value="1" <?php echo $form['is_active'] === 1 ? 'checked' : ''; ?>>
          Tampilkan di kuis publik
        </label>
      </div>

      <label class="field form-full">
        Penjelasan Jawaban
        <textarea
          name="explanation"
          rows="2"
          maxlength="1000"
          required
          placeholder="Jelaskan alasan singkat agar pengguna belajar dari jawaban."
          <?php if (isset($fieldErrors['explanation'])): ?>aria-invalid="true" aria-describedby="err-quiz-exp"<?php endif; ?>
        ><?php echo e($form['explanation']); ?></textarea>
        <?php if (isset($fieldErrors['explanation'])): ?>
          <small class="field-error" id="err-quiz-exp"><?php echo e($fieldErrors['explanation']); ?></small>
        <?php endif; ?>
      </label>

      <div class="form-full inline-actions">
        <button type="submit" class="btn"><?php echo $isEdit ? 'Simpan Perubahan' : 'Tambah Soal'; ?></button>
        <?php if ($isEdit): ?>
          <a href="quiz.php" class="btn btn-ghost">Batal</a>
        <?php endif; ?>
      </div>
    </form>
  </section>
</details>

<section class="panel-card">
  <div class="panel-head">
    <h2>Daftar Bank Soal (<?php echo count($questions); ?>)</h2>
  </div>
  <?php if (empty($questions)): ?>
    <div class="empty-state">
      <p>Belum ada soal kuis tersimpan. Buka form di atas untuk menambahkan soal pertama.</p>
    </div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="resp-table">
        <thead>
          <tr>
            <th scope="col">Urut</th>
            <th scope="col">Pertanyaan &amp; Pilihan</th>
            <th scope="col">Penjelasan</th>
            <th scope="col">Status</th>
            <th scope="col">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($questions as $q): ?>
            <?php
              $ansIdx = (int)$q['answer_index'];
              $qText = (string)$q['question'];
              $qPreview = str_len($qText) > 70 ? str_sub($qText, 0, 70) . '...' : $qText;
              $deleteQuizConfirm = 'Hapus soal #' . (int)$q['id'] . ' ("' . $qPreview . '") secara permanen?';
            ?>
            <tr>
              <td data-label="Urut" class="num"><?php echo (int)$q['sort_order']; ?></td>
              <td data-label="Pertanyaan" class="cell-wrap">
                <strong><?php echo e($q['question']); ?></strong>
                <div class="muted mt-sm">
                  <div class="<?php echo $ansIdx === 0 ? 'opt-ok' : ''; ?>">A. <?php echo e($q['option_a']); ?><?php echo $ansIdx === 0 ? ' (Benar)' : ''; ?></div>
                  <div class="<?php echo $ansIdx === 1 ? 'opt-ok' : ''; ?>">B. <?php echo e($q['option_b']); ?><?php echo $ansIdx === 1 ? ' (Benar)' : ''; ?></div>
                  <div class="<?php echo $ansIdx === 2 ? 'opt-ok' : ''; ?>">C. <?php echo e($q['option_c']); ?><?php echo $ansIdx === 2 ? ' (Benar)' : ''; ?></div>
                </div>
              </td>
              <td data-label="Penjelasan" class="cell-wrap"><?php echo e($q['explanation']); ?></td>
              <td data-label="Status">
                <?php if ((int)$q['is_active'] === 1): ?>
                  <span class="badge badge-active">Aktif</span>
                <?php else: ?>
                  <span class="badge badge-inactive">Nonaktif</span>
                <?php endif; ?>
              </td>
              <td data-label="Aksi" class="cell-actions">
                <div class="inline-actions">
                  <a href="quiz.php?edit=<?php echo (int)$q['id']; ?>#quiz-form" class="btn btn-sm btn-ghost">Edit</a>
                  <form method="post" action="quiz.php" class="inline-form">
                    <input type="hidden" name="csrf_token" value="<?php echo e($csrf); ?>">
                    <input type="hidden" name="action" value="toggle_active">
                    <input type="hidden" name="id" value="<?php echo (int)$q['id']; ?>">
                    <button type="submit" class="btn btn-sm btn-ghost"><?php echo (int)$q['is_active'] === 1 ? 'Nonaktifkan' : 'Aktifkan'; ?></button>
                  </form>
                  <div class="danger-slot">
                    <form method="post" action="quiz.php" class="inline-form" data-confirm="<?php echo e($deleteQuizConfirm); ?>">
                      <input type="hidden" name="csrf_token" value="<?php echo e($csrf); ?>">
                      <input type="hidden" name="action" value="delete_question">
                      <input type="hidden" name="id" value="<?php echo (int)$q['id']; ?>">
                      <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                    </form>
                  </div>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

<?php include __DIR__ . '/_layout_bottom.php'; ?>
