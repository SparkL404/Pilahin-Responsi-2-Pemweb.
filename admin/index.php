<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/koneksi.php';

require_admin();

// 1. Statistik Laporan
$repStatsRes = mysqli_query(
    $koneksi,
    "SELECT
        COUNT(*) AS total,
        COALESCE(SUM(status = 'baru'), 0) AS baru,
        COALESCE(SUM(status = 'diproses'), 0) AS diproses,
        COALESCE(SUM(status = 'selesai'), 0) AS selesai
     FROM reports"
);
$repStats = mysqli_fetch_assoc($repStatsRes);
$totalReports = (int)$repStats['total'];
$doneReports = (int)$repStats['selesai'];
$pendingReportsCount = (int)$repStats['baru'] + (int)$repStats['diproses'];
$completionRate = $totalReports > 0 ? (int)round(($doneReports / $totalReports) * 100) : 0;

// 2. Warna kategori untuk bar chart
$catColors = array();
$catColorsRes = mysqli_query($koneksi, 'SELECT name, color FROM categories');
while ($cRow = mysqli_fetch_assoc($catColorsRes)) {
    $catColors[$cRow['name']] = $cRow['color'];
}

// 3. Sebaran Laporan per Jenis Sampah
$distRows = array();
$maxDist = 1;
$distRes = mysqli_query($koneksi, 'SELECT waste_type, COUNT(*) AS cnt FROM reports GROUP BY waste_type ORDER BY cnt DESC, waste_type ASC');
while ($dRow = mysqli_fetch_assoc($distRes)) {
    $cnt = (int)$dRow['cnt'];
    if ($cnt > $maxDist) {
        $maxDist = $cnt;
    }
    $distRows[] = $dRow;
}

// 4. Hitungan Kategori, Kata Kunci & Search Miss Pending
$catCountRow = mysqli_fetch_assoc(mysqli_query($koneksi, 'SELECT COUNT(*) AS cnt FROM categories'));
$itemCountRow = mysqli_fetch_assoc(mysqli_query($koneksi, 'SELECT COUNT(*) AS cnt FROM items'));
$missCountRow = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS cnt FROM search_misses WHERE status = 'pending'"));
$pendingMissCount = (int)$missCountRow['cnt'];

$topMisses = array();
$missRes = mysqli_query($koneksi, "SELECT id, query_text, hit_count, last_searched_at FROM search_misses WHERE status = 'pending' ORDER BY hit_count DESC, last_searched_at DESC LIMIT 5");
while ($mRow = mysqli_fetch_assoc($missRes)) {
    $topMisses[] = $mRow;
}

// 5. Statistik Kuis Edukasi
$quizQRow = mysqli_fetch_assoc(mysqli_query($koneksi, 'SELECT COUNT(*) AS total_q, COALESCE(SUM(is_active = 1), 0) AS active_q FROM quiz_questions'));
$attemptRow = mysqli_fetch_assoc(
    mysqli_query(
        $koneksi,
        'SELECT COUNT(*) AS total_attempts, COALESCE(ROUND(AVG(score), 1), 0) AS avg_score, COALESCE(ROUND(AVG(score / NULLIF(total_questions, 0)) * 100, 0), 0) AS avg_percent FROM quiz_attempts'
    )
);

// 6. 5 Laporan Terbaru
$latestReports = array();
$latestRes = mysqli_query($koneksi, 'SELECT id, waste_type, location, status, created_at FROM reports ORDER BY created_at DESC, id DESC LIMIT 5');
while ($rRow = mysqli_fetch_assoc($latestRes)) {
    $latestReports[] = $rRow;
}

$pageTitle = 'Dashboard Statistik SDGs';
$activeNav = 'dashboard';
include __DIR__ . '/_layout_top.php';
?>

<div class="page-head">
  <div>
    <h1>Dashboard Statistik Dampak</h1>
    <p class="page-sub">Ringkasan pelaporan sampah liar, edukasi kuis, dan kelengkapan kamus kata kunci Pilahin.</p>
    <div class="sdg-meta">
      <span class="sdg-tag">SDG 11 &middot; Kota Berkelanjutan</span>
      <span class="sdg-tag">SDG 12 &middot; Konsumsi &amp; Produksi Bertanggung Jawab</span>
    </div>
  </div>
  <div class="head-actions">
    <a href="reports.php" class="btn">Kelola Laporan</a>
    <a href="export_reports.php" class="btn btn-ghost">Export Laporan CSV</a>
  </div>
</div>

