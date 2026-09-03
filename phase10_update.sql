-- ════════════════════════════════════════
-- Phase 10 — Quiz, Assignment, Certificate
-- phpMyAdmin এ run করুন
-- ════════════════════════════════════════

-- Quiz Table
CREATE TABLE IF NOT EXISTS `quizzes` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `course_id`    INT NOT NULL,
  `lesson_id`    INT DEFAULT NULL,
  `title`        VARCHAR(200) NOT NULL,
  `description`  TEXT DEFAULT NULL,
  `time_limit`   INT DEFAULT NULL COMMENT 'minutes, NULL=unlimited',
  `pass_mark`    INT DEFAULT 60 COMMENT 'percentage',
  `max_attempts` INT DEFAULT 3,
  `is_active`    TINYINT(1) DEFAULT 1,
  `created_at`   DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Quiz Questions
CREATE TABLE IF NOT EXISTS `quiz_questions` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `quiz_id`     INT NOT NULL,
  `question`    TEXT NOT NULL,
  `type`        ENUM('mcq','true_false','short') DEFAULT 'mcq',
  `option_a`    VARCHAR(300) DEFAULT NULL,
  `option_b`    VARCHAR(300) DEFAULT NULL,
  `option_c`    VARCHAR(300) DEFAULT NULL,
  `option_d`    VARCHAR(300) DEFAULT NULL,
  `correct`     VARCHAR(10) NOT NULL COMMENT 'a/b/c/d or true/false',
  `explanation` TEXT DEFAULT NULL,
  `marks`       INT DEFAULT 1,
  `sort_order`  INT DEFAULT 0,
  FOREIGN KEY (`quiz_id`) REFERENCES `quizzes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Quiz Attempts
CREATE TABLE IF NOT EXISTS `quiz_attempts` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `quiz_id`     INT NOT NULL,
  `student_id`  INT NOT NULL,
  `answers`     JSON DEFAULT NULL,
  `score`       INT DEFAULT 0,
  `total_marks` INT DEFAULT 0,
  `percentage`  INT DEFAULT 0,
  `passed`      TINYINT(1) DEFAULT 0,
  `time_taken`  INT DEFAULT NULL COMMENT 'seconds',
  `attempted_at`DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`quiz_id`)    REFERENCES `quizzes`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`student_id`) REFERENCES `users`(`id`)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Assignments
CREATE TABLE IF NOT EXISTS `assignments` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `course_id`   INT NOT NULL,
  `teacher_id`  INT NOT NULL,
  `title`       VARCHAR(200) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `due_date`    DATETIME DEFAULT NULL,
  `max_marks`   INT DEFAULT 100,
  `is_active`   TINYINT(1) DEFAULT 1,
  `created_at`  DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`course_id`)  REFERENCES `courses`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`teacher_id`) REFERENCES `users`(`id`)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Assignment Submissions
CREATE TABLE IF NOT EXISTS `assignment_submissions` (
  `id`            INT AUTO_INCREMENT PRIMARY KEY,
  `assignment_id` INT NOT NULL,
  `student_id`    INT NOT NULL,
  `answer_text`   TEXT DEFAULT NULL,
  `file_url`      VARCHAR(500) DEFAULT NULL,
  `marks`         INT DEFAULT NULL,
  `feedback`      TEXT DEFAULT NULL,
  `status`        ENUM('submitted','graded','returned') DEFAULT 'submitted',
  `submitted_at`  DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uniq` (`assignment_id`,`student_id`),
  FOREIGN KEY (`assignment_id`) REFERENCES `assignments`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`student_id`)    REFERENCES `users`(`id`)       ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Certificates
CREATE TABLE IF NOT EXISTS `certificates` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `course_id`    INT NOT NULL,
  `student_id`   INT NOT NULL,
  `cert_id`      VARCHAR(20) NOT NULL UNIQUE COMMENT 'TAQWIM-XXXX-XXXX',
  `issued_at`    DATETIME DEFAULT CURRENT_TIMESTAMP,
  `expires_at`   DATETIME DEFAULT NULL,
  `is_valid`     TINYINT(1) DEFAULT 1,
  UNIQUE KEY `uniq` (`course_id`,`student_id`),
  FOREIGN KEY (`course_id`)  REFERENCES `courses`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`student_id`) REFERENCES `users`(`id`)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
