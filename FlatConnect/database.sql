-- ======================================================
-- FlatConnect Database Schema
-- Database Name: flatconnect_db
-- ======================================================

CREATE DATABASE IF NOT EXISTS `flatconnect_db` 
DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `flatconnect_db`;

-- 1. Users Table (Stores Owners, Tenants, and Seekers)
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `mobile` VARCHAR(20) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('owner', 'tenant', 'seeker') NOT NULL DEFAULT 'seeker',
    `preferred_location` VARCHAR(100) DEFAULT NULL,
    `max_budget` INT DEFAULT NULL,
    `preferred_type` VARCHAR(50) DEFAULT NULL,
    `preferred_furnishing` VARCHAR(50) DEFAULT NULL,
    `bio` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Properties Table (Posted by Property Owners)
CREATE TABLE IF NOT EXISTS `properties` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `owner_id` INT NOT NULL,
    `title` VARCHAR(150) NOT NULL,
    `property_type` ENUM('Flat', 'PG', 'Room') NOT NULL,
    `location` VARCHAR(100) NOT NULL,
    `address` TEXT NOT NULL,
    `rent` INT NOT NULL,
    `deposit` INT NOT NULL,
    `furnishing` ENUM('Fully Furnished', 'Semi-Furnished', 'Unfurnished') NOT NULL,
    `facilities` TEXT NOT NULL,
    `bachelor_allowed` ENUM('Yes', 'No', 'Boys Only', 'Girls Only', 'Family Only') DEFAULT 'Yes',
    `available_from` DATE NOT NULL,
    `is_urgent` TINYINT(1) DEFAULT 0,
    `description` TEXT NOT NULL,
    `contact_phone` VARCHAR(20) DEFAULT NULL,
    `status` ENUM('Available', 'Rented', 'Under Discussion') DEFAULT 'Available',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_properties_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Property Images Table (Supports multiple photos per property)
CREATE TABLE IF NOT EXISTS `property_images` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `property_id` INT NOT NULL,
    `image_path` VARCHAR(255) NOT NULL,
    `is_primary` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_property_images` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Roommate Posts Table (Posted by Tenants or Seekers looking for flatmates)
CREATE TABLE IF NOT EXISTS `roommate_posts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `occupation` ENUM('Student', 'Working Professional') NOT NULL,
    `preferred_location` VARCHAR(100) NOT NULL,
    `budget` INT NOT NULL,
    `sharing_preference` ENUM('Single Room', '2 Sharing', '3 Sharing', 'Any') NOT NULL,
    `lifestyle_preferences` VARCHAR(255) NOT NULL,
    `move_in_date` DATE NOT NULL,
    `contact_number` VARCHAR(20) NOT NULL,
    `notes` TEXT DEFAULT NULL,
    `status` ENUM('Active', 'Closed') DEFAULT 'Active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_roommate_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Tenant Replacement Posts Table (Existing tenant finding replacement)
CREATE TABLE IF NOT EXISTS `replacement_posts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT NOT NULL,
    `property_name` VARCHAR(150) NOT NULL,
    `location` VARCHAR(100) NOT NULL,
    `rent` INT NOT NULL,
    `deposit` INT NOT NULL,
    `available_from` DATE NOT NULL,
    `sharing_details` VARCHAR(100) NOT NULL,
    `facilities` TEXT NOT NULL,
    `description` TEXT NOT NULL,
    `contact_preference` VARCHAR(100) NOT NULL,
    `status` ENUM('Active', 'Closed') DEFAULT 'Active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_replacement_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Enquiries Table (Direct message/enquiry between users)
CREATE TABLE IF NOT EXISTS `enquiries` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `property_id` INT DEFAULT NULL,
    `replacement_id` INT DEFAULT NULL,
    `roommate_post_id` INT DEFAULT NULL,
    `sender_id` INT NOT NULL,
    `receiver_id` INT NOT NULL,
    `message` TEXT NOT NULL,
    `response` TEXT DEFAULT NULL,
    `status` ENUM('Pending', 'Replied') DEFAULT 'Pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_enquiries_property` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_enquiries_replacement` FOREIGN KEY (`replacement_id`) REFERENCES `replacement_posts` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_enquiries_roommate` FOREIGN KEY (`roommate_post_id`) REFERENCES `roommate_posts` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_enquiries_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_enquiries_receiver` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Saved Properties Table (Favorites for seekers)
CREATE TABLE IF NOT EXISTS `saved_properties` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `property_id` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_user_property` (`user_id`, `property_id`),
    CONSTRAINT `fk_saved_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_saved_property` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Indexing for high-performance searches and filters
CREATE INDEX `idx_prop_location` ON `properties` (`location`);
CREATE INDEX `idx_prop_rent` ON `properties` (`rent`);
CREATE INDEX `idx_prop_type` ON `properties` (`property_type`);
CREATE INDEX `idx_prop_bachelor` ON `properties` (`bachelor_allowed`);
CREATE INDEX `idx_roommate_loc` ON `roommate_posts` (`preferred_location`);
CREATE INDEX `idx_replacement_loc` ON `replacement_posts` (`location`);
