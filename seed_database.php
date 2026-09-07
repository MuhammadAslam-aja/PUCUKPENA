<?php
// seed_database.php — Mengisi database MySQL ariweb dengan 101 artikel lengkap, ads, dan breaking news
require_once __DIR__ . '/includes/db.php';

try {
    $db = getDB();
    echo "Koneksi database berhasil!\n";

    // Pastikan tabel siap
    $db->exec("
    CREATE TABLE IF NOT EXISTS `admin_users` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `username` VARCHAR(100) NOT NULL UNIQUE,
      `password` VARCHAR(255) NOT NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $db->exec("
    CREATE TABLE IF NOT EXISTS `articles` (
      `id` INT PRIMARY KEY,
      `type` VARCHAR(100) NOT NULL DEFAULT 'Berita Utama',
      `cat` VARCHAR(50) NOT NULL DEFAULT 'berita',
      `badge` VARCHAR(50) NOT NULL DEFAULT 'berita',
      `title` VARCHAR(500) NOT NULL,
      `excerpt` TEXT,
      `content` LONGTEXT,
      `author` VARCHAR(200),
      `date_display` VARCHAR(100),
      `views` VARCHAR(20) DEFAULT '0',
      `img` TEXT,
      `tags` VARCHAR(500) DEFAULT '[]',
      `status` ENUM('draft','published') DEFAULT 'published',
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $db->exec("
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

    $db->exec("
    CREATE TABLE IF NOT EXISTS `breaking_news` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `text` VARCHAR(500) NOT NULL,
      `active` TINYINT(1) DEFAULT 1,
      `sort_order` INT DEFAULT 0,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // 1. Admin default
    $adminPass = password_hash('admin123', PASSWORD_BCRYPT);
    $stmt = $db->prepare("INSERT INTO `admin_users` (username, password) VALUES (?, ?) ON DUPLICATE KEY UPDATE password = VALUES(password)");
    $stmt->execute(['admin', $adminPass]);
    echo "✔ Admin default (admin / admin123) siap!\n";

    // 2. Ads default (3 manual, 2 Google Ads)
    $db->exec("TRUNCATE TABLE `ads`");
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
         '', 'ca-pub-XXXXXXXXXXXXXXXX', '1234567890', '', 1],
        ['Google Ads — Sticky Bottom', 'google', 'google_sticky',
         '', 'ca-pub-XXXXXXXXXXXXXXXX', '0987654321', '', 1],
    ];
    $stmtA = $db->prepare("INSERT INTO `ads` (name, type, slot, content, google_client, google_slot, url, active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($ads as $ad) {
        $stmtA->execute($ad);
    }
    echo "✔ Iklan default (3 manual + 2 Google Ads) siap!\n";

    // 3. Breaking news default
    $db->exec("TRUNCATE TABLE `breaking_news`");
    $breaking = [
        ['🔴 BREAKING: Pemerintah Umumkan Kebijakan Energi Terbarukan Baru', 1, 1],
        ['⚡ TERKINI: Timnas Indonesia Menang di Kualifikasi Piala Asia', 1, 2],
        ['🌿 UPDATE: Deklarasi Bersama Ekonomi Digital ASEAN Disahkan', 1, 3],
        ['📢 INFO: Program Beasiswa Nasional 2025 Dibuka untuk Mahasiswa', 1, 4],
    ];
    $stmtB = $db->prepare("INSERT INTO `breaking_news` (text, active, sort_order) VALUES (?, ?, ?)");
    foreach ($breaking as $b) {
        $stmtB->execute($b);
    }
    echo "✔ Breaking news default siap!\n";

    // 4. Seed all 101 articles from articles_normalized.json
    $jsonFile = __DIR__ . '/articles_normalized.json';
    if (!file_exists($jsonFile)) {
        die("Error: articles_normalized.json tidak ditemukan!\n");
    }
    $articles = json_decode(file_get_contents($jsonFile), true);

    $db->exec("TRUNCATE TABLE `articles`");
    $stmtArt = $db->prepare("INSERT INTO `articles` 
      (id, type, cat, badge, title, excerpt, content, author, date_display, views, img, tags, status) 
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'published')");

    $count = 0;
    foreach ($articles as $a) {
        $tagsJson = json_encode($a['tags'] ?? [], JSON_UNESCAPED_UNICODE);
        $stmtArt->execute([
            $a['id'],
            $a['type'],
            $a['cat'],
            $a['badge'],
            $a['title'],
            $a['excerpt'] ?? '',
            $a['content'] ?? '',
            $a['author'] ?? 'Redaksi',
            $a['date'] ?? date('d F Y'),
            $a['views'] ?? '10K',
            $a['img'] ?? 'img/desa_wisata.png',
            $tagsJson
        ]);
        $count++;
    }

    echo "✔ Berhasil menanam $count artikel ke tabel MySQL `articles`!\n";
    echo "Semua artikel siap dibaca dan diakses secara dinamis!\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
