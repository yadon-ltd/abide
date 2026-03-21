<?php
/*
  public/index.php — Home page
  ──────────────────────────────────────────────────────────────
  The landing page. Intentionally minimal — logo, nav, footer.
  Does not use core/header.php (no header bar on the home page).
  Includes core/nav.php and core/footer.php directly.

  To customise:
    • Change the logo area to anything you want — image, text, SVG
    • Add a tagline below the logo using SITE_TAGLINE from config.php
    • Add page-specific sections between the logo and footer
  ──────────────────────────────────────────────────────────────
*/
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php echo htmlspecialchars(function_exists('abide_site_name') ? abide_site_name() : (defined('SITE_NAME') ? SITE_NAME : 'Home')); ?></title>

  <link rel="stylesheet" href="/style.css" />

  <style>
    /* ── Home page layout overrides ────────────────────────────
       The home page is a centred flex column, not the standard
       header/main/footer layout used by tool pages. The body
       here fills the viewport and centres its content.
    ─────────────────────────────────────────────────────────── */

    html, body {
      /* Override the default overflow:hidden to allow the body to
         fill the viewport naturally on this minimal page */
      height: 100%;
      overflow: hidden;
    }

    body {
      /* Dark — slightly different from the tool-page bg for depth */
      background-color: #000;
      color: var(--text);
      font-family: var(--mono);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      /* Subtle SVG noise texture for depth — no external image needed */
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='300' height='300'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.75' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='300' height='300' filter='url(%23n)' opacity='0.03'/%3E%3C/svg%3E");
    }

    /* ── Hamburger trigger — fixed on landing page ────────────
       On tool pages, the trigger lives inside the header bar.
       Here there is no header bar, so it is fixed to the corner.
    ─────────────────────────────────────────────────────────── */
    #menu-trigger {
      position: fixed;
      top: 1.25rem;
      left: 1.25rem;
      z-index: 1000;
      animation: fadeUp 1.2s ease 0.4s both;
    }

    /* Nav panel — offset to match fixed trigger height */
    #nav-menu {
      top: 4rem;
      max-height: calc(100vh - 5rem);
    }

    /* ── Logo / identity block ────────────────────────────────
       Replace with your own markup — image, SVG, or plain text.
    ─────────────────────────────────────────────────────────── */
    .home-identity {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 1rem;
      animation: fadeUp 1.2s ease both;
      cursor: pointer;   /* hint that the logo is interactive */
    }

    /* Site name as text — shown when no logo image is present */
    .home-name {
      font-family: var(--mono);
      font-size: clamp(1.5rem, 5vw, 3rem);
      letter-spacing: 0.18em;
      text-transform: uppercase;
      color: var(--text);
      text-decoration: none;
    }

    /* Tagline — shown only when SITE_TAGLINE is non-empty */
    .home-tagline {
      font-family: var(--mono);
      font-size: 0.72rem;
      letter-spacing: 0.2em;
      text-transform: uppercase;
      color: var(--text-dimmer);
      text-align: center;
      max-width: clamp(280px, 70vw, 560px);
      line-height: 1.7;
      word-break: break-word;
    }

    /* Support link — quiet, below tagline */
    .home-support {
      font-family: var(--mono);
      font-size: 0.58rem;
      letter-spacing: 0.18em;
      text-transform: uppercase;
      margin-top: 0.25rem;
    }

    .home-support a {
      color: rgba(255, 255, 255, 0.2);
      text-decoration: none;
      transition: color 0.2s ease;
    }

    .home-support a:hover {
      color: rgba(255, 255, 255, 0.5);
    }

    /* If you use a logo image, swap .home-name for:
       <img src="/assets/img/logo.png" alt="<?php echo SITE_NAME; ?>" class="home-logo-img" /> */
    .home-logo-img {
      display: block;
      width: clamp(200px, 45vw, 420px);
      height: auto;
    }

    /* ── Footer overrides ─────────────────────────────────────
       Footer uses the same markup as tool pages, but with a
       pure-black bg to match the landing page.
    ─────────────────────────────────────────────────────────── */
    footer {
      background: rgba(0, 0, 0, 0.6);
      animation: fadeUp 1.2s ease 0.6s both;
    }

    .footer-home a,
    .footer-copy,
    .info-item {
      color: rgba(255, 255, 255, 0.50);
    }

    .info-item .label { color: rgba(255, 255, 255, 0.38); }
    .info-item .value { color: rgba(255, 255, 255, 0.60); }

  </style>
