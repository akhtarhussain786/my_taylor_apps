-- ==========================================================
-- MY TAYLOR - Database Schema
-- Doorstep Tailoring & 24-Hour Express Delivery Platform
-- ==========================================================

CREATE DATABASE IF NOT EXISTS `u586401351_MyTaylor` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `u586401351_MyTaylor`;

-- 1. Users Table (Core Auth & Roles)
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(120) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `mobile` VARCHAR(20) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('customer', 'measurement_executive', 'cutting_staff', 'tailor', 'qc_staff', 'packing_staff', 'delivery_executive', 'admin') NOT NULL DEFAULT 'customer',
  `status` ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
  `gender` ENUM('male', 'female', 'other') DEFAULT NULL,
  `profile_image` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Customer Profiles
CREATE TABLE IF NOT EXISTS `customer_profiles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL UNIQUE,
  `preferred_language` VARCHAR(50) DEFAULT 'English',
  `notes` TEXT DEFAULT NULL,
  `total_orders` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 3. Addresses
CREATE TABLE IF NOT EXISTS `addresses` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `title` VARCHAR(50) DEFAULT 'Home', -- Home, Office, Other
  `house_no` VARCHAR(100) NOT NULL,
  `building` VARCHAR(150) DEFAULT NULL,
  `street` VARCHAR(150) NOT NULL,
  `area` VARCHAR(150) NOT NULL,
  `landmark` VARCHAR(150) DEFAULT NULL,
  `city` VARCHAR(100) NOT NULL DEFAULT 'Mumbai',
  `state` VARCHAR(100) NOT NULL DEFAULT 'Maharashtra',
  `pincode` VARCHAR(10) NOT NULL,
  `latitude` DECIMAL(10, 8) DEFAULT NULL,
  `longitude` DECIMAL(11, 8) DEFAULT NULL,
  `is_default` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 4. Service Categories & Garments
CREATE TABLE IF NOT EXISTS `services` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `category` ENUM('men', 'women', 'custom', 'alteration') NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  `slug` VARCHAR(120) NOT NULL UNIQUE,
  `description` TEXT,
  `base_price` DECIMAL(10, 2) NOT NULL DEFAULT 499.00,
  `express_price` DECIMAL(10, 2) NOT NULL DEFAULT 199.00,
  `measurement_fee` DECIMAL(10, 2) NOT NULL DEFAULT 99.00,
  `is_24h_eligible` TINYINT(1) DEFAULT 1,
  `estimated_hours` INT DEFAULT 24,
  `image_url` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('active', 'inactive') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 5. Fabrics & Inventory
CREATE TABLE IF NOT EXISTS `fabrics` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `sku` VARCHAR(50) NOT NULL UNIQUE,
  `name` VARCHAR(150) NOT NULL,
  `category` VARCHAR(80) NOT NULL,
  `color` VARCHAR(50) NOT NULL,
  `pattern` VARCHAR(50) DEFAULT 'Solid',
  `price_per_meter` DECIMAL(10, 2) NOT NULL DEFAULT 599.00,
  `stock_meters` DECIMAL(10, 2) NOT NULL DEFAULT 100.00,
  `reorder_level` DECIMAL(10, 2) DEFAULT 20.00,
  `image_url` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('in_stock', 'low_stock', 'out_of_stock') DEFAULT 'in_stock',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 6. Service Areas & Capacity
CREATE TABLE IF NOT EXISTS `service_areas` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `city` VARCHAR(100) NOT NULL,
  `area_name` VARCHAR(150) NOT NULL,
  `pincode` VARCHAR(10) NOT NULL UNIQUE,
  `is_active` TINYINT(1) DEFAULT 1,
  `is_24h_available` TINYINT(1) DEFAULT 1,
  `max_daily_capacity` INT DEFAULT 30,
  `current_booked_today` INT DEFAULT 0,
  `measurement_fee` DECIMAL(10, 2) DEFAULT 99.00,
  `delivery_charge` DECIMAL(10, 2) DEFAULT 0.00,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 7. Appointments (Doorstep Measurement Bookings)
CREATE TABLE IF NOT EXISTS `appointments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `appointment_code` VARCHAR(50) NOT NULL UNIQUE, -- e.g. APT-20260920-001
  `customer_id` INT NOT NULL,
  `service_id` INT NOT NULL,
  `address_id` INT NOT NULL,
  `executive_id` INT DEFAULT NULL, -- assigned measurement executive
  `appointment_date` DATE NOT NULL,
  `time_slot` VARCHAR(50) NOT NULL, -- e.g. "10:00 AM – 11:00 AM"
  `status` ENUM('BOOKED', 'EXECUTIVE_ASSIGNED', 'EXECUTIVE_ON_THE_WAY', 'EXECUTIVE_ARRIVED', 'MEASUREMENT_STARTED', 'MEASUREMENT_COMPLETED', 'CANCELLED', 'RESCHEDULED', 'NO_SHOW') NOT NULL DEFAULT 'BOOKED',
  `notes` TEXT DEFAULT NULL,
  `delivery_preference` ENUM('24H_EXPRESS', 'STANDARD') DEFAULT '24H_EXPRESS',
  `completed_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`customer_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT,
  `created_at_idx` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 8. Digital Measurement Profiles
