<?php
/**
 * MY TAYLOR - Database Installer & Migration Utility
 * Connects to MySQL, creates database if not exists, imports schema and seeds.
 */

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/database.php';

if (php_sapi_name() !== 'cli') {
    echo "<!DOCTYPE html><html><head><title>MY TAYLOR Database Setup</title><style>body{background:#0F172A;color:#F8FAFC;font-family:monospace;padding:30px;line-height:1.6;}pre{background:#1E293B;padding:20px;border-radius:8px;border:1px solid #334155;color:#E2E8F0;font-size:14px;}</style></head><body><h2>MY TAYLOR Database Setup & Migration</h2><pre>";
}

echo "========================================================\n";
echo "    MY TAYLOR - Database Initializer & Migration Tool   \n";
echo "========================================================\n\n";

try {
    $dbConfig = getDbConfig();
    $host = $dbConfig['host'];
    $port = $dbConfig['port'];
    $user = $dbConfig['user'];
    $pass = $dbConfig['pass'];
    $dbname = $dbConfig['name'];

    echo "[1/4] Connecting to MySQL database `{$dbname}` at {$host}:{$port}...\n";
    try {
        $pdo = new PDO("mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    } catch (PDOException $e) {
        if (strpos($_SERVER['HTTP_HOST'] ?? '', 'mytaylor.in') === false) {
            // Localhost: try creating database
            $pdoRoot = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            $pdoRoot->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo = new PDO("mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
        } else {
            throw $e;
        }
    }
    echo "  -> Connected successfully!\n\n";

    echo "[2/4] Initializing database schema...\n";
    $schemaSql = file_get_contents(ROOT_PATH . '/database/schema.sql');
    // Remove any CREATE DATABASE or USE statements that fail in shared hosting
    $cleanSchema = preg_replace('/CREATE\s+DATABASE[^;]+;/i', '', $schemaSql);
    $cleanSchema = preg_replace('/USE\s+[^;]+;/i', '', $cleanSchema);
    $pdo->exec($cleanSchema);
    echo "  -> Schema created / verified successfully!\n\n";

    echo "[3/4] Seeding initial data (services, staff, sample 24H orders)...\n";
    $seedSql = file_get_contents(ROOT_PATH . '/database/seed.sql');
    $cleanSeed = preg_replace('/USE\s+[^;]+;/i', '', $seedSql);
    $pdo->exec($cleanSeed);

    // Update password hashes with fresh bcrypt for password123
    $defaultHash = password_hash('password123', PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("UPDATE `users` SET `password_hash` = ? WHERE `password_hash` LIKE '$2y$%'");
    $stmt->execute([$defaultHash]);
    echo "  -> Demo accounts initialized with default password: 'password123'\n\n";

    echo "[4/4] Verifying database tables...\n";
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $tbl) {
        $count = $pdo->query("SELECT COUNT(*) FROM `{$tbl}`")->fetchColumn();
        echo "  - Table: `{$tbl}` ({$count} records)\n";
    }

    echo "\n========================================================\n";
    echo "  SUCCESS! MY TAYLOR Database is fully configured!      \n";
    echo "========================================================\n";

    if (php_sapi_name() !== 'cli') {
        echo "</pre><br><a href='../index.php' style='display:inline-block;padding:12px 24px;background:#D4AF37;color:#0B132B;text-decoration:none;border-radius:6px;font-family:sans-serif;font-weight:bold;'>Launch MY TAYLOR Website &rarr;</a></body></html>";
    }

} catch (Exception $e) {
    echo "\n[ERROR] Database setup failed:\n" . $e->getMessage() . "\n";
    if (php_sapi_name() !== 'cli') {
        echo "</pre><div style='background:#7F1D1D;color:#FECACA;padding:16px;border-radius:6px;margin-top:10px;'><strong>Setup Error:</strong> " . htmlspecialchars($e->getMessage()) . "</div></body></html>";
    }
}
