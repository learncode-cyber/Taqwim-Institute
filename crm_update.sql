-- CRM Update SQL — phpMyAdmin এ run করুন
ALTER TABLE `leads`
  ADD COLUMN IF NOT EXISTS `stage`         ENUM('new','contacted','demo','enrolled','lost') DEFAULT 'new',
  ADD COLUMN IF NOT EXISTS `updated_at`    DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

CREATE TABLE IF NOT EXISTS `comm_logs` (
  `id`        INT AUTO_INCREMENT PRIMARY KEY,
  `type`      ENUM('lead','student') DEFAULT 'lead',
  `ref_id`    INT NOT NULL,
  `channel`   ENUM('whatsapp','call','sms','email','meeting','other') DEFAULT 'whatsapp',
  `direction` ENUM('inbound','outbound') DEFAULT 'outbound',
  `note`      TEXT NOT NULL,
  `logged_by` INT NOT NULL,
  `logged_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `followups` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `type`       ENUM('lead','student') DEFAULT 'lead',
  `ref_id`     INT NOT NULL,
  `ref_name`   VARCHAR(150) NOT NULL,
  `ref_phone`  VARCHAR(20) DEFAULT NULL,
  `note`       TEXT DEFAULT NULL,
  `due_at`     DATETIME NOT NULL,
  `is_done`    TINYINT(1) DEFAULT 0,
  `created_by` INT NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
