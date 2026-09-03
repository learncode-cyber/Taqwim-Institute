-- ════════════════════════════════════════
-- Taqwim Institute — Coupon System Update
-- phpMyAdmin এ এই SQL run করুন (একবারই)
-- ════════════════════════════════════════

CREATE TABLE IF NOT EXISTS `coupons` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `code`        VARCHAR(50) NOT NULL UNIQUE,
  `type`        ENUM('percent','fixed') DEFAULT 'percent',
  `value`       DECIMAL(10,2) NOT NULL DEFAULT 0,
  `min_amount`  DECIMAL(10,2) DEFAULT 0,
  `max_uses`    INT DEFAULT NULL COMMENT 'NULL = সীমাহীন',
  `used_count`  INT DEFAULT 0,
  `valid_from`  DATE DEFAULT NULL,
  `valid_until` DATE DEFAULT NULL,
  `is_active`   TINYINT(1) DEFAULT 1,
  `created_at`  DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `coupon_uses` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `coupon_id`  INT NOT NULL,
  `student_id` INT NOT NULL,
  `payment_id` INT DEFAULT NULL,
  `discount`   DECIMAL(10,2) NOT NULL,
  `used_at`    DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`coupon_id`)  REFERENCES `coupons`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`student_id`) REFERENCES `users`(`id`)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample coupons
INSERT IGNORE INTO `coupons` (`code`,`type`,`value`,`max_uses`,`is_active`) VALUES
('TAQWIM10',  'percent', 10,  100, 1),
('WELCOME20', 'percent', 20,  50,  1),
('EID50',     'percent', 50,  30,  1),
('FLAT500',   'fixed',   500, 20,  1);
