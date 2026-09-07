<?php
// includes/db.php — Database Connection PDO (Laragon & Railway / Production Environment)

function getDB(): PDO {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    // Check Railway DATABASE_URL / MYSQL_URL
    $dbUrl = getenv('DATABASE_URL') ?: getenv('MYSQL_URL');
    if ($dbUrl) {
        $parsed = parse_url($dbUrl);
        $host = $parsed['host'] ?? 'localhost';
        $port = $parsed['port'] ?? 3306;
        $user = $parsed['user'] ?? 'root';
        $pass = $parsed['pass'] ?? '';
        $name = ltrim($parsed['path'] ?? 'ariweb', '/');
    } else {
        $host = getenv('MYSQLHOST') ?: getenv('MYSQL_HOST') ?: getenv('DB_HOST') ?: 'localhost';
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
    ];

    try {
        $pdo = new PDO($dsn, $user, $pass, $options);
    } catch (PDOException $e) {
        // If database doesn't exist, attempt to create it (if user has permissions)
        try {
            $tempPdo = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $user, $pass, $options);
            $tempPdo->exec("CREATE DATABASE IF NOT EXISTS `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo = new PDO($dsn, $user, $pass, $options);
        } catch (Exception $e2) {
            http_response_code(500);
            die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
        }
    }

    // Auto-seed database if tables don't exist yet (useful for initial Railway deployment)
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
        // ignore auto-seed check errors
    }

    return $pdo;
}
