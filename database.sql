-- ════════════════════════════════════════════
-- Taqwim Institute LMS — Complete Database
-- ════════════════════════════════════════════
SET NAMES utf8mb4;
SET foreign_key_checks = 0;

-- Users
CREATE TABLE IF NOT EXISTS `users` (
  `id`             INT AUTO_INCREMENT PRIMARY KEY,
  `name`           VARCHAR(150) NOT NULL,
  `email`          VARCHAR(150) NOT NULL UNIQUE,
  `phone`          VARCHAR(20)  NOT NULL,
  `password`       VARCHAR(255) NOT NULL,
  `role`           ENUM('admin','teacher','student') DEFAULT 'student',
  `package`        ENUM('basic','standard','premium') DEFAULT 'basic',
  `guardian_phone` VARCHAR(20)  DEFAULT NULL,
  `bio`            TEXT         DEFAULT NULL,
  `is_active`      TINYINT(1)   DEFAULT 1,
  `created_at`     DATETIME     DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Classes
CREATE TABLE IF NOT EXISTS `classes` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `title`       VARCHAR(200) NOT NULL,
  `teacher_id`  INT NOT NULL,
  `class_date`  DATE NOT NULL,
  `class_time`  TIME NOT NULL,
  `duration`    INT  DEFAULT 45,
  `platform`    ENUM('google_meet','zoom') DEFAULT 'google_meet',
  `meet_link`   VARCHAR(500) DEFAULT NULL,
  `zoom_link`   VARCHAR(500) DEFAULT NULL,
  `class_type`  ENUM('group','individual') DEFAULT 'group',
  `status`      ENUM('scheduled','completed','cancelled') DEFAULT 'scheduled',
  `notes`       TEXT DEFAULT NULL,
  `created_at`  DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`teacher_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Class Students
CREATE TABLE IF NOT EXISTS `class_students` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `class_id`   INT NOT NULL,
  `student_id` INT NOT NULL,
  UNIQUE KEY `uniq` (`class_id`,`student_id`),
  FOREIGN KEY (`class_id`)   REFERENCES `classes`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`student_id`) REFERENCES `users`(`id`)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Attendance
CREATE TABLE IF NOT EXISTS `attendance` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `class_id`   INT NOT NULL,
  `student_id` INT NOT NULL,
  `status`     ENUM('present','absent') DEFAULT 'absent',
  `marked_at`  DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uniq` (`class_id`,`student_id`),
  FOREIGN KEY (`class_id`)   REFERENCES `classes`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`student_id`) REFERENCES `users`(`id`)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Payments
CREATE TABLE IF NOT EXISTS `payments` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `student_id`   INT NOT NULL,
  `amount`       DECIMAL(10,2) NOT NULL,
  `package`      VARCHAR(50)   DEFAULT NULL,
  `method`       ENUM('bkash','nagad') DEFAULT 'bkash',
  `txn_id`       VARCHAR(100)  NOT NULL,
  `month_year`   VARCHAR(10)   DEFAULT NULL,
  `status`       ENUM('pending','confirmed','rejected') DEFAULT 'pending',
  `confirmed_by` INT           DEFAULT NULL,
  `confirmed_at` DATETIME      DEFAULT NULL,
  `created_at`   DATETIME      DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Reports