CREATE TABLE IF NOT EXISTS `measurements` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `measurement_code` VARCHAR(50) NOT NULL UNIQUE, -- e.g. MT-M-0001028
  `customer_id` INT NOT NULL,
  `garment_category` VARCHAR(80) NOT NULL, -- Shirt, Trouser, Kurta, Blouse, Suit, etc.
  `measurements_json` JSON NOT NULL, -- e.g. {"neck": 15.5, "chest": 40, "waist": 34, ...}
  `fit_preference` ENUM('Slim Fit', 'Regular Fit', 'Comfort Fit', 'Loose Fit') DEFAULT 'Regular Fit',
  `design_specs_json` JSON DEFAULT NULL, -- collar, sleeve, cuff, pocket, button
  `reference_image` VARCHAR(255) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_by_user_id` INT DEFAULT NULL, -- executive who took it
  `is_active_profile` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`customer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 9. Orders
CREATE TABLE IF NOT EXISTS `orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `booking_id` VARCHAR(50) NOT NULL UNIQUE, -- e.g. MYT-20260920-001245
  `customer_id` INT NOT NULL,
  `appointment_id` INT DEFAULT NULL,
  `measurement_id` INT DEFAULT NULL,
  `service_id` INT NOT NULL,
  `fabric_source` ENUM('CUSTOMER_PROVIDED', 'MY_TAYLOR_FABRIC') DEFAULT 'MY_TAYLOR_FABRIC',
  `fabric_id` INT DEFAULT NULL,
  `delivery_address_id` INT NOT NULL,
  `priority` ENUM('NORMAL', 'EXPRESS', 'URGENT') DEFAULT 'EXPRESS',
  `is_24h_delivery` TINYINT(1) DEFAULT 1,
  `tailoring_charge` DECIMAL(10, 2) NOT NULL DEFAULT 599.00,
  `fabric_charge` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `measurement_fee` DECIMAL(10, 2) NOT NULL DEFAULT 99.00,
  `express_fee` DECIMAL(10, 2) NOT NULL DEFAULT 199.00,
  `customization_fee` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `discount_amount` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `total_amount` DECIMAL(10, 2) NOT NULL DEFAULT 897.00,
  `payment_method` ENUM('UPI', 'CARD', 'NET_BANKING', 'COD') DEFAULT 'UPI',
  `payment_status` ENUM('PENDING', 'PAID', 'FAILED', 'REFUND_PENDING', 'REFUNDED') DEFAULT 'PAID',
  `order_status` ENUM(
    'ORDER_CREATED',
    'MEASUREMENT_COMPLETED',
    'ORDER_CONFIRMED',
    'FABRIC_READY',
    'CUTTING_ASSIGNED',
    'CUTTING_IN_PROGRESS',
    'CUTTING_COMPLETED',
    'STITCHING_ASSIGNED',
    'STITCHING_IN_PROGRESS',
    'STITCHING_COMPLETED',
    'FINISHING',
    'QUALITY_CHECK',
    'QC_PASSED',
    'PACKING',
    'READY_FOR_DISPATCH',
    'DELIVERY_ASSIGNED',
    'OUT_FOR_DELIVERY',
    'DELIVERED',
    'REWORK',
    'ON_HOLD',
    'CANCELLED'
  ) NOT NULL DEFAULT 'ORDER_CONFIRMED',
  `sla_start_time` DATETIME DEFAULT NULL,
  `sla_deadline` DATETIME DEFAULT NULL,
  `delivered_at` DATETIME DEFAULT NULL,
  `assigned_cutting_id` INT DEFAULT NULL,
  `assigned_tailor_id` INT DEFAULT NULL,
  `assigned_qc_id` INT DEFAULT NULL,
  `assigned_packing_id` INT DEFAULT NULL,
  `assigned_delivery_id` INT DEFAULT NULL,
  `cancellation_reason` VARCHAR(255) DEFAULT NULL,
  `special_instructions` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`customer_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- 10. Production Tasks & Timeline
