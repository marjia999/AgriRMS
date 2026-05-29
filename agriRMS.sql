-- ============================================
-- Database: agrirms
-- Complete Database Schema with Sample Data
-- ============================================

-- Drop database if exists and create fresh
DROP DATABASE IF EXISTS `agrirms`;
CREATE DATABASE `agrirms` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_general_ci;

USE `agrirms`;

-- ============================================
-- 1. USERS TABLE
-- ============================================
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `role` enum('Admin','Client') DEFAULT 'Client',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert Sample Users
INSERT INTO `users` (`full_name`, `email`, `password`, `phone`, `address`, `role`) VALUES
('Admin User', 'admin@agrirms.com', 'admin123', '01710000000', 'Dhaka, Bangladesh', 'Admin'),
('Md. Rahim Uddin', 'rahim@example.com', '123456', '01711111111', 'House 12, Road 5, Gulshan, Dhaka', 'Client'),
('Sultana Begum', 'sultana@example.com', '123456', '01722222222', 'Village: Kamalpur, Upazila: Sadar, District: Gazipur', 'Client'),
('Md. Karim Mia', 'karim@example.com', '123456', '01733333333', 'Chittagong City Corporation, Chittagong', 'Client');

-- ============================================
-- 2. RESOURCES TABLE (Agricultural Machinery)
-- ============================================
CREATE TABLE `resources` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `model` varchar(100) NOT NULL,
  `type` enum('Tractor','Soil Cultivation','Planting','Irrigation','Harvesting','Hay Making','Loading','Fertilizer Dispenser','Produce Sorter','Post Harvest') NOT NULL,
  `category` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `manufacturer` varchar(100) DEFAULT NULL,
  `manufacturing_year` year DEFAULT NULL,
  `horsepower` int(11) DEFAULT NULL,
  `fuel_type` enum('Diesel','Petrol','Electric','Solar','Manual') DEFAULT 'Diesel',
  `daily_rate` decimal(10,2) NOT NULL,
  `weekly_rate` decimal(10,2) DEFAULT NULL,
  `monthly_rate` decimal(10,2) DEFAULT NULL,
  `security_deposit` decimal(10,2) NOT NULL DEFAULT 5000.00,
  `status` enum('Available','Rented','Under Maintenance','Out of Service') DEFAULT 'Available',
  `quantity` int(11) NOT NULL DEFAULT 1,
  `image_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert Sample Resources
INSERT INTO `resources` (`name`, `model`, `type`, `category`, `description`, `manufacturer`, `manufacturing_year`, `horsepower`, `fuel_type`, `daily_rate`, `weekly_rate`, `monthly_rate`, `security_deposit`, `status`, `quantity`) VALUES
('Sonalika Tractor', '35RX', 'Tractor', 'Standard Tractor', 'Power steering tractor suitable for farming and agricultural transport', 'ACI Motors', 2024, 35, 'Diesel', 2200.00, 14000.00, 52000.00, 10000.00, 'Available', 5),
('Sonalika Tractor', 'All Rounder SS-55', 'Tractor', 'Premium Tractor', 'High-low-medium gear system with turbocharged engine', 'ACI Motors', 2024, 55, 'Diesel', 3200.00, 20000.00, 75000.00, 15000.00, 'Available', 3),
('Case IH Tractor', 'Farmall', 'Tractor', 'Premium Tractor', 'Cutting-edge tractor for maximum efficiency', 'Abedin Equipment', 2023, 50, 'Diesel', 3500.00, 22000.00, 80000.00, 15000.00, 'Available', 2),
('Power Tiller', 'PT-15', 'Soil Cultivation', 'Power Tiller', 'Fuel efficient power tiller for small land preparation', 'ACI Motors', 2024, 15, 'Diesel', 800.00, 5000.00, 18000.00, 3000.00, 'Available', 10),
('Combined Harvester', 'CH-100', 'Harvesting', 'Combine Harvester', 'Local technology harvester, affordable and efficient', 'Janata Engineering', 2024, 45, 'Diesel', 3500.00, 22000.00, 80000.00, 15000.00, 'Available', 3),
('Mini Combine Harvester', 'MCH-1', 'Harvesting', 'Combine Harvester', 'Customized for BD soil conditions', 'ACI Motors', 2024, 30, 'Diesel', 2500.00, 15000.00, 55000.00, 10000.00, 'Available', 4),
('Reaper', 'R-100', 'Harvesting', 'Reaper', 'For cutting paddy and wheat', 'ACI Motors', 2024, 12, 'Diesel', 1200.00, 7500.00, 28000.00, 5000.00, 'Available', 6),
('Seed Sower', 'SS-8', 'Planting', 'Seed Drill', '8 line seed sowing machine', 'Janata Engineering', 2024, NULL, 'Manual', 900.00, 5500.00, 20000.00, 4000.00, 'Available', 8),
('Disk Harrow', 'DH-16', 'Soil Cultivation', 'Disk Harrow', '16 disk harrow for soil preparation', 'Metalika', 2023, NULL, 'Manual', 1200.00, 7500.00, 28000.00, 5000.00, 'Available', 6),
('Cultivator', 'CUL-9', 'Soil Cultivation', 'Cultivator', '9 tyne cultivator for weed control', 'Metalika', 2024, NULL, 'Manual', 600.00, 3800.00, 14000.00, 3000.00, 'Available', 8),
('Water Pump', 'WP-5', 'Irrigation', 'Water Pump', '5 HP diesel water pump for irrigation', 'ACI Motors', 2024, 5, 'Diesel', 600.00, 3500.00, 12000.00, 3000.00, 'Available', 20),
('Sprinkler System', 'SP-360', 'Irrigation', 'Sprinkler System', '360 degree rotating sprinkler for 1 acre', 'Jalsech Limited', 2024, 5, 'Diesel', 700.00, 4200.00, 15000.00, 3000.00, 'Available', 10),
('Drum Thresher', 'DT-200', 'Post Harvest', 'Thresher', 'High capacity drum thresher for rice and wheat', 'Janata Engineering', 2024, 15, 'Diesel', 1500.00, 9000.00, 35000.00, 6000.00, 'Available', 10),
('Sprayer', 'SP-12', 'Fertilizer Dispenser', 'Sprayer', '12 liter back pack sprayer', 'Asian Paints', 2024, NULL, 'Manual', 400.00, 2500.00, 9000.00, 1500.00, 'Available', 15),
('Grain Trailer', 'GT-3', 'Post Harvest', 'Grain Trailer', '3 ton grain trailer', 'Bangla Trailers', 2024, NULL, 'Manual', 1500.00, 9000.00, 35000.00, 6000.00, 'Available', 6);

-- ============================================
-- 3. MAINTENANCE TABLE
-- ============================================
CREATE TABLE `maintenance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `resource_id` int(11) NOT NULL,
  `maintenance_type` enum('Routine Service','Repair','Overhaul','Inspection','Part Replacement') NOT NULL,
  `maintenance_date` date NOT NULL,
  `next_maintenance_date` date DEFAULT NULL,
  `issue_description` text NOT NULL,
  `work_done` text DEFAULT NULL,
  `cost` decimal(10,2) NOT NULL,
  `technician` varchar(100) DEFAULT NULL,
  `status` enum('Pending','In Progress','Completed','Cancelled') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `resource_id` (`resource_id`),
  FOREIGN KEY (`resource_id`) REFERENCES `resources` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert Sample Maintenance Records
