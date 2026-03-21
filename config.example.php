<?php
/*
  config.example.php — Abide configuration template
  ──────────────────────────────────────────────────────────────
  Copy this file to config.php and fill in your values.
  config.php is listed in .gitignore and is never committed.

  This file IS safe to commit — it contains no credentials.

  Loaded automatically by core/init.php before every request.

  Alternatively, run the setup wizard at /setup/ — it writes
  config.php for you and locks itself on completion.
  ──────────────────────────────────────────────────────────────
*/


// ── Setup ────────────────────────────────────────────────────
// Written by the setup wizard on first run.
// The wizard checks for this constant to determine whether
// setup has already completed. Do not remove or set to false.
define('SETUP_COMPLETE', true);


// ── Site Identity ─────────────────────────────────────────────
// SITE_NAME appears in page titles, the footer, and anywhere
// else that refers to the site by name.
define('SITE_NAME',    'My Site');
define('SITE_URL',     'https://example.com');
define('SITE_TAGLINE', '');   // optional — shown on the home page


// ── Database ──────────────────────────────────────────────────
// Leave DB_NAME empty ('') to skip the database entirely.
// core/init.php will not load core/db.php if DB_NAME is blank.
define('DB_HOST', 'localhost');
define('DB_NAME', '');          // '' = database disabled
define('DB_USER', '');
define('DB_PASS', '');
define('DB_CHAR', 'utf8mb4');


// ── Email / SMTP ─────────────────────────────────────────────
// Only required if using the auth module or sending transactional
// mail. Leave empty if neither applies.
// Port 465 = SSL   |   Port 587 = TLS (STARTTLS)
define('SMTP_HOST', '');
define('SMTP_USER', '');
define('SMTP_PASS', '');
define('SMTP_PORT', 465);
define('SMTP_FROM', '');
define('SMTP_NAME', SITE_NAME);  // display name on outgoing mail


// ── Auth Module ───────────────────────────────────────────────
// Set to true only after modules/auth/ is in place and the
// schema has been imported. Requires DB and SMTP above.
define('AUTH_ENABLED', false);


// ── Navigation ────────────────────────────────────────────────
// 'hamburger' is the only option in v1. Placeholder for future
// nav styles (e.g. 'topbar', 'sidebar').
define('NAV_STYLE', 'hamburger');