CREATE TABLE IF NOT EXISTS `production_tasks` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `stage` ENUM('MEASUREMENT', 'FABRIC_PREP', 'CUTTING', 'STITCHING', 'FINISHING', 'QC', 'PACKING', 'DELIVERY') NOT NULL,
  `assigned_user_id` INT DEFAULT NULL,
  `status` ENUM('PENDING', 'IN_PROGRESS', 'COMPLETED', 'FAILED', 'REWORK') DEFAULT 'PENDING',
  `notes` TEXT DEFAULT NULL,
  `started_at` DATETIME DEFAULT NULL,
  `completed_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 11. QC Inspection Logs
CREATE TABLE IF NOT EXISTS `qc_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `inspector_id` INT NOT NULL,
  `checklist_json` JSON NOT NULL, -- measurements, stitching, fabric, buttons, symmetry, ironing
  `result` ENUM('PASS', 'REWORK_REQUIRED') NOT NULL,
  `rework_reason` VARCHAR(255) DEFAULT NULL,
  `rework_target_stage` ENUM('CUTTING', 'STITCHING', 'FINISHING') DEFAULT NULL,
  `comments` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 12. Delivery Proof & Verification (No OTP as per PRD)
CREATE TABLE IF NOT EXISTS `delivery_proofs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL UNIQUE,
  `delivery_executive_id` INT NOT NULL,
  `verification_type` ENUM('SIGNATURE_AND_PHOTO', 'BOOKING_ID_CONFIRMATION', 'QR_SCAN', 'CUSTOMER_NAME') DEFAULT 'SIGNATURE_AND_PHOTO',
  `customer_signature_svg` MEDIUMTEXT DEFAULT NULL,
  `delivery_photo_url` VARCHAR(255) DEFAULT NULL,
  `recipient_name` VARCHAR(120) DEFAULT NULL,
  `recipient_relation` VARCHAR(50) DEFAULT 'Self',
  `delivery_lat` DECIMAL(10, 8) DEFAULT NULL,
  `delivery_lng` DECIMAL(11, 8) DEFAULT NULL,
  `delivered_timestamp` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 13. System Audit Logs
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT DEFAULT NULL,
  `appointment_id` INT DEFAULT NULL,
  `user_id` INT DEFAULT NULL,
  `action` VARCHAR(100) NOT NULL,
  `previous_state` VARCHAR(100) DEFAULT NULL,
  `new_state` VARCHAR(100) DEFAULT NULL,
  `description` TEXT,
  `ip_address` VARCHAR(50) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 14. Customer Reviews & Ratings
CREATE TABLE IF NOT EXISTS `reviews` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL UNIQUE,
  `customer_id` INT NOT NULL,
  `overall_rating` INT DEFAULT 5,
  `measurement_rating` INT DEFAULT 5,
  `stitching_rating` INT DEFAULT 5,
  `delivery_rating` INT DEFAULT 5,
  `comment` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 15. Customer Testimonials & Showcase
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
