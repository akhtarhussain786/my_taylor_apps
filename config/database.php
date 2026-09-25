<?php
/**
 * MY TAYLOR - Database Configuration & PDO Factory
 */

function getDbConfig() {
    $isLive = (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'mytaylor.in') !== false);
    
    if ($isLive) {
        return [
            'host' => 'localhost',
            'port' => '3306',
            'user' => 'u586401351_MyTaylor',
            'pass' => 'MyTaylor@1122',
            'name' => 'u586401351_MyTaylor'
        ];
    }

    // Local / XAMPP Environment (or Default)
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

        // Ensure password_resets table exists
        try {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `password_resets` (
                  `id` INT AUTO_INCREMENT PRIMARY KEY,
                  `email` VARCHAR(150) NOT NULL,
                  `token` VARCHAR(100) NOT NULL UNIQUE,
                  `expires_at` DATETIME NOT NULL,
                  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                  INDEX `idx_token` (`token`),
                  INDEX `idx_email` (`email`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");

            // Ensure mytaylor302@gmail.com admin exists
            $chkAdmin = $pdo->prepare("SELECT id FROM `users` WHERE `email` = 'mytaylor302@gmail.com'");
            $chkAdmin->execute();
            if (!$chkAdmin->fetch()) {
                $hash = password_hash('password123', PASSWORD_BCRYPT);
                // Check if admin@mytaylor.com exists to update, otherwise insert
                $upd = $pdo->prepare("UPDATE `users` SET `email` = 'mytaylor302@gmail.com' WHERE `email` = 'admin@mytaylor.com' OR `id` = 1");
                $upd->execute();
                if ($upd->rowCount() === 0) {
                    $ins = $pdo->prepare("INSERT INTO `users` (`name`, `email`, `mobile`, `password_hash`, `role`, `status`) VALUES ('Admin Master', 'mytaylor302@gmail.com', '9800000000', ?, 'admin', 'active') ON DUPLICATE KEY UPDATE `email` = 'mytaylor302@gmail.com'");
                    $ins->execute([$hash]);
                }
            }
            // Ensure testimonials table exists and is populated
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `testimonials` (
                  `id` INT AUTO_INCREMENT PRIMARY KEY,
                  `customer_name` VARCHAR(100) NOT NULL,
                  `customer_role` VARCHAR(100) DEFAULT 'Verified Client',
                  `location` VARCHAR(100) DEFAULT 'Mumbai',
                  `rating` INT DEFAULT 5,
                  `review_text` TEXT NOT NULL,
                  `image_url` VARCHAR(255) DEFAULT NULL,
                  `garment_type` VARCHAR(100) DEFAULT 'Bespoke Suit',
                  `status` ENUM('active', 'inactive') DEFAULT 'active',
                  `display_order` INT DEFAULT 0,
                  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");

            $testiCount = (int)$pdo->query("SELECT COUNT(*) FROM `testimonials`")->fetchColumn();
            if ($testiCount === 0) {
                $insTesti = $pdo->prepare("INSERT INTO `testimonials` (`customer_name`, `customer_role`, `location`, `rating`, `review_text`, `image_url`, `garment_type`, `status`, `display_order`) VALUES (?, ?, ?, ?, ?, ?, ?, 'active', ?)");
                $sampleReviews = [
                    ['Vikramaditya Mehta', 'Managing Director, Horizon Capital', 'Bandra West, Mumbai', 5, 'Needed a bespoke Italian-cut 2-piece suit for an international investor summit on 24 hours notice. The master measurement executive arrived at my apartment with laser dockets, and the tailored garment was delivered to my doorstep next afternoon. Immaculate silhouette and pristine stitching!', 'assets/images/testimonial_1.jpg', 'Bespoke Suit & Shirt', 1],
                    ['Ananya Deshmukh', 'Fashion Designer & Stylist', 'Juhu, Mumbai', 5, 'The padded princess-cut designer saree blouse was executed with French seam precision and zero fabric pulling. The 24-hour express doorstep delivery turnaround is revolutionary in Mumbai bespoke fashion. Absolutely ecstatic with the finish!', 'assets/images/testimonial_2.jpg', 'Designer Saree Blouse', 2],
                    ['Rohan Singhania', 'Tech Founder & VP Engineering', 'Powai, Mumbai', 5, 'Live tracking my shirts from cutting bay to tailor station felt like watching an Apple keynote. The collar firmness and custom sleeve monogram are top-tier. No tailor in Mumbai matches this digital convenience and craft.', 'assets/images/testimonial_3.jpg', 'Custom Egyptian Shirts', 3],
                    ['Dr. Radhika Sen', 'Senior Consultant Surgeon', 'South Mumbai', 5, 'As a doctor with erratic hospital shifts, visiting tailor shops was impossible. MY TAYLOR scheduled a 7:30 PM measurement visit, took 15 anatomical points, and delivered 2 formal trousers and a blazer flawlessly within 24 hours.', 'assets/images/testimonial_4.jpg', 'Tailored Trousers & Blazer', 4]
                ];
                foreach ($sampleReviews as $r) {
                    $insTesti->execute($r);
                }
            }
        } catch (Exception $e) {
            // Non-blocking schema assurance
        }
    }
    return $pdo;
}
