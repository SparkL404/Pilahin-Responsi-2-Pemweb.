<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/koneksi.php';

require_admin();

$formError = '';
$fieldErrors = array();
$form = array(
    'id' => 0,
    'name' => '',
    'slug' => '',
    'color' => '#3E9B4F',
    'bin' => '',
    'examples' => '',
    'sort_order' => 0,
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (!verify_csrf($token)) {
        set_flash('error', 'Token keamanan tidak valid.');
        header('Location: categories.php');
        exit;
    }

    $action = isset($_POST['action']) ? $_POST['action'] : '';

    if ($action === 'delete') {
        $catId = filter_var(isset($_POST['id']) ? $_POST['id'] : 0, FILTER_VALIDATE_INT);
        if ($catId > 0) {
            $cntStmt = mysqli_prepare($koneksi, 'SELECT COUNT(*) AS cnt FROM items WHERE category_id = ?');
            mysqli_stmt_bind_param($cntStmt, 'i', $catId);
            mysqli_stmt_execute($cntStmt);
            $cntRow = mysqli_fetch_assoc(mysqli_stmt_get_result($cntStmt));
            mysqli_stmt_close($cntStmt);

            if ((int)$cntRow['cnt'] > 0) {
                set_flash('warning', 'Kategori tidak dapat dihapus karena masih memiliki ' . (int)$cntRow['cnt'] . ' kata kunci terkait. Pindahkan atau hapus kata kuncinya terlebih dahulu.');
            } else {
                $delStmt = mysqli_prepare($koneksi, 'DELETE FROM categories WHERE id = ?');
                mysqli_stmt_bind_param($delStmt, 'i', $catId);
                mysqli_stmt_execute($delStmt);
                mysqli_stmt_close($delStmt);
                set_flash('success', 'Kategori berhasil dihapus.');
            }
        }
        header('Location: categories.php');
        exit;
    }

    if ($action === 'save') {
        $form['id'] = (int)(filter_var(isset($_POST['id']) ? $_POST['id'] : 0, FILTER_VALIDATE_INT) ?: 0);
        $form['name'] = trim((string)(isset($_POST['name']) ? $_POST['name'] : ''));
        $rawSlug = trim((string)(isset($_POST['slug']) ? $_POST['slug'] : ''));
        $form['slug'] = slugify($rawSlug !== '' ? $rawSlug : $form['name']);
        $form['color'] = strtoupper(trim((string)(isset($_POST['color']) ? $_POST['color'] : '#3E9B4F')));
        $form['bin'] = trim((string)(isset($_POST['bin']) ? $_POST['bin'] : ''));
        $form['examples'] = trim((string)(isset($_POST['examples']) ? $_POST['examples'] : ''));
        $form['sort_order'] = (int)(isset($_POST['sort_order']) ? $_POST['sort_order'] : 0);

        if ($form['name'] === '' || str_len($form['name']) > 100) {
            $fieldErrors['name'] = 'Nama kategori wajib diisi (maks. 100 karakter).';
        }
        if ($form['slug'] === '' || str_len($form['slug']) > 50) {
            $fieldErrors['slug'] = 'Kode URL kategori wajib valid (huruf kecil, angka, tanda hubung, maks. 50 karakter).';
        }
        if (!preg_match('/^#[0-9A-F]{6}$/', $form['color'])) {
            $fieldErrors['color'] = 'Pilih warna tempat sampah yang valid.';
        }
        if ($form['bin'] === '' || str_len($form['bin']) > 80) {
            $fieldErrors['bin'] = 'Label warna tempat sampah wajib diisi (maks. 80 karakter).';
        }
        if ($form['examples'] === '' || str_len($form['examples']) > 500) {
            $fieldErrors['examples'] = 'Contoh sampah wajib diisi (maks. 500 karakter).';
        }

        if (!empty($fieldErrors)) {
            $formError = 'Periksa kembali kolom yang ditandai merah di bawah.';
        } else {
            try {
                if ($form['id'] > 0) {
                    $stmt = mysqli_prepare(
                        $koneksi,
                        'UPDATE categories SET slug = ?, name = ?, color = ?, bin = ?, examples = ?, sort_order = ? WHERE id = ?'
                    );
                    mysqli_stmt_bind_param(
                        $stmt,
                        'sssssii',
                        $form['slug'],
                        $form['name'],
                        $form['color'],
                        $form['bin'],
                        $form['examples'],
                        $form['sort_order'],
                        $form['id']
                    );
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                    set_flash('success', 'Kategori "' . $form['name'] . '" berhasil diperbarui.');
                } else {
                    $stmt = mysqli_prepare(
                        $koneksi,
                        'INSERT INTO categories (slug, name, color, bin, examples, sort_order) VALUES (?, ?, ?, ?, ?, ?)'
                    );
                    mysqli_stmt_bind_param(
                        $stmt,
                        'sssssi',
                        $form['slug'],
                        $form['name'],
                        $form['color'],
                        $form['bin'],
                        $form['examples'],
                        $form['sort_order']
                    );
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                    set_flash('success', 'Kategori "' . $form['name'] . '" berhasil ditambahkan.');
                }
                header('Location: categories.php');
                exit;
            } catch (mysqli_sql_exception $e) {
                if ((int)$e->getCode() === 1062) {
                    $formError = 'Nama atau kode URL kategori sudah terdaftar. Gunakan nilai lain.';
                    $fieldErrors['name'] = 'Pastikan nama kategori belum digunakan.';
                    $fieldErrors['slug'] = 'Pastikan kode URL belum digunakan.';
                } else {
                    $formError = 'Gagal menyimpan kategori ke database.';
                }
            }
        }
    }
} elseif (isset($_GET['edit'])) {
    $editId = filter_var($_GET['edit'], FILTER_VALIDATE_INT);
    if ($editId > 0) {
        $stmt = mysqli_prepare($koneksi, 'SELECT id, slug, name, color, bin, examples, sort_order FROM categories WHERE id = ? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 'i', $editId);
        mysqli_stmt_execute($stmt);
        $editRow = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
        if ($editRow) {
            $form = array(
                'id' => (int)$editRow['id'],
                'name' => $editRow['name'],
                'slug' => $editRow['slug'],
                'color' => $editRow['color'],
                'bin' => $editRow['bin'],
                'examples' => $editRow['examples'],
                'sort_order' => (int)$editRow['sort_order'],
            );
        }
    }
}

$categories = array();
$res = mysqli_query(
    $koneksi,
    'SELECT c.id, c.slug, c.name, c.color, c.bin, c.examples, c.sort_order, COUNT(i.id) AS item_count
     FROM categories c
     LEFT JOIN items i ON i.category_id = c.id
     GROUP BY c.id
     ORDER BY c.sort_order ASC, c.id ASC'
);
while ($row = mysqli_fetch_assoc($res)) {
    $categories[] = $row;
}

$isEdit = $form['id'] > 0;
$openForm = ($isEdit || $formError !== '');
$pageTitle = 'Manajemen Kategori Tempat Sampah';
$activeNav = 'categories';
include __DIR__ . '/_layout_top.php';
?>

<div class="page-head">
  <div>
    <h1>Manajemen Kategori Tempat Sampah</h1>
    <p class="page-sub">Atur jenis tempat sampah, warna ikon, label bin, dan contoh sampah yang tampil di halaman utama.</p>
  </div>
</div>

<details class="form-disclosure" id="cat-form"<?php echo $openForm ? ' open' : ''; ?>>
  <summary class="btn"><?php echo $isEdit ? 'Edit Kategori: ' . e($form['name']) : '+ Tambah Kategori Baru'; ?></summary>
  <section class="panel-card">
    <div class="panel-head">
      <h2><?php echo $isEdit ? 'Edit Kategori: ' . e($form['name']) : 'Tambah Kategori Baru'; ?></h2>
      <?php if ($isEdit): ?>
        <a href="categories.php" class="btn btn-sm btn-ghost">Batal Edit</a>
      <?php endif; ?>
    </div>

    <?php if ($formError !== ''): ?>
      <div class="flash flash-error" role="alert"><?php echo e($formError); ?></div>
    <?php endif; ?>

    <form method="post" action="categories.php" class="form-grid">
      <input type="hidden" name="csrf_token" value="<?php echo e($csrf); ?>">
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" value="<?php echo (int)$form['id']; ?>">

      <label class="field">
        Nama Kategori
        <input
          type="text"
          name="name"
          maxlength="100"
          required
          value="<?php echo e($form['name']); ?>"
          placeholder="Contoh: Kaca"
          <?php if (isset($fieldErrors['name'])): ?>aria-invalid="true" aria-describedby="err-cat-name"<?php endif; ?>
        >
        <?php if (isset($fieldErrors['name'])): ?>
          <small class="field-error" id="err-cat-name"><?php echo e($fieldErrors['name']); ?></small>
        <?php endif; ?>
      </label>

      <label class="field">
        Kode URL (opsional, dibuat otomatis)
        <input
          type="text"
          name="slug"
          maxlength="50"
          value="<?php echo e($form['slug']); ?>"
          placeholder="contoh: kaca"
          <?php if (isset($fieldErrors['slug'])): ?>aria-invalid="true" aria-describedby="err-cat-slug"<?php endif; ?>
        >
        <?php if (isset($fieldErrors['slug'])): ?>
          <small class="field-error" id="err-cat-slug"><?php echo e($fieldErrors['slug']); ?></small>
        <?php endif; ?>
      </label>

      <label class="field">
        Warna Tempat Sampah
        <div class="color-picker-wrap">
          <input
            type="color"
            name="color"
            value="<?php echo e($form['color']); ?>"
            required
            data-color-output="cat-color-hex"
            <?php if (isset($fieldErrors['color'])): ?>aria-invalid="true" aria-describedby="err-cat-color"<?php endif; ?>
          >
          <span class="color-hex" id="cat-color-hex"><?php echo e($form['color']); ?></span>
        </div>
        <?php if (isset($fieldErrors['color'])): ?>
          <small class="field-error" id="err-cat-color"><?php echo e($fieldErrors['color']); ?></small>
        <?php endif; ?>
      </label>

      <label class="field">
        Label Tempat Sampah
        <input
          type="text"
          name="bin"
          maxlength="80"
          required
          value="<?php echo e($form['bin']); ?>"
          placeholder="contoh: putih / hijau"
          <?php if (isset($fieldErrors['bin'])): ?>aria-invalid="true" aria-describedby="err-cat-bin"<?php endif; ?>
        >
        <?php if (isset($fieldErrors['bin'])): ?>
          <small class="field-error" id="err-cat-bin"><?php echo e($fieldErrors['bin']); ?></small>
        <?php endif; ?>
      </label>

      <label class="field">
        Urutan Tampil
        <input type="number" name="sort_order" value="<?php echo (int)$form['sort_order']; ?>" min="0" max="999">
      </label>

      <label class="field form-full">
        Contoh Sampah
        <input
          type="text"
          name="examples"
          maxlength="500"
          required
          value="<?php echo e($form['examples']); ?>"
          placeholder="Contoh: Botol sirup, gelas kaca, toples kaca"
          <?php if (isset($fieldErrors['examples'])): ?>aria-invalid="true" aria-describedby="err-cat-examples"<?php endif; ?>
        >
        <?php if (isset($fieldErrors['examples'])): ?>
          <small class="field-error" id="err-cat-examples"><?php echo e($fieldErrors['examples']); ?></small>
        <?php endif; ?>
      </label>

      <div class="form-full inline-actions">
        <button type="submit" class="btn"><?php echo $isEdit ? 'Simpan Perubahan' : 'Tambah Kategori'; ?></button>
        <?php if ($isEdit): ?>
          <a href="categories.php" class="btn btn-ghost">Batal</a>
        <?php endif; ?>
      </div>
    </form>
  </section>
</details>

<section class="panel-card">
  <div class="panel-head">
    <h2>Daftar Kategori (<?php echo count($categories); ?>)</h2>
  </div>
  <?php if (empty($categories)): ?>
    <div class="empty-state">
      <p>Belum ada kategori tempat sampah tersimpan.</p>
    </div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="resp-table">
        <thead>
          <tr>
            <th scope="col">Urut</th>
            <th scope="col">Kategori</th>
            <th scope="col">Kode</th>
            <th scope="col">Tempat Sampah</th>
            <th scope="col">Contoh</th>
            <th scope="col">Kata Kunci</th>
            <th scope="col">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($categories as $cat): ?>
            <?php $itemCount = (int)$cat['item_count']; ?>
            <tr>
              <td data-label="Urut" class="num"><?php echo (int)$cat['sort_order']; ?></td>
              <td data-label="Kategori">
                <span class="cat-chip">
                  <i class="cat-dot" style="--dot-color:<?php echo e($cat['color']); ?>" aria-hidden="true"></i>
                  <?php echo e($cat['name']); ?>
                </span>
              </td>
              <td data-label="Kode" class="muted"><code><?php echo e($cat['slug']); ?></code></td>
              <td data-label="Bin"><?php echo e($cat['bin']); ?></td>
              <td data-label="Contoh" class="cell-wrap"><?php echo e($cat['examples']); ?></td>
              <td data-label="Kata Kunci"><a href="items.php?category_id=<?php echo (int)$cat['id']; ?>" class="row-link num"><?php echo $itemCount; ?> kata</a></td>
              <td data-label="Aksi" class="cell-actions">
                <div class="inline-actions">
                  <a href="categories.php?edit=<?php echo (int)$cat['id']; ?>#cat-form" class="btn btn-sm btn-ghost">Edit</a>
                  <div class="danger-slot">
                    <?php if ($itemCount > 0): ?>
                      <button
                        type="button"
                        class="btn btn-sm btn-danger"
                        disabled
                        aria-disabled="true"
                        title="Pindahkan atau hapus <?php echo $itemCount; ?> kata kunci terkait terlebih dahulu"
                      >Hapus</button>
                    <?php else: ?>
                      <form method="post" action="categories.php" class="inline-form" data-confirm="<?php echo e('Hapus kategori "' . $cat['name'] . '" secara permanen?'); ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo e($csrf); ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo (int)$cat['id']; ?>">
                        <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                      </form>
                    <?php endif; ?>
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
