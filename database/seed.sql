-- ==========================================================
-- MY TAYLOR - Demo Seed Data
-- ==========================================================

USE `my_taylor_db`;

-- Passwords are all: password123 (hashed with password_hash PASSWORD_BCRYPT)
-- $2y$10$tZ2X5y1234567890abcdef...
-- We will compute the hash dynamically in setup.php or use standard bcrypt hash:
-- '$2y$10$wTf4Jv81m7L7r7.o3b3rveE7XjY8FzO7Hj/mJc2i21Y5R7x.qU7K6'

INSERT INTO `users` (`id`, `name`, `email`, `mobile`, `password_hash`, `role`, `status`, `gender`) VALUES
(1, 'Admin Master', 'mytaylor302@gmail.com', '9800000000', '$2y$10$VLD/S4qI11FBJdk1GVvzwuo06mgLCRNeciGElL72q8VFdoOLRceW2', 'admin', 'active', 'male'),
(2, 'Vikram Singh (Executive)', 'exec.vikram@mytaylor.com', '9800000001', '$2y$10$VLD/S4qI11FBJdk1GVvzwuo06mgLCRNeciGElL72q8VFdoOLRceW2', 'measurement_executive', 'active', 'male'),
(3, 'Ramesh Kumar (Master Cutter)', 'cutting.ramesh@mytaylor.com', '9800000002', '$2y$10$VLD/S4qI11FBJdk1GVvzwuo06mgLCRNeciGElL72q8VFdoOLRceW2', 'cutting_staff', 'active', 'male'),
(4, 'Master Anwar (Senior Tailor)', 'tailor.anwar@mytaylor.com', '9800000003', '$2y$10$VLD/S4qI11FBJdk1GVvzwuo06mgLCRNeciGElL72q8VFdoOLRceW2', 'tailor', 'active', 'male'),
(5, 'Meera Deshmukh (QC Lead)', 'qc.meera@mytaylor.com', '9800000004', '$2y$10$VLD/S4qI11FBJdk1GVvzwuo06mgLCRNeciGElL72q8VFdoOLRceW2', 'qc_staff', 'active', 'female'),
(6, 'Suresh Verma (Packaging)', 'pack.suresh@mytaylor.com', '9800000005', '$2y$10$VLD/S4qI11FBJdk1GVvzwuo06mgLCRNeciGElL72q8VFdoOLRceW2', 'packing_staff', 'active', 'male'),
(7, 'Rohit Sharma (Express Rider)', 'delivery.rohit@mytaylor.com', '9800000006', '$2y$10$VLD/S4qI11FBJdk1GVvzwuo06mgLCRNeciGElL72q8VFdoOLRceW2', 'delivery_executive', 'active', 'male'),
(8, 'Rahul Sharma', 'rahul.sharma@example.com', '9876543210', '$2y$10$VLD/S4qI11FBJdk1GVvzwuo06mgLCRNeciGElL72q8VFdoOLRceW2', 'customer', 'active', 'male'),
(9, 'Priya Patel', 'priya.patel@example.com', '9876543211', '$2y$10$VLD/S4qI11FBJdk1GVvzwuo06mgLCRNeciGElL72q8VFdoOLRceW2', 'customer', 'active', 'female')
ON DUPLICATE KEY UPDATE `password_hash`=VALUES(`password_hash`), `name`=VALUES(`name`), `email`=VALUES(`email`);

-- Customer Profiles
INSERT INTO `customer_profiles` (`user_id`, `preferred_language`, `notes`, `total_orders`) VALUES
(8, 'English', 'Prefers Slim Fit for Formal Shirts, Crisp Collars', 2),
(9, 'English', 'Prefers comfort fit with boat neck blouses', 1)
ON DUPLICATE KEY UPDATE `notes`=VALUES(`notes`);

