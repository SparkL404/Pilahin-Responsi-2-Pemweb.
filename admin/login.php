<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/koneksi.php';

start_app_session();
ensure_default_admin($koneksi);

if (is_admin_logged_in()) {
    header('Location: index.php');
    exit;
}

$error = '';
$fieldErrors = array();
$usernameValue = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    $usernameValue = trim((string)(isset($_POST['username']) ? $_POST['username'] : ''));
    $password = (string)(isset($_POST['password']) ? $_POST['password'] : '');

    if (!verify_csrf($token)) {
        $error = 'Sesi formulir tidak valid. Silakan muat ulang halaman dan coba lagi.';
    } elseif ($usernameValue === '' || $password === '') {
        if ($usernameValue === '') {
            $fieldErrors['username'] = 'Username wajib diisi.';
        }
        if ($password === '') {
            $fieldErrors['password'] = 'Kata sandi wajib diisi.';
        }
        $error = 'Username dan kata sandi wajib diisi.';
    } else {
        $stmt = mysqli_prepare($koneksi, 'SELECT id, username, full_name, password_hash FROM admins WHERE username = ? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 's', $usernameValue);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $admin = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);

        if ($admin && password_verify($password, $admin['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int)$admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_name'] = $admin['full_name'];
            set_flash('success', 'Selamat datang kembali, ' . $admin['full_name'] . '.');
            header('Location: index.php');
            exit;
        } else {
            $error = 'Username atau kata sandi salah.';
        }
    }
}

$flash = get_flash();
$csrf = csrf_token();
$shouldAutofocus = ($error === '' && !$flash);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Login Admin - Pilahin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@700;800&family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="admin.css">
</head>
<body>
<main class="login-shell">
  <div class="login-card">
    <h1>Masuk Panel Admin</h1>
    <p class="login-lead">Kelola laporan sampah liar, kata kunci pencarian, kategori, dan soal kuis Pilahin.</p>

    <?php if ($flash): ?>
      <div class="flash flash-<?php echo e($flash['type']); ?> flash-row" role="status">
        <span><?php echo e($flash['message']); ?></span>
        <button type="button" class="flash-close" data-dismiss-flash aria-label="Tutup notifikasi">&times;</button>
      </div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
      <div class="flash flash-error" role="alert"><?php echo e($error); ?></div>
    <?php endif; ?>

    <form method="post" action="login.php" class="login-form">
      <input type="hidden" name="csrf_token" value="<?php echo e($csrf); ?>">
      <label class="field">
        Username
        <input
          type="text"
          name="username"
          value="<?php echo e($usernameValue); ?>"
          required
          <?php echo $shouldAutofocus ? 'autofocus' : ''; ?>
          autocomplete="username"
          placeholder="admin"
          <?php if (isset($fieldErrors['username'])): ?>aria-invalid="true" aria-describedby="err-login-username"<?php endif; ?>
        >
        <?php if (isset($fieldErrors['username'])): ?>
          <small class="field-error" id="err-login-username"><?php echo e($fieldErrors['username']); ?></small>
        <?php endif; ?>
      </label>
      <label class="field">
        Kata Sandi
        <div class="password-wrap">
          <input
            type="password"
            id="login-password"
            name="password"
            required
            autocomplete="current-password"
            placeholder="Masukkan kata sandi"
            <?php if (isset($fieldErrors['password'])): ?>aria-invalid="true" aria-describedby="err-login-password"<?php endif; ?>
          >
          <button type="button" class="password-toggle" data-toggle-password="login-password" aria-pressed="false">Tampilkan</button>
        </div>
        <?php if (isset($fieldErrors['password'])): ?>
          <small class="field-error" id="err-login-password"><?php echo e($fieldErrors['password']); ?></small>
        <?php endif; ?>
      </label>
      <button type="submit" class="btn w-full">Masuk</button>
    </form>

    <div class="demo-hint">
      <strong>Akun demo default:</strong><br>
      Username: <code>admin</code> &middot; Sandi: <code>admin123</code>
    </div>

    <div class="login-back">
      <a href="../index.php">&larr; Kembali ke Beranda Pilahin</a>
    </div>
  </div>
</main>
<script src="admin.js" defer></script>
</body>
</html>