INSERT INTO `maintenance` (`resource_id`, `maintenance_type`, `maintenance_date`, `next_maintenance_date`, `issue_description`, `work_done`, `cost`, `technician`, `status`) VALUES
(1, 'Routine Service', '2024-03-15', '2024-06-15', 'Regular oil change and filter replacement', 'Oil changed, filters replaced, all fluids checked', 3500.00, 'Md. Shahidul Islam', 'Completed'),
(2, 'Repair', '2024-02-10', '2024-05-10', 'Engine overheating issue', 'Cooling system repaired, radiator cleaned', 8500.00, 'Md. Rashed Khan', 'Completed'),
(4, 'Routine Service', '2024-04-01', '2024-07-01', 'General service and inspection', 'All parts inspected, lubricated', 2800.00, 'Md. Kamal Hossain', 'In Progress');

-- ============================================
-- 4. DELIVERY TABLE
-- ============================================
CREATE TABLE `delivery` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `delivery_type` varchar(50) NOT NULL,
  `location_type` enum('Inside Dhaka','Outside Dhaka') NOT NULL,
  `delivery_fee` decimal(10,2) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert Delivery Options
INSERT INTO `delivery` (`delivery_type`, `location_type`, `delivery_fee`, `is_active`) VALUES
('Standard Delivery', 'Inside Dhaka', 1000.00, 1),
('Standard Delivery', 'Outside Dhaka', 2500.00, 1);

