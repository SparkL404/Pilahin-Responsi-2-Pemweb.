<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="description" content="Pilahin membantu memilah sampah rumah tangga ke 6 tempat sampah berwarna, uji kebiasaan lewat kuis singkat, dan laporkan tumpukan sampah liar.">
<title>Pilahin - Panduan Pilah Sampah dari Rumah</title>
<link rel="icon" href="assets/img/cat-organik.svg" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@700;800&family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css?v=<?php echo filemtime(__DIR__ . '/style.css'); ?>">
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>

<main>
<section class="hero s1" id="home"><div class="wrap">
<div class="hero-top">
<div class="hero-main">
<h1>Sampahmu masuk tempat sampah yang mana?</h1>
<p class="lead">Ketik nama barangnya. Pilahin menunjukkan tempat sampah yang tepat, lengkap dengan warnanya.</p>
<div class="finder"><div class="finder-field"><input id="q" type="search" placeholder="Contoh: botol plastik, baterai, popok" aria-label="Cari jenis sampah" autocomplete="off" spellcheck="false" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="sug" aria-haspopup="listbox"><ul class="sug hidden" id="sug" role="listbox" aria-label="Saran kata kunci sampah"></ul></div><button class="btn" id="go" type="button">Cari</button></div>
<div class="chips" id="chips" role="group" aria-label="Contoh pencarian cepat"><span class="chips-label">Coba cepat:</span><button class="chip" type="button" data-q="baterai">Baterai</button><button class="chip" type="button" data-q="botol plastik">Botol plastik</button><button class="chip" type="button" data-q="kulit pisang">Kulit pisang</button><button class="chip" type="button" data-q="popok">Popok</button><button class="chip chip-shuffle" id="shuffle" type="button" aria-label="Acak contoh pencarian cepat">Acak <span class="shuffle-ico" aria-hidden="true">↻</span></button></div>
<div class="answer" id="ans" aria-live="polite">Hasil pencarian muncul di sini. Kamu juga bisa menekan salah satu tempat sampah di bawah.</div>
</div>
<figure class="hero-art" aria-hidden="true"><img src="assets/img/hero-sort.svg?v=<?php echo filemtime(__DIR__ . '/assets/img/hero-sort.svg'); ?>" alt="" width="420" height="320" decoding="async"></figure>
</div>
<div class="bins" id="bins" role="group" aria-label="Pilihan enam tempat sampah"></div>
</div></section>

<section class="cats s2" id="kategori"><div class="wrap">
<h2>Enam jenis, enam warna</h2>
<p class="lead">Pegangan dasar untuk memilah sampah di rumah.</p>
<div id="list" class="cat-list"></div>
</div></section>

<section id="kuis" class="s3"><div class="wrap">
<h2>Uji kebiasaan memilahmu</h2>
<p class="lead">Lima pertanyaan singkat. Langsung tahu jawabannya setelah memilih.</p>
<div class="panel section-panel" id="quiz"></div>
</div></section>

<section id="lapor" class="s4"><div class="wrap">
<h2>Lihat tumpukan sampah liar?</h2>
<p class="lead">Isi formulir ini untuk mencatat lokasinya.</p>
<p class="form-note">Laporan bersifat anonim (tanpa nama atau login) dan masuk ke antrean tindak lanjut pengelola.</p>
<div class="panel section-panel">
<form id="rf">
<label>Jenis sampah<select id="rj" required><option value="">Pilih jenis sampah</option><option value="Organik">Organik</option><option value="Plastik">Plastik</option><option value="Kertas">Kertas</option><option value="B3">B3 (baterai, lampu, obat)</option><option value="Residu">Residu (tisu kotor, popok)</option><option value="Elektronik">Elektronik (kabel, charger)</option><option value="Campuran">Campuran</option></select></label>
<label>Lokasi<input id="rl" required maxlength="200" placeholder="Nama jalan, RT/RW, patokan"></label>
<label><span>Keterangan <span class="opt-tag">(opsional)</span></span><textarea id="rk" rows="3" maxlength="1000" placeholder="Seberapa banyak? Sudah berapa lama?"></textarea></label>
<button class="btn" type="submit">Kirim laporan</button>
<p class="fb" id="rerr" aria-live="polite"></p>
</form>
<div id="done" class="hidden" role="status"><div class="done-head"><h3 class="done-title" tabindex="-1">Laporan tercatat</h3><span class="ticket-badge hidden" id="dticket"></span></div><p class="lead" id="dtxt"></p><button class="btn ghost mt-sm" id="again" type="button">Buat laporan lain</button></div>
</div>
</div></section>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script src="script.js?v=<?php echo filemtime(__DIR__ . '/script.js'); ?>"></script>
</body>
</html>
