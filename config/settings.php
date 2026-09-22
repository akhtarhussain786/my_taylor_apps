<?php
/**
 * MY TAYLOR - System Settings & Cashfree Gateway Configuration Helper
 */

require_once __DIR__ . '/database.php';

// Ensure system_settings table exists
function initSettingsTable($pdo) {
    $pdo->exec("
      CREATE TABLE IF NOT EXISTS `system_settings` (
        `setting_key` VARCHAR(100) PRIMARY KEY,
        `setting_value` TEXT NULL,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
      ) ENGINE=InnoDB;
    ");

    // Insert defaults if empty
    $defaults = [
        'cashfree_app_id'        => 'TEST_APP_ID_MYTAYLOR',
        'cashfree_secret_key'    => 'TEST_SECRET_KEY_MYTAYLOR',
        'cashfree_mode'          => 'TEST', // TEST or PROD
        'cashfree_enabled'       => '1',
        'cod_enabled'            => '1',
        'default_measurement_fee'=> '99.00',
        'default_express_fee'    => '199.00',
        'company_phone'          => '+91 98000 00000',
        'company_email'          => 'concierge@mytaylor.com',
        'sla_guarantee_hours'    => '24'
    ];

    foreach ($defaults as $k => $v) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO `system_settings` (`setting_key`, `setting_value`) VALUES (?, ?)");
        $stmt->execute([$k, $v]);
    }
}

// Get single setting
function getSetting($key, $default = null) {
    static $settingsCache = null;
    $pdo = getDbConnection();
    
    if ($settingsCache === null) {
        initSettingsTable($pdo);
        $stmt = $pdo->query("SELECT `setting_key`, `setting_value` FROM `system_settings`");
        $settingsCache = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }
    
    return $settingsCache[$key] ?? $default;
}

// Update settings array
function saveSettings($settingsArray) {
    $pdo = getDbConnection();
    initSettingsTable($pdo);
    $stmt = $pdo->prepare("REPLACE INTO `system_settings` (`setting_key`, `setting_value`) VALUES (?, ?)");
    foreach ($settingsArray as $k => $v) {
        $stmt->execute([$k, (string)$v]);
    }
}