</head>
<body>

  <?php include ABIDE_CORE . '/nav.php'; ?>

  <!-- ── Identity block ────────────────────────────────────────
       Hovering or clicking opens the nav menu — see the JS below.
       Replace the .home-name span with an <img> tag if you have
       a logo image in public/assets/img/.
  ─────────────────────────────────────────────────────────────── -->
  <?php
  /*
    Index identity block — four-mode, DB-first via abide_index_* helpers.
    Falls back gracefully if the settings module is not loaded.

    Modes:
      both       — logo image + site name (default)
      logo       — image only
      wordmark   — site name as styled text
      page_title — site name as plain text (original behavior)
  */
  $_idx_mode     = function_exists('abide_index_logo_mode') ? abide_index_logo_mode() : 'both';
  $_idx_file     = function_exists('abide_index_logo_file') ? abide_index_logo_file() : '/assets/img/logo.png';
  $_idx_alt      = function_exists('abide_index_logo_alt')  ? abide_index_logo_alt()  : (defined('SITE_NAME') ? SITE_NAME : 'Abide');
  $_idx_name     = function_exists('abide_site_name')       ? abide_site_name()       : (defined('SITE_NAME') ? SITE_NAME : 'Abide');
  $_idx_tagline  = function_exists('abide_tagline')         ? abide_tagline()         : (defined('SITE_TAGLINE') ? SITE_TAGLINE : '');
  ?>

  <div class="home-identity">

    <?php if ($_idx_mode === 'logo') : ?>

      <!-- Logo image only -->
      <img
        src="<?php echo htmlspecialchars($_idx_file); ?>"
        alt="<?php echo htmlspecialchars($_idx_alt); ?>"
        class="home-logo-img"
      />

    <?php elseif ($_idx_mode === 'wordmark') : ?>

      <!-- Wordmark — site name as styled text -->
      <span class="home-name">
        <?php echo htmlspecialchars($_idx_name); ?>
      </span>

    <?php elseif ($_idx_mode === 'both') : ?>

      <!-- Logo + site name -->
      <img
        src="<?php echo htmlspecialchars($_idx_file); ?>"
        alt="<?php echo htmlspecialchars($_idx_alt); ?>"
        class="home-logo-img"
      />
      <span class="home-name">
        <?php echo htmlspecialchars($_idx_name); ?>
      </span>

    <?php else : ?>

      <!-- page_title — plain site name text (default fallback) -->
      <span class="home-name">
        <?php echo htmlspecialchars($_idx_name); ?>
      </span>

    <?php endif; ?>

    <?php if ($_idx_tagline !== ''): ?>
    <span class="home-tagline">
      <?php echo htmlspecialchars($_idx_tagline); ?>
    </span>
    <?php endif; ?>

    <div class="home-support">
      <a href="/donate">support this project</a>
    </div>

  </div><!-- /.home-identity -->


  <?php include ABIDE_CORE . '/footer.php'; ?>


  <script>
    // ─────────────────────────────────────────────────────────
    // HOME PAGE — nav menu hover behavior (index.php only)
    // ─────────────────────────────────────────────────────────
    // Three zones: identity block, hamburger trigger, nav panel.
    // Entering any zone opens the menu (if closed).
    // Leaving all three zones closes it.
    // Clicking identity or trigger toggles as before.
    //
    // State management (ARIA, classList) stays in nav.php's IIFE.
    // We delegate by calling trigger.click() — open when closed,
    // close when open. No state duplication.
    //
    // relatedTarget tells us where the mouse is going on leave.
    // If it's going into one of our three zones, do nothing.
    // If it's going somewhere else (or null = left window), close.
    // ─────────────────────────────────────────────────────────

    (function () {
      var identity = document.querySelector('.home-identity');
      var trigger  = document.getElementById('menu-trigger');
      var navMenu  = document.getElementById('nav-menu');

      if (!identity || !trigger || !navMenu) return;

      // Helper: is a node inside one of our two hover zones?
      // The trigger is intentionally excluded — it stays click-only.
      function inZone(node) {
        if (!node) return false;
        return identity.contains(node) || node === identity
            || navMenu.contains(node)  || node === navMenu;
      }

      // Open when entering a hover zone — only if currently closed
      function openIfClosed() {
        if (!navMenu.classList.contains('visible')) {
          trigger.click();
        }
      }

      // Close when leaving a zone — only if destination is outside both zones
      function closeIfLeft(e) {
        if (inZone(e.relatedTarget)) return;
        if (navMenu.classList.contains('visible')) {
          trigger.click(); // toggle closed
        }
      }

      // ── Mouseenter: open (identity + nav panel only) ──────
      identity.addEventListener('mouseenter', openIfClosed);
      navMenu.addEventListener('mouseenter',  openIfClosed);

      // ── Mouseleave: close if exiting both zones ───────────
      identity.addEventListener('mouseleave', closeIfLeft);
      navMenu.addEventListener('mouseleave',  closeIfLeft);

      // ── Click: toggle (existing behavior) ─────────────────
      identity.addEventListener('click', function () {
        trigger.click();
      });

    })();
  </script>

</body>
</html>
