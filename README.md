# Pilahin — Panduan Pilah Sampah Rumah Tangga & Pelaporan Sampah Liar

**Pilahin** adalah aplikasi web full-stack berbasis **PHP Native (tanpa framework)** dan **MySQL (XAMPP)** untuk membantu warga memilah sampah rumah tangga ke tempat sampah yang tepat serta melaporkan titik tumpukan sampah liar di lingkungan sekitar.

Proyek ini dikembangkan untuk memenuhi proyek akhir **Responsi 2 Praktikum Pemrograman Web I (Jurusan Informatika, Fakultas Teknik, Universitas Jenderal Soedirman)**.

---

## 1. Deskripsi Proyek

Pilahin menggabungkan dua sisi layanan dalam satu sistem terintegrasi:
- **Portal Publik Warga (`index.php`):** Pencarian cepat nama barang sampah (otomatis menyorot warna tempat sampah yang sesuai), panduan 6 kategori tempat sampah, kuis interaktif pemilahan sampah (dengan pencatatan skor otomatis ke server), serta formulir pelaporan tumpukan sampah liar. Tampilan UI halaman publik dipertahankan 100% identik dengan versi statis awal.
- **Panel Admin (`admin/`):** Terproteksi **PHP Session** untuk memantau statistik dampak SDGs, mengelola laporan masuk beserta ekspor CSV, mengelola kategori & kamus kata kunci (termasuk fitur pintar *Search Miss Log*), serta mengelola bank soal kuis.

---

## 2. Latar Belakang, Keterkaitan SDGs, dan Tujuan

### Latar Belakang
Banyak warga masih ragu menentukan jenis tempat sampah yang benar—terutama untuk limbah berbahaya (B3) dan limbah elektronik (*e-waste*)—serta belum memiliki kanal sederhana untuk mencatat tumpukan sampah liar di lingkungan sekitar agar dapat ditindaklanjuti.

### Keterkaitan Sustainable Development Goals (SDGs)
- **SDG 12 — Responsible Consumption and Production:** Meningkatkan kesadaran dan ketepatan pemilahan sampah rumah tangga (Organik, Plastik, Kertas, B3, Residu, Elektronik) melalui kamus pencarian cepat dan kuis edukasi terukur.
- **SDG 11 — Sustainable Cities and Communities:** Mendukung lingkungan permukiman yang bersih melalui pelaporan lokasi sampah liar yang terdokumentasi, terpantau status penanganannya (`baru` &rarr; `diproses` &rarr; `selesai`), dan siap direkap ke format CSV untuk pengelola lingkungan (RT/RW/DLH).

### Tujuan
1. Menyediakan panduan pemilahan sampah yang instan, visual, dan mudah diakses warga dari desktop maupun ponsel.
2. Menyimpan data kamus sampah, soal kuis, skor pemahaman warga, dan laporan sampah liar secara terpusat di database relasional MySQL.
3. Memberikan dashboard operasional kepada pengelola untuk memperbarui status laporan serta melengkapi kamus kata kunci berdasarkan kebutuhan pencarian nyata warga.

---

## 3. Daftar Modul & Fitur yang Diimplementasikan

Aplikasi ini mengimplementasikan **10 modul bisnis terintegrasi** yang menerapkan seluruh materi Praktikum Pemrograman Web I (HTML, HTML Lanjut, CSS, JavaScript, PHP Dasar, PHP CRUD `mysqli`, dan PHP Session):