CREATE TABLE IF NOT EXISTS `reports` (
  `id`            INT AUTO_INCREMENT PRIMARY KEY,
  `teacher_id`    INT NOT NULL,
  `student_id`    INT NOT NULL,
  `report_type`   ENUM('weekly','monthly','special') DEFAULT 'weekly',
  `tilawat_grade` ENUM('excellent','good','average','needs_improvement') DEFAULT 'good',
  `content`       TEXT NOT NULL,
  `homework`      TEXT DEFAULT NULL,
  `created_at`    DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`teacher_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Leads
CREATE TABLE IF NOT EXISTS `leads` (
  `id`               INT AUTO_INCREMENT PRIMARY KEY,
  `name`             VARCHAR(150) NOT NULL,
  `phone`            VARCHAR(20)  NOT NULL,
  `email`            VARCHAR(150) DEFAULT NULL,
  `course`           VARCHAR(100) DEFAULT NULL,
  `for_whom`         VARCHAR(100) DEFAULT NULL,
  `package_interest` VARCHAR(50)  DEFAULT NULL,
  `source`           VARCHAR(50)  DEFAULT 'landing_page',
  `status`           ENUM('new','contacted','enrolled','cancelled') DEFAULT 'new',
  `created_at`       DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Settings
CREATE TABLE IF NOT EXISTS `settings` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `key_name`   VARCHAR(100) NOT NULL UNIQUE,
  `value`      TEXT         DEFAULT NULL,
  `updated_at` DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Coupons
CREATE TABLE IF NOT EXISTS `coupons` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `code`        VARCHAR(50)   NOT NULL UNIQUE,
  `type`        ENUM('percent','fixed') DEFAULT 'percent',
  `value`       DECIMAL(10,2) NOT NULL DEFAULT 0,
  `min_amount`  DECIMAL(10,2) DEFAULT 0,
  `max_uses`    INT           DEFAULT NULL,
  `used_count`  INT           DEFAULT 0,
  `valid_from`  DATE          DEFAULT NULL,
  `valid_until` DATE          DEFAULT NULL,
  `is_active`   TINYINT(1)    DEFAULT 1,
  `created_at`  DATETIME      DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Coupon Uses
CREATE TABLE IF NOT EXISTS `coupon_uses` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `coupon_id`  INT           NOT NULL,
  `student_id` INT           NOT NULL,
  `payment_id` INT           DEFAULT NULL,
  `discount`   DECIMAL(10,2) NOT NULL,
  `used_at`    DATETIME      DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`coupon_id`)  REFERENCES `coupons`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`student_id`) REFERENCES `users`(`id`)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET foreign_key_checks = 1;

-- ════ DEFAULT SETTINGS ════
INSERT IGNORE INTO `settings` (`key_name`, `value`) VALUES
('site_name',        'Taqwim Institute'),
('site_tagline',     'Knowledge · Character · Guidance'),
('site_logo',        ''),
('meta_pixel_id',    ''),
('telegram_bot_token',''),
('telegram_chat_id', ''),
('whatsapp_number',  ''),
('bkash_number',     ''),
('nagad_number',     ''),
('zoom_default_link',''),
('meet_default_link',''),
('facebook_page',    ''),
('youtube_channel',  ''),
('admin_email',      ''),
('site_footer_text', ''),
('currency_symbol',  '৳'),
('currency_code',    'BDT'),
('theme_primary',    '#1e5c32'),
('theme_gold',       '#b8963e'),
('theme_dark',       '#14381e'),
('theme_preset',     'taqwim');

-- ════ ADMIN USER ════
-- Password: Taqwim@2025
INSERT IGNORE INTO `users` (`name`,`email`,`phone`,`password`,`role`,`is_active`) VALUES
('Super Admin','admin@taqwiminstitute.com','01700000000',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'admin',1);

-- ════ SAMPLE COUPONS ════
INSERT IGNORE INTO `coupons` (`code`,`type`,`value`,`max_uses`,`is_active`) VALUES
('TAQWIM10',  'percent', 10,  100, 1),
('WELCOME20', 'percent', 20,  50,  1),
('EID50',     'percent', 50,  30,  1),
('FLAT500',   'fixed',   500, 20,  1);

-- ⚠️ Default login:
-- Email   : admin@taqwiminstitute.com
-- Password: Taqwim@2025
-- Change at: yourdomain.com/admin/change_password.php

-- ════════════════════════════════════════
-- SUPER ADMIN SYSTEM (Multi-Client SaaS)
-- ════════════════════════════════════════
CREATE TABLE IF NOT EXISTS `tenants` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `name`         VARCHAR(150) NOT NULL,
  `domain`       VARCHAR(200) NOT NULL UNIQUE,
  `admin_email`  VARCHAR(150) NOT NULL,
  `admin_pass`   VARCHAR(255) NOT NULL,
  `package`      ENUM('basic','pro','enterprise') DEFAULT 'basic',
  `status`       ENUM('active','suspended','trial') DEFAULT 'trial',
  `trial_ends`   DATE DEFAULT NULL,
  `notes`        TEXT DEFAULT NULL,
  `created_at`   DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `super_admins` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `name`       VARCHAR(150) NOT NULL,
  `email`      VARCHAR(150) NOT NULL UNIQUE,
  `password`   VARCHAR(255) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Super Admin: Abdullah Raiyan — Password: SuperAdmin@2025
INSERT IGNORE INTO `super_admins` (`name`,`email`,`password`) VALUES
('Abdullah Raiyan','admin@abdullahraiyan.com',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- First tenant
INSERT IGNORE INTO `tenants` (`name`,`domain`,`admin_email`,`admin_pass`,`package`,`status`) VALUES
('Taqwim Institute','arprimemarket.shop','admin@taqwiminstitute.com',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','pro','active');

-- ════════════════════════════════════════
-- PHASE 8 — Multi-Course Platform
-- ════════════════════════════════════════

-- Course Categories
CREATE TABLE IF NOT EXISTS `course_categories` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `name`        VARCHAR(100) NOT NULL,
  `slug`        VARCHAR(100) NOT NULL UNIQUE,
  `icon`        VARCHAR(10)  DEFAULT '📚',
  `description` TEXT DEFAULT NULL,
  `sort_order`  INT DEFAULT 0,
  `is_active`   TINYINT(1) DEFAULT 1,
  `created_at`  DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Courses
CREATE TABLE IF NOT EXISTS `courses` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `category_id`  INT NOT NULL,
  `title`        VARCHAR(200) NOT NULL,
  `slug`         VARCHAR(200) NOT NULL UNIQUE,
  `description`  TEXT DEFAULT NULL,
  `thumbnail`    VARCHAR(300) DEFAULT NULL,
  `instructor`   VARCHAR(150) DEFAULT NULL,
  `level`        ENUM('beginner','intermediate','advanced') DEFAULT 'beginner',
  `language`     ENUM('bangla','english','arabic') DEFAULT 'bangla',
  `model`        ENUM('self_paced','cohort','subscription','live') DEFAULT 'self_paced',
  `price`        DECIMAL(10,2) DEFAULT 0,
  `sale_price`   DECIMAL(10,2) DEFAULT NULL,
  `duration`     VARCHAR(50) DEFAULT NULL,
  `total_lessons` INT DEFAULT 0,
  `is_free`      TINYINT(1) DEFAULT 0,
  `is_featured`  TINYINT(1) DEFAULT 0,
  `is_active`    TINYINT(1) DEFAULT 1,
  `sort_order`   INT DEFAULT 0,
  `created_at`   DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Modules (Chapter)
CREATE TABLE IF NOT EXISTS `course_modules` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `course_id`  INT NOT NULL,
  `title`      VARCHAR(200) NOT NULL,
  `sort_order` INT DEFAULT 0,
  `is_active`  TINYINT(1) DEFAULT 1,
  FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Lessons
CREATE TABLE IF NOT EXISTS `course_lessons` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `module_id`   INT NOT NULL,
  `course_id`   INT NOT NULL,
  `title`       VARCHAR(200) NOT NULL,
  `type`        ENUM('video','pdf','text','live','quiz') DEFAULT 'video',
  `content_url` VARCHAR(500) DEFAULT NULL,
  `duration`    VARCHAR(20) DEFAULT NULL,
  `is_free`     TINYINT(1) DEFAULT 0,
  `sort_order`  INT DEFAULT 0,
  `is_active`   TINYINT(1) DEFAULT 1,
  FOREIGN KEY (`module_id`)  REFERENCES `course_modules`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`course_id`)  REFERENCES `courses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Course Enrollments
CREATE TABLE IF NOT EXISTS `course_enrollments` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `course_id`    INT NOT NULL,
  `student_id`   INT NOT NULL,
  `enrolled_at`  DATETIME DEFAULT CURRENT_TIMESTAMP,
  `expires_at`   DATETIME DEFAULT NULL,
  `status`       ENUM('active','expired','cancelled') DEFAULT 'active',
  `payment_id`   INT DEFAULT NULL,
  UNIQUE KEY `uniq` (`course_id`,`student_id`),
  FOREIGN KEY (`course_id`)  REFERENCES `courses`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Lesson Progress
CREATE TABLE IF NOT EXISTS `lesson_progress` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `student_id`   INT NOT NULL,
  `course_id`    INT NOT NULL,
  `lesson_id`    INT NOT NULL,
  `is_completed` TINYINT(1) DEFAULT 0,
  `completed_at` DATETIME DEFAULT NULL,
  UNIQUE KEY `uniq` (`student_id`,`lesson_id`),
  FOREIGN KEY (`student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`lesson_id`)  REFERENCES `course_lessons`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ════ DEFAULT CATEGORIES ════
INSERT IGNORE INTO `course_categories` (`name`,`slug`,`icon`,`description`,`sort_order`) VALUES
('ইসলামিক শিক্ষা',    'islamic',    '📖', 'কুরআন, তাজওয়িদ, হিফজ, ইসলামিক জ্ঞান', 1),
('ওয়েব ডেভেলপমেন্ট', 'web-dev',    '💻', 'HTML, CSS, PHP, JavaScript, Vibe Coding', 2),
('ডিজিটাল মার্কেটিং', 'marketing',  '📱', 'Meta Ads, SEO, Content Marketing, Social Media', 3),
('AI অটোমেশন',        'automation', '🤖', 'n8n, Make.com, ChatGPT API, Zapier', 4),
('AI টুলস',           'ai-tools',   '✨', 'Midjourney, Claude AI, Perplexity, AI Workflow', 5);

-- ════ SAMPLE COURSES ════
INSERT IGNORE INTO `courses` (`category_id`,`title`,`slug`,`description`,`instructor`,`level`,`model`,`price`,`sale_price`,`duration`,`is_featured`,`is_active`) VALUES
(1,'শুদ্ধ কুরআন তেলাওয়াত','quran-tilawat','তাজওয়িদের নিয়ম মেনে শুদ্ধ কুরআন পড়তে শিখুন','উস্তাদ রাইয়ান','beginner','live',3200,NULL,'৩ মাস',1,1),
(1,'হিফজুল কুরআন প্রোগ্রাম','hifz-quran','পদ্ধতিগতভাবে কুরআন হিফজ করুন অভিজ্ঞ শিক্ষকের তত্ত্বাবধানে','উস্তাদ রাইয়ান','intermediate','cohort',3800,NULL,'১ বছর',1,1),
(2,'Vibe Coding with AI','vibe-coding','AI দিয়ে code করুন — no-code থেকে full-stack পর্যন্ত','Abdullah Raiyan','beginner','self_paced',2500,1999,'৬ সপ্তাহ',1,1),
(2,'PHP & MySQL থেকে শুরু','php-mysql','শূন্য থেকে PHP দিয়ে ওয়েব অ্যাপ বানানো শিখুন','Abdullah Raiyan','beginner','self_paced',1999,NULL,'৮ সপ্তাহ',0,1),
(3,'Meta Ads মাস্টারক্লাস','meta-ads','Facebook ও Instagram Ads দিয়ে business grow করুন','Abdullah Raiyan','intermediate','self_paced',2999,1999,'৪ সপ্তাহ',1,1),
(3,'SEO সম্পূর্ণ গাইড','seo-guide','Google-এ rank করুন — On-page, Off-page, Technical SEO','Abdullah Raiyan','beginner','self_paced',1999,NULL,'৫ সপ্তাহ',0,1),
(4,'n8n Automation Bootcamp','n8n-bootcamp','n8n দিয়ে workflow automate করুন — beginner to pro','Abdullah Raiyan','beginner','cohort',3500,2999,'৪ সপ্তাহ',1,1),
(4,'Make.com দিয়ে AI Workflow','make-workflow','Make.com ও AI দিয়ে business automate করুন','Abdullah Raiyan','beginner','self_paced',2500,NULL,'৩ সপ্তাহ',0,1),
(5,'AI Tools Masterclass','ai-tools-master','ChatGPT, Claude, Midjourney — সব AI tool এক জায়গায়','Abdullah Raiyan','beginner','self_paced',1999,999,'৩ সপ্তাহ',1,1),
(5,'ChatGPT Prompt Engineering','prompt-engineering','Perfect prompt লিখুন, AI থেকে সেরা output নিন','Abdullah Raiyan','beginner','self_paced',999,NULL,'২ সপ্তাহ',0,1);
