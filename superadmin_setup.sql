-- ════════════════════════════════════════════
-- AR Qudrix Super Admin — SQL Setup
-- phpMyAdmin এ u290513561_talim_database তে run করুন
-- ════════════════════════════════════════════

-- Tenants table (multi-client tracking)
CREATE TABLE IF NOT EXISTS `tenants` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `name`         VARCHAR(150) NOT NULL,
  `domain`       VARCHAR(200) NOT NULL UNIQUE,
  `admin_email`  VARCHAR(150) NOT NULL,
  `admin_pass`   VARCHAR(255) NOT NULL COMMENT 'hashed',
  `package`      ENUM('basic','pro','enterprise') DEFAULT 'basic',
  `status`       ENUM('active','suspended','trial') DEFAULT 'trial',
  `trial_ends`   DATE DEFAULT NULL,
  `notes`        TEXT DEFAULT NULL,
  `created_at`   DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Super Admin table
CREATE TABLE IF NOT EXISTS `super_admins` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `name`       VARCHAR(150) NOT NULL,
  `email`      VARCHAR(150) NOT NULL UNIQUE,
  `password`   VARCHAR(255) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Super Admin: Abdullah Raiyan
-- Default Password: SuperAdmin@2025
-- ⚠️ প্রথম login এর পরেই পাসওয়ার্ড বদলান!
INSERT IGNORE INTO `super_admins` (`name`, `email`, `password`) VALUES
('Abdullah Raiyan',
 'admin@abdullahraiyan.com',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- First tenant (Taqwim Institute)
INSERT IGNORE INTO `tenants` (`name`,`domain`,`admin_email`,`admin_pass`,`package`,`status`) VALUES
('Taqwim Institute',
 'arprimemarket.shop',
 'admin@taqwiminstitute.com',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'pro',
 'active');

-- ════════════════════════
-- DEFAULT LOGINS:
-- Super Admin:
--   URL     : yourdomain.com/superadmin/
--   Email   : admin@abdullahraiyan.com
--   Password: SuperAdmin@2025
--
-- LMS Admin (Taqwim):
--   Email   : admin@taqwiminstitute.com
--   Password: Taqwim@2025
-- ════════════════════════
