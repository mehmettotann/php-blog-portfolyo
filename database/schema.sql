-- =========================================================================
-- example.com — İlişkisel MySQL Veritabanı Şeması
-- Uyumlu: MySQL 5.5 / 5.6 / 5.7 / 8.0+ ve MariaDB 10.x (Plesk & cPanel Hazır)
-- =========================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(60) NOT NULL,
    `display_name` VARCHAR(120) NOT NULL,
    `email` VARCHAR(180) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `last_login_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_username` (`username`),
    UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `settings` (
    `setting_key` VARCHAR(100) NOT NULL,
    `setting_value` MEDIUMTEXT DEFAULT NULL,
    `updated_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(120) NOT NULL,
    `description` VARCHAR(255) DEFAULT NULL,
    `created_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_categories_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `posts` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `category_id` INT UNSIGNED DEFAULT NULL,
    `title` VARCHAR(200) NOT NULL,
    `slug` VARCHAR(191) NOT NULL,
    `excerpt` TEXT DEFAULT NULL,
    `content_blocks` LONGTEXT NOT NULL COMMENT 'Blok tabanlı içerik JSON verisi',
    `content_text` MEDIUMTEXT DEFAULT NULL COMMENT 'Arama ve okuma süresi için düz metin indeksi',
    `cover_image` VARCHAR(255) DEFAULT NULL,
    `reading_time` SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    `status` ENUM('draft', 'published') NOT NULL DEFAULT 'draft',
    `published_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME DEFAULT NULL,
    `updated_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_posts_slug` (`slug`),
    KEY `idx_posts_status_pub` (`status`, `published_at`),
    KEY `idx_posts_category` (`category_id`),
    CONSTRAINT `fk_posts_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `projects` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(200) NOT NULL,
    `slug` VARCHAR(191) NOT NULL,
    `summary` TEXT NOT NULL,
    `description_blocks` LONGTEXT DEFAULT NULL COMMENT 'Detaylı proje hikayesi JSON blokları',
    `cover_image` VARCHAR(255) DEFAULT NULL,
    `technologies` VARCHAR(500) DEFAULT NULL COMMENT 'Virgülle ayrılmış teknolojiler/etiketler',
    `live_url` VARCHAR(255) DEFAULT NULL,
    `source_url` VARCHAR(255) DEFAULT NULL,
    `project_year` VARCHAR(20) DEFAULT NULL,
    `status` ENUM('draft', 'published') NOT NULL DEFAULT 'published',
    `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` DATETIME DEFAULT NULL,
    `updated_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_projects_slug` (`slug`),
    KEY `idx_projects_status_feat` (`status`, `is_featured`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `project_media` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `project_id` INT UNSIGNED NOT NULL,
    `media_type` ENUM('image', 'video_local', 'video_external') NOT NULL,
    `file_path` VARCHAR(255) DEFAULT NULL COMMENT 'Yerel görsel veya MP4/WebM dosya yolu',
    `poster_path` VARCHAR(255) DEFAULT NULL COMMENT 'Yerel video için kapak (poster) görseli',
    `external_url` VARCHAR(255) DEFAULT NULL COMMENT 'YouTube veya Vimeo bağlantısı',
    `provider` VARCHAR(30) DEFAULT NULL COMMENT 'youtube | vimeo',
    `video_id` VARCHAR(64) DEFAULT NULL COMMENT 'Ayrıştırılmış harici video kimliği',
    `title` VARCHAR(180) DEFAULT NULL,
    `caption` VARCHAR(300) DEFAULT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_pm_project_sort` (`project_id`, `sort_order`),
    CONSTRAINT `fk_project_media_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `media_files` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `file_type` ENUM('image', 'video') NOT NULL,
    `file_path` VARCHAR(191) NOT NULL,
    `original_name` VARCHAR(255) NOT NULL,
    `mime_type` VARCHAR(100) NOT NULL,
    `file_size` BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_media_files_path` (`file_path`),
    KEY `idx_media_type` (`file_type`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `contact_messages` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(120) NOT NULL,
    `email` VARCHAR(180) NOT NULL,
    `subject` VARCHAR(180) NOT NULL DEFAULT 'İletişim Formu Mesajı',
    `message` TEXT NOT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `is_read` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_contact_read_date` (`is_read`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `login_attempts` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ip_address` VARCHAR(45) NOT NULL,
    `username` VARCHAR(80) DEFAULT NULL,
    `attempted_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_login_ip_time` (`ip_address`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
