<?php
/**
 * MY TAYLOR - Database Installer & Migration Utility
 * Connects to MySQL, creates database if not exists, imports schema and seeds.
 */

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/database.php';

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

    echo "[1/4] Connecting to MySQL server at {$host}:{$port}...\n";
    $pdo = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    echo "  -> Connected successfully!\n\n";

    echo "[2/4] Initializing database `{$dbname}` & schema...\n";
    $schemaSql = file_get_contents(ROOT_PATH . '/database/schema.sql');
    $pdo->exec($schemaSql);
    echo "  -> Schema created / verified successfully!\n\n";

    $pdo->exec("USE `{$dbname}`");

    echo "[3/4] Seeding initial data (services, staff, sample 24H orders)...\n";
    $seedSql = file_get_contents(ROOT_PATH . '/database/seed.sql');
    $pdo->exec($seedSql);

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
        echo "<br><a href='../index.php' style='display:inline-block;padding:12px 24px;background:#0B132B;color:#D4AF37;text-decoration:none;border-radius:6px;font-family:sans-serif;'>Launch MY TAYLOR Website</a>";
    }

} catch (Exception $e) {
    echo "\n[ERROR] Database setup failed:\n" . $e->getMessage() . "\n";
    if (php_sapi_name() !== 'cli') {
        http_response_code(500);
    }
}
