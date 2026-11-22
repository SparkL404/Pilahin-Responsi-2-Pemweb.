# Pilahin (versi statis)

Tanpa Node.js, tanpa library, tanpa instalasi. Cukup buka `index.html` di browser (klik dua kali).

## File
    index.html   struktur halaman (HTML)
    style.css    tampilan (CSS)
    data.js      data: kategori, kata kunci pencarian, soal kuis
    script.js    logika: pencarian, kuis, laporan

## Laporan
Laporan dari formulir disimpan di browser pengguna (localStorage), bukan di server.
Untuk melihatnya: tekan F12, buka Console, ketik: JSON.parse(localStorage.pilahin_reports)

## Menambah data
Buka `data.js`, tambahkan kata kunci di daftar `items` atau soal di daftar `quiz`, simpan, lalu refresh halaman.
