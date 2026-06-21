-- ============================================================
-- Stadium Reservation System - Database Schema
-- Engine: InnoDB | Charset: utf8mb4 | Collation: utf8mb4_unicode_ci
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET time_zone = '+03:30';

DROP DATABASE IF EXISTS `stadium_reservation`;
CREATE DATABASE `stadium_reservation`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `stadium_reservation`;

-- ============================================================
-- TABLE: roles
-- ============================================================
CREATE TABLE `roles` (
                         `id`         TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
                         `name`       VARCHAR(50)      NOT NULL,
                         `label`      VARCHAR(100)     NOT NULL,
                         `created_at` TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
                         PRIMARY KEY (`id`),
                         UNIQUE KEY `uq_roles_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: users
-- ============================================================
CREATE TABLE `users` (
                         `id`                   INT UNSIGNED     NOT NULL AUTO_INCREMENT,
                         `role_id`              TINYINT UNSIGNED NOT NULL DEFAULT 2,
                         `full_name`            VARCHAR(150)     NOT NULL,
                         `username`             VARCHAR(80)      NOT NULL,
                         `email`                VARCHAR(200)     NULL,
                         `phone`                VARCHAR(20)      NOT NULL,
                         `password_hash`        VARCHAR(255)     NOT NULL,
                         `avatar`               VARCHAR(255)     NULL,
                         `status`               ENUM('active','inactive','banned') NOT NULL DEFAULT 'active',
                         `email_verified`       TINYINT(1)       NOT NULL DEFAULT 0,
                         `email_verify_token`   VARCHAR(100)     NULL,
                         `remember_token`       VARCHAR(100)     NULL,
                         `remember_expires`     DATETIME         NULL,
                         `login_attempts`       TINYINT UNSIGNED NOT NULL DEFAULT 0,
                         `locked_until`         DATETIME         NULL,
                         `last_login_at`        DATETIME         NULL,
                         `last_login_ip`        VARCHAR(45)      NULL,
                         `created_at`           TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
                         `updated_at`           TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                         PRIMARY KEY (`id`),
                         UNIQUE KEY `uq_users_username` (`username`),
                         UNIQUE KEY `uq_users_email`    (`email`),
                         UNIQUE KEY `uq_users_phone`    (`phone`),
                         KEY `idx_users_role`           (`role_id`),
                         KEY `idx_users_status`         (`status`),
                         CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: categories
-- ============================================================
CREATE TABLE `categories` (
                              `id`         SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
                              `name`       VARCHAR(100)      NOT NULL,
                              `slug`       VARCHAR(120)      NOT NULL,
                              `icon`       VARCHAR(100)      NULL,
                              `created_at` TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
                              PRIMARY KEY (`id`),
                              UNIQUE KEY `uq_categories_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: locations  (stadiums / venues)
-- ============================================================
CREATE TABLE `locations` (
                             `id`            INT UNSIGNED      NOT NULL AUTO_INCREMENT,
                             `category_id`   SMALLINT UNSIGNED NOT NULL,
                             `created_by`    INT UNSIGNED      NOT NULL,
                             `title`         VARCHAR(200)      NOT NULL,
                             `slug`          VARCHAR(220)      NOT NULL,
                             `description`   TEXT              NULL,
                             `address`       VARCHAR(500)      NOT NULL,
                             `city`          VARCHAR(100)      NOT NULL DEFAULT 'تهران',
                             `district`      VARCHAR(100)      NULL,
                             `price_per_session` DECIMAL(12,0) NOT NULL DEFAULT 0,
                             `capacity`      TINYINT UNSIGNED  NOT NULL DEFAULT 10,
                             `surface_type`  ENUM('artificial','natural','indoor','outdoor') NOT NULL DEFAULT 'artificial',
                             `amenities`     JSON              NULL,
                             `rules`         TEXT              NULL,
                             `phone`         VARCHAR(20)       NULL,
                             `latitude`      DECIMAL(10,8)     NULL,
                             `longitude`     DECIMAL(11,8)     NULL,
                             `status`        ENUM('active','inactive') NOT NULL DEFAULT 'active',
                             `rating_avg`    DECIMAL(3,2)      NOT NULL DEFAULT 0.00,
                             `rating_count`  INT UNSIGNED      NOT NULL DEFAULT 0,
                             `created_at`    TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
                             `updated_at`    TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                             PRIMARY KEY (`id`),
                             UNIQUE KEY `uq_locations_slug`  (`slug`),
                             KEY `idx_locations_category`    (`category_id`),
                             KEY `idx_locations_status`      (`status`),
                             KEY `idx_locations_city`        (`city`),
                             CONSTRAINT `fk_locations_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON UPDATE CASCADE,
                             CONSTRAINT `fk_locations_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: location_images
-- ============================================================
CREATE TABLE `location_images` (
                                   `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
                                   `location_id` INT UNSIGNED NOT NULL,
                                   `filename`    VARCHAR(255) NOT NULL,
                                   `alt_text`    VARCHAR(200) NULL,
                                   `sort_order`  TINYINT UNSIGNED NOT NULL DEFAULT 0,
                                   `is_primary`  TINYINT(1)   NOT NULL DEFAULT 0,
                                   `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                   PRIMARY KEY (`id`),
                                   KEY `idx_location_images_location` (`location_id`),
                                   CONSTRAINT `fk_location_images_location` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: available_slots  (predefined time slots per location)
-- ============================================================
CREATE TABLE `available_slots` (
                                   `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
                                   `location_id` INT UNSIGNED  NOT NULL,
                                   `day_of_week` TINYINT UNSIGNED NOT NULL COMMENT '0=Sunday … 6=Saturday',
                                   `start_time`  TIME          NOT NULL,
                                   `end_time`    TIME          NOT NULL,
                                   `price_override` DECIMAL(12,0) NULL COMMENT 'NULL = inherit from location',
                                   `is_active`   TINYINT(1)    NOT NULL DEFAULT 1,
                                   PRIMARY KEY (`id`),
                                   KEY `idx_slots_location` (`location_id`),
                                   CONSTRAINT `fk_slots_location` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: reservations
-- ============================================================
CREATE TABLE `reservations` (
                                `id`              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
                                `user_id`         INT UNSIGNED  NOT NULL,
                                `location_id`     INT UNSIGNED  NOT NULL,
                                `slot_id`         INT UNSIGNED  NULL,
                                `reservation_date` DATE         NOT NULL,
                                `start_time`      TIME          NOT NULL,
                                `end_time`        TIME          NOT NULL,
                                `people_count`    TINYINT UNSIGNED NOT NULL DEFAULT 1,
                                `notes`           TEXT          NULL,
                                `status`          ENUM('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
                                `total_price`     DECIMAL(12,0) NOT NULL DEFAULT 0,
                                `cancelled_at`    DATETIME      NULL,
                                `cancel_reason`   VARCHAR(500)  NULL,
                                `approved_by`     INT UNSIGNED  NULL,
                                `approved_at`     DATETIME      NULL,
                                `created_at`      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                `updated_at`      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                                PRIMARY KEY (`id`),
                                UNIQUE KEY `uq_reservation_slot` (`location_id`, `reservation_date`, `start_time`, `status`),
                                KEY `idx_reservations_user`     (`user_id`),
                                KEY `idx_reservations_location` (`location_id`),
                                KEY `idx_reservations_date`     (`reservation_date`),
                                KEY `idx_reservations_status`   (`status`),
                                CONSTRAINT `fk_reservations_user`     FOREIGN KEY (`user_id`)     REFERENCES `users`     (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                                CONSTRAINT `fk_reservations_location` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                                CONSTRAINT `fk_reservations_slot`     FOREIGN KEY (`slot_id`)     REFERENCES `available_slots` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
                                CONSTRAINT `fk_reservations_approver` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: comments
-- ============================================================
CREATE TABLE `comments` (
                            `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
                            `user_id`     INT UNSIGNED  NOT NULL,
                            `location_id` INT UNSIGNED  NOT NULL,
                            `parent_id`   INT UNSIGNED  NULL COMMENT 'For future reply support',
                            `body`        TEXT          NOT NULL,
                            `rating`      TINYINT UNSIGNED NULL CHECK (`rating` BETWEEN 1 AND 5),
                            `status`      ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
                            `created_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                            `updated_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                            PRIMARY KEY (`id`),
                            KEY `idx_comments_user`     (`user_id`),
                            KEY `idx_comments_location` (`location_id`),
                            KEY `idx_comments_status`   (`status`),
                            CONSTRAINT `fk_comments_user`     FOREIGN KEY (`user_id`)     REFERENCES `users`     (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                            CONSTRAINT `fk_comments_location` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                            CONSTRAINT `fk_comments_parent`   FOREIGN KEY (`parent_id`)   REFERENCES `comments`  (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: notifications
-- ============================================================
CREATE TABLE `notifications` (
                                 `id`         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
                                 `user_id`    INT UNSIGNED  NOT NULL,
                                 `type`       VARCHAR(80)   NOT NULL,
                                 `title`      VARCHAR(200)  NOT NULL,
                                 `message`    TEXT          NOT NULL,
                                 `is_read`    TINYINT(1)    NOT NULL DEFAULT 0,
                                 `link`       VARCHAR(500)  NULL,
                                 `created_at` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                 PRIMARY KEY (`id`),
                                 KEY `idx_notifications_user`   (`user_id`),
                                 KEY `idx_notifications_unread` (`user_id`, `is_read`),
                                 CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: contact_messages
-- ============================================================
CREATE TABLE `contact_messages` (
                                    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
                                    `name`       VARCHAR(150) NOT NULL,
                                    `email`      VARCHAR(200) NOT NULL,
                                    `phone`      VARCHAR(20)  NULL,
                                    `subject`    VARCHAR(300) NOT NULL,
                                    `message`    TEXT         NOT NULL,
                                    `ip_address` VARCHAR(45)  NULL,
                                    `is_read`    TINYINT(1)   NOT NULL DEFAULT 0,
                                    `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                    PRIMARY KEY (`id`),
                                    KEY `idx_contact_read` (`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: activity_logs
-- ============================================================
CREATE TABLE `activity_logs` (
                                 `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                                 `user_id`     INT UNSIGNED    NULL,
                                 `action`      VARCHAR(100)    NOT NULL,
                                 `description` VARCHAR(500)    NOT NULL,
                                 `entity_type` VARCHAR(80)     NULL,
                                 `entity_id`   INT UNSIGNED    NULL,
                                 `ip_address`  VARCHAR(45)     NULL,
                                 `user_agent`  VARCHAR(500)    NULL,
                                 `extra_data`  JSON            NULL,
                                 `created_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                 PRIMARY KEY (`id`),
                                 KEY `idx_logs_user`      (`user_id`),
                                 KEY `idx_logs_action`    (`action`),
                                 KEY `idx_logs_entity`    (`entity_type`, `entity_id`),
                                 KEY `idx_logs_created`   (`created_at`),
                                 CONSTRAINT `fk_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: login_attempts  (brute force protection)
-- ============================================================
CREATE TABLE `login_attempts` (
                                  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
                                  `identifier` VARCHAR(255) NOT NULL COMMENT 'username/email/phone tried',
                                  `ip_address` VARCHAR(45)  NOT NULL,
                                  `attempted_at` TIMESTAMP  NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                  PRIMARY KEY (`id`),
                                  KEY `idx_attempts_ip`         (`ip_address`),
                                  KEY `idx_attempts_identifier` (`identifier`),
                                  KEY `idx_attempts_time`       (`attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: csrf_tokens
-- ============================================================
CREATE TABLE `csrf_tokens` (
                               `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
                               `token`      VARCHAR(100) NOT NULL,
                               `session_id` VARCHAR(100) NOT NULL,
                               `expires_at` DATETIME     NOT NULL,
                               `used`       TINYINT(1)   NOT NULL DEFAULT 0,
                               `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                               PRIMARY KEY (`id`),
                               UNIQUE KEY `uq_csrf_token` (`token`),
                               KEY `idx_csrf_session` (`session_id`),
                               KEY `idx_csrf_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;