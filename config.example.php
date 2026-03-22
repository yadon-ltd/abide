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


// ── Remember Me ───────────────────────────────────────────────
// Persistent login tokens — opt-in, off by default.
// Requires AUTH_ENABLED = true and the user_tokens schema imported
// (see modules/auth/user_tokens.sql).
// When enabled, users can toggle the preference in their profile.
// Tokens last 30 days and are rotated on each use.
define('REMEMBER_ME_ENABLED', false);


// ── Navigation ────────────────────────────────────────────────
// 'hamburger' is the only option in v1. Placeholder for future
// nav styles (e.g. 'topbar', 'sidebar').
define('NAV_STYLE', 'hamburger');


// ── Header Branding ──────────────────────────────────────────
// Controls what appears in the centre column of the site header.
//
// LOGO_MODE options:
//   'page_title' — shows the current page name as text (default)
//   'logo'       — shows only the logo image
//   'wordmark'   — shows only SITE_NAME as styled text
//   'both'       — shows logo image + SITE_NAME text side by side
//
// LOGO_FILE: path to the logo image, relative to the web root.
// LOGO_ALT:  alt text for the <img> tag. Defaults to SITE_NAME
//            if not set — override here if you need something
//            more descriptive.
//
// No core file edits are required after setting these values.
define('LOGO_MODE', 'page_title');
define('LOGO_FILE', '/assets/img/logo.png');
define('LOGO_ALT',  SITE_NAME);


// ── Logo Edge Fade ────────────────────────────────────────────
// When true, applies a CSS mask to the header logo image so its
// edges fade into the page background. Works with any image format
// (JPG, PNG, etc.) — no transparent asset required.
// Use false if your logo already has a transparent background and
// you want precise edge control.
define('LOGO_FADE_EDGES', false);


// ── Footer ────────────────────────────────────────────────────
// FOOTER_SUPPORT_URL:   URL for the left footer link.
//                       Leave empty ('') to show no link.
// FOOTER_SUPPORT_LABEL: Display text for the link.
//                       Falls back to the URL if left empty.
//
// Example — link to a donate page:
//   define('FOOTER_SUPPORT_URL',   '/donate');
//   define('FOOTER_SUPPORT_LABEL', 'Support');
define('FOOTER_SUPPORT_URL',   '');
define('FOOTER_SUPPORT_LABEL', '');


// ── Visitor Info ─────────────────────────────────────────────
// When true, shows the visitor's IP address and browser name in
// the footer. IP is resolved server-side ($_SERVER['REMOTE_ADDR'])
// — no external requests are made.
// Set to false to show nothing in the right footer slot.
define('VISITOR_INFO_ENABLED', false);
