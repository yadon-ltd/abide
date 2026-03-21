-- ============================================================
-- Abide — Auth Module Database Schema
-- modules/auth/schema.sql
--
-- Run this against your database after creating it.
--
-- phpMyAdmin:  select your database, then import this file.
-- CLI:         mysql -u <user> -p <dbname> < schema.sql
-- ============================================================


-- ── users ────────────────────────────────────────────────────
-- Core auth table. Stores credentials, role slug, permission
-- bitfield, and tokens for email verification and password reset.
--
-- Role and permissions are maintained in sync:
--   role='tap'      permissions=0   (unverified / free)
--   role='node'     permissions=1   (PERM_NODE)
--   role='backbone' permissions=3   (PERM_NODE | PERM_BACKBONE)
--   role='headend'  permissions=7   (PERM_NODE | PERM_BACKBONE | PERM_HEADEND)
--
-- Widen `permissions` to SMALLINT UNSIGNED when future tier
-- bits push beyond 255.
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `users` (

    -- Primary key
    `id`                     INT UNSIGNED     NOT NULL AUTO_INCREMENT,

    -- Identity & credentials
    `email`                  VARCHAR(255)     NOT NULL,
    `password_hash`          VARCHAR(255)     NOT NULL,    -- bcrypt / password_hash() output

    -- Role slug (human-readable) and permissions bitfield (machine-readable).
    -- Keep these in sync on every write.
    `role`                   VARCHAR(20)      NOT NULL DEFAULT 'tap',
    `permissions`            TINYINT UNSIGNED NOT NULL DEFAULT 0,

    -- Email verification
    `email_verified`         TINYINT(1)       NOT NULL DEFAULT 0,
    `email_verify_token`     VARCHAR(64)      DEFAULT NULL,    -- null once verified
    `email_verify_expires`   DATETIME         DEFAULT NULL,    -- 24-hour TTL

    -- Password reset
    `password_reset_token`   VARCHAR(64)      DEFAULT NULL,    -- null when not in use
    `password_reset_expires` DATETIME         DEFAULT NULL,    -- 1-hour TTL

    -- Timestamps
    `last_login`             DATETIME         DEFAULT NULL,
    `created_at`             DATETIME         NOT NULL,
    `updated_at`             DATETIME         NOT NULL,

    -- Constraints
    PRIMARY KEY (`id`),
    UNIQUE KEY  `uq_email`                   (`email`),
    KEY         `idx_email_verify_token`     (`email_verify_token`),
    KEY         `idx_password_reset_token`   (`password_reset_token`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- Seed: owner (headend) account
--
-- Replace the email and password hash before running.
-- The hash below is a placeholder and will not authenticate.
--
-- Generate your own hash with PHP:
--   php -r "echo password_hash('YourPasswordHere', PASSWORD_BCRYPT);"
--
-- permissions = 7  (PERM_NODE | PERM_BACKBONE | PERM_HEADEND)
-- email_verified = 1  (no verification email needed for the owner account)
-- ============================================================
INSERT INTO `users`
    (`email`, `password_hash`, `role`, `permissions`,
     `email_verified`, `created_at`, `updated_at`)
VALUES
    (
        'jayadon@gmail.com',
        '$2b$12$IUdguzIX2Nd/dmpd1mCHyuAGPuEIqWWz4smzbCgmvJTxYkN/aGnFi',
        'headend',
        7,
        1,
        NOW(),
        NOW()
    );
