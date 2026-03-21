<?php
/*
  core/init.php — Abide bootstrap
  ──────────────────────────────────────────────────────────────
  Loaded automatically before every PHP request via
  auto_prepend_file in public/.htaccess.

  Responsibilities:
    1. Load config.php from the project root
    2. Define path constants for use throughout the project
    3. Start the session with secure settings
    4. Load core/db.php if a database is configured
    5. Load modules/settings/settings.php if a database is configured
    6. Load modules/auth/auth.php if AUTH_ENABLED = true

  Do not include this file manually — .htaccess handles it.
  ──────────────────────────────────────────────────────────────
*/


// ── Path constants ────────────────────────────────────────────
// Defined early so any file included after init.php can use them
// without worrying about its own directory context.
define('ABIDE_ROOT',   dirname(__DIR__));           // project root (abide/)
define('ABIDE_CORE',   __DIR__);                    // core/
define('ABIDE_PUBLIC', ABIDE_ROOT . '/public');     // public/


// ── Load config ───────────────────────────────────────────────
// config.php lives at the project root, one level above core/.
// It is never committed — config.example.php is the template.
$_config = ABIDE_ROOT . '/config.php';

if (!file_exists($_config)) {
    http_response_code(503);
    exit(
        '<b>Abide setup required:</b> config.php not found.<br>' .
        'Copy config.example.php to config.php and fill in your values.'
    );
}

require_once $_config;
unset($_config);


// ── Session ───────────────────────────────────────────────────
// Start the session once per request with hardened cookie settings.
// Checking session_status() first makes it safe to call init.php
// even on pages that might start a session themselves.
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,              // session cookie (expires on browser close)
        'path'     => '/',
        'secure'   => isset($_SERVER['HTTPS']),   // HTTPS-only in production
        'httponly' => true,           // not accessible via JS
        'samesite' => 'Lax',          // CSRF mitigation
    ]);
    session_start();
}


// ── Database (optional) ───────────────────────────────────────
// Only loaded when DB_NAME is set to a non-empty string in config.php.
// This keeps the scaffold functional for projects that don't need a DB.
if (defined('DB_NAME') && DB_NAME !== '') {
    require_once ABIDE_CORE . '/db.php';
}


// ── Settings module ───────────────────────────────────────────
// Provides settings_get(), settings_set(), settings_get_all(), and
// settings_get_css() for DB-backed site configuration.
// Only loaded when the DB is configured — settings require a connection.
if (defined('DB_NAME') && DB_NAME !== '') {
    require_once ABIDE_ROOT . '/modules/settings/settings.php';
}


// ── Auth module (optional) ────────────────────────────────────
// Only loaded when AUTH_ENABLED is true in config.php.
// The module lives in modules/auth/ to keep it self-contained.
if (defined('AUTH_ENABLED') && AUTH_ENABLED === true) {
    require_once ABIDE_ROOT . '/modules/auth/auth.php';
}