| No | Modul | Fitur Utama | File Terkait |
|---|---|---|---|
| **M1** | **Pencarian Jenis Sampah** | Pencarian kata kunci (`exact` &rarr; *substring 2 arah* &rarr; terpanjang) dengan highlight animasi 6 tempat sampah & pencatatan kata yang belum ditemukan. | `index.php`, `script.js`, `api/search.php` |
| **M2** | **Katalog 6 Kategori Tempat Sampah** | Menampilkan 6 jenis & warna tempat sampah (Hijau, Kuning, Biru, Merah, Abu-abu, Ungu) beserta sinkronisasi dropdown laporan dari database. | `index.php`, `script.js`, `api/categories.php` |
| **M3** | **Kuis Edukasi Pemilahan** | Soal pilihan ganda dinamis dari database dengan umpan balik jawaban benar/salah dan penjelasan langsung. | `index.php`, `script.js`, `api/quiz.php` |
| **M4** | **Rekap & Analitik Skor Kuis** | Mencatat skor akhir peserta secara otomatis ke MySQL (`POST api/quiz_attempts.php`) untuk analitik edukasi SDG 12 di Dashboard Admin tanpa mengubah tampilan publik. | `script.js`, `api/quiz_attempts.php`, `admin/index.php` |
| **M5** | **Pelaporan Tumpukan Sampah Liar** | Form pelaporan jenis sampah, lokasi, dan keterangan dengan validasi sisi client dan server sebelum disimpan ke MySQL. | `index.php`, `script.js`, `api/reports.php` |
| **M6** | **Autentikasi & Proteksi PHP Session** | Login/logout admin menggunakan `password_hash()` / `password_verify()`, regenerasi session ID, guard akses halaman, flash message, dan CSRF token. | `includes/auth.php`, `admin/login.php`, `admin/logout.php` |
| **M7** | **Dashboard Statistik SDGs & Export CSV** | Kartu KPI dampak lingkungan, grafik batang CSS sebaran jenis sampah, ringkasan kuis, dan unduh rekap laporan CSV (UTF-8 BOM). | `admin/index.php`, `admin/export_reports.php` |
| **M8** | **Manajemen Laporan Sampah Liar** | Filter laporan (status, jenis, pencarian teks), ubah status tindak lanjut (`baru` &rarr; `diproses` &rarr; `selesai`), dan hapus laporan. | `admin/reports.php`, `admin/_reports_filter.php` |
| **M9** | **Manajemen Kategori, Kata Kunci & Search Miss Log** | CRUD kategori & kata kunci pencarian, dilengkapi daftar kata yang dicari warga tapi belum terdaftar beserta tombol 1-klik **"Tambahkan ke Kata Kunci"**. | `admin/categories.php`, `admin/items.php` |
| **M10** | **Manajemen Bank Soal Kuis** | CRUD soal kuis (pertanyaan, 3 opsi jawaban, kunci, penjelasan, urutan, dan toggle aktif/nonaktif). | `admin/quiz.php` |

---

## 4. Teknologi yang Digunakan

- **Frontend:** HTML5 Semantik, CSS3 (Responsive Grid/Flexbox & Custom Properties), Vanilla JavaScript (`fetch` API)
- **Backend:** PHP Native (tanpa framework, tanpa library eksternal) menggunakan ekstensi **`mysqli` prosedural** (*Prepared Statements*), **PHP Session**, dan `include` modular
- **Database:** MySQL / MariaDB (7 tabel relasional InnoDB: `categories`, `items`, `search_misses`, `quiz_questions`, `quiz_attempts`, `reports`, `admins`)
- **Web Server Lokal:** XAMPP (Apache HTTP Server + MariaDB/MySQL + phpMyAdmin)

---

## 5. Panduan Instalasi dan Konfigurasi

1. **Siapkan XAMPP:** Pastikan **Apache** dan **MySQL** terpasang dan dalam keadaan **Running** di XAMPP Control Panel.
2. **Letakkan Proyek di `htdocs`:** Salin seluruh folder proyek ini ke direktori `C:\xampp\htdocs\pilahin\` (atau nama folder pilihan Anda).
3. **Buat & Import Database:**
   - Buka browser ke `http://localhost/phpmyadmin`.
   - Klik menu **Import** (atau tab **SQL**).
   - Pilih file [`database/pilahin_db.sql`](database/pilahin_db.sql) lalu klik **Go / Kirim**.
   - File tersebut otomatis membuat database `pilahin_db`, 7 tabel relasional, serta seluruh data awal (6 kategori, 54 kata kunci, 5 soal kuis, dan data contoh demo).
