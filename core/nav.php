<?php
/*
  core/nav.php — Navigation menu
  ──────────────────────────────────────────────────────────────
  Included by core/header.php on every page.
  On index.php (no header bar) it is included directly.

  Usage:  <?php include ABIDE_CORE . '/nav.php'; ?>
          (You don't need to call this manually — header.php does it.)

  How to add menu items:
    • Simple link:      duplicate the "Simple link" block below
    • Dropdown group:   duplicate the "Dropdown" block below
    • Sub-section:      see the nested .subsection example

  Ordering convention:
    Order items by logical workflow or priority, not alphabetically
    or by build date. Apply this principle when adding to any group.
  ──────────────────────────────────────────────────────────────
*/

// Determine current page so we can show/hide the Home link
$_nav_current = basename($_SERVER['PHP_SELF']);
$_nav_show_home = ($_nav_current !== 'index.php');

// Resolve the current user once for this nav render.
// auth_user() is only available when AUTH_ENABLED = true and the
// auth module is loaded. Guard every reference with function_exists().
$_nav_user = (defined('AUTH_ENABLED') && AUTH_ENABLED && function_exists('auth_user'))
    ? auth_user()
    : null;
?>

<!-- ══════════════════════════════════════════════════════════
     HAMBURGER TRIGGER
     Sits in header grid column 1 on tool pages.
     On index.php it is overridden to position:fixed.
     ══════════════════════════════════════════════════════════ -->
<button
  id="menu-trigger"
  aria-label="Open navigation menu"
  aria-expanded="false"
  aria-controls="nav-menu"
>
  <span></span>
  <span></span>
  <span></span>
</button>


<!-- ══════════════════════════════════════════════════════════
     NAVIGATION MENU PANEL
     ══════════════════════════════════════════════════════════ -->
<nav id="nav-menu" role="navigation" aria-label="Site navigation" aria-hidden="true">

  <?php if ($_nav_show_home): ?>
  <!-- Home link — hidden on index.php -->
  <div class="menu-item">
    <a class="menu-label" href="/" style="text-decoration:none;">
      &#8962; Home
    </a>
  </div>
  <hr class="submenu-divider" style="margin: 0.2rem 0.9rem 0.4rem;" />
  <?php endif; ?>


  <!-- ══════════════════════════════════════════════════════════
       ADD MENU ITEMS BELOW
       ══════════════════════════════════════════════════════════

  ── PATTERN A: Simple direct link ─────────────────────────────
  Use for top-level pages that don't need a dropdown.

  <div class="menu-item" id="item-about">
    <a class="menu-label" href="/about" style="text-decoration:none;">
      About
    </a>
  </div>

  ── PATTERN B: Dropdown group ─────────────────────────────────
  Use for a category with multiple child links.
  The .menu-label div is the clickable trigger (no href).
  Links live inside .submenu.

  <div class="menu-item" id="item-tools">
    <div class="menu-label" role="button" tabindex="0" aria-expanded="false">
      Tools
      <span class="arrow">&#9654;</span>
    </div>
    <div class="submenu" role="menu">
      <a href="/tools/widget">Widget</a>
      <a href="/tools/gadget">Gadget</a>
    </div>
  </div>

  ── PATTERN C: Dropdown with sub-sections ─────────────────────
  Use for large groups that benefit from collapsible sub-headings.
  Each .subsection collapses independently (mutual exclusion).

  <div class="menu-item" id="item-resources">
    <div class="menu-label" role="button" tabindex="0" aria-expanded="false">
      Resources
      <span class="arrow">&#9654;</span>
    </div>
    <div class="submenu" role="menu">
      <div class="subsection" id="subsec-one">
        <div class="subsection-label" role="button" tabindex="0" aria-expanded="false">
          Section One <span class="sub-arrow">&#9654;</span>
        </div>
        <div class="subsection-body">
          <a href="/one/alpha">Alpha</a>
          <a href="/one/beta">Beta</a>
        </div>
      </div>
      <hr class="submenu-divider" />
      <div class="subsection" id="subsec-two">
        <div class="subsection-label" role="button" tabindex="0" aria-expanded="false">
          Section Two <span class="sub-arrow">&#9654;</span>
        </div>
        <div class="subsection-body">
          <a href="/two/gamma">Gamma</a>
        </div>
      </div>
    </div>
  </div>

  ══════════════════════════════════════════════════════════ -->

  <!-- ── Bottom divider + utility links ─────────────────────── -->
  <hr class="submenu-divider" style="margin: 0.4rem 0.9rem;" />

  <?php if ($_nav_user): ?>

    <?php if ($_nav_user['permissions'] & PERM_HEADEND): ?>
    <!-- Admin link — visible to PERM_HEADEND only -->
    <div class="menu-item">
      <a class="menu-label" href="/admin" style="text-decoration:none;">
        Configuration
      </a>
    </div>
    <?php endif; ?>

    <div class="menu-item">
      <a class="menu-label" href="/logout" style="text-decoration:none;">Sign out</a>
    </div>

  <?php else: ?>

    <div class="menu-item">
      <a class="menu-label" href="/login" style="text-decoration:none;">Sign in</a>
    </div>

  <?php endif; ?>

