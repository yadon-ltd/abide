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
  <title><?php echo htmlspecialchars(defined('SITE_NAME') ? SITE_NAME : 'Home'); ?></title>

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
  <div class="home-identity">

    <span class="home-name">
      <?php echo htmlspecialchars(defined('SITE_NAME') ? SITE_NAME : 'Abide'); ?>
    </span>

    <?php if (defined('SITE_TAGLINE') && SITE_TAGLINE !== ''): ?>
    <span class="home-tagline">
      <?php echo htmlspecialchars(SITE_TAGLINE); ?>
    </span>
    <?php endif; ?>

  </div><!-- /.home-identity -->


  <?php include ABIDE_CORE . '/footer.php'; ?>


  <script>
    // ─────────────────────────────────────────────────────────
    // HOME PAGE — identity block opens the nav menu
    // ─────────────────────────────────────────────────────────
    // Hovering the logo/name block opens the menu if it is
    // currently closed. Clicking it toggles (open or close).
    // We delegate to the hamburger trigger so all open/close/ARIA
    // state logic stays in nav.php's IIFE — no duplication.
    // ─────────────────────────────────────────────────────────

    (function () {
      var identity = document.querySelector('.home-identity');
      var trigger  = document.getElementById('menu-trigger');
      var navMenu  = document.getElementById('nav-menu');

      if (!identity || !trigger || !navMenu) return;

      // Open on hover — only if the menu is currently closed
      identity.addEventListener('mouseenter', function () {
        if (!navMenu.classList.contains('visible')) {
          trigger.click();
        }
      });

      // Toggle on click — mirror the hamburger directly
      identity.addEventListener('click', function () {
        trigger.click();
      });

    })();
  </script>

</body>
</html>