<div class="kpi-grid">
  <a class="kpi-card kpi-link" href="reports.php">
    <div class="kpi-label">Total Laporan Sampah</div>
    <div class="kpi-val"><?php echo $totalReports; ?></div>
    <div class="kpi-sub">
      <?php echo $doneReports; ?> selesai (<strong><?php echo $completionRate; ?>%</strong>)
    </div>
  </a>

  <a class="kpi-card kpi-link" href="reports.php?status=baru">
    <div class="kpi-label">Perlu Tindak Lanjut</div>
    <div class="kpi-val"><?php echo $pendingReportsCount; ?></div>
    <div class="kpi-sub">
      <?php echo (int)$repStats['baru']; ?> baru &middot; <?php echo (int)$repStats['diproses']; ?> diproses
    </div>
  </a>

  <a class="kpi-card kpi-link" href="<?php echo $pendingMissCount > 0 ? 'items.php?tab=misses' : 'items.php'; ?>">
    <div class="kpi-label">Kamus Kata Kunci</div>
    <div class="kpi-val"><?php echo (int)$itemCountRow['cnt']; ?></div>
    <div class="kpi-sub">
      <?php echo (int)$catCountRow['cnt']; ?> kategori &middot; <strong><?php echo $pendingMissCount; ?> saran baru</strong>
    </div>
  </a>

  <a class="kpi-card kpi-link" href="quiz.php">
    <div class="kpi-label">Edukasi Kuis (SDG 12)</div>
    <div class="kpi-val"><?php echo (int)$attemptRow['avg_percent']; ?>%</div>
    <div class="kpi-sub">
      Rata-rata <?php echo e($attemptRow['avg_score']); ?> &middot; <?php echo (int)$attemptRow['total_attempts']; ?> percobaan &middot; <?php echo (int)$quizQRow['active_q']; ?> soal aktif
    </div>
  </a>
</div>

<div class="two-col">
  <section class="panel-card">
    <div class="panel-head">
      <h2>Sebaran Laporan per Jenis</h2>
      <a href="reports.php" class="btn btn-sm btn-ghost">Lihat Semua</a>
    </div>
    <?php if (empty($distRows)): ?>
      <div class="empty-state">
        <p>Belum ada data laporan masuk.</p>
      </div>
    <?php else: ?>
      <div class="bar-list">
        <?php foreach ($distRows as $d): ?>
          <?php
            $cnt = (int)$d['cnt'];
            $pct = (int)round(($cnt / $maxDist) * 100);
            $sharePct = $totalReports > 0 ? (int)round(($cnt / $totalReports) * 100) : 0;
            $fillColor = isset($catColors[$d['waste_type']]) ? $catColors[$d['waste_type']] : '#27667B';
          ?>
          <a class="bar-link" href="reports.php?type=<?php echo urlencode($d['waste_type']); ?>">
            <div class="bar-head">
              <span><?php echo e($d['waste_type']); ?></span>
              <span><?php echo $cnt; ?> laporan (<?php echo $sharePct; ?>%)</span>
            </div>
            <div class="bar-track">
              <div class="bar-fill" style="--bar-w:<?php echo $pct; ?>%;--bar-color:<?php echo e($fillColor); ?>"></div>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <section class="panel-card">
    <div class="panel-head">
      <h2>Pencarian Belum Terdaftar</h2>
      <a href="items.php?tab=misses" class="btn btn-sm btn-ghost">Tambahkan ke Kamus</a>
    </div>
    <?php if (empty($topMisses)): ?>
      <div class="empty-state">
        <p>Semua pencarian warga sudah terdaftar atau ditinjau.</p>
        <a href="items.php" class="btn btn-sm btn-ghost">Buka Kamus Kata Kunci</a>
      </div>
    <?php else: ?>
      <div class="table-wrap">
        <table class="resp-table">
          <thead>
            <tr>
              <th scope="col">Kata Dicari</th>
              <th scope="col">Frekuensi</th>
              <th scope="col">Terakhir Dicari</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($topMisses as $m): ?>
              <tr>
                <td data-label="Kata Dicari" class="cell-wrap">
                  <a href="items.php?tab=misses" class="row-link"><?php echo e($m['query_text']); ?></a>
                </td>
                <td data-label="Frekuensi"><span class="badge badge-miss num"><?php echo (int)$m['hit_count']; ?>x dicari</span></td>
                <td data-label="Terakhir" class="muted"><?php echo e(format_datetime($m['last_searched_at'])); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </section>
</div>

<section class="panel-card">
  <div class="panel-head">
    <h2>5 Laporan Terbaru</h2>
    <a href="reports.php" class="btn btn-sm">Tindak Lanjuti Laporan</a>
  </div>
  <?php if (empty($latestReports)): ?>
    <div class="empty-state">
      <p>Belum ada laporan sampah liar yang masuk dari warga.</p>
    </div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="resp-table">
        <thead>
          <tr>
            <th scope="col">ID</th>
            <th scope="col">Jenis</th>
            <th scope="col">Lokasi</th>
            <th scope="col">Status</th>
            <th scope="col">Waktu Masuk</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($latestReports as $rep): ?>
            <?php $rowHref = 'reports.php?status=' . urlencode($rep['status']) . '&q=' . urlencode($rep['location']); ?>
            <tr>
              <td data-label="ID" class="num"><a href="<?php echo e($rowHref); ?>" class="row-link">#<?php echo (int)$rep['id']; ?></a></td>
              <td data-label="Jenis"><strong><?php echo e($rep['waste_type']); ?></strong></td>
              <td data-label="Lokasi" class="cell-wrap"><a href="<?php echo e($rowHref); ?>" class="row-link"><?php echo e($rep['location']); ?></a></td>
              <td data-label="Status"><span class="badge badge-<?php echo e($rep['status']); ?>"><?php echo e(ucfirst($rep['status'])); ?></span></td>
              <td data-label="Masuk" class="muted text-nowrap"><?php echo e(format_datetime($rep['created_at'])); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

<?php include __DIR__ . '/_layout_bottom.php'; ?>