4. **Cek Konfigurasi Koneksi (Opsional):**
   - Secara default [`config/koneksi.php`](config/koneksi.php) sudah memakai pengaturan standar XAMPP (`host: localhost`, `user: root`, `password: ""`, `database: pilahin_db`, `port: 3306`).
   - Jika konfigurasi MySQL lokal Anda berbeda (misalnya memakai password atau port khusus), salin [`config/config.example.php`](config/config.example.php) menjadi `config/config.local.php` lalu sesuaikan nilainya.

---

## 6. Panduan Menjalankan Aplikasi

1. **Buka Halaman Publik (Warga):**
   - Akses `http://localhost/pilahin/` di browser.
   - Uji pencarian barang (misal: `botol plastik`, `baterai`, atau barang baru seperti `styrofoam`), jelajahi 6 tempat sampah, kerjakan 5 soal kuis, dan kirim laporan sampah liar.
2. **Masuk ke Panel Admin:**
   - Buka langsung `http://localhost/pilahin/admin/login.php` di browser (tampilan publik di `index.php` dibiarkan tetap bersih tanpa tautan admin).
   - Gunakan kredensial demo default berikut *(otomatis dibuat aman menggunakan `password_hash()` saat halaman login pertama kali dibuka)*:
     - **Username:** `admin`
     - **Kata Sandi:** `admin123`
3. **Alur Demonstrasi Terintegrasi (Untuk Presentasi):**
   - Di beranda, cari kata yang belum terdaftar (misal `kaca pecah`) &rarr; sistem mencatatnya ke *Search Miss Log*.
   - Kirim laporan sampah liar baru melalui formulir `#lapor`.
   - Login ke **Panel Admin** &rarr; lihat perubahan KPI di **Dashboard**, ubah status laporan di menu **Laporan** &rarr; klik **Export CSV**.
   - Buka menu **Kata Kunci**, lihat `kaca pecah` di daftar *Saran dari Pencarian Belum Ditemukan*, lalu klik **Tambahkan ke Kata Kunci** ke kategori `Residu`.
   - Kembali ke beranda dan cari `kaca pecah` lagi &rarr; hasil langsung muncul mengarah ke tempat sampah Residu!

---

## Struktur Direktori

```text
pilahin/
├── index.php                 # Halaman publik utama
├── style.css                 # Styling halaman publik
├── script.js                 # Logika frontend & pemanggilan REST API (fetch)
├── config/
│   ├── config.example.php    # Template override konfigurasi DB
│   └── koneksi.php           # Koneksi MySQLi prosedural
├── includes/
│   ├── helpers.php           # Helper session, JSON, CSRF, flash, sanitasi, CSV
│   ├── auth.php              # Autentikasi & proteksi PHP Session admin
│   ├── header.php            # Komponen header publik (PHP include)
│   └── footer.php            # Komponen footer publik (PHP include)
├── api/
│   ├── categories.php        # GET daftar kategori
│   ├── search.php            # GET pencarian sampah + log search misses
│   ├── quiz.php              # GET soal kuis aktif
│   ├── quiz_attempts.php     # GET/POST rekap skor kuis warga
│   └── reports.php           # POST simpan laporan sampah liar
├── admin/
│   ├── admin.css             # Styling panel admin
│   ├── _layout_top.php       # Layout atas & navigasi admin
│   ├── _layout_bottom.php    # Penutup layout admin
│   ├── _reports_filter.php   # Helper filter bersama laporan & CSV
│   ├── login.php             # Login admin (PHP Session)
│   ├── logout.php            # Logout admin (POST + CSRF)
│   ├── index.php             # Dashboard statistik SDGs
│   ├── reports.php           # Manajemen & filter laporan
│   ├── export_reports.php    # Export CSV laporan
│   ├── categories.php        # CRUD kategori tempat sampah
│   ├── items.php             # CRUD kata kunci + promote Search Misses
│   └── quiz.php              # CRUD bank soal kuis
├── database/
│   └── pilahin_db.sql        # Skema 7 tabel & seed data siap import phpMyAdmin
├── legacy/                   # Arsip versi statis awal (HTML + data.js + localStorage)
└── PRD.md                    # Product Requirements Document lengkap
```
