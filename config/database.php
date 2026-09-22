<?php
/**
 * MY TAYLOR - Database Configuration & PDO Factory
 */

function getDbConfig() {
    return [
        'host' => '127.0.0.1',
        'port' => '3306',
        'user' => 'root',
        'pass' => '',
        'name' => 'my_taylor_db'
    ];
}

function getDbConnection() {
    static $pdo = null;
    if ($pdo === null) {
        $cfg = getDbConfig();
        $dsn = "mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['name']};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], $options);
        } catch (PDOException $e) {
            // Check if database doesn't exist, try connecting without dbname and auto-initialize
            try {
                $rootPdo = new PDO("mysql:host={$cfg['host']};port={$cfg['port']};charset=utf8mb4", $cfg['user'], $cfg['pass'], $options);
                $rootPdo->exec("CREATE DATABASE IF NOT EXISTS `{$cfg['name']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], $options);
            } catch (Exception $ex) {
                die("<h3>Database Connection Error</h3><p>" . htmlspecialchars($e->getMessage()) . "</p><p>Please make sure MySQL is running in XAMPP and run <a href='database/setup.php'>database/setup.php</a></p>");
            }
        }
    }
    return $pdo;
}