-- Addresses
INSERT INTO `addresses` (`id`, `user_id`, `title`, `house_no`, `building`, `street`, `area`, `landmark`, `city`, `state`, `pincode`, `latitude`, `longitude`, `is_default`) VALUES
(1, 8, 'Home', 'Flat 402', 'Imperial Heights', 'Pali Hill Road', 'Bandra West', 'Near Cafe Basilico', 'Mumbai', 'Maharashtra', '400050', 19.0600, 72.8258, 1),
(2, 8, 'Office', 'Unit 12B', 'Maker Chambers VI', 'Jamnalal Bajaj Marg', 'Nariman Point', 'Near Air India Bldg', 'Mumbai', 'Maharashtra', '400021', 18.9270, 72.8210, 0),
(3, 9, 'Home', 'Villa 7', 'Palm Meadows', 'Varthur Road', 'Indiranagar', 'Opp Metro Station', 'Bangalore', 'Karnataka', '560038', 12.9784, 77.6408, 1)
ON DUPLICATE KEY UPDATE `house_no`=VALUES(`house_no`);

-- Service Areas
INSERT INTO `service_areas` (`city`, `area_name`, `pincode`, `is_active`, `is_24h_available`, `max_daily_capacity`, `current_booked_today`, `measurement_fee`, `delivery_charge`) VALUES
('Mumbai', 'Bandra West & Khar', '400050', 1, 1, 40, 6, 99.00, 0.00),
('Mumbai', 'Andheri West & Lokhandwala', '400053', 1, 1, 50, 12, 99.00, 0.00),
('Mumbai', 'Powai & Hiranandani', '400076', 1, 1, 35, 4, 99.00, 0.00),
('Mumbai', 'South Mumbai / Nariman Point', '400021', 1, 1, 30, 3, 99.00, 0.00),
('Bangalore', 'Indiranagar & Domlur', '560038', 1, 1, 45, 8, 99.00, 0.00),
('Bangalore', 'Koramangala & HSR', '560034', 1, 1, 45, 9, 99.00, 0.00),
('Delhi NCR', 'Connaught Place & Central', '110001', 1, 1, 30, 5, 99.00, 0.00),
('Delhi NCR', 'Cyber City & Golf Course Rd', '122002', 1, 1, 35, 7, 99.00, 0.00)
ON DUPLICATE KEY UPDATE `is_active`=VALUES(`is_active`);

-- Services
INSERT INTO `services` (`id`, `category`, `name`, `slug`, `description`, `base_price`, `express_price`, `measurement_fee`, `is_24h_eligible`, `estimated_hours`, `image_url`) VALUES
(1, 'men', 'Bespoke Formal Shirt', 'bespoke-formal-shirt', 'Handcrafted tailored shirt with reinforced collar, cuff options, and contoured fit.', 599.00, 199.00, 99.00, 1, 24, 'shirt.jpg'),
(2, 'men', 'Tailored Formal Trouser', 'tailored-formal-trouser', 'Custom pleated or flat front trousers with tailored waistband and comfort rise.', 699.00, 199.00, 99.00, 1, 24, 'trouser.jpg'),
(3, 'men', 'Designer Kurta Pajama', 'designer-kurta-pajama', 'Classic Indian royal kurta with churidar or straight pants, tailored to perfection.', 899.00, 199.00, 99.00, 1, 24, 'kurta.jpg'),
(4, 'men', 'Bespoke 2-Piece Suit / Blazer', 'bespoke-suit-blazer', 'Italian cut canvassed suit with handcrafted lapel and personalized inner lining.', 3499.00, 499.00, 99.00, 1, 36, 'suit.jpg'),
(5, 'women', 'Designer Saree Blouse', 'designer-saree-blouse', 'Princess cut, padded, boat neck, backless, or designer piping tailored blouse.', 699.00, 199.00, 99.00, 1, 24, 'blouse.jpg'),
(6, 'women', 'Custom Kurti & Palazzo Set', 'custom-kurti-palazzo', 'Contemporary ethnic silhouette with custom sleeve lengths and flare.', 899.00, 199.00, 99.00, 1, 24, 'kurti.jpg'),
(7, 'women', 'Evening Gown / Dress', 'evening-gown-dress', 'Flattering evening silhouette crafted according to precision contours.', 1499.00, 299.00, 99.00, 1, 36, 'gown.jpg'),
(8, 'alteration', 'Express Fit Alteration', 'express-fit-alteration', 'Waist tightening, sleeve length alteration, trouser hem adjustment.', 299.00, 99.00, 99.00, 1, 12, 'alteration.jpg'),
(9, 'custom', 'Bespoke Custom Tailoring', 'bespoke-custom-tailoring', 'Submit your custom design sketch or reference photograph for artisanal fabrication.', 1299.00, 299.00, 99.00, 1, 24, 'custom.jpg')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

