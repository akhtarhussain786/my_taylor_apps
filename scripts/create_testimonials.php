<?php
require_once __DIR__ . '/../config/config.php';

try {
    $pdo = getDbConnection();
    
    // 1. Create Testimonials Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `testimonials` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    
    // 2. Check if testimonials exist, seed realistic luxury reviews if empty
    $count = $pdo->query("SELECT COUNT(*) FROM `testimonials`")->fetchColumn();
    if ($count == 0) {
        $stmt = $pdo->prepare("INSERT INTO `testimonials` (`customer_name`, `customer_role`, `location`, `rating`, `review_text`, `image_url`, `garment_type`, `display_order`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        $samples = [
            [
                'Vikram Singhania',
                'Managing Director, Fintech Venture',
                'South Mumbai',
                5,
                'Booked a bespoke 3-piece tuxedo for an international summit on Thursday morning. The master specialist visited my Nariman Point office, took 15 precision points, and the hand-finished suit was delivered Friday at 11 AM. The fit was sharper than Savile Row off-the-rack.',
                'assets/images/testimonial_1.jpg',
                '3-Piece Italian Wool Tuxedo',
                1
            ],
            [
                'Ananya Deshmukh',
                'Fashion Architect & Stylist',
                'Bandra West',
                5,
                'Getting a designer bridal blouse tailored in Mumbai usually takes 2 weeks of endless trials. MY TAYLOR master executive arrived with swatch books, measured digitally, and delivered my padded raw-silk blouse in exactly 23 hours. Pure perfection.',
                'assets/images/testimonial_2.jpg',
                'Raw Silk Padded Designer Blouse',
                2
            ],
            [
                'Rohan Mehra',
                'Founder, Mehra Capital',
                'Juhu',
                5,
                'The live tracker on WhatsApp and the web dashboard gave me real-time visibility from master cutting to Gutermann thread stitching and courier dispatch. Zero showroom visits, genuine 24-hour turnaround.',
                'assets/images/testimonial_3.jpg',
                'Egyptian Cotton Bespoke Shirts (Pack of 3)',
                3
            ],
            [
                'Kavita Sen',
                'Creative Director',
                'Worli Seaface',
                5,
                'Emergency fitting for an awards gala tonight! I requested 24H express alteration for my evening gown and suit trousers. Rider picked it up, atelier finished the hem & waist taper, and brought it back before lunch. Lifesavers!',
                'assets/images/testimonial_4.jpg',
                'Express Luxury Alterations',
                4
            ]
        ];
        
        foreach ($samples as $s) {
            $stmt->execute($s);
        }
    }
    
    // Ensure uploads/testimonials directory exists
    $uploadDir = __DIR__ . '/../uploads/testimonials';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    echo "Testimonials setup completed successfully!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
