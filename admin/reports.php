<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/_reports_filter.php';

require_admin();

$filter = parse_report_filters($_GET);
$perPage = 15;
$pageRaw = filter_var(isset($_GET['page']) ? $_GET['page'] : 1, FILTER_VALIDATE_INT);
$page = ($pageRaw !== false && $pageRaw > 0) ? (int)$pageRaw : 1;

$redirectParams = array();
if ($filter['status'] !== '') {
    $redirectParams['status'] = $filter['status'];
}
if ($filter['type'] !== '') {
    $redirectParams['type'] = $filter['type'];
}
if ($filter['q'] !== '') {
    $redirectParams['q'] = $filter['q'];
}
if ($page > 1) {
    $redirectParams['page'] = $page;
}
$redirectQs = http_build_query($redirectParams);
$redirectTarget = 'reports.php' . ($redirectQs !== '' ? '?' . $redirectQs : '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (!verify_csrf($token)) {
        set_flash('error', 'Token keamanan tidak valid. Silakan coba lagi.');
        header('Location: ' . $redirectTarget);
        exit;
    }

    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $reportId = filter_var(isset($_POST['id']) ? $_POST['id'] : 0, FILTER_VALIDATE_INT);

    if ($action === 'update_status' && $reportId > 0) {
        $newStatus = isset($_POST['status']) ? trim((string)$_POST['status']) : '';
        $allowedStatus = array('baru', 'diproses', 'selesai');
        if (!in_array($newStatus, $allowedStatus, true)) {
            set_flash('error', 'Status laporan tidak valid.');
        } else {
            $stmt = mysqli_prepare($koneksi, 'UPDATE reports SET status = ? WHERE id = ?');
            mysqli_stmt_bind_param($stmt, 'si', $newStatus, $reportId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            set_flash('success', 'Status laporan #' . $reportId . ' diperbarui menjadi "' . $newStatus . '".');
        }
        header('Location: ' . $redirectTarget);
        exit;
    }

    if ($action === 'delete' && $reportId > 0) {
        $stmt = mysqli_prepare($koneksi, 'DELETE FROM reports WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $reportId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        set_flash('success', 'Laporan #' . $reportId . ' telah dihapus.');
        header('Location: ' . $redirectTarget);
        exit;
    }

    set_flash('error', 'Aksi tidak dikenali.');
    header('Location: ' . $redirectTarget);
    exit;
}

// Hitungan per status untuk quick tabs
$tabCountsRow = mysqli_fetch_assoc(
    mysqli_query(
        $koneksi,
        "SELECT
            COUNT(*) AS all_cnt,
            COALESCE(SUM(status = 'baru'), 0) AS baru_cnt,
            COALESCE(SUM(status = 'diproses'), 0) AS diproses_cnt,
            COALESCE(SUM(status = 'selesai'), 0) AS selesai_cnt
         FROM reports"
    )
);

// Daftar opsi filter jenis sampah
$typeOptions = array();
$catRes = mysqli_query($koneksi, 'SELECT name FROM categories ORDER BY sort_order ASC, id ASC');
while ($cRow = mysqli_fetch_assoc($catRes)) {
    $typeOptions[$cRow['name']] = $cRow['name'];
}
$typeOptions['Campuran'] = 'Campuran';
$repTypesRes = mysqli_query($koneksi, 'SELECT DISTINCT waste_type FROM reports ORDER BY waste_type ASC');
while ($rtRow = mysqli_fetch_assoc($repTypesRes)) {
    $typeOptions[$rtRow['waste_type']] = $rtRow['waste_type'];
}

// Hitung total hasil terfilter untuk pagination
$cntSql = 'SELECT COUNT(*) AS total FROM reports' . $filter['where_sql'];
$cntStmt = mysqli_prepare($koneksi, $cntSql);
stmt_bind_params($cntStmt, $filter['bind_types'], $filter['bind_params']);
mysqli_stmt_execute($cntStmt);
$cntRow = mysqli_fetch_assoc(mysqli_stmt_get_result($cntStmt));
mysqli_stmt_close($cntStmt);

$totalRows = (int)$cntRow['total'];
$totalPages = max(1, (int)ceil($totalRows / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
}
$offset = ($page - 1) * $perPage;

$sql = 'SELECT id, waste_type, location, notes, status, created_at, updated_at
        FROM reports' . $filter['where_sql'] . '
        ORDER BY created_at DESC, id DESC
        LIMIT ? OFFSET ?';
$pageTypes = $filter['bind_types'] . 'ii';
$pageParams = $filter['bind_params'];
$pageParams[] = $perPage;
$pageParams[] = $offset;

$stmt = mysqli_prepare($koneksi, $sql);
stmt_bind_params($stmt, $pageTypes, $pageParams);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

$reports = array();
while ($row = mysqli_fetch_assoc($res)) {
    $reports[] = $row;
}
mysqli_stmt_close($stmt);

$exportHref = 'export_reports.php' . ($filter['query_string'] !== '' ? '?' . $filter['query_string'] : '');
$postAction = $redirectTarget;

function build_reports_url($overrides = array())
{
    global $filter;
    $params = array();
    if ($filter['status'] !== '') {
        $params['status'] = $filter['status'];
    }
    if ($filter['type'] !== '') {
        $params['type'] = $filter['type'];
    }
    if ($filter['q'] !== '') {
        $params['q'] = $filter['q'];
    }
    foreach ($overrides as $k => $v) {
        if ($v === '' || $v === null || ($k === 'page' && (int)$v <= 1)) {
            unset($params[$k]);
        } else {
            $params[$k] = $v;
        }
    }
    $q = http_build_query($params);
    return 'reports.php' . ($q !== '' ? '?' . $q : '');
}

$pageTitle = 'Manajemen Laporan Sampah';
$activeNav = 'reports';
include __DIR__ . '/_layout_top.php';
?>

<div class="page-head">
  <div>
    <h1>Manajemen Laporan Sampah Liar</h1>
    <p class="page-sub">Filter, perbarui status penanganan (baru &rarr; diproses &rarr; selesai), atau ekspor rekap CSV.</p>
  </div>
  <div class="head-actions">
    <a href="<?php echo e($exportHref); ?>" class="btn btn-ghost">Export CSV<?php echo $filter['query_string'] !== '' ? ' (Sesuai Filter)' : ''; ?></a>
  </div>
</div>

<section class="panel-card">
  <div class="tabs" role="navigation" aria-label="Filter cepat status laporan">
    <a href="<?php echo e(build_reports_url(array('status' => '', 'page' => 1))); ?>" class="tab-link <?php echo $filter['status'] === '' ? 'active' : ''; ?>">
      Semua <span class="tab-count">(<?php echo (int)$tabCountsRow['all_cnt']; ?>)</span>
    </a>
    <a href="<?php echo e(build_reports_url(array('status' => 'baru', 'page' => 1))); ?>" class="tab-link <?php echo $filter['status'] === 'baru' ? 'active' : ''; ?>">
      Baru <span class="tab-count">(<?php echo (int)$tabCountsRow['baru_cnt']; ?>)</span>
    </a>
    <a href="<?php echo e(build_reports_url(array('status' => 'diproses', 'page' => 1))); ?>" class="tab-link <?php echo $filter['status'] === 'diproses' ? 'active' : ''; ?>">
      Diproses <span class="tab-count">(<?php echo (int)$tabCountsRow['diproses_cnt']; ?>)</span>
    </a>
    <a href="<?php echo e(build_reports_url(array('status' => 'selesai', 'page' => 1))); ?>" class="tab-link <?php echo $filter['status'] === 'selesai' ? 'active' : ''; ?>">
      Selesai <span class="tab-count">(<?php echo (int)$tabCountsRow['selesai_cnt']; ?>)</span>
    </a>
  </div>

  <form method="get" action="reports.php" class="filter-bar">
    <label class="field">
      Status
      <select name="status">
        <option value="">Semua Status</option>
        <option value="baru" <?php echo $filter['status'] === 'baru' ? 'selected' : ''; ?>>Baru</option>
        <option value="diproses" <?php echo $filter['status'] === 'diproses' ? 'selected' : ''; ?>>Diproses</option>
        <option value="selesai" <?php echo $filter['status'] === 'selesai' ? 'selected' : ''; ?>>Selesai</option>
      </select>
    </label>
    <label class="field">
      Jenis Sampah
      <select name="type">
        <option value="">Semua Jenis</option>
        <?php foreach ($typeOptions as $tOpt): ?>
          <option value="<?php echo e($tOpt); ?>" <?php echo $filter['type'] === $tOpt ? 'selected' : ''; ?>><?php echo e($tOpt); ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label class="field">
      Cari Lokasi / Keterangan
      <input type="search" name="q" value="<?php echo e($filter['q']); ?>" placeholder="Contoh: Grendeng, Unsoed">
    </label>
    <div class="inline-actions">
      <button type="submit" class="btn">Terapkan Filter</button>
      <?php if ($filter['query_string'] !== ''): ?>
        <a href="reports.php" class="btn btn-ghost">Reset</a>
      <?php endif; ?>
    </div>
  </form>

  <p class="muted mb-sm">
    Menampilkan <strong class="num"><?php echo count($reports); ?></strong> dari <strong class="num"><?php echo $totalRows; ?></strong> laporan<?php echo $totalPages > 1 ? ' (halaman ' . $page . ' dari ' . $totalPages . ')' : ''; ?>.
  </p>

  <?php if (empty($reports)): ?>
    <div class="empty-state">
      <p>Tidak ada laporan yang cocok dengan filter saat ini.</p>
      <?php if ($filter['query_string'] !== ''): ?>
        <a href="reports.php" class="btn btn-sm btn-ghost">Reset Filter</a>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="resp-table">
        <thead>
          <tr>
            <th scope="col">ID</th>
            <th scope="col">Jenis</th>
            <th scope="col">Lokasi &amp; Keterangan</th>
            <th scope="col">Status</th>
            <th scope="col">Masuk</th>
            <th scope="col">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($reports as $rep): ?>
            <?php
              $locText = (string)$rep['location'];
              $locPreview = str_len($locText) > 60 ? str_sub($locText, 0, 60) . '...' : $locText;
              $deleteConfirm = 'Hapus laporan #' . (int)$rep['id'] . ' (' . $rep['waste_type'] . ' di ' . $locPreview . ') secara permanen?';
            ?>
            <tr>
              <td data-label="ID" class="num">#<?php echo (int)$rep['id']; ?></td>
              <td data-label="Jenis"><strong><?php echo e($rep['waste_type']); ?></strong></td>
              <td data-label="Lokasi" class="cell-wrap">
                <div><strong><?php echo e($rep['location']); ?></strong></div>
                <?php if (trim((string)$rep['notes']) !== ''): ?>
                  <div class="muted mt-sm"><?php echo e($rep['notes']); ?></div>
                <?php endif; ?>
              </td>
              <td data-label="Status">
                <span class="badge badge-<?php echo e($rep['status']); ?>"><?php echo e(ucfirst($rep['status'])); ?></span>
              </td>
              <td data-label="Masuk" class="muted text-nowrap"><?php echo e(format_datetime($rep['created_at'])); ?></td>
              <td data-label="Aksi" class="cell-actions">
                <div class="inline-actions">
                  <form method="post" action="<?php echo e($postAction); ?>" class="inline-form">
                    <input type="hidden" name="csrf_token" value="<?php echo e($csrf); ?>">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="id" value="<?php echo (int)$rep['id']; ?>">
                    <select name="status" aria-label="Ubah status laporan #<?php echo (int)$rep['id']; ?>">
                      <option value="baru" <?php echo $rep['status'] === 'baru' ? 'selected' : ''; ?>>Baru</option>
                      <option value="diproses" <?php echo $rep['status'] === 'diproses' ? 'selected' : ''; ?>>Diproses</option>
                      <option value="selesai" <?php echo $rep['status'] === 'selesai' ? 'selected' : ''; ?>>Selesai</option>
                    </select>
                    <button type="submit" class="btn btn-sm">Simpan</button>
                  </form>
                  <div class="danger-slot">
                    <form method="post" action="<?php echo e($postAction); ?>" class="inline-form" data-confirm="<?php echo e($deleteConfirm); ?>">
                      <input type="hidden" name="csrf_token" value="<?php echo e($csrf); ?>">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?php echo (int)$rep['id']; ?>">
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

    <?php if ($totalPages > 1): ?>
      <nav class="pagination" aria-label="Navigasi halaman laporan">
        <span class="muted">Halaman <?php echo $page; ?> dari <?php echo $totalPages; ?></span>
        <div class="page-links">
          <?php if ($page > 1): ?>
            <a class="page-btn" href="<?php echo e(build_reports_url(array('page' => $page - 1))); ?>">&laquo; Sebelumnya</a>
          <?php endif; ?>
          <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <a class="page-btn <?php echo $p === $page ? 'active' : ''; ?>" href="<?php echo e(build_reports_url(array('page' => $p))); ?>"<?php echo $p === $page ? ' aria-current="page"' : ''; ?>><?php echo $p; ?></a>
          <?php endfor; ?>
          <?php if ($page < $totalPages): ?>
            <a class="page-btn" href="<?php echo e(build_reports_url(array('page' => $page + 1))); ?>">Berikutnya &raquo;</a>
          <?php endif; ?>
        </div>
      </nav>
    <?php endif; ?>
  <?php endif; ?>
</section>

<?php include __DIR__ . '/_layout_bottom.php'; ?>
