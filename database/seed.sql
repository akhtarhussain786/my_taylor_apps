-- ==========================================================
-- MY TAYLOR - Demo Seed Data
-- ==========================================================

USE `my_taylor_db`;

-- Passwords are all: password123 (hashed with password_hash PASSWORD_BCRYPT)
-- $2y$10$tZ2X5y1234567890abcdef...
-- We will compute the hash dynamically in setup.php or use standard bcrypt hash:
-- '$2y$10$wTf4Jv81m7L7r7.o3b3rveE7XjY8FzO7Hj/mJc2i21Y5R7x.qU7K6'

INSERT INTO `users` (`id`, `name`, `email`, `mobile`, `password_hash`, `role`, `status`, `gender`) VALUES
(1, 'Admin Master', 'admin@mytaylor.com', '9800000000', '$2y$10$VLD/S4qI11FBJdk1GVvzwuo06mgLCRNeciGElL72q8VFdoOLRceW2', 'admin', 'active', 'male'),
(2, 'Vikram Singh (Executive)', 'exec.vikram@mytaylor.com', '9800000001', '$2y$10$VLD/S4qI11FBJdk1GVvzwuo06mgLCRNeciGElL72q8VFdoOLRceW2', 'measurement_executive', 'active', 'male'),
(3, 'Ramesh Kumar (Master Cutter)', 'cutting.ramesh@mytaylor.com', '9800000002', '$2y$10$VLD/S4qI11FBJdk1GVvzwuo06mgLCRNeciGElL72q8VFdoOLRceW2', 'cutting_staff', 'active', 'male'),
(4, 'Master Anwar (Senior Tailor)', 'tailor.anwar@mytaylor.com', '9800000003', '$2y$10$VLD/S4qI11FBJdk1GVvzwuo06mgLCRNeciGElL72q8VFdoOLRceW2', 'tailor', 'active', 'male'),
(5, 'Meera Deshmukh (QC Lead)', 'qc.meera@mytaylor.com', '9800000004', '$2y$10$VLD/S4qI11FBJdk1GVvzwuo06mgLCRNeciGElL72q8VFdoOLRceW2', 'qc_staff', 'active', 'female'),
(6, 'Suresh Verma (Packaging)', 'pack.suresh@mytaylor.com', '9800000005', '$2y$10$VLD/S4qI11FBJdk1GVvzwuo06mgLCRNeciGElL72q8VFdoOLRceW2', 'packing_staff', 'active', 'male'),
(7, 'Rohit Sharma (Express Rider)', 'delivery.rohit@mytaylor.com', '9800000006', '$2y$10$VLD/S4qI11FBJdk1GVvzwuo06mgLCRNeciGElL72q8VFdoOLRceW2', 'delivery_executive', 'active', 'male'),
(8, 'Rahul Sharma', 'rahul.sharma@example.com', '9876543210', '$2y$10$VLD/S4qI11FBJdk1GVvzwuo06mgLCRNeciGElL72q8VFdoOLRceW2', 'customer', 'active', 'male'),
(9, 'Priya Patel', 'priya.patel@example.com', '9876543211', '$2y$10$VLD/S4qI11FBJdk1GVvzwuo06mgLCRNeciGElL72q8VFdoOLRceW2', 'customer', 'active', 'female')
ON DUPLICATE KEY UPDATE `password_hash`=VALUES(`password_hash`), `name`=VALUES(`name`);

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

-- Fabrics
INSERT INTO `fabrics` (`id`, `sku`, `name`, `category`, `color`, `pattern`, `price_per_meter`, `stock_meters`, `reorder_level`) VALUES
(1, 'FAB-EGY-001', 'Egyptian Giza Cotton 100s', 'Cotton', 'Crisp White', 'Solid Twill', 899.00, 120.00, 20.00),
(2, 'FAB-ITA-002', 'Italian Superfine Linen', 'Linen', 'Sky Blue', 'Chambray', 1199.00, 85.00, 15.00),
(3, 'FAB-RAY-003', 'Raymond Poly-Wool Blend', 'Wool Blend', 'Charcoal Grey', 'Micro Houndstooth', 1499.00, 60.00, 10.00),
(4, 'FAB-SIL-004', 'Banarasi Chanderi Silk', 'Silk', 'Imperial Maroon', 'Zari Buta', 1899.00, 45.00, 10.00),
(5, 'FAB-SAT-005', 'Royal Satin Cotton', 'Satin Cotton', 'Midnight Black', 'Solid Satin', 799.00, 95.00, 20.00)
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

