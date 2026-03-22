-- ══════════════════════════════════════════════════════════════
-- modules/auth/user_tokens.sql — Persistent login token schema
-- ══════════════════════════════════════════════════════════════
-- Import this in phpMyAdmin before enabling REMEMBER_ME_ENABLED
-- in config.php.
--
-- Two operations:
--   1. Adds remember_me preference column to the users table
--   2. Creates the user_tokens table
--
-- Safe to run more than once — uses IF NOT EXISTS / IF NOT EXISTS
-- patterns where MySQL supports it.
-- ══════════════════════════════════════════════════════════════


-- ── 1. Add remember_me to users ───────────────────────────────
-- Stores the per-user opt-in preference for persistent login.
-- 0 = off (default), 1 = on.
-- Wrapped in a procedure so it skips gracefully if the column
-- already exists (MySQL lacks ADD COLUMN IF NOT EXISTS before 8.0).

DROP PROCEDURE IF EXISTS abide_add_remember_me;

DELIMITER $$
CREATE PROCEDURE abide_add_remember_me()
BEGIN
  IF NOT EXISTS (
    SELECT 1
      FROM information_schema.columns
     WHERE table_schema = DATABASE()
       AND table_name   = 'users'
       AND column_name  = 'remember_me'
  ) THEN
    ALTER TABLE users
      ADD COLUMN remember_me TINYINT(1) NOT NULL DEFAULT 0
      AFTER permissions;
  END IF;
END$$
DELIMITER ;

CALL abide_add_remember_me();
DROP PROCEDURE IF EXISTS abide_add_remember_me;


-- ── 2. Create user_tokens ─────────────────────────────────────
-- One row per active persistent login token.
-- token_hash stores SHA-256 of the raw cookie value — the raw
-- token is never persisted server-side.
-- Tokens are rotated (delete + reissue) on every use.

CREATE TABLE IF NOT EXISTS user_tokens (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id      INT UNSIGNED NOT NULL,
  token_hash   CHAR(64)     NOT NULL,           -- SHA-256 hex of raw cookie value
  created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  expires_at   DATETIME     NOT NULL,           -- 30 days from issuance
  last_used_at DATETIME     NULL DEFAULT NULL,  -- updated on each successful use
  PRIMARY KEY  (id),
  UNIQUE KEY   uq_token_hash (token_hash),
  KEY          idx_user_id   (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
