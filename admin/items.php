<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/koneksi.php';

require_admin();

// Daftar kategori untuk dropdown & validasi
$categories = array();
$categoryMap = array();
$catRes = mysqli_query($koneksi, 'SELECT id, name, color FROM categories ORDER BY sort_order ASC, id ASC');
while ($cRow = mysqli_fetch_assoc($catRes)) {
    $cid = (int)$cRow['id'];
    $categories[] = $cRow;
    $categoryMap[$cid] = $cRow;
}

$tab = (isset($_GET['tab']) && $_GET['tab'] === 'misses') ? 'misses' : 'dictionary';

// Parameter filter & pagination kamus kata kunci
$filterCat = filter_var(isset($_GET['category_id']) ? $_GET['category_id'] : 0, FILTER_VALIDATE_INT);
$filterCat = $filterCat > 0 ? $filterCat : 0;
$filterQ = trim((string)(isset($_GET['q']) ? $_GET['q'] : ''));
$perPage = 20;
$pageRaw = filter_var(isset($_GET['page']) ? $_GET['page'] : 1, FILTER_VALIDATE_INT);
$page = ($pageRaw !== false && $pageRaw > 0) ? (int)$pageRaw : 1;

function build_items_url($overrides = array())
{
    global $tab, $filterCat, $filterQ, $page;
    $params = array();
    if ($tab === 'misses') {
        $params['tab'] = 'misses';
    }
    if ($filterCat > 0) {
        $params['category_id'] = $filterCat;
    }
    if ($filterQ !== '') {
        $params['q'] = $filterQ;
    }
    if ($page > 1 && $tab === 'dictionary') {
        $params['page'] = $page;
    }

    foreach ($overrides as $k => $v) {
        if ($v === '' || $v === null || ($k === 'category_id' && (int)$v <= 0) || ($k === 'page' && (int)$v <= 1) || ($k === 'tab' && $v === 'dictionary')) {
            unset($params[$k]);
        } else {
            $params[$k] = $v;
        }
    }

    if (isset($params['tab']) && $params['tab'] === 'misses') {
        unset($params['category_id'], $params['q'], $params['page']);
    }

    $q = http_build_query($params);
    return 'items.php' . ($q !== '' ? '?' . $q : '');
}

$dictParams = array();
if ($filterCat > 0) {
    $dictParams['category_id'] = $filterCat;
}
if ($filterQ !== '') {
    $dictParams['q'] = $filterQ;
}
$dictFilterQs = http_build_query($dictParams);

$redirectTarget = build_items_url();
$missesActionUrl = 'items.php?tab=misses';
$dictActionUrl = build_items_url(array('tab' => 'dictionary'));