-- Measurements Profile
INSERT INTO `measurements` (`id`, `measurement_code`, `customer_id`, `garment_category`, `measurements_json`, `fit_preference`, `design_specs_json`, `notes`, `created_by_user_id`) VALUES
(1, 'MT-M-0001028', 8, 'Shirt', '{"neck": 16.0, "shoulder": 18.5, "chest": 40.0, "waist": 34.0, "hip": 41.0, "sleeve_length": 25.5, "bicep": 14.5, "wrist": 7.5, "shirt_length": 30.0}', 'Slim Fit', '{"collar": "Semi-Cutaway", "cuff": "French Cuff (Double)", "pocket": "Single Left V-Pocket", "placket": "French Front", "button": "Mother of Pearl"}', 'Customer prefers snug fit on biceps, extra room around wrist for watch.', 2),
(2, 'MT-M-0001029', 8, 'Trouser', '{"waist": 34.0, "hip": 41.0, "rise": 10.5, "thigh": 24.0, "knee": 17.0, "calf": 15.0, "bottom": 14.5, "inseam": 32.0, "outseam": 41.5}', 'Slim Fit', '{"waistband": "Side Adjusters (No Loops)", "pleat": "Flat Front", "bottom_style": "Turn-Up Cuff 1.5 inch"}', 'Slanted side pockets and one coin pocket.', 2),
(3, 'MT-M-0001030', 9, 'Blouse', '{"bust": 36.0, "underbust": 30.0, "waist": 28.0, "shoulder": 14.5, "sleeve_length": 11.0, "armhole": 16.0, "front_neck_depth": 7.0, "back_neck_depth": 9.5, "blouse_length": 14.5}', 'Regular Fit', '{"neck_style": "Boat Neck", "cut": "Princess Cut Padded", "closure": "Back Hooks with Dori & Tassels"}', 'Padding thickness medium, contrast gold piping.', 2)
ON DUPLICATE KEY UPDATE `measurement_code`=VALUES(`measurement_code`);

-- Appointments
INSERT INTO `appointments` (`id`, `appointment_code`, `customer_id`, `service_id`, `address_id`, `executive_id`, `appointment_date`, `time_slot`, `status`, `notes`, `delivery_preference`, `completed_at`) VALUES
(1, 'APT-20260920-001', 8, 1, 1, 2, '2026-09-20', '10:00 AM – 11:00 AM', 'MEASUREMENT_COMPLETED', 'Doorstep measurement done at Bandra West residence.', '24H_EXPRESS', '2026-09-20 10:45:00'),
(2, 'APT-20260920-002', 9, 5, 3, 2, '2026-09-20', '02:00 PM – 03:00 PM', 'EXECUTIVE_ON_THE_WAY', 'Silk blouse measurement with sample reference.', '24H_EXPRESS', NULL),
(3, 'APT-20260921-003', 8, 4, 2, 2, '2026-09-21', '11:00 AM – 12:00 PM', 'BOOKED', 'Bespoke suit measurement at Nariman Point office.', '24H_EXPRESS', NULL)
ON DUPLICATE KEY UPDATE `appointment_code`=VALUES(`appointment_code`);

-- Active Orders
INSERT INTO `orders` (`id`, `booking_id`, `customer_id`, `appointment_id`, `measurement_id`, `service_id`, `fabric_source`, `fabric_id`, `delivery_address_id`, `priority`, `is_24h_delivery`, `tailoring_charge`, `fabric_charge`, `measurement_fee`, `express_fee`, `discount_amount`, `total_amount`, `payment_method`, `payment_status`, `order_status`, `sla_start_time`, `sla_deadline`, `assigned_cutting_id`, `assigned_tailor_id`, `assigned_qc_id`, `assigned_packing_id`, `assigned_delivery_id`, `special_instructions`) VALUES
(1, 'MYT-20260920-001245', 8, 1, 1, 1, 'MY_TAYLOR_FABRIC', 1, 1, 'EXPRESS', 1, 599.00, 899.00, 99.00, 199.00, 100.00, 1696.00, 'UPI', 'PAID', 'STITCHING_IN_PROGRESS', '2026-09-20 11:00:00', '2026-09-21 11:00:00', 3, 4, 5, 6, 7, '24-Hour Express guarantee. Customer requested contrast dark blue inner collar piping.'),
(2, 'MYT-20260919-001240', 8, 1, 2, 2, 'CUSTOMER_PROVIDED', NULL, 1, 'EXPRESS', 1, 699.00, 0.00, 99.00, 199.00, 0.00, 997.00, 'CARD', 'PAID', 'DELIVERED', '2026-09-19 09:00:00', '2026-09-20 09:00:00', 3, 4, 5, 6, 7, 'Navy Italian Wool Trouser, Delivered with signature confirmation.')
ON DUPLICATE KEY UPDATE `booking_id`=VALUES(`booking_id`);

