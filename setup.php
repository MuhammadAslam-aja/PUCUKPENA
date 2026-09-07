<?php
// setup.php — Jalankan SEKALI untuk membuat database, tabel, dan seed data
// Akses via browser: http://localhost/ARIWEB/setup.php
// Hapus file ini setelah selesai dijalankan!

error_reporting(E_ALL);
ini_set('display_errors', 1);

$host    = 'localhost';
$user    = 'root';
$pass    = '';
$dbname  = 'ariweb';
$charset = 'utf8mb4';

echo "<style>body{font-family:sans-serif;max-width:800px;margin:40px auto;padding:20px}
pre{background:#f4f4f4;padding:12px;border-radius:6px;overflow:auto}
.ok{color:#059669;font-weight:bold}.err{color:#dc2626;font-weight:bold}
h2{color:#064e3b}h3{color:#065f46;margin-top:24px}</style>";
echo "<h2>🌿 Setup Pucuk Pena — Inisialisasi Database</h2>";

// 1. Koneksi tanpa nama DB dulu (untuk CREATE DATABASE)
try {
    $pdo = new PDO("mysql:host=$host;charset=$charset", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "<p class='ok'>✔ Koneksi MySQL berhasil</p>";
} catch (PDOException $e) {
    die("<p class='err'>✘ Gagal koneksi MySQL: " . $e->getMessage() . "</p>");
}

// 2. Buat database
$pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
echo "<p class='ok'>✔ Database `$dbname` siap</p>";

$pdo->exec("USE `$dbname`");

// 3. Buat tabel
$pdo->exec("
CREATE TABLE IF NOT EXISTS `admin_users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
echo "<p class='ok'>✔ Tabel admin_users dibuat</p>";

$pdo->exec("
CREATE TABLE IF NOT EXISTS `articles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `type` VARCHAR(50) NOT NULL DEFAULT 'berita',
  `cat` VARCHAR(50) NOT NULL DEFAULT 'nasional',
  `badge` VARCHAR(50) NOT NULL DEFAULT 'berita',
  `title` VARCHAR(500) NOT NULL,
  `excerpt` TEXT,
  `content` LONGTEXT,
  `author` VARCHAR(200),
  `date_display` VARCHAR(100),
  `views` VARCHAR(20) DEFAULT '0',
  `img` VARCHAR(500),
  `tags` VARCHAR(500) DEFAULT '[]',
  `status` ENUM('draft','published') DEFAULT 'published',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
echo "<p class='ok'>✔ Tabel articles dibuat</p>";

$pdo->exec("
CREATE TABLE IF NOT EXISTS `ads` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(200) NOT NULL,
  `type` ENUM('manual','google') DEFAULT 'manual',
  `slot` VARCHAR(100) NOT NULL,
  `content` TEXT,
  `google_client` VARCHAR(200),
  `google_slot` VARCHAR(100),
  `url` VARCHAR(500),
  `active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
echo "<p class='ok'>✔ Tabel ads dibuat</p>";

$pdo->exec("
CREATE TABLE IF NOT EXISTS `breaking_news` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `text` VARCHAR(500) NOT NULL,
  `active` TINYINT(1) DEFAULT 1,
  `sort_order` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
echo "<p class='ok'>✔ Tabel breaking_news dibuat</p>";

// 4. Buat admin default (username: admin, password: admin123)
$adminPass = password_hash('admin123', PASSWORD_BCRYPT);
$stmt = $pdo->prepare("INSERT IGNORE INTO `admin_users` (username, password) VALUES (?, ?)");
$stmt->execute(['admin', $adminPass]);
echo "<p class='ok'>✔ Admin default dibuat (username: <b>admin</b>, password: <b>admin123</b>)</p>";

// 5. Seed breaking news
$breaking = [
    ['🔴 BREAKING: Pemerintah Umumkan Kebijakan Energi Terbarukan Baru — Baca Selengkapnya', 1, 1],
    ['⚡ TERKINI: Timnas Indonesia Menang 3-0 di Kualifikasi — Simak Laporan Lengkap', 1, 2],
    ['🌿 UPDATE: KTT ASEAN Hasilkan Deklarasi Bersama Ekonomi Digital — Detail di Sini', 1, 3],
    ['📢 INFO: Program Beasiswa Nasional 2025 Dibuka — Daftar Sekarang!', 1, 4],
];
$stmtB = $pdo->prepare("INSERT IGNORE INTO `breaking_news` (text, active, sort_order) VALUES (?, ?, ?)");
foreach ($breaking as $b) {
    $stmtB->execute($b);
}
echo "<p class='ok'>✔ Breaking news default di-seed</p>";

// 6. Seed ads default
$ads = [
    ['GreenLife Insurance — Leaderboard', 'manual', 'leaderboard',
     '<strong style="color:var(--green-700)">🌿 GreenLife Insurance</strong> — Proteksi keluarga Anda mulai Rp50rb/bulan · <u>Klik di sini</u>',
     null, null, 'https://example.com/greenlife', 1],
    ['AriTel 5G — Native Feed', 'manual', 'native1',
     '📱|AriTel 5G — Internet Super Cepat di Seluruh Indonesia|Paket unlimited mulai 89rb. Gratis router untuk pelanggan baru!|Pelajari Lebih Lanjut',
     null, null, 'https://example.com/aritel', 1],
    ['Kopi Nusantara — Native Sidebar', 'manual', 'native2',
     '☕|Kopi Nusantara Premium|Rasakan cita rasa kopi lokal terbaik. Diskon 20%!|Beli Sekarang',
     null, null, 'https://example.com/kopi', 1],
    ['Google Ads — Rectangle 300x250', 'google', 'google_rectangle',
     '', 'ca-pub-XXXXXXXXXXXXXXXXX', '1234567890', '', 1],
    ['Google Ads — Sticky Bottom', 'google', 'google_sticky',
     '', 'ca-pub-XXXXXXXXXXXXXXXXX', '0987654321', '', 1],
];
$stmtA = $pdo->prepare("INSERT IGNORE INTO `ads` (name, type, slot, content, google_client, google_slot, url, active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
foreach ($ads as $ad) {
    $stmtA->execute($ad);
}
echo "<p class='ok'>✔ Iklan default di-seed (3 manual + 2 Google placeholder)</p>";

// 7. Seed semua artikel dari versi statis
echo "<h3>📝 Menanam artikel dari versi statis...</h3>";

$articles = [
  ['berita','nasional','berita',
   'Pemerintah Luncurkan Program Revitalisasi 100 Desa Wisata di Seluruh Nusantara',
   'Program senilai Rp2,5 triliun diharapkan meningkatkan ekonomi kerakyatan dan menyerap 50 ribu tenaga kerja baru di sektor pariwisata.',
   "Jakarta — Pemerintah melalui Kementerian Pariwisata dan Ekonomi Kreatif resmi meluncurkan program Revitalisasi 100 Desa Wisata yang menargetkan peningkatan infrastruktur, pelatihan SDM, dan promosi digital di desa-desa potensial.\n\nMenteri menyatakan program ini merupakan bagian dari visi Indonesia Emas 2045, di mana sektor pariwisata diharapkan menyumbang 12 persen terhadap PDB nasional.\n\n\"Kita tidak hanya membangun jalan dan homestay, tapi juga ekosistem digital agar desa-desa ini bisa dikenal dunia,\" ujarnya dalam konferensi pers di Jakarta, Jumat (28/6).\n\nProgram akan dimulai di 20 provinsi prioritas termasuk Bali, Yogyakarta, NTT, dan Sulawesi Selatan.",
   'Rina Wijaya','28 Juni 2025','45.2K','img/desa_wisata.png','["Pariwisata","Pemerintah","Ekonomi"]'],
  ['berita','internasional','berita',
   'G20 Sepakat Perkuat Kerjasama Transisi Energi Hijau Global',
   'Para pemimpin negara G20 menyepakati deklarasi bersama untuk mengalokasikan USD 100 miliar guna akselerasi energi terbarukan.',
   "Roma — KTT G20 yang digelar di Italia menghasilkan deklarasi bersejarah tentang transisi energi hijau. Para pemimpin dunia sepakat meningkatkan investasi energi terbarukan dan mengurangi ketergantungan bahan bakar fosil.\n\nIndonesia menjadi salah satu negara yang mendapat apresiasi atas komitmen menutup PLTU batu bara secara bertahap dan mengembangkan energi geothermal serta surya.",
   'Ahmad Rizki','27 Juni 2025','38.7K','img/g20_summit.png','["Internasional","Lingkungan","G20"]'],
  ['berita','ekonomi','ekonomi',
   'Rupiah Menguat ke Rp15.820 per Dolar AS, BI Optimis Stabil',
   'Bank Indonesia mencatat aliran masuk investasi portofolio asing sebesar USD 1,2 miliar dalam sepekan terakhir.',
   "Jakarta — Rupiah menguat 0,3 persen pada perdagangan Kamis kemarin, didorong oleh sentimen positif pasar global dan kebijakan moneter BI yang dianggap hawkish.\n\nDeputi Gubernur BI menyatakan cadangan devisa tetap aman di level USD 148 miliar, cukup untuk 6,2 bulan impor.",
   'Dian Pratiwi','27 Juni 2025','29.1K','img/rupiah_exchange.png','["Rupiah","BI","Ekonomi"]'],
  ['berita','teknologi','teknologi',
   'Startup AI Indonesia Raih Pendanaan Series B Senilai USD 80 Juta',
   'NeuralNusantara dikembangkan untuk solusi AI berbahasa Indonesia yang melayani 700 bahasa daerah.',
   "Bandung — Startup kecerdasan buatan asal Bandung, NeuralNusantara, berhasil meraih pendanaan Series B dari investor global termasuk Sequoia Capital India dan Alpha JWC Ventures.\n\nCEO startup ini menyebut produk mereka sudah digunakan 200 perusahaan enterprise di Indonesia dan ASEAN.",
   'Fajar Nugroho','26 Juni 2025','52.4K','img/ai_startup.png','["Startup","AI","Teknologi"]'],
  ['berita','olahraga','berita',
   'Timnas Indonesia Lolos ke Piala Asia 2027 Setelah Dramatis di Kualifikasi',
   'Gol injury time Evan Dimas memastikan kemenangan 2-1 atas Bahrain dan tiket ke Qatar.',
   "Surabaya — Stadion Gelora Bung Tomo bergemuruh. Timnas Indonesia menorehkan sejarah dengan lolos ke Piala Asia 2027 setelah mengalahkan Bahrain 2-1.\n\nPelatih Shin Tae-yong memuji mental juara para pemain yang bermain total meski dalam tekanan.",
   'Bambang Sutrisno','26 Juni 2025','89.3K','img/timnas_football.png','["Timnas","Sepak Bola","Olahraga"]'],
  ['opini','opini','opini',
   'Demokrasi Digital: Antara Kebebasan Berekspresi dan Misinformasi',
   'Media sosial telah mengubah landscape demokrasi, tapi tantangan hoaks dan echo chamber semakin mengkhawatirkan.',
   "Opini — Kita hidup di era di mana setiap warga negara bisa menjadi produsen informasi. Namun kemudahan ini membawa konsekuensi: misinformasi menyebar 6 kali lebih cepat dibanding fakta.\n\nPemerintah perlu regulasi yang tepat, bukan sensor, melainkan literasi digital masif. Media mainstream juga harus beradaptasi dengan kecepatan digital tanpa mengorbankan verifikasi.\n\nDemokrasi digital bukan ancaman — jika kita mampu mengelolanya dengan bijak.",
   'Prof. Dr. Maya Sari','25 Juni 2025','18.6K','img/demokrasi_digital.png','["Demokrasi","Media","Opini"]'],
  ['opini','opini','opini',
   'Pendidikan Merdeka Belajar: Dua Tahun Later, Apa Hasilnya?',
   'Evaluasi kritis terhadap kebijakan Merdeka Belajar dan rekomendasi perbaikan untuk era berikutnya.',
   "Refleksi kebijakan pendidikan nasional menunjukkan progress di beberapa aspek, terutama fleksibilitas kurikulum. Namun kesenjangan infrastruktur digital antar daerah masih menjadi pekerjaan rumah besar.\n\nGuru-guru hebat ada di mana-mana, tapi sistem penghargaan dan pengembangan profesional mereka masih perlu diperbaiki.",
   'Dr. Hendra Wijaya','24 Juni 2025','14.2K','img/pendidikan_sekolah.png','["Pendidikan","Kebijakan","Opini"]'],
  ['essay','essay','essay',
   'Langit Senja di Kampung Halaman: Ode untuk yang Telah Pergi',
   'Essay tentang nostalgia, kehilangan, dan makna pulang ke akar budaya di tengah modernitas yang menggerus.',
   "Essay — Ada sesuatu yang magis tentang senja di desa. Jemputan angin padi, suara azan yang bersahutan, dan aroma ibu yang sedang menumis rempah di dapur.\n\nSaya pulang setelah lima belas tahun merantau. Jalan setapak yang dulu saya lalui dengan sepatu bolong kini sudah diaspal. Warung Pak Temon sudah diganti minimarket. Tapi langit senjanya — langit senja tetap sama.\n\nDalam essay ini, saya mencoba merangkum perasaan yang sulit diucapkan: bahwa pulang bukan tentang tempat, melainkan tentang siapa kita setelah pergi dan kembali.",
   'Saraswati Dewi','23 Juni 2025','22.8K','img/senja_desa.png','["Essay","Sastra","Nostalgia"]'],
  ['essay','essay','essay',
   'Catatan dari Garis Khatulistiwa: Iklim, Identitas, dan Masa Depan',
   'Perjalanan literer melintasi Indonesia equatorial dan refleksi tentang krisis iklim yang personal.',
   "Membaca perubahan iklim dari buku teks berbeda dengan merasakannya di tubuh sendiri. Di Palangkaraya, kabut asap bukan lagi musiman — ia tinggal.\n\nEssay ini adalah catatan perjalanan dan pergulatan batin tentang apa artinya menjadi generasi yang mewarisi bumi yang sedang sakit.",
   'Agus Prasetyo','22 Juni 2025','16.4K','img/senja_desa.png','["Essay","Iklim","Lingkungan"]'],
  ['artikel','artikel','artikel',
   'Sejarah Tersembunyi: Jejak Perdagangan Rempah di Maluku',
   'Artikel mendalam tentang bagaimana cengkeh dan pala membentuk geopolitik dunia selama empat abad.',
   "Maluku pernah menjadi pusat dunia. Cengkeh dan pala yang tumbuh hanya di sana membuat bangsa-bangsa Eropa berlayar ribuan mil, berperang, dan berkoloni.\n\nArtikel ini menelusuri jejak-jejak sejarah yang masih bisa ditemukan di Ternate, Tidore, dan Banda — dari benteng VOC hingga kompleks istana Kesultanan.",
   'Historian Team','21 Juni 2025','31.5K','img/desa_wisata.png','["Sejarah","Maluku","Budaya"]'],
  ['berita','nasional','investigasi',
   '[INVESTIGASI] Jejak Korupsi Proyek Jalan Tol Trans-Pulau: Apa yang Terlewat?',
   'Tim investigasi Pucuk Pena menelusuri aliran dana proyek tol senilai Rp12 triliun yang mangkrak selama tiga tahun.',
   "Investigasi — Dokumen yang kami peroleh menunjukkan indikasi mark-up biaya konstruksi hingga 40 persen. Empat perusahaan konstruksi terafiliasi dengan pejabat daerah tercatat sebagai penerima tender.\n\nKPK telah menerima laporan dan memulai audit internal. Artikel ini adalah bagian pertama dari seri tiga laporan investigasi.",
   'Tim Investigasi Pucuk Pena','19 Juni 2025','67.9K','img/demokrasi_digital.png','["Investigasi","Korupsi","Nasional"]'],
  ['berita','olahraga','olahraga',
   'Jonatan Christie Juara All England 2026, Ukir Sejarah Tunggal Putra',
   'Kemenangan dua game langsung atas wakil Denmark mengakhiri penantian panjang 10 tahun gelar tunggal putra All England.',
   "Birmingham — Pebulutangkis tunggal putra Indonesia, Jonatan Christie, sukses merebut gelar juara turnamen prestisius All England 2026 setelah menundukkan rival sengitnya di babak final.\n\nDalam pertandingan yang berlangsung intens selama 48 menit, Jonatan mengendalikan tempo permainan dengan smash keras dan pertahanan rapat.\n\n\"Ini adalah impian masa kecil saya yang menjadi kenyataan. Terima kasih kepada seluruh rakyat Indonesia atas dukungannya,\" ujarnya penuh haru.",
   'Slamet Rahardjo','5 Juni 2025','72.4K','img/badminton_court.png','["Bulutangkis","AllEngland","Olahraga"]'],
  ['berita','olahraga','olahraga',
   'Sirkuit Mandalika Siap Gelar MotoGP 2025, Logistik Pembalap Mulai Tiba',
   'Penyelenggara mengonfirmasi kesiapan aspal sirkuit, marshal, dan akomodasi untuk menampung 100 ribu penonton.',
   "Lombok Tengah — Logistik untuk gelaran MotoGP seri Indonesia di Sirkuit Mandalika dilaporkan mulai tiba melalui Bandara Internasional Lombok.\n\nDirektur Utama MGPA menegaskan bahwa seluruh infrastruktur sirkuit termasuk pengaspalan ulang area run-off telah rampung 100%.\n\n\"Kami siap menyuguhkan balapan terbaik dunia dengan keindahan panorama alam Lombok,\" tuturnya.",
   'Aditya Putra','4 Juni 2025','58.9K','img/mandalika_motogp.png','["MotoGP","Mandalika","Olahraga"]'],
  ['berita','olahraga','olahraga',
   'Lari Maraton Internasional Borobudur Diikuti 5.000 Pelari dari 20 Negara',
   'Rute maraton melewati keindahan candi bersejarah Borobudur dan pedesaan asri sekitarnya.',
   "Magelang — Borobudur Marathon 2025 sukses digelar pagi ini dengan diikuti oleh ribuan pelari dari dalam dan luar negeri. Rute lari tahun ini dirancang melewati jalan-jalan desa yang asri di sekitar kompleks Candi Borobudur.\n\nPelari asal Kenya mendominasi podium kategori Full Marathon putra dengan catatan waktu 2 jam 12 menit.",
   'Eko Wahyudi','3 Juni 2025','31.2K','img/maraton_borobudur.png','["Maraton","Borobudur","Olahraga"]'],
  ['opini','opini','opini',
   'Krisis Eksistensial Generasi Z di Tengah Dominasi Algoritma Media Sosial',
   'Mengapa scrolling tiada akhir merusak rentang perhatian anak muda dan bagaimana kita bisa merebut kembali kesadaran kita.',
   "Opini — Menghabiskan waktu 5-6 jam sehari untuk menggulirkan layar (scrolling) media sosial telah menjadi norma bagi mayoritas Generasi Z. Algoritma platform yang dirancang untuk menjaga perhatian pengguna telah menciptakan ruang gema (echo chamber) yang memperkuat kecemasan sosial.\n\nPsikolog klinis berpendapat bahwa paparan terus-menerus terhadap kehidupan orang lain yang \"sempurna\" di layar memicu sindrom FOMO.\n\nLangkah kecil seperti detoks digital 24 jam seminggu bisa menjadi awal yang baik.",
   'Nadia Utami, M.Psi','9 Juni 2025','28.9K','img/demokrasi_digital.png','["Gen-Z","Mental","Media"]'],
  ['berita','ekonomi','ekonomi',
   'Ekspor Nikel Indonesia Melonjak 35% di Semester Pertama 2025',
   'Kebijakan hilirisasi nikel berhasil mendongkrak devisa negara dan menarik investasi baterai kendaraan listrik.',
   "Jakarta — Badan Pusat Statistik (BPS) melaporkan nilai ekspor produk olahan nikel Indonesia mencapai rekor tertinggi baru dengan lonjakan 35% secara tahunan.\n\nInvestasi pada pabrik peleburan (smelter) baterai kendaraan listrik di Morowali dan Weda Bay menjadi motor utama kenaikan devisa.",
   'Dian Pratiwi','14 Mei 2025','39.6K','img/rupiah_exchange.png','["Nikel","Hilirisasi","Ekonomi"]'],
  ['berita','teknologi','teknologi',
   'Kota Cerdas Nusantara: Implementasi IoT untuk Pemantauan Polusi Air',
   'Sistem sensor real-time dipasang di sepanjang sungai utama ibu kota baru untuk menjaga kualitas air baku.',
   "Nusantara — Otorita Ibu Kota Nusantara (IKN) berkolaborasi dengan lembaga riset teknologi nasional mulai memasang ratusan sensor Internet of Things (IoT) di sepanjang aliran sungai utama kawasan inti pusat pemerintahan.\n\nSensor-sensor ini mendeteksi kadar pH, oksigen terlarut, serta kandungan zat kimia berbahaya secara real-time.",
   'Lab Lingkungan','26 Mei 2025','42.7K','img/iot_polusi_air.png','["SmartCity","IoT","Lingkungan"]'],
  ['berita','nasional','nasional',
   'Festival Budaya Nusantara di Candi Prambanan Tarik Ribuan Turis',
   'Acara tahunan menampilkan tarian kolosal tradisional dari Aceh hingga Papua di bawah sorotan lampu candi.',
   "Yogyakarta — Festival Budaya Nusantara resmi dibuka malam ini di pelataran Candi Prambanan. Ribuan penonton lokal dan wisatawan mancanegara memadati tribun terbuka untuk menyaksikan tarian kolosal.\n\nSebanyak 500 seniman tradisional dari berbagai pelosok negeri berpartisipasi dalam pentas bertema persatuan bangsa ini.",
   'Eko Wahyudi','24 Mei 2025','32.5K','img/desa_wisata.png','["Festival","Prambanan","Nasional"]'],
  ['berita','nasional','nasional',
   'Polri Luncurkan Sistem Tilang Elektronik ETLE Mobile Berbasis AI',
   'Kamera terpasang di helm petugas mampu mengidentifikasi pengendara tanpa sabuk pengaman atau helm secara otomatis.',
   "Jakarta — Korlantas Polri meresmikan sistem tilang elektronik terbaru berbasis kecerdasan buatan yang dinamakan ETLE Mobile. Kamera pintar yang dipasang di kendaraan patroli dan helm petugas akan mendeteksi pelanggaran secara otomatis saat berpatroli.\n\n\"Sistem AI akan menyaring plat nomor dan jenis pelanggaran secara presisi tanpa perlu menghentikan pengendara di jalan,\" jelas Kakorlantas.",
   'Bambang Sutrisno','21 Mei 2025','51.2K','img/ai_startup.png','["LaluLintas","Polri","Nasional"]'],
  ['berita','internasional','internasional',
   'KTT Asean ke-46: Fokus pada Konektivitas Ekonomi Regional',
   'Para pemimpin negara menyepakati integrasi sistem pembayaran QR code lintas batas di seluruh Asia Tenggara.',
   "Bangkok — Konferensi Tingkat Tinggi (KTT) ASEAN ke-46 resmi dimulai dengan fokus integrasi ekonomi digital regional. Seluruh kepala negara menyepakati perluasan QR Code Payment ASEAN agar turis dapat bertransaksi langsung tanpa tukar valuta.",
   'Rina Wijaya','18 Mei 2025','33.4K','img/g20_summit.png','["ASEAN","Ekonomi","Internasional"]'],
  ['berita','nasional','nasional',
   'Pemerintah Umumkan Pembangunan Jalur Kereta Api Cepat Tahap Dua',
   'Proyek kereta cepat Jakarta-Surabaya resmi dimulai dengan rute melewati Bandung, Yogyakarta, dan Solo.',
   "Jakarta — Kementerian Perhubungan secara resmi mengumumkan peta jalan pembangunan proyek Kereta Cepat Jakarta-Surabaya yang merupakan kelanjutan dari proyek Whoosh tahap pertama.\n\nProyek infrastruktur raksasa ini ditargetkan rampung pada tahun 2030 dan akan memangkas waktu tempuh perjalanan darat Jakarta-Surabaya menjadi hanya 3,5 jam saja.",
   'Humas Kemenhub','28 Mei 2025','65.4K','img/maraton_borobudur.png','["KeretaCepat","Kemenhub","Nasional"]'],
  ['berita','nasional','nasional',
   'Ilmuwan Indonesia Temukan Spesies Anggrek Langka Baru di Hutan Kalimantan',
   'Spesies yang dinamai Dendrobium arianae ini memiliki kelopak berwarna ungu menyala dengan ketahanan cuaca ekstrem.',
   "Kalimantan — Tim peneliti botani gabungan berhasil menemukan spesies anggrek langka baru di pedalaman hutan lindung Kalimantan Barat. Spesies ini diklasifikasikan ke dalam genus Dendrobium dan diberi nama Dendrobium arianae.\n\nAnggrek ini memiliki ciri khas yang sangat unik, yaitu kelopak berwarna ungu neon menyala dan kemampuan hidup menempel pada pohon tinggi tanpa air selama berminggu-minggu.",
   'Flora Desk','6 Juni 2025','37.5K','img/anggrek_ungu.png','["Anggrek","Kalimantan","Sains"]'],
  ['essay','essay','essay',
   'Secangkir Kopi Gayo: Rasa, Aroma, dan Narasi Perjuangan Petani',
   'Esai mendalam tentang kehidupan petani kopi di dataran tinggi Gayo.',
   "Aceh — Di dataran tinggi Gayo, kopi bukan sekadar komoditas dagang, melainkan napas kehidupan dan sejarah perlawanan. Sejak era kolonial Belanda, varietas kopi Arabika Gayo telah mengharumkan nusantara hingga pasar Eropa.\n\nNamun di balik aromanya yang harum dan rasanya yang kaya dengan keasaman seimbang, tersimpan pergulatan para petani lokal dalam menghadapi fluktuasi harga global.\n\n\"Tahun ini curah hujan tidak menentu. Bunganya gugur sebelum jadi buah,\" keluh Pak Hamdan, seorang petani kopi di Bener Meriah.",
   'Arian Ramadhan','13 Juni 2025','18.5K','img/senja_desa.png','["Kopi","Gayo","Esai"]'],
  ['berita','nasional','nasional',
   'Kemenkes Bagikan 1 Juta Alat Deteksi Stunting Gratis ke Puskesmas',
   'Penyaluran antropometri kit digital terstandar diharapkan menurunkan angka tengkes secara signifikan.',
   "Jakarta — Kementerian Kesehatan membagikan satu juta paket alat ukur antropometri digital terstandar ke puskesmas dan posyandu di daerah 3T. Langkah ini bertujuan melakukan deteksi dini kasus gizi buruk pada balita.\n\nPemerintah menargetkan prevalensi stunting turun di bawah 14% tahun ini.",
   'Indra Jaya','22 Mei 2025','22.1K','img/pendidikan_sekolah.png','["Kesehatan","Kemenkes","Nasional"]'],
  ['opini','opini','opini',
   'UMKM Go Digital: Peluang atau Jebakan Platform?',
   'Refleksi tentang ketergantungan UMKM pada marketplace dan strategi membangun brand mandiri.',
   "Digitalisasi UMKM bukan lagi pilihan, melainkan keharusan. Tapi ketergantungan pada platform e-commerce dengan komisi 15-25 persen menggerus margin tipis pelaku usaha kecil.\n\nSaya berargumen: UMKM perlu strategi omnichannel dengan website sendiri sebagai home base.",
   'Ratna Kartika, SE','17 Juni 2025','12.7K','img/rupiah_exchange.png','["UMKM","Digital","Opini"]'],
];

// Seed videos (as articles with type='video')
$videos = [
  ['video','video','video',
   'Laporan Khusus: Kehidupan Nelayan di Tengah Krisis Ikan Laut',
   'Tim Pucuk Pena TV turun langsung ke kampung nelayan di pesisir utara Jawa untuk merekam keseharian mereka.',
   'Video dokumenter eksklusif tentang kehidupan nelayan tradisional Indonesia.',
   'Pucuk Pena TV','1 Juli 2025','125K','img/desa_wisata.png','["Video","Nelayan","Dokumenter"]'],
  ['video','video','video',
   'Wawancara Eksklusif: Pelatih Timnas Buka Suara soal Taktik Kualifikasi',
   'Shin Tae-yong berbicara panjang lebar tentang persiapan dan mental tim jelang Piala Asia.',
   'Wawancara mendalam dengan pelatih timnas Indonesia.',
   'Sport Desk','29 Juni 2025','98K','img/timnas_football.png','["Video","Timnas","Wawancara"]'],
  ['video','video','video',
   'Behind the Scene: Produksi Batik Tulis Yogyakarta yang Memukau',
   'Proses pembuatan batik tulis tradisional yang rumit namun penuh keindahan.',
   'Dokumentasi proses pembuatan batik tulis oleh pengrajin Yogyakarta.',
   'Budaya Desk','25 Juni 2025','67K','img/desa_wisata.png','["Video","Batik","Budaya"]'],
];

// Seed photos (as articles with type='foto')
$photos = [
  ['foto','foto','foto',
   'Galeri: Indahnya Panorama Gunung Rinjani Saat Golden Hour',
   'Koleksi foto terbaik dari ekspedisi pendakian Gunung Rinjani oleh fotografer alam Pucuk Pena.',
   'Kumpulan foto memukau dari puncak Gunung Rinjani saat golden hour.',
   'Foto Desk','28 Juni 2025','44K','img/desa_wisata.png','["Foto","Rinjani","Alam"]'],
  ['foto','foto','foto',
   'Potret: Keberagaman Busana Adat di Festival Nusantara 2025',
   'Ratusan peserta memamerkan keindahan pakaian adat dari 34 provinsi.',
   'Dokumentasi visual keindahan busana adat dari seluruh nusantara.',
   'Foto Desk','24 Juni 2025','38K','img/desa_wisata.png','["Foto","Budaya","Festival"]'],
  ['foto','foto','foto',
   'Foto Eksklusif: Suasana Persidangan KTT G20 dari Dalam Ruangan',
   'Momen-momen langka di dalam ruangan sidang G20 yang bersejarah.',
   'Foto eksklusif dari dalam ruang sidang G20 di Italia.',
   'Foto Desk','27 Juni 2025','31K','img/g20_summit.png','["Foto","G20","Internasional"]'],
];

$stmtArt = $pdo->prepare("INSERT INTO `articles` 
  (type, cat, badge, title, excerpt, content, author, date_display, views, img, tags, status) 
  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'published')");

$count = 0;
foreach (array_merge($articles, $videos, $photos) as $art) {
    try {
        $stmtArt->execute($art);
        $count++;
    } catch (Exception $e) {
        echo "<p class='err'>✘ Gagal menanam artikel: " . htmlspecialchars($art[3]) . " — " . $e->getMessage() . "</p>";
    }
}

echo "<p class='ok'>✔ $count artikel berhasil di-seed ke database</p>";

echo "<hr>";
echo "<h3 style='color:#059669'>🎉 Setup Selesai!</h3>";
echo "<p><b>Langkah selanjutnya:</b></p><ol>";
echo "<li>Buka <a href='/ARIWEB/' target='_blank'>http://localhost/ARIWEB/</a> — halaman utama</li>";
echo "<li>Buka <a href='/ARIWEB/admin/' target='_blank'>http://localhost/ARIWEB/admin/</a> — panel admin</li>";
echo "<li>Login dengan: <b>admin</b> / <b>admin123</b></li>";
echo "<li><b style='color:#dc2626'>HAPUS file setup.php ini setelah selesai!</b></li>";
echo "</ol>";
echo "<p><a href='/ARIWEB/' style='background:#059669;color:#fff;padding:10px 20px;border-radius:6px;text-decoration:none'>→ Buka Website</a> &nbsp;";
echo "<a href='/ARIWEB/admin/' style='background:#064e3b;color:#fff;padding:10px 20px;border-radius:6px;text-decoration:none'>→ Buka Admin Panel</a></p>";
