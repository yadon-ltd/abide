<?php
/*
  core/header.php — Shared site header
  ──────────────────────────────────────────────────────────────
  Include this file on every page that needs the sticky header bar.

  Usage:
    <?php
      $page_title = 'Page Name';
      include ABIDE_CORE . '/header.php';
    ?>

  What it outputs:
    • <header> element (styled by public/style.css)
    • Hamburger trigger + nav panel (via core/nav.php)
    • Centre column: page title OR logo/wordmark (see below)

  The header also opens <html>, <head>, and <body> so you don't
  have to repeat that boilerplate on every page. footer.php closes
  </body> and </html>.

  ── Centre-column branding ────────────────────────────────────
  Controlled by LOGO_MODE in config.php. Options:

    'page_title'  Shows the current page name as text (default)
    'logo'        Shows only the logo image
    'wordmark'    Shows only SITE_NAME as styled text
    'both'        Shows logo image + SITE_NAME text side by side

  Set LOGO_FILE and LOGO_ALT in config.php to configure the image.
  No edits to this file are required — change config.php only.

  ── Right-column slot (header grid col 3) ─────────────────────
  Empty by default. To add something (e.g. a search form,
  a user avatar, a CTA button):
    1. Add your element here, after the centre-column block
    2. In style.css, change `grid-template-columns: 3rem 1fr 3rem`
       to `3rem 1fr auto` so the column expands to fit its content
  ──────────────────────────────────────────────────────────────
*/

// $page_title should be set by the calling page before this include.
// Falls back to SITE_NAME if not set.
$_header_title = isset($page_title) ? $page_title : (defined('SITE_NAME') ? SITE_NAME : '');

// Read the logo mode from config. Defaults to 'page_title' if not set,
// so the header behaves identically to v1 for anyone who hasn't added
// the branding constants to their config.php yet.
$_logo_mode = defined('LOGO_MODE') ? LOGO_MODE : 'page_title';
$_logo_file = defined('LOGO_FILE') ? LOGO_FILE : '';
$_logo_alt  = defined('LOGO_ALT')  ? LOGO_ALT  : (defined('SITE_NAME') ? SITE_NAME : '');
$_site_name = defined('SITE_NAME') ? SITE_NAME : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php echo htmlspecialchars($_header_title); ?><?php echo $_header_title ? ' — ' . htmlspecialchars(SITE_NAME) : htmlspecialchars(SITE_NAME); ?></title>

  <!-- 1. Default design tokens -->
  <link rel="stylesheet" href="/style.css" />

  <!-- 2. DB-driven token overrides (admin config page → settings table) -->
  <!--    Outputs an empty :root{} if nothing has been customised yet.   -->
  <link rel="stylesheet" href="/assets/css.php" />

  <!-- 3. Auth module styles -->
  <link rel="stylesheet" href="/assets/auth.css" />

  <?php
  /*
    Per-page styles: each page defines its own inline <style> block
    *after* including this header (not before, and not in this file).
    This keeps page-specific rules scoped to the page that needs them.
  */
  ?>
</head>
<body>

<header>

  <?php include ABIDE_CORE . '/nav.php'; ?>

  <?php
  /*
    Centre column — header grid column 2.

    Renders one of four modes based on LOGO_MODE in config.php:
      page_title  → plain text (the current page name)
      logo        → <img> linking home
      wordmark    → SITE_NAME text linking home
      both        → <img> + SITE_NAME text, side by side, linking home

    The brand-link, header-logo, and brand-wordmark CSS classes
    are defined in public/style.css.
  */
  ?>

  <?php if ($_logo_mode === 'logo') : ?>

    <!-- Header brand — logo only -->
    <div class="header-brand logo-only">
      <a href="/" class="brand-link" aria-label="<?php echo htmlspecialchars($_site_name); ?> — home">
        <img
          src="<?php echo htmlspecialchars($_logo_file); ?>"
          alt="<?php echo htmlspecialchars($_logo_alt); ?>"
          class="header-logo"
        />
      </a>
    </div>

  <?php elseif ($_logo_mode === 'wordmark') : ?>

    <!-- Header brand — wordmark only -->
    <div class="header-brand wordmark-only">
      <a href="/" class="brand-link">
        <span class="brand-wordmark"><?php echo htmlspecialchars($_site_name); ?></span>
      </a>
    </div>

  <?php elseif ($_logo_mode === 'both') : ?>

    <!-- Header brand — logo + wordmark -->
    <div class="header-brand brand-both">
      <a href="/" class="brand-link" aria-label="<?php echo htmlspecialchars($_site_name); ?> — home">
        <img
          src="<?php echo htmlspecialchars($_logo_file); ?>"
          alt="<?php echo htmlspecialchars($_logo_alt); ?>"
          class="header-logo"
        />
        <span class="brand-wordmark"><?php echo htmlspecialchars($_site_name); ?></span>
      </a>
    </div>

  <?php else : ?>

    <!-- Page title — default (LOGO_MODE = 'page_title' or not set) -->
    <div class="page-title"><?php echo htmlspecialchars($_header_title); ?></div>

  <?php endif; ?>

  <!--
    RIGHT-COLUMN SLOT — header grid column 3
    Add your element here. See file header comment for instructions.
  -->

</header>