</nav><!-- /#nav-menu -->


<script>
  // ─────────────────────────────────────────────────────────────
  // NAVIGATION — hamburger open/close + accordion submenus
  // Lives in nav.php so it travels with the component.
  // Wrapped in an IIFE to avoid polluting global scope.
  // ─────────────────────────────────────────────────────────────

  (function () {
    var trigger = document.getElementById('menu-trigger');
    var navMenu = document.getElementById('nav-menu');

    if (!trigger || !navMenu) return;

    // ── Open / close the panel ────────────────────────────────
    trigger.addEventListener('click', function (e) {
      e.stopPropagation();
      var isOpen = navMenu.classList.toggle('visible');
      trigger.classList.toggle('open', isOpen);
      trigger.setAttribute('aria-expanded', String(isOpen));
      navMenu.setAttribute('aria-hidden',   String(!isOpen));
    });

    // Close when clicking anywhere outside the panel or trigger
    document.addEventListener('click', function (e) {
      if (!navMenu.contains(e.target) && e.target !== trigger) {
        closeMenu();
      }
    });

    // Close on Escape — return focus to trigger for keyboard users
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') {
        closeMenu();
        trigger.focus();
      }
    });

    function closeMenu() {
      navMenu.classList.remove('visible');
      trigger.classList.remove('open');
      trigger.setAttribute('aria-expanded', 'false');
      navMenu.setAttribute('aria-hidden',   'true');
    }


    // ── Top-level accordion — mutual exclusion ────────────────
    // Opening one dropdown closes any other that was open.
    document.querySelectorAll('.menu-item > .menu-label').forEach(function (label) {

      label.addEventListener('click', function () {
        var item    = label.parentElement;
        var submenu = item.querySelector('.submenu');
        if (!submenu) return;   // direct link — no accordion needed

        var isOpen = item.classList.contains('open');

        // Collapse all dropdowns
        document.querySelectorAll('.menu-item').forEach(function (mi) {
          mi.classList.remove('open');
          var ml = mi.querySelector(':scope > .menu-label');
          if (ml) ml.setAttribute('aria-expanded', 'false');
          var sm = mi.querySelector('.submenu');
          if (sm) sm.classList.remove('open');
        });

        // If it was closed, open it; if open, leave collapsed (toggle)
        if (!isOpen) {
          item.classList.add('open');
          submenu.classList.add('open');
          label.setAttribute('aria-expanded', 'true');
        }
      });

      // Keyboard: Enter or Space activates the label
      label.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          label.click();
        }
      });
    });


    // ── Sub-section accordions — mutual exclusion ─────────────
    // Each .subsection inside a .submenu collapses independently.
    // Only one sub-section per parent can be open at a time.
    document.querySelectorAll('.subsection-label').forEach(function (label) {

      label.addEventListener('click', function () {
        var subsection = label.parentElement;
        var parentMenu = subsection.closest('.submenu');
        var isOpen     = subsection.classList.contains('open');

        // Collapse all siblings
        parentMenu.querySelectorAll('.subsection').forEach(function (s) {
          s.classList.remove('open');
          s.querySelector('.subsection-label').setAttribute('aria-expanded', 'false');
        });

        // If closed, open it; if open, leave collapsed
        if (!isOpen) {
          subsection.classList.add('open');
          label.setAttribute('aria-expanded', 'true');
        }
      });

      label.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          label.click();
        }
      });
    });

  })();
</script>