-- Production Tasks for Active Order 1
INSERT INTO `production_tasks` (`order_id`, `stage`, `assigned_user_id`, `status`, `notes`, `started_at`, `completed_at`) VALUES
(1, 'MEASUREMENT', 2, 'COMPLETED', 'Doorstep measurement completed in 25 mins.', '2026-09-20 10:15:00', '2026-09-20 10:45:00'),
(1, 'FABRIC_PREP', 3, 'COMPLETED', 'Egyptian Giza Cotton 100s issued from inventory (2.2m).', '2026-09-20 11:15:00', '2026-09-20 11:30:00'),
(1, 'CUTTING', 3, 'COMPLETED', 'Cut with French collar and double-cuff pattern template.', '2026-09-20 11:35:00', '2026-09-20 12:45:00'),
(1, 'STITCHING', 4, 'IN_PROGRESS', 'Stitching in progress by Master Anwar. Collars fused and aligned.', '2026-09-20 13:00:00', NULL),
(1, 'FINISHING', 4, 'PENDING', 'Buttonhole threading and steam press pending.', NULL, NULL),
(1, 'QC', 5, 'PENDING', 'Final 10-point measurement and stitch inspection pending.', NULL, NULL),
(1, 'PACKING', 6, 'PENDING', 'Custom garment bag and QR code sealing pending.', NULL, NULL),
(1, 'DELIVERY', 7, 'PENDING', 'Express courier route queued for Bandra West.', NULL, NULL)
ON DUPLICATE KEY UPDATE `notes`=VALUES(`notes`);

-- Completed Delivery Proof for Order 2
INSERT INTO `delivery_proofs` (`order_id`, `delivery_executive_id`, `verification_type`, `customer_signature_svg`, `delivery_photo_url`, `recipient_name`, `recipient_relation`, `delivered_timestamp`) VALUES
(2, 7, 'SIGNATURE_AND_PHOTO', 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="300" height="100"><path d="M 10 50 Q 80 10 150 50 T 280 50" stroke="#000" fill="none" stroke-width="2"/></svg>', 'proof_myt20260919001240.jpg', 'Rahul Sharma', 'Self', '2026-09-20 08:35:00')
ON DUPLICATE KEY UPDATE `recipient_name`=VALUES(`recipient_name`);

-- Reviews for Delivered Order
INSERT INTO `reviews` (`order_id`, `customer_id`, `overall_rating`, `measurement_rating`, `stitching_rating`, `delivery_rating`, `comment`) VALUES
(2, 8, 5, 5, 5, 5, 'Remarkable service! The measurement executive arrived right on time, and the trousers fit like a dream. Delivered in under 24 hours!')
ON DUPLICATE KEY UPDATE `comment`=VALUES(`comment`);

-- Audit Logs
INSERT INTO `audit_logs` (`order_id`, `appointment_id`, `user_id`, `action`, `previous_state`, `new_state`, `description`) VALUES
(1, 1, 2, 'MEASUREMENT_RECORDED', 'EXECUTIVE_ARRIVED', 'MEASUREMENT_COMPLETED', 'Doorstep measurement recorded by Vikram Singh for Booking MYT-20260920-001245'),
(1, NULL, 3, 'CUTTING_COMPLETED', 'CUTTING_IN_PROGRESS', 'CUTTING_COMPLETED', 'Master Cutter Ramesh finished pattern cutting and handed over to Tailor'),
(1, NULL, 4, 'STITCHING_STARTED', 'STITCHING_ASSIGNED', 'STITCHING_IN_PROGRESS', 'Master Anwar started stitching assembly')
ON DUPLICATE KEY UPDATE `action`=VALUES(`action`);