-- ============================================
-- 5. SERVICE REQUESTS TABLE
-- ============================================
CREATE TABLE `service_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `resource_id` int(11) NOT NULL,
  `delivery_id` int(11) DEFAULT NULL,
  `rental_duration` enum('Daily','Weekly','Monthly') NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `total_rental_cost` decimal(10,2) NOT NULL,
  `delivery_cost` decimal(10,2) DEFAULT 0.00,
  `total_cost` decimal(10,2) NOT NULL,
  `delivery_address` text NOT NULL,
  `delivery_district` varchar(50) NOT NULL,
  `delivery_upazila` varchar(50) DEFAULT NULL,
  `special_instructions` text DEFAULT NULL,
  `request_status` enum('Pending','Approved','Processing','Delivered','Returned','Cancelled') DEFAULT 'Pending',
  `payment_status` enum('Pending','Paid','Partial') DEFAULT 'Pending',
  `payment_id` int(11) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `delivered_at` datetime DEFAULT NULL,
  `returned_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `resource_id` (`resource_id`),
  KEY `delivery_id` (`delivery_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`resource_id`) REFERENCES `resources` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`delivery_id`) REFERENCES `delivery` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert Sample Service Requests
INSERT INTO `service_requests` (`user_id`, `resource_id`, `delivery_id`, `rental_duration`, `quantity`, `start_date`, `end_date`, `total_rental_cost`, `delivery_cost`, `total_cost`, `delivery_address`, `delivery_district`, `delivery_upazila`, `request_status`, `payment_status`) VALUES
(2, 1, 1, 'Daily', 1, '2024-05-01', '2024-05-05', 8800.00, 1000.00, 9800.00, 'House 12, Road 5, Gulshan', 'Dhaka', 'Gulshan', 'Returned', 'Paid'),
(2, 5, 2, 'Weekly', 1, '2024-05-10', '2024-05-16', 22000.00, 2500.00, 24500.00, 'House 12, Road 5, Gulshan', 'Dhaka', 'Gulshan', 'Delivered', 'Paid'),
(3, 4, 2, 'Daily', 2, '2024-05-15', '2024-05-18', 4800.00, 2500.00, 7300.00, 'Village: Kamalpur, Upazila: Sadar', 'Gazipur', 'Sadar', 'Processing', 'Pending'),
(4, 7, 2, 'Monthly', 1, '2024-06-01', '2024-06-30', 28000.00, 2500.00, 30500.00, 'Chittagong City Corporation', 'Chittagong', 'Double Mooring', 'Approved', 'Pending');

-- ============================================
-- 6. PAYMENTS TABLE
-- ============================================
CREATE TABLE `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `booking_id` int(11) DEFAULT NULL COMMENT 'Reference to service request/booking',
  `resource_id` int(11) DEFAULT NULL,
  `delivery_id` int(11) DEFAULT NULL,
  `payment_type` enum('Rental','Delivery','Maintenance','Security Deposit','Full Payment') NOT NULL,
  `resource_cost` decimal(10,2) DEFAULT 0.00,
  `delivery_cost` decimal(10,2) DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL,
  `paid_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `due_amount` decimal(10,2) GENERATED ALWAYS AS (`total_amount` - `paid_amount`) STORED,
  `payment_method` enum('Cash on Delivery','Bkash','Nagad','Rocket','Bank Transfer','Credit Card') DEFAULT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `payment_status` enum('Pending','Paid','Partial','Failed','Refunded') DEFAULT 'Pending',
  `payment_date` datetime DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `resource_id` (`resource_id`),
  KEY `delivery_id` (`delivery_id`),
  KEY `booking_id` (`booking_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`resource_id`) REFERENCES `resources` (`id`) ON DELETE SET NULL,
  FOREIGN KEY (`delivery_id`) REFERENCES `delivery` (`id`) ON DELETE SET NULL,
  FOREIGN KEY (`booking_id`) REFERENCES `service_requests` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert Sample Payments