-- Fabrics Catalog
INSERT INTO `fabrics` (`id`, `sku`, `name`, `category`, `color`, `pattern`, `price_per_meter`, `stock_meters`, `reorder_level`) VALUES
(1, 'FAB-EGY-001', 'Egyptian Giza Cotton 100s', 'Cotton', 'Crisp White', 'Solid Twill', 899.00, 120.00, 20.00),
(2, 'FAB-ITA-002', 'Italian Superfine Linen', 'Linen', 'Sky Blue', 'Chambray', 1199.00, 85.00, 15.00),
(3, 'FAB-RAY-003', 'Raymond Poly-Wool Blend', 'Wool Blend', 'Charcoal Grey', 'Micro Houndstooth', 1499.00, 60.00, 10.00),
(4, 'FAB-SIL-004', 'Banarasi Chanderi Silk', 'Silk', 'Imperial Maroon', 'Zari Buta', 1899.00, 45.00, 10.00),
(5, 'FAB-SAT-005', 'Royal Satin Cotton', 'Satin Cotton', 'Midnight Black', 'Solid Satin', 799.00, 95.00, 20.00)
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

-- Customer Testimonials & Reviews
INSERT INTO `testimonials` (`id`, `customer_name`, `customer_role`, `location`, `rating`, `review_text`, `image_url`, `garment_type`, `status`, `display_order`) VALUES
(1, 'Vikram Singhania', 'Managing Director, Singhania Capital', 'Bandra West, Mumbai', 5, 'I needed a bespoke tuxedo tailored within 24 hours for an international summit in Dubai. The measurement executive arrived at my home with swatches, and the finished tuxedo was delivered the next evening with Savile Row precision. Truly exceptional service!', 'assets/images/men_bespoke.jpg', '3-Piece Bespoke Tuxedo', 'active', 1),
(2, 'Ananya Iyer', 'Fashion Stylist & Creative Lead', 'Indiranagar, Bangalore', 5, 'Finding a master tailor for intricate princess-cut designer blouses without visiting crowded markets used to be impossible. MY TAYLOR took 15 precise anatomical points in my living room, and the fit was 100% flawless on the first try.', 'assets/images/women_atelier.jpg', 'Designer Saree Blouse', 'active', 2),
(3, 'Karan Mehra', 'Tech Founder & YC Alum', 'Cyber City, Delhi NCR', 5, 'The digital measurement docket is a complete game changer. Once measured, I reordered 5 Egyptian cotton shirts with 1-click. Delivered crisp, monogrammed, and fitted to perfection in under 24 hours.', 'assets/images/hero_tailor.jpg', 'Egyptian Cotton Shirts', 'active', 3),
(4, 'Dr. Radhika Sen', 'Surgeon & Sartorial Enthusiast', 'South Extension, New Delhi', 5, 'Luxury atelier craftsmanship combined with doorstep concierge convenience. Their master cutters and German Gutermann stitching quality are unmatched. Zero store visits and absolute peace of mind.', 'assets/images/women_atelier.jpg', 'Bespoke Anarkali Suit', 'active', 4),
(5, 'Rohan Malhotra', 'Architect & Design Principal', 'Juhu, Mumbai', 5, 'The 24-Hour express SLA guarantee is 100% genuine. Live tracking showed every single stage from master cutting to stitching, QC, and dispatch. MY TAYLOR is the future of luxury tailoring in India.', 'assets/images/men_bespoke.jpg', 'Italian Linen Blazer', 'active', 5)
ON DUPLICATE KEY UPDATE `customer_name`=VALUES(`customer_name`), `review_text`=VALUES(`review_text`);


