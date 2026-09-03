-- ════════════════════════════════════════
-- Phase 8 Update SQL
-- আগের DB থাকলে শুধু এটা run করুন
-- ════════════════════════════════════════

CREATE TABLE IF NOT EXISTS `course_categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY, `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL UNIQUE, `icon` VARCHAR(10) DEFAULT '📚',
  `description` TEXT DEFAULT NULL, `sort_order` INT DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1, `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `courses` (
  `id` INT AUTO_INCREMENT PRIMARY KEY, `category_id` INT NOT NULL,
  `title` VARCHAR(200) NOT NULL, `slug` VARCHAR(200) NOT NULL UNIQUE,
  `description` TEXT, `thumbnail` VARCHAR(300), `instructor` VARCHAR(150),
  `level` ENUM('beginner','intermediate','advanced') DEFAULT 'beginner',
  `language` ENUM('bangla','english','arabic') DEFAULT 'bangla',
  `model` ENUM('self_paced','cohort','subscription','live') DEFAULT 'self_paced',
  `price` DECIMAL(10,2) DEFAULT 0, `sale_price` DECIMAL(10,2) DEFAULT NULL,
  `duration` VARCHAR(50), `total_lessons` INT DEFAULT 0,
  `is_free` TINYINT(1) DEFAULT 0, `is_featured` TINYINT(1) DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1, `sort_order` INT DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `course_modules` (
  `id` INT AUTO_INCREMENT PRIMARY KEY, `course_id` INT NOT NULL,
  `title` VARCHAR(200) NOT NULL, `sort_order` INT DEFAULT 0, `is_active` TINYINT(1) DEFAULT 1,
  FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `course_lessons` (
  `id` INT AUTO_INCREMENT PRIMARY KEY, `module_id` INT NOT NULL, `course_id` INT NOT NULL,
  `title` VARCHAR(200) NOT NULL, `type` ENUM('video','pdf','text','live','quiz') DEFAULT 'video',
  `content_url` VARCHAR(500), `duration` VARCHAR(20), `is_free` TINYINT(1) DEFAULT 0,
  `sort_order` INT DEFAULT 0, `is_active` TINYINT(1) DEFAULT 1,
  FOREIGN KEY (`module_id`) REFERENCES `course_modules`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `course_enrollments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY, `course_id` INT NOT NULL, `student_id` INT NOT NULL,
  `enrolled_at` DATETIME DEFAULT CURRENT_TIMESTAMP, `expires_at` DATETIME DEFAULT NULL,
  `status` ENUM('active','expired','cancelled') DEFAULT 'active', `payment_id` INT DEFAULT NULL,
  UNIQUE KEY `uniq` (`course_id`,`student_id`),
  FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `lesson_progress` (
  `id` INT AUTO_INCREMENT PRIMARY KEY, `student_id` INT NOT NULL,
  `course_id` INT NOT NULL, `lesson_id` INT NOT NULL,
  `is_completed` TINYINT(1) DEFAULT 0, `completed_at` DATETIME DEFAULT NULL,
  UNIQUE KEY `uniq` (`student_id`,`lesson_id`),
  FOREIGN KEY (`student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`lesson_id`) REFERENCES `course_lessons`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `course_categories` (`name`,`slug`,`icon`,`description`,`sort_order`) VALUES
('ইসলামিক শিক্ষা','islamic','📖','কুরআন, তাজওয়িদ, হিফজ',1),
('ওয়েব ডেভেলপমেন্ট','web-dev','💻','HTML, CSS, PHP, Vibe Coding',2),
('ডিজিটাল মার্কেটিং','marketing','📱','Meta Ads, SEO, Content',3),
('AI অটোমেশন','automation','🤖','n8n, Make.com, ChatGPT API',4),
('AI টুলস','ai-tools','✨','Claude, Midjourney, AI Workflow',5);

INSERT IGNORE INTO `courses` (`category_id`,`title`,`slug`,`description`,`instructor`,`level`,`model`,`price`,`sale_price`,`duration`,`is_featured`,`is_active`) VALUES
(1,'শুদ্ধ কুরআন তেলাওয়াত','quran-tilawat','তাজওয়িদের নিয়ম মেনে শুদ্ধ কুরআন পড়তে শিখুন','উস্তাদ রাইয়ান','beginner','live',3200,NULL,'৩ মাস',1,1),
(1,'হিফজুল কুরআন প্রোগ্রাম','hifz-quran','পদ্ধতিগতভাবে কুরআন হিফজ করুন','উস্তাদ রাইয়ান','intermediate','cohort',3800,NULL,'১ বছর',1,1),
(2,'Vibe Coding with AI','vibe-coding','AI দিয়ে code করুন — no-code থেকে full-stack','Abdullah Raiyan','beginner','self_paced',2500,1999,'৬ সপ্তাহ',1,1),
(2,'PHP & MySQL থেকে শুরু','php-mysql','শূন্য থেকে PHP দিয়ে ওয়েব অ্যাপ বানানো','Abdullah Raiyan','beginner','self_paced',1999,NULL,'৮ সপ্তাহ',0,1),
(3,'Meta Ads মাস্টারক্লাস','meta-ads','Facebook ও Instagram Ads দিয়ে business grow করুন','Abdullah Raiyan','intermediate','self_paced',2999,1999,'৪ সপ্তাহ',1,1),
(3,'SEO সম্পূর্ণ গাইড','seo-guide','Google-এ rank করুন — On-page, Off-page, Technical SEO','Abdullah Raiyan','beginner','self_paced',1999,NULL,'৫ সপ্তাহ',0,1),
(4,'n8n Automation Bootcamp','n8n-bootcamp','n8n দিয়ে workflow automate করুন','Abdullah Raiyan','beginner','cohort',3500,2999,'৪ সপ্তাহ',1,1),
(4,'Make.com দিয়ে AI Workflow','make-workflow','Make.com ও AI দিয়ে business automate করুন','Abdullah Raiyan','beginner','self_paced',2500,NULL,'৩ সপ্তাহ',0,1),
(5,'AI Tools Masterclass','ai-tools-master','ChatGPT, Claude, Midjourney — সব AI tool','Abdullah Raiyan','beginner','self_paced',1999,999,'৩ সপ্তাহ',1,1),
(5,'ChatGPT Prompt Engineering','prompt-engineering','Perfect prompt লিখুন, AI থেকে সেরা output নিন','Abdullah Raiyan','beginner','self_paced',999,NULL,'২ সপ্তাহ',0,1);
