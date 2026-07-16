-- ============================================================
--  Video Player - Database Schema
--  Run this in phpMyAdmin / MySQL CLI if you're not using
--  CodeIgniter migrations (php spark migrate).
-- ============================================================

CREATE TABLE IF NOT EXISTS `videos` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT NULL DEFAULT NULL,
  `filename` VARCHAR(255) NOT NULL COMMENT 'Randomized name stored on disk',
  `original_filename` VARCHAR(255) NOT NULL COMMENT 'Original uploaded filename',
  `file_path` VARCHAR(500) NOT NULL COMMENT 'Path relative to /public, e.g. uploads/videos/xxx.mp4',
  `mime_type` VARCHAR(100) NOT NULL,
  `file_size` INT UNSIGNED NOT NULL COMMENT 'Size in bytes',
  `duration_seconds` INT UNSIGNED NULL DEFAULT NULL,
  `thumbnail_path` VARCHAR(500) NULL DEFAULT NULL,
  `status` ENUM('active','deleted') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NULL DEFAULT NULL,
  `updated_at` DATETIME NULL DEFAULT NULL,
  `deleted_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Optional: a couple of sample rows so the player has something to show.
-- Replace the file_path values with real files inside public/uploads/videos/
-- once you've uploaded something, or just delete these two lines.
--
-- INSERT INTO `videos` (`title`, `description`, `filename`, `original_filename`, `file_path`, `mime_type`, `file_size`, `status`, `created_at`, `updated_at`)
-- VALUES ('Sample Clip', 'Test upload', 'sample.mp4', 'sample.mp4', 'uploads/videos/sample.mp4', 'video/mp4', 1048576, 'active', NOW(), NOW());
