# PUCUK PENA — Portal Berita Online Indonesia

Portal berita online dinamis dengan arsitektur **PHP Native + MySQL**, desain antarmuka majalah berita modern, responsif di segala perangkat (Laptop, Tablet, dan Smartphone), serta siap dideploy ke **Railway**.

---

## Fitur Utama

- **Frontend Dinamis**:
  - Halaman Beranda (Headline Berita Utama, Rubrik Opini, Essay, Hikmat, Ekonomi, Pendidikan, Olahraga, Galeri Foto, dan TV Video).
  - Single Page Application (SPA) routing instan tanpa reload browser.
  - Pembaca Berita Lengkap (`#article-[ID]`) dengan kutipan, foto hero, tagar, tombol bagikan (WhatsApp, dll.), dan sistem komentar interaktif.
  - Mode Gelap (Dark Mode) & Pencarian Cepat (Live Search).
  - Simpan Berita (Bookmark / Read Later).
  - Dukungan responsif multi-device dengan pencegahan *over-swipe*.
- **Admin Management Panel (`/admin/`)**:
  - **Kelola Artikel**: Tambah berita, edit berita, hapus berita, unggah gambar ke `uploads/`, pengaturan badge dan kategori.
  - **Kelola Iklan**: 3 slot manual (Leaderboard atas, Native sidebar 1, Native sidebar 2) + 2 slot Google AdSense (Rectangle & Sticky bottom).
  - **Kelola Breaking News**: Ticker teks berjalan teraktual di bagian atas.
- **Autentikasi Aman**: Session-based auth untuk login administrator.

---

## Akun Admin Default

- **URL Admin**: `/admin/` (atau `http://localhost/ARIWEB/admin/`)
- **Username**: `admin`
- **Password**: `admin123`

---

## Panduan Hosting di Railway

1. **Buat Project Baru di Railway**:
   - Masuk ke [railway.app](https://railway.app).
   - Klik **New Project** > **Deploy from GitHub repo**.
   - Pilih repository **`MuhammadAslam-aja/PUCUKPENA`**.

2. **Tambahkan Layanan MySQL Database**:
   - Di dashboard project Railway Anda, klik **+ New** > **Database** > **Add MySQL**.
   - Railway akan otomatis menyediakan variabel database (`MYSQLHOST`, `MYSQLUSER`, `MYSQLPASSWORD`, `MYSQLDATABASE`, `MYSQLPORT`).

3. **Inisialisasi Database Otomatis**:
   - Aplikasi telah dilengkapi skrip deteksi otomatis (`includes/db.php`).
   - Pada saat pertama kali aplikasi dibuka di Railway, database akan otomatis mengimpor seluruh tabel dan data 102+ berita awal dari `database.sql`.

4. **Selesai**:
   - Generate domain publik di tab **Settings** > **Networking** > **Generate Domain**.
   - Website Pucuk Pena Anda langsung aktif dan dapat diakses publik!

---

## Struktur Direktori

```
├── admin/               # Panel Admin (index, articles, ads, breaking, login, logout)
├── img/                 # Aset grafis, logo, dan foto lokal
├── includes/            # Helper koneksi database (db.php) dan autentikasi (auth.php)
├── uploads/             # Direktori penyimpanan foto berita yang diunggah
├── .htaccess            # Konfigurasi Apache rewrite dan routing
├── api.php              # Endpoint REST API JSON untuk frontend
├── database.sql         # Skrip dump skema dan data awal MySQL
├── Dockerfile           # Konfigurasi container PHP 8.2 Apache untuk Railway
├── index.php            # Tampilan utama website Pucuk Pena dinamis
├── README.md            # Dokumentasi proyek
└── start.sh             # Entrypoint script Apache dengan port dinamis
```
