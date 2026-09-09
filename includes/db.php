<?php
// includes/db.php — Database Connection PDO (Laragon & Railway / Production Environment)

function getDB(): PDO {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    // 1. Ambil URL koneksi database dari variabel Railway / Cloud
    $dbUrl = getenv('DATABASE_URL') ?: getenv('MYSQL_URL') ?: getenv('MYSQL_PRIVATE_URL') ?: getenv('MYSQL_PUBLIC_URL');
    
    if ($dbUrl) {
        $parsed = parse_url($dbUrl);
        $host = $parsed['host'] ?? '127.0.0.1';
        $port = $parsed['port'] ?? 3306;
        $user = $parsed['user'] ?? 'root';
        $pass = $parsed['pass'] ?? '';
        $name = ltrim($parsed['path'] ?? 'railway', '/');
    } else {
        // Ambil variabel individual Railway / Standar
        $host = getenv('MYSQLHOST') ?: getenv('MYSQL_HOST') ?: getenv('DB_HOST') ?: '127.0.0.1';
        $port = getenv('MYSQLPORT') ?: getenv('MYSQL_PORT') ?: getenv('DB_PORT') ?: '3306';
        $user = getenv('MYSQLUSER') ?: getenv('MYSQL_USER') ?: getenv('DB_USER') ?: 'root';
        $pass = getenv('MYSQLPASSWORD') ?: getenv('MYSQL_PASSWORD') ?: (getenv('DB_PASS') !== false && getenv('DB_PASS') !== null ? getenv('DB_PASS') : '');
        $name = getenv('MYSQLDATABASE') ?: getenv('MYSQL_DATABASE') ?: getenv('DB_NAME') ?: 'ariweb';
    }

    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_MULTI_STATEMENTS => true,
    ];

    try {
        $pdo = new PDO($dsn, $user, $pass, $options);
    } catch (PDOException $e) {
        // Coba buat database otomatis jika belum ada di server MySQL
        try {
            $tempPdo = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $user, $pass, $options);
            $tempPdo->exec("CREATE DATABASE IF NOT EXISTS `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo = new PDO($dsn, $user, $pass, $options);
        } catch (Exception $e2) {
            // Tampilkan panduan elegan jika belum terhubung di Railway
            renderDbSetupGuide($e->getMessage(), $host, $port, $name);
            exit;
        }
    }

    // 2. Auto-seed: jika tabel articles belum ada, impor otomatis seluruh data dari database.sql
    try {
        $check = $pdo->query("SHOW TABLES LIKE 'articles'")->rowCount();
        if ($check === 0) {
            $dumpFile = __DIR__ . '/../database.sql';
            if (file_exists($dumpFile)) {
                $sql = file_get_contents($dumpFile);
                if (!empty($sql)) {
                    $pdo->exec($sql);
                }
            }
        }
    } catch (Exception $e) {
        // Lanjutkan jika auto-seed ada kendala minor
    }

    // 3. Auto-migration: Pastikan tabel comments & kolom ads.image selalu ada
    try {
        // Tabel comments
        $pdo->exec("CREATE TABLE IF NOT EXISTS `comments` (
            `id` int NOT NULL AUTO_INCREMENT,
            `article_id` int NOT NULL,
            `name` varchar(150) NOT NULL,
            `comment` text NOT NULL,
            `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `article_id` (`article_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Seed komentar awal jika tabel masih kosong
        $commCount = $pdo->query("SELECT COUNT(*) FROM `comments`")->fetchColumn();
        if ((int)$commCount === 0) {
            $pdo->exec("INSERT INTO `comments` (`article_id`, `name`, `comment`, `created_at`) VALUES
                (1, 'Ahmad Fauzi', 'Pembahasan yang sangat mendalam dan berbobot. Senang membaca ulasan di Pucuk Pena.', NOW() - INTERVAL 2 HOUR),
                (1, 'Siti Rahma', 'Setuju sekali. Sangat relevan dengan kondisi lapangan saat ini.', NOW() - INTERVAL 1 HOUR)");
        }

        // Kolom image pada tabel ads
        $colCheck = $pdo->query("SHOW COLUMNS FROM `ads` LIKE 'image'")->rowCount();
        if ($colCheck === 0) {
            $pdo->exec("ALTER TABLE `ads` ADD COLUMN `image` varchar(500) DEFAULT NULL AFTER `active`");
            // Set gambar default jika kosong
            $pdo->exec("UPDATE `ads` SET `image` = 'img/desa_wisata.png' WHERE `slot` = 'leaderboard' AND (`image` IS NULL OR `image` = '')");
            $pdo->exec("UPDATE `ads` SET `image` = 'img/ai_startup.png' WHERE `slot` = 'native1' AND (`image` IS NULL OR `image` = '')");
            $pdo->exec("UPDATE `ads` SET `image` = 'img/timnas_football.png' WHERE `slot` = 'native2' AND (`image` IS NULL OR `image` = '')");
        }
        // Migrasi Hikmat -> Hikmah jika masih ada artikel dengan type 'Hikmat'
        $pdo->exec("UPDATE `articles` SET `type` = 'Hikmah' WHERE `type` = 'Hikmat'");
    } catch (Exception $e) {
        // Lanjutkan jika migrasi telah terpasang
    }


    return $pdo;
}

function renderDbSetupGuide(string $errorMsg, string $host, $port, string $dbname) {
    http_response_code(500);
    $isJson = isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false;
    if ($isJson) {
        header('Content-Type: application/json');
        echo json_encode([
            'error' => 'Database connection failed: ' . $errorMsg,
            'hint'  => 'Hubungkan variabel layanan MySQL ke layanan PUCUKPENA di Railway.'
        ]);
        exit;
    }
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Setup Database Railway — Pucuk Pena</title>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
        <style>
            :root {
                --green-900: #022c22;
                --green-700: #047857;
                --green-600: #059669;
                --green-50: #ecfdf5;
                --bg: #f8fafc;
                --card: #ffffff;
                --text: #0f172a;
                --muted: #64748b;
                --border: #e2e8f0;
            }
            * { box-sizing: border-box; margin: 0; padding: 0; }
            body { font-family: 'Plus Jakarta Sans', sans-serif; background: var(--bg); color: var(--text); padding: 40px 20px; line-height: 1.6; display: grid; place-items: center; min-height: 100vh; }
            .setup-card { background: var(--card); border: 1px solid var(--border); border-radius: 16px; max-width: 680px; width: 100%; padding: 36px; box-shadow: 0 10px 40px rgba(0,0,0,0.06); }
            .header-logo { display: flex; align-items: center; gap: 12px; margin-bottom: 24px; padding-bottom: 20px; border-bottom: 1px solid var(--border); }
            .header-logo h2 { font-size: 1.25rem; font-weight: 800; color: var(--green-900); }
            .badge-railway { background: rgba(5,150,105,0.1); color: var(--green-700); padding: 4px 10px; border-radius: 50px; font-size: 0.75rem; font-weight: 700; }
            h1 { font-size: 1.45rem; font-weight: 800; margin-bottom: 12px; color: var(--text); }
            p.lead { color: var(--muted); font-size: 0.95rem; margin-bottom: 24px; }
            .step-box { background: var(--green-50); border: 1px solid #a7f3d0; border-radius: 12px; padding: 20px; margin-bottom: 24px; }
            .step-box h3 { color: var(--green-900); font-size: 1rem; margin-bottom: 12px; display: flex; align-items: center; gap: 8px; }
            .step-list { padding-left: 20px; color: #065f46; font-size: 0.9rem; }
            .step-list li { margin-bottom: 10px; }
            .code-pill { background: #064e3b; color: #a7f3d0; padding: 3px 8px; border-radius: 6px; font-family: monospace; font-size: 0.85rem; }
            .error-details { background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 12px 16px; font-size: 0.82rem; color: #991b1b; margin-bottom: 24px; font-family: monospace; }
            .btn-refresh { display: inline-flex; align-items: center; justify-content: center; gap: 8px; width: 100%; padding: 14px 20px; background: var(--green-700); color: #fff; border: none; border-radius: 10px; font-weight: 700; font-size: 0.95rem; cursor: pointer; transition: 0.2s; text-decoration: none; }
            .btn-refresh:hover { background: var(--green-600); }
        </style>
    </head>
    <body>
        <div class="setup-card">
            <div class="header-logo">
                <h2>PUCUK PENA</h2>
                <span class="badge-railway">Railway Hosting Guide</span>
            </div>
            
            <h1>Layanan MySQL Belum Dihubungkan</h1>
            <p class="lead">Container web PUCUKPENA sudah berjalan aktif, namun perlu dihubungkan ke layanan MySQL yang sudah Anda buat di Railway.</p>

            <div class="step-box">
                <h3>Cara Menghubungkan di Dashboard Railway (Hanya 1 Langkah):</h3>
                <ol class="step-list">
                    <li>Buka tab project Railway Anda (tab <strong>MySQL</strong> yang ada di sebelah tab ini).</li>
                    <li>Klik pada kotak layanan <strong>PUCUKPENA</strong> (web service).</li>
                    <li>Buka tab <strong>Variables</strong>.</li>
                    <li>Klik tombol <strong>+ New Variable</strong> lalu pilih <strong>Add Reference</strong>.</li>
                    <li>Pilih layanan <strong>MySQL</strong>, lalu pilih <strong>MYSQL_URL</strong> (atau pilih semua variabel MySQL).</li>
                    <li>Klik tombol <strong>Muat Ulang</strong> di bawah ini setelah Railway me-redeploy (otomatis).</li>
                </ol>
            </div>

            <div class="error-details">
                <strong>Detail Error:</strong> <?= htmlspecialchars($errorMsg) ?><br>
                <strong>Host yang Dicoba:</strong> <?= htmlspecialchars($host) ?>:<?= htmlspecialchars($port) ?>
            </div>

            <a href="javascript:location.reload()" class="btn-refresh">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l6.07-1.19"/></svg>
                Periksa Koneksi Ulang / Refresh Halaman
            </a>
        </div>
    </body>
    </html>
    <?php
}
