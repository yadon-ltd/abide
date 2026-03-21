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
    • Page title in the centre column

  The header also opens <html>, <head>, and <body> so you don't
  have to repeat that boilerplate on every page. footer.php closes
  </body> and </html>.

  Right-column slot (header grid col 3):
    Empty by default. To add something (e.g. a search form,
    a user avatar, a CTA button):
      1. Add your element here, after the .page-title div
      2. In style.css, change `grid-template-columns: 3rem 1fr 3rem`
         to `3rem 1fr auto` so the column expands to fit its content
  ──────────────────────────────────────────────────────────────
*/

// $page_title should be set by the calling page before this include.
// Falls back to SITE_NAME if not set.
$_header_title = isset($page_title) ? $page_title : (defined('SITE_NAME') ? SITE_NAME : '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php echo htmlspecialchars($_header_title); ?><?php echo $_header_title ? ' — ' . htmlspecialchars(SITE_NAME) : htmlspecialchars(SITE_NAME); ?></title>

  <link rel="stylesheet" href="/style.css" />

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

  <!-- Page title — sits in header grid column 2 (centre) -->
  <div class="page-title"><?php echo htmlspecialchars($_header_title); ?></div>

  <!--
    RIGHT-COLUMN SLOT — header grid column 3
    Add your element here. See file header comment for instructions.
  -->

</header>
