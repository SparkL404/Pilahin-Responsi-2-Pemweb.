<?php
$adminUser = current_admin();
$flash = get_flash();
$csrf = csrf_token();
$pageTitle = isset($pageTitle) ? $pageTitle : 'Panel Admin';
$activeNav = isset($activeNav) ? $activeNav : 'dashboard';

$navPendingReports = 0;
$navPendingMisses = 0;
if (isset($koneksi) && $koneksi instanceof mysqli) {
    $repBadgeRes = mysqli_query($koneksi, "SELECT COUNT(*) AS cnt FROM reports WHERE status IN ('baru', 'diproses')");
    if ($repBadgeRes) {
        $repBadgeRow = mysqli_fetch_assoc($repBadgeRes);
        $navPendingReports = (int)$repBadgeRow['cnt'];
    }
    $missBadgeRes = mysqli_query($koneksi, "SELECT COUNT(*) AS cnt FROM search_misses WHERE status = 'pending'");
    if ($missBadgeRes) {
        $missBadgeRow = mysqli_fetch_assoc($missBadgeRes);
        $navPendingMisses = (int)$missBadgeRow['cnt'];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?php echo e($pageTitle); ?> - Pilahin Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@700;800&family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="admin.css">
</head>
<body>
<a class="skip-link" href="#main-content">Lewati ke konten utama</a>
<header class="admin-header">
  <div class="admin-wrap admin-header-inner">
    <a class="brand" href="index.php">
      Pilahin <span class="brand-tag">Admin</span>
    </a>
    <nav class="admin-nav" aria-label="Menu Admin">
      <a href="index.php" class="<?php echo $activeNav === 'dashboard' ? 'active' : ''; ?>"<?php echo $activeNav === 'dashboard' ? ' aria-current="page"' : ''; ?>>Dashboard</a>
      <a href="reports.php" class="<?php echo $activeNav === 'reports' ? 'active' : ''; ?>"<?php echo $activeNav === 'reports' ? ' aria-current="page"' : ''; ?>>
        Laporan<?php if ($navPendingReports > 0): ?> <span class="nav-count" aria-label="<?php echo $navPendingReports; ?> laporan aktif"><?php echo $navPendingReports; ?></span><?php endif; ?>
      </a>
      <a href="categories.php" class="<?php echo $activeNav === 'categories' ? 'active' : ''; ?>"<?php echo $activeNav === 'categories' ? ' aria-current="page"' : ''; ?>>Kategori</a>
      <a href="items.php" class="<?php echo $activeNav === 'items' ? 'active' : ''; ?>"<?php echo $activeNav === 'items' ? ' aria-current="page"' : ''; ?>>
        Kata Kunci<?php if ($navPendingMisses > 0): ?> <span class="nav-count" aria-label="<?php echo $navPendingMisses; ?> saran baru"><?php echo $navPendingMisses; ?></span><?php endif; ?>
      </a>
      <a href="quiz.php" class="<?php echo $activeNav === 'quiz' ? 'active' : ''; ?>"<?php echo $activeNav === 'quiz' ? ' aria-current="page"' : ''; ?>>Kuis</a>
    </nav>
    <div class="admin-user">
      <a href="../index.php" class="admin-ext-link" target="_blank" rel="noopener noreferrer">Lihat Situs <span aria-hidden="true">&#8599;</span></a>
      <span class="admin-name" title="<?php echo e($adminUser ? $adminUser['name'] : 'Admin'); ?>"><?php echo e($adminUser ? $adminUser['name'] : 'Admin'); ?></span>
      <form method="post" action="logout.php" class="inline-form">
        <input type="hidden" name="csrf_token" value="<?php echo e($csrf); ?>">
        <button type="submit" class="btn btn-sm btn-light">Keluar</button>
      </form>
    </div>
  </div>
</header>

<main class="admin-main" id="main-content" tabindex="-1">
  <div class="admin-wrap">
    <?php if ($flash): ?>
      <div class="flash flash-<?php echo e($flash['type']); ?> flash-row" role="status">
        <span><?php echo e($flash['message']); ?></span>
        <button type="button" class="flash-close" data-dismiss-flash aria-label="Tutup notifikasi">&times;</button>
      </div>
    <?php endif; ?>
