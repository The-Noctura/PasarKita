SET NAMES utf8mb4;
SET time_zone = '+07:00';

CREATE DATABASE IF NOT EXISTS `pasarkita`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `pasarkita`;

CREATE TABLE IF NOT EXISTS `users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `full_name` VARCHAR(150) NOT NULL,
  `username` VARCHAR(50) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(30) NOT NULL,
  `role` ENUM('free','plus') NOT NULL DEFAULT 'free',
  `trial_started_at` DATETIME NULL,
  `is_trial_active` TINYINT(1) NOT NULL DEFAULT 0,
  `birth_date` DATE NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `last_login_at` DATETIME NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_username_unique` (`username`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `admins` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `full_name` VARCHAR(150) NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admins_username_unique` (`username`),
  UNIQUE KEY `admins_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NULL,
  `admin_id` BIGINT UNSIGNED NULL,
  `action` VARCHAR(120) NOT NULL,
  `description` TEXT NULL,
  `ip_address` VARCHAR(64) NULL,
  `user_agent` VARCHAR(255) NULL,
  `metadata` LONGTEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_logs_created_at_idx` (`created_at`, `id`),
  KEY `user_logs_user_id_created_idx` (`user_id`, `created_at`),
  KEY `user_logs_admin_id_created_idx` (`admin_id`, `created_at`),
  CONSTRAINT `user_logs_user_id_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `user_logs_admin_id_fk` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `categories` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL,
  `description` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `categories_name_unique` (`name`),
  UNIQUE KEY `categories_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `products` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` BIGINT UNSIGNED NULL,
  `name` VARCHAR(150) NOT NULL,
  `description` TEXT NULL,
  `badge` VARCHAR(30) NULL,
  `shopee_price` INT UNSIGNED NOT NULL DEFAULT 0,
  `markup` INT UNSIGNED NOT NULL DEFAULT 0,
  `shopee_link` VARCHAR(255) NULL,
  `price` INT UNSIGNED NOT NULL DEFAULT 0,
  `stock` INT UNSIGNED NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `products_name_unique` (`name`),
  KEY `products_active_idx` (`is_active`, `id`),
  CONSTRAINT `products_category_id_fk` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_addresses` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `label` VARCHAR(20) NOT NULL DEFAULT 'home',
  `recipient_name` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(30) NOT NULL,
  `region` VARCHAR(255) NOT NULL,
  `street` VARCHAR(255) NOT NULL,
  `detail` VARCHAR(255) NULL,
  `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_addresses_user_id_idx` (`user_id`),
  KEY `user_addresses_primary_idx` (`user_id`, `is_primary`),
  CONSTRAINT `user_addresses_user_id_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `orders` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NULL,
  `total_amount` INT UNSIGNED NOT NULL DEFAULT 0,
  `status` ENUM('awaiting_payment','payment_review','paid','processing','shipped','delivered','cancelled','placed') NOT NULL DEFAULT 'awaiting_payment',
  `payment_method` VARCHAR(50) NULL,
  `shipping_method` VARCHAR(100) NULL,
  `shipping_fee` INT UNSIGNED NOT NULL DEFAULT 0,
  `handling_fee` INT UNSIGNED NOT NULL DEFAULT 0,
  `payment_proof` VARCHAR(255) NULL,
  `shipping_address` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `orders_user_id_idx` (`user_id`),
  CONSTRAINT `orders_user_id_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `order_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `product_id` BIGINT UNSIGNED NULL,
  `product_name` VARCHAR(150) NOT NULL,
  `unit_price` INT UNSIGNED NOT NULL DEFAULT 0,
  `qty` INT UNSIGNED NOT NULL DEFAULT 1,
  `line_total` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `order_items_order_id_idx` (`order_id`),
  KEY `order_items_product_id_idx` (`product_id`),
  CONSTRAINT `order_items_order_id_fk` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_product_id_fk` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `order_shipments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `carrier` VARCHAR(60) NULL,
  `service` VARCHAR(60) NULL,
  `tracking_number` VARCHAR(80) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_shipments_order_id_unique` (`order_id`),
  KEY `order_shipments_tracking_idx` (`tracking_number`),
  CONSTRAINT `order_shipments_order_id_fk` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `order_tracking_events` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `occurred_at` DATETIME NOT NULL,
  `title` VARCHAR(120) NOT NULL,
  `description` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `order_tracking_events_order_id_idx` (`order_id`),
  KEY `order_tracking_events_occurred_idx` (`order_id`, `occurred_at`),
  CONSTRAINT `order_tracking_events_order_id_fk` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Triggers: auto-log status changes into tracking events (keeps timeline realtime)
-- Note: When importing via phpMyAdmin, ensure it supports DELIMITER blocks.
DELIMITER $$

DROP TRIGGER IF EXISTS trg_orders_after_insert_tracking $$
CREATE TRIGGER trg_orders_after_insert_tracking
AFTER INSERT ON `orders`
FOR EACH ROW
BEGIN
  DELETE FROM `order_tracking_events` WHERE `order_id` = NEW.`id` AND `title` = 'Pesanan dibuat';
  INSERT INTO `order_tracking_events` (`order_id`, `occurred_at`, `title`, `description`)
  VALUES (NEW.`id`, IFNULL(NEW.`created_at`, NOW()), 'Pesanan dibuat', NULL);
END $$

DROP TRIGGER IF EXISTS trg_orders_after_update_status_tracking $$
CREATE TRIGGER trg_orders_after_update_status_tracking
AFTER UPDATE ON `orders`
FOR EACH ROW
BEGIN
  IF (NEW.`status` <> OLD.`status`) THEN
    IF (NEW.`status` = 'payment_review') THEN
      DELETE FROM `order_tracking_events` WHERE `order_id` = NEW.`id` AND `title` = 'Bukti pembayaran dikirim';
      INSERT INTO `order_tracking_events` (`order_id`, `occurred_at`, `title`, `description`)
      VALUES (NEW.`id`, NOW(), 'Bukti pembayaran dikirim', 'Menunggu konfirmasi admin.');
    ELSEIF (NEW.`status` = 'paid') THEN
      DELETE FROM `order_tracking_events` WHERE `order_id` = NEW.`id` AND `title` = 'Pembayaran dikonfirmasi';
      INSERT INTO `order_tracking_events` (`order_id`, `occurred_at`, `title`, `description`)
      VALUES (NEW.`id`, NOW(), 'Pembayaran dikonfirmasi', 'Pembayaran sudah dikonfirmasi admin.');
    ELSEIF (NEW.`status` = 'processing') THEN
      DELETE FROM `order_tracking_events` WHERE `order_id` = NEW.`id` AND `title` = 'Pesanan diproses';
      INSERT INTO `order_tracking_events` (`order_id`, `occurred_at`, `title`, `description`)
      VALUES (NEW.`id`, NOW(), 'Pesanan diproses', NULL);
    ELSEIF (NEW.`status` = 'shipped') THEN
      DELETE FROM `order_tracking_events` WHERE `order_id` = NEW.`id` AND `title` = 'Pesanan dikirim';
      INSERT INTO `order_tracking_events` (`order_id`, `occurred_at`, `title`, `description`)
      VALUES (NEW.`id`, NOW(), 'Pesanan dikirim', NULL);
    ELSEIF (NEW.`status` = 'delivered') THEN
      DELETE FROM `order_tracking_events` WHERE `order_id` = NEW.`id` AND `title` = 'Pesanan selesai';
      INSERT INTO `order_tracking_events` (`order_id`, `occurred_at`, `title`, `description`)
      VALUES (NEW.`id`, NOW(), 'Pesanan selesai', NULL);
    ELSEIF (NEW.`status` = 'cancelled') THEN
      DELETE FROM `order_tracking_events` WHERE `order_id` = NEW.`id` AND `title` = 'Pesanan dibatalkan';
      INSERT INTO `order_tracking_events` (`order_id`, `occurred_at`, `title`, `description`)
      VALUES (NEW.`id`, NOW(), 'Pesanan dibatalkan', NULL);
    END IF;
  END IF;
END $$

DELIMITER ;

CREATE TABLE IF NOT EXISTS `settings` (
  `key_name` VARCHAR(100) NOT NULL,
  `value` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`key_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `settings` (`key_name`, `value`)
VALUES ('handling_fee', '2000')
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);