$formError = '';
$fieldErrors = array();
$form = array(
    'id' => 0,
    'keyword' => '',
    'category_id' => 0,
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (!verify_csrf($token)) {
        set_flash('error', 'Token keamanan tidak valid.');
        header('Location: ' . $redirectTarget);
        exit;
    }

    $action = isset($_POST['action']) ? $_POST['action'] : '';

    if ($action === 'promote_miss') {
        $missId = filter_var(isset($_POST['miss_id']) ? $_POST['miss_id'] : 0, FILTER_VALIDATE_INT);
        $catId = filter_var(isset($_POST['category_id']) ? $_POST['category_id'] : 0, FILTER_VALIDATE_INT);

        if ($missId <= 0 || !isset($categoryMap[$catId])) {
            set_flash('error', 'Pilih kategori tujuan yang valid sebelum menambahkan.');
            header('Location: ' . $missesActionUrl);
            exit;
        }

        $mStmt = mysqli_prepare($koneksi, 'SELECT id, query_text FROM search_misses WHERE id = ? LIMIT 1');
        mysqli_stmt_bind_param($mStmt, 'i', $missId);
        mysqli_stmt_execute($mStmt);
        $missRow = mysqli_fetch_assoc(mysqli_stmt_get_result($mStmt));
        mysqli_stmt_close($mStmt);

        if (!$missRow) {
            set_flash('error', 'Data saran pencarian tidak ditemukan.');
            header('Location: ' . $missesActionUrl);
            exit;
        }

        $kw = str_lower(trim($missRow['query_text']));

        $chkStmt = mysqli_prepare($koneksi, 'SELECT id FROM items WHERE LOWER(keyword) = ? LIMIT 1');
        mysqli_stmt_bind_param($chkStmt, 's', $kw);
        mysqli_stmt_execute($chkStmt);
        $existingItem = mysqli_fetch_assoc(mysqli_stmt_get_result($chkStmt));
        mysqli_stmt_close($chkStmt);

        if (!$existingItem) {
            $insStmt = mysqli_prepare($koneksi, 'INSERT INTO items (keyword, category_id) VALUES (?, ?)');
            mysqli_stmt_bind_param($insStmt, 'si', $kw, $catId);
            mysqli_stmt_execute($insStmt);
            mysqli_stmt_close($insStmt);
            $flashMsg = 'Kata kunci "' . $kw . '" berhasil ditambahkan ke kategori ' . $categoryMap[$catId]['name'] . '.';
        } else {
            $flashMsg = 'Kata kunci "' . $kw . '" sudah ada di kamus; status saran ditandai selesai.';
        }

        $resStmt = mysqli_prepare($koneksi, "UPDATE search_misses SET status = 'resolved' WHERE id = ?");
        mysqli_stmt_bind_param($resStmt, 'i', $missId);
        mysqli_stmt_execute($resStmt);
        mysqli_stmt_close($resStmt);

        set_flash('success', $flashMsg);
        header('Location: ' . $missesActionUrl);
        exit;
    }

    if ($action === 'ignore_miss') {
        $missId = filter_var(isset($_POST['miss_id']) ? $_POST['miss_id'] : 0, FILTER_VALIDATE_INT);
        if ($missId > 0) {
            $igStmt = mysqli_prepare($koneksi, "UPDATE search_misses SET status = 'ignored' WHERE id = ?");
            mysqli_stmt_bind_param($igStmt, 'i', $missId);
            mysqli_stmt_execute($igStmt);
            mysqli_stmt_close($igStmt);
            set_flash('success', 'Saran kata kunci dipindahkan ke daftar diabaikan.');
        }
        header('Location: ' . $missesActionUrl);
        exit;
    }

    if ($action === 'restore_miss') {
        $missId = filter_var(isset($_POST['miss_id']) ? $_POST['miss_id'] : 0, FILTER_VALIDATE_INT);
        if ($missId > 0) {
            $rsStmt = mysqli_prepare($koneksi, "UPDATE search_misses SET status = 'pending' WHERE id = ?");
            mysqli_stmt_bind_param($rsStmt, 'i', $missId);
            mysqli_stmt_execute($rsStmt);
            mysqli_stmt_close($rsStmt);
            set_flash('success', 'Saran kata kunci dipulihkan ke daftar antrean.');
        }
        header('Location: ' . $missesActionUrl);
        exit;
    }

    if ($action === 'delete_item') {
        $itemId = filter_var(isset($_POST['id']) ? $_POST['id'] : 0, FILTER_VALIDATE_INT);
        if ($itemId > 0) {
            $delStmt = mysqli_prepare($koneksi, 'DELETE FROM items WHERE id = ?');
            mysqli_stmt_bind_param($delStmt, 'i', $itemId);
            mysqli_stmt_execute($delStmt);
            mysqli_stmt_close($delStmt);
            set_flash('success', 'Kata kunci berhasil dihapus.');
        }
        header('Location: ' . $dictActionUrl);
        exit;
    }

    if ($action === 'save_item') {
        $tab = 'dictionary';
        $form['id'] = (int)(filter_var(isset($_POST['id']) ? $_POST['id'] : 0, FILTER_VALIDATE_INT) ?: 0);
        $form['keyword'] = str_lower(trim((string)(isset($_POST['keyword']) ? $_POST['keyword'] : '')));
        $form['category_id'] = (int)(filter_var(isset($_POST['category_id']) ? $_POST['category_id'] : 0, FILTER_VALIDATE_INT) ?: 0);

        if ($form['keyword'] === '' || str_len($form['keyword']) > 100) {
            $fieldErrors['keyword'] = 'Kata kunci wajib diisi (maks. 100 karakter).';
        }
        if (!isset($categoryMap[$form['category_id']])) {
            $fieldErrors['category_id'] = 'Pilih kategori tujuan yang valid.';
        }

        if (!empty($fieldErrors)) {
            $formError = 'Periksa kembali kolom yang ditandai merah di bawah.';
        } else {
            try {
                if ($form['id'] > 0) {
                    $stmt = mysqli_prepare($koneksi, 'UPDATE items SET keyword = ?, category_id = ? WHERE id = ?');
                    mysqli_stmt_bind_param($stmt, 'sii', $form['keyword'], $form['category_id'], $form['id']);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                    $msg = 'Kata kunci "' . $form['keyword'] . '" berhasil diperbarui.';
                } else {
                    $stmt = mysqli_prepare($koneksi, 'INSERT INTO items (keyword, category_id) VALUES (?, ?)');
                    mysqli_stmt_bind_param($stmt, 'si', $form['keyword'], $form['category_id']);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                    $msg = 'Kata kunci "' . $form['keyword'] . '" berhasil ditambahkan.';
                }

                $syncStmt = mysqli_prepare($koneksi, "UPDATE search_misses SET status = 'resolved' WHERE query_text = ? AND status = 'pending'");
                mysqli_stmt_bind_param($syncStmt, 's', $form['keyword']);
                mysqli_stmt_execute($syncStmt);
                mysqli_stmt_close($syncStmt);

                set_flash('success', $msg);
                header('Location: ' . $dictActionUrl);
                exit;
            } catch (mysqli_sql_exception $e) {
                if ((int)$e->getCode() === 1062) {
                    $formError = 'Kata kunci "' . $form['keyword'] . '" sudah terdaftar di kamus.';
                    $fieldErrors['keyword'] = 'Gunakan kata kunci lain yang belum terdaftar.';
                } else {
                    $formError = 'Gagal menyimpan kata kunci ke database.';
                }
            }
        }
    }
} elseif (isset($_GET['edit'])) {
    $editId = filter_var($_GET['edit'], FILTER_VALIDATE_INT);
    if ($editId > 0) {
        $tab = 'dictionary';
        $stmt = mysqli_prepare($koneksi, 'SELECT id, keyword, category_id FROM items WHERE id = ? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 'i', $editId);
        mysqli_stmt_execute($stmt);
        $editRow = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
        if ($editRow) {
            $form = array(
                'id' => (int)$editRow['id'],
                'keyword' => $editRow['keyword'],
                'category_id' => (int)$editRow['category_id'],
            );
        }
    }
}

// Hitungan tab
$totalItemsAllRow = mysqli_fetch_assoc(mysqli_query($koneksi, 'SELECT COUNT(*) AS cnt FROM items'));
$totalItemsAll = (int)$totalItemsAllRow['cnt'];

// 1. Pending & ignored search misses
$pendingMisses = array();
$missRes = mysqli_query(
    $koneksi,
    "SELECT id, query_text, hit_count, last_searched_at
     FROM search_misses
     WHERE status = 'pending'
     ORDER BY hit_count DESC, last_searched_at DESC"
);
while ($mRow = mysqli_fetch_assoc($missRes)) {
    $pendingMisses[] = $mRow;
}

$ignoredMisses = array();
$ignRes = mysqli_query(
    $koneksi,
    "SELECT id, query_text, hit_count, last_searched_at
     FROM search_misses
     WHERE status = 'ignored'
     ORDER BY last_searched_at DESC
     LIMIT 30"
);
while ($ignRow = mysqli_fetch_assoc($ignRes)) {
    $ignoredMisses[] = $ignRow;
}

// 2. Daftar kata kunci dengan filter & pagination
$where = array();
$types = '';
$params = array();
if ($filterCat > 0) {
    $where[] = 'i.category_id = ?';
    $types .= 'i';
    $params[] = $filterCat;
}
if ($filterQ !== '') {
    $like = '%' . escape_like(str_lower($filterQ), '!') . '%';
    $where[] = "LOWER(i.keyword) LIKE ? ESCAPE '!'";
    $types .= 's';
    $params[] = $like;
}
$whereSql = !empty($where) ? (' WHERE ' . implode(' AND ', $where)) : '';

$cntSql = 'SELECT COUNT(*) AS total FROM items i' . $whereSql;
$cntStmt = mysqli_prepare($koneksi, $cntSql);
stmt_bind_params($cntStmt, $types, $params);
mysqli_stmt_execute($cntStmt);
$cntRow = mysqli_fetch_assoc(mysqli_stmt_get_result($cntStmt));
mysqli_stmt_close($cntStmt);

$totalFilteredItems = (int)$cntRow['total'];
$totalPages = max(1, (int)ceil($totalFilteredItems / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
}
$offset = ($page - 1) * $perPage;

$itemSql = 'SELECT i.id, i.keyword, i.category_id, i.created_at, c.name AS category_name, c.color AS category_color
            FROM items i
            INNER JOIN categories c ON c.id = i.category_id' . $whereSql . '
            ORDER BY i.id DESC
            LIMIT ? OFFSET ?';
$pageTypes = $types . 'ii';
$pageParams = $params;
$pageParams[] = $perPage;
$pageParams[] = $offset;

$itemStmt = mysqli_prepare($koneksi, $itemSql);
stmt_bind_params($itemStmt, $pageTypes, $pageParams);
mysqli_stmt_execute($itemStmt);
$itemRes = mysqli_stmt_get_result($itemStmt);
$items = array();
while ($iRow = mysqli_fetch_assoc($itemRes)) {
    $items[] = $iRow;
}
mysqli_stmt_close($itemStmt);

$isEdit = $form['id'] > 0;
$openForm = ($isEdit || $formError !== '');
$hasCategories = !empty($categories);
$pageTitle = 'Manajemen Kata Kunci & Saran Pencarian';
$activeNav = 'items';
include __DIR__ . '/_layout_top.php';
?>

<div class="page-head">
  <div>
    <h1>Kata Kunci &amp; Saran Pencarian Warga</h1>
    <p class="page-sub">Kelola kamus pencarian sampah dan tambahkan barang yang sering dicari warga namun belum terdaftar.</p>
  </div>
</div>

<div class="tabs" role="navigation" aria-label="Bagian kata kunci">
  <a href="items.php" class="tab-link <?php echo $tab === 'dictionary' ? 'active' : ''; ?>">
    Kamus Kata Kunci <span class="tab-count">(<?php echo $totalItemsAll; ?>)</span>
  </a>
  <a href="items.php?tab=misses" class="tab-link <?php echo $tab === 'misses' ? 'active' : ''; ?>">
    Saran Pencarian Warga <span class="tab-count">(<?php echo count($pendingMisses); ?>)</span>
  </a>
</div>

<?php if ($tab === 'misses'): ?>
  <section class="panel-card">
    <div class="panel-head">
      <h2>Saran dari Pencarian Belum Ditemukan (<?php echo count($pendingMisses); ?>)</h2>
      <span class="muted">Otomatis tercatat saat warga mencari barang yang belum ada</span>
    </div>
    <?php if (empty($pendingMisses)): ?>
      <div class="empty-state">
        <p>Semua saran kata kunci dari pencarian warga sudah ditinjau.</p>
        <a href="items.php" class="btn btn-sm btn-ghost">Lihat Kamus Kata Kunci</a>
      </div>
    <?php elseif (!$hasCategories): ?>
      <div class="flash flash-warning">Buat minimal satu kategori terlebih dahulu di menu Kategori sebelum mempromosikan kata kunci.</div>
    <?php else: ?>
      <div class="table-wrap">
        <table class="resp-table">
          <thead>
            <tr>
              <th scope="col">Kata Dicari Warga</th>
              <th scope="col">Frekuensi</th>
              <th scope="col">Terakhir Dicari</th>
              <th scope="col">Tambahkan ke Kamus</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($pendingMisses as $miss): ?>
              <tr>
                <td data-label="Kata Dicari" class="cell-wrap"><strong><?php echo e($miss['query_text']); ?></strong></td>
                <td data-label="Frekuensi"><span class="badge badge-miss num"><?php echo (int)$miss['hit_count']; ?>x</span></td>
                <td data-label="Terakhir" class="muted text-nowrap"><?php echo e(format_datetime($miss['last_searched_at'])); ?></td>
                <td data-label="Aksi" class="cell-actions">
                  <div class="inline-actions">
                    <form method="post" action="<?php echo e($missesActionUrl); ?>" class="inline-form">
                      <input type="hidden" name="csrf_token" value="<?php echo e($csrf); ?>">
                      <input type="hidden" name="action" value="promote_miss">
                      <input type="hidden" name="miss_id" value="<?php echo (int)$miss['id']; ?>">
                      <select name="category_id" required aria-label="Pilih kategori untuk <?php echo e($miss['query_text']); ?>">
                        <option value="" disabled selected>Pilih kategori…</option>
                        <?php foreach ($categories as $cat): ?>
                          <option value="<?php echo (int)$cat['id']; ?>"><?php echo e($cat['name']); ?></option>
                        <?php endforeach; ?>
                      </select>
                      <button type="submit" class="btn btn-sm btn-success">Tambahkan ke Kata Kunci</button>
                    </form>
                    <form
                      method="post"
                      action="<?php echo e($missesActionUrl); ?>"
                      class="inline-form"
                      data-confirm="<?php echo e('Abaikan saran "' . $miss['query_text'] . '"? Anda masih dapat memulihkannya nanti dari daftar Diabaikan.'); ?>"
                    >
                      <input type="hidden" name="csrf_token" value="<?php echo e($csrf); ?>">
                      <input type="hidden" name="action" value="ignore_miss">
                      <input type="hidden" name="miss_id" value="<?php echo (int)$miss['id']; ?>">
                      <button type="submit" class="btn btn-sm btn-ghost">Abaikan</button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

    <?php if (!empty($ignoredMisses)): ?>
      <details class="ignored-details">
        <summary>Lihat saran yang diabaikan (<?php echo count($ignoredMisses); ?>)</summary>
        <div class="table-wrap">
          <table class="resp-table">
            <thead>
              <tr>
                <th scope="col">Kata Dicari</th>
                <th scope="col">Frekuensi</th>
                <th scope="col">Terakhir Dicari</th>
                <th scope="col">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($ignoredMisses as $ign): ?>
                <tr>
                  <td data-label="Kata Dicari" class="cell-wrap"><strong><?php echo e($ign['query_text']); ?></strong></td>
                  <td data-label="Frekuensi"><span class="badge badge-inactive num"><?php echo (int)$ign['hit_count']; ?>x</span></td>
                  <td data-label="Terakhir" class="muted text-nowrap"><?php echo e(format_datetime($ign['last_searched_at'])); ?></td>
                  <td data-label="Aksi" class="cell-actions">
                    <form method="post" action="<?php echo e($missesActionUrl); ?>" class="inline-form">
                      <input type="hidden" name="csrf_token" value="<?php echo e($csrf); ?>">
                      <input type="hidden" name="action" value="restore_miss">
                      <input type="hidden" name="miss_id" value="<?php echo (int)$ign['id']; ?>">
                      <button type="submit" class="btn btn-sm btn-ghost">Pulihkan</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </details>
    <?php endif; ?>
  </section>
<?php else: ?>
  <details class="form-disclosure" id="item-form"<?php echo $openForm ? ' open' : ''; ?>>
    <summary class="btn"><?php echo $isEdit ? 'Edit Kata Kunci: ' . e($form['keyword']) : '+ Tambah Kata Kunci Baru'; ?></summary>
    <section class="panel-card">
      <div class="panel-head">
        <h2><?php echo $isEdit ? 'Edit Kata Kunci: ' . e($form['keyword']) : 'Tambah Kata Kunci Baru'; ?></h2>
        <?php if ($isEdit): ?>
          <a href="<?php echo e($dictActionUrl); ?>" class="btn btn-sm btn-ghost">Batal Edit</a>
        <?php endif; ?>
      </div>

      <?php if ($formError !== ''): ?>
        <div class="flash flash-error" role="alert"><?php echo e($formError); ?></div>
      <?php endif; ?>

      <?php if (!$hasCategories): ?>
        <div class="flash flash-warning">Belum ada kategori tersedia. Tambahkan kategori terlebih dahulu sebelum menambah kata kunci.</div>
      <?php else: ?>
        <form method="post" action="<?php echo e($dictActionUrl); ?>" class="form-grid">
          <input type="hidden" name="csrf_token" value="<?php echo e($csrf); ?>">
          <input type="hidden" name="action" value="save_item">
          <input type="hidden" name="id" value="<?php echo (int)$form['id']; ?>">

          <label class="field">
            Kata Kunci Barang
            <input
              type="text"
              name="keyword"
              maxlength="100"
              required
              value="<?php echo e($form['keyword']); ?>"
              placeholder="contoh: styrofoam, minyak jelantah"
              <?php if (isset($fieldErrors['keyword'])): ?>aria-invalid="true" aria-describedby="err-item-keyword"<?php endif; ?>
            >
            <?php if (isset($fieldErrors['keyword'])): ?>
              <small class="field-error" id="err-item-keyword"><?php echo e($fieldErrors['keyword']); ?></small>
            <?php endif; ?>
          </label>

          <label class="field">
            Kategori Tempat Sampah
            <select
              name="category_id"
              required
              <?php if (isset($fieldErrors['category_id'])): ?>aria-invalid="true" aria-describedby="err-item-category"<?php endif; ?>
            >
              <option value="" disabled <?php echo (int)$form['category_id'] === 0 ? 'selected' : ''; ?>>Pilih kategori…</option>
              <?php foreach ($categories as $cat): ?>
                <option value="<?php echo (int)$cat['id']; ?>" <?php echo (int)$form['category_id'] === (int)$cat['id'] ? 'selected' : ''; ?>>
                  <?php echo e($cat['name']); ?>
                </option>
              <?php endforeach; ?>
            </select>
            <?php if (isset($fieldErrors['category_id'])): ?>
              <small class="field-error" id="err-item-category"><?php echo e($fieldErrors['category_id']); ?></small>
            <?php endif; ?>
          </label>

          <div class="form-full inline-actions">
            <button type="submit" class="btn"><?php echo $isEdit ? 'Simpan Perubahan' : 'Tambah Kata Kunci'; ?></button>
            <?php if ($isEdit): ?>
              <a href="<?php echo e($dictActionUrl); ?>" class="btn btn-ghost">Batal</a>
            <?php endif; ?>
          </div>
        </form>
      <?php endif; ?>
    </section>
  </details>

  <section class="panel-card">
    <div class="panel-head">
      <h2>Daftar Kata Kunci Kamus (<?php echo $totalFilteredItems; ?>)</h2>
    </div>

    <form method="get" action="items.php" class="filter-bar">
      <label class="field">
        Filter Kategori
        <select name="category_id">
          <option value="0">Semua Kategori</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?php echo (int)$cat['id']; ?>" <?php echo $filterCat === (int)$cat['id'] ? 'selected' : ''; ?>>
              <?php echo e($cat['name']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>
      <label class="field">
        Cari Kata Kunci
        <input type="search" name="q" value="<?php echo e($filterQ); ?>" placeholder="Ketik kata kunci...">
      </label>
      <div class="inline-actions">
        <button type="submit" class="btn">Filter</button>
        <?php if ($dictFilterQs !== ''): ?>
          <a href="items.php" class="btn btn-ghost">Reset</a>
        <?php endif; ?>
      </div>
    </form>

    <p class="muted mb-sm">
      Menampilkan <strong class="num"><?php echo count($items); ?></strong> dari <strong class="num"><?php echo $totalFilteredItems; ?></strong> kata kunci<?php echo $totalPages > 1 ? ' (halaman ' . $page . ' dari ' . $totalPages . ')' : ''; ?>.
    </p>

    <?php if (empty($items)): ?>
      <div class="empty-state">
        <p>Belum ada kata kunci yang sesuai filter saat ini.</p>
        <?php if ($dictFilterQs !== ''): ?>
          <a href="items.php" class="btn btn-sm btn-ghost">Reset Filter</a>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <div class="table-wrap">
        <table class="resp-table">
          <thead>
            <tr>
              <th scope="col">ID</th>
              <th scope="col">Kata Kunci</th>
              <th scope="col">Kategori</th>
              <th scope="col">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($items as $it): ?>
              <?php
                $editHref = build_items_url(array('edit' => (int)$it['id'])) . '#item-form';
                $deleteMsg = 'Hapus kata kunci "' . $it['keyword'] . '" (kategori ' . $it['category_name'] . ')?';
              ?>
              <tr>
                <td data-label="ID" class="num">#<?php echo (int)$it['id']; ?></td>
                <td data-label="Kata Kunci" class="cell-wrap"><strong><?php echo e($it['keyword']); ?></strong></td>
                <td data-label="Kategori">
                  <span class="cat-chip">
                    <i class="cat-dot" style="--dot-color:<?php echo e($it['category_color']); ?>" aria-hidden="true"></i>
                    <?php echo e($it['category_name']); ?>
                  </span>
                </td>
                <td data-label="Aksi" class="cell-actions">
                  <div class="inline-actions">
                    <a href="<?php echo e($editHref); ?>" class="btn btn-sm btn-ghost">Edit</a>
                    <div class="danger-slot">
                      <form method="post" action="<?php echo e($dictActionUrl); ?>" class="inline-form" data-confirm="<?php echo e($deleteMsg); ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo e($csrf); ?>">
                        <input type="hidden" name="action" value="delete_item">
                        <input type="hidden" name="id" value="<?php echo (int)$it['id']; ?>">
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
        <nav class="pagination" aria-label="Navigasi halaman kata kunci">
          <span class="muted">Halaman <?php echo $page; ?> dari <?php echo $totalPages; ?></span>
          <div class="page-links">
            <?php if ($page > 1): ?>
              <a class="page-btn" href="<?php echo e(build_items_url(array('page' => $page - 1))); ?>">&laquo; Sebelumnya</a>
            <?php endif; ?>
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
              <a class="page-btn <?php echo $p === $page ? 'active' : ''; ?>" href="<?php echo e(build_items_url(array('page' => $p))); ?>"<?php echo $p === $page ? ' aria-current="page"' : ''; ?>><?php echo $p; ?></a>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
              <a class="page-btn" href="<?php echo e(build_items_url(array('page' => $page + 1))); ?>">Berikutnya &raquo;</a>
            <?php endif; ?>
          </div>
        </nav>
      <?php endif; ?>
    <?php endif; ?>
  </section>
<?php endif; ?>

<?php include __DIR__ . '/_layout_bottom.php'; ?>