INSERT INTO `payments` (`user_id`, `booking_id`, `resource_id`, `delivery_id`, `payment_type`, `resource_cost`, `delivery_cost`, `total_amount`, `paid_amount`, `payment_method`, `transaction_id`, `payment_status`, `payment_date`) VALUES
(2, 1, 1, 1, 'Rental', 8800.00, 1000.00, 9800.00, 9800.00, 'Bkash', 'TRX123456', 'Paid', '2024-05-02 10:30:00'),
(2, 2, 5, 2, 'Rental', 22000.00, 2500.00, 24500.00, 24500.00, 'Nagad', 'TRX789012', 'Paid', '2024-05-12 14:45:00'),
(3, 3, 4, 2, 'Rental', 4800.00, 2500.00, 7300.00, 0.00, NULL, NULL, 'Pending', NULL),
(4, 4, 7, 2, 'Rental', 28000.00, 2500.00, 30500.00, 0.00, NULL, NULL, 'Pending', NULL);

-- ============================================
-- 7. CONTACT MESSAGES TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `contact_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================
-- 8. REQUEST COMMENTS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `request_comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `request_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_request_comments_request` (`request_id`),
  CONSTRAINT `fk_request_comments_request` FOREIGN KEY (`request_id`) REFERENCES `service_requests` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_request_comments_admin` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================
-- 9. NOTIFICATIONS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','success','warning','error') DEFAULT 'info',
  `related_url` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_notifications_user_read` (`user_id`, `is_read`, `created_at`),
  CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================
-- 10. RESOURCE WISHLIST TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `resource_wishlist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `resource_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_wishlist_user_resource` (`user_id`, `resource_id`),
  KEY `idx_wishlist_resource` (`resource_id`),
  CONSTRAINT `fk_wishlist_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_wishlist_resource` FOREIGN KEY (`resource_id`) REFERENCES `resources` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================
-- 11. RESOURCE REVIEWS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `resource_reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `resource_id` int(11) NOT NULL,
  `rating` tinyint(1) NOT NULL,
  `review` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_review_user_resource` (`user_id`, `resource_id`),
  KEY `idx_reviews_resource` (`resource_id`),
  CONSTRAINT `fk_reviews_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reviews_resource` FOREIGN KEY (`resource_id`) REFERENCES `resources` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_rating_range` CHECK (`rating` BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `resources`
ADD COLUMN IF NOT EXISTS `next_maintenance_date` date DEFAULT NULL;

-- ============================================
-- ADD INDEXES FOR BETTER PERFORMANCE
-- ============================================
ALTER TABLE `service_requests` ADD INDEX `idx_user_dates` (`user_id`, `start_date`, `end_date`);
ALTER TABLE `service_requests` ADD INDEX `idx_resource_status` (`resource_id`, `request_status`);
ALTER TABLE `payments` ADD INDEX `idx_user_payment_status` (`user_id`, `payment_status`);
ALTER TABLE `maintenance` ADD INDEX `idx_resource_maintenance` (`resource_id`, `maintenance_date`);
ALTER TABLE `contact_messages` ADD INDEX `idx_contact_messages_created` (`created_at`);

-- ============================================
-- VERIFY ALL DATA
-- ============================================
SELECT '=== USERS ===' as '';
SELECT id, full_name, email, role FROM users;

SELECT '=== RESOURCES ===' as '';
SELECT id, name, type, daily_rate, status, quantity FROM resources;

SELECT '=== MAINTENANCE ===' as '';
SELECT id, resource_id, maintenance_type, maintenance_date, status FROM maintenance;

SELECT '=== DELIVERY ===' as '';
SELECT id, delivery_type, location_type, delivery_fee FROM delivery;

SELECT '=== SERVICE REQUESTS ===' as '';
SELECT id, user_id, resource_id, rental_duration, total_cost, request_status FROM service_requests;

SELECT '=== PAYMENTS ===' as '';
SELECT id, user_id, booking_id, total_amount, paid_amount, payment_status FROM payments;

