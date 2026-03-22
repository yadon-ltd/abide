<?php
/*
  core/footer.php — Shared site footer
  ──────────────────────────────────────────────────────────────
  Include this file at the bottom of every page.

  Usage:  <?php include ABIDE_CORE . '/footer.php'; ?>

  What it outputs:
    • <footer> bar (fixed bottom, full width, always visible)
    • Left:   optional support link (FOOTER_SUPPORT_URL in config.php)
    • Centre: copyright year + site name (absolutely centred on page)
    • Right:  visitor IP + browser — only when VISITOR_INFO_ENABLED
              = true in config.php. IP is resolved server-side via
              $_SERVER['REMOTE_ADDR']; no external requests are made.
    • Closing </body> and </html> tags

  The site name is resolved DB-first via abide_site_name(), which
  checks the settings table before falling back to the SITE_NAME
  constant in config.php.
  ──────────────────────────────────────────────────────────────
*/

// Resolve site name — DB-first, constant fallback, then 'Home'
$_footer_name = function_exists('abide_site_name')
    ? abide_site_name()
    : (defined('SITE_NAME') ? SITE_NAME : 'Home');

// Resolve support link values from config constants
$_footer_support_url   = defined('FOOTER_SUPPORT_URL')   ? FOOTER_SUPPORT_URL   : '';
$_footer_support_label = defined('FOOTER_SUPPORT_LABEL') ? FOOTER_SUPPORT_LABEL : '';

// Use URL as fallback label if label is empty
if ($_footer_support_url !== '' && $_footer_support_label === '') {
    $_footer_support_label = $_footer_support_url;
}

// Visitor info — only collected when opt-in is enabled
$_show_visitor_info = defined('VISITOR_INFO_ENABLED') && VISITOR_INFO_ENABLED;
$_visitor_ip        = $_show_visitor_info ? ($_SERVER['REMOTE_ADDR'] ?? '') : '';
?>

<footer>

  <!-- Left: optional support link (config-driven) -->
  <div class="footer-left">
    <?php if ($_footer_support_url !== ''): ?>
      <a href="<?php echo htmlspecialchars($_footer_support_url); ?>">
        <?php echo htmlspecialchars($_footer_support_label); ?>
      </a>
    <?php endif; ?>
  </div>

  <!-- Centre: copyright + site name (absolutely centred via CSS) -->
  <div class="footer-copy">
    <span>&copy; <span id="copy-year"></span> <?php echo htmlspecialchars($_footer_name); ?></span>
  </div>

  <!-- Right: visitor IP and browser — opt-in via VISITOR_INFO_ENABLED -->
  <?php if ($_show_visitor_info): ?>
  <div class="visitor-info">
    <div class="info-item">
      <span class="label">ip</span>
      <span class="value"><?php echo htmlspecialchars($_visitor_ip); ?></span>
    </div>
    <div class="info-item">
      <span class="label">via</span>
      <span class="value" id="visitor-browser">…</span>
    </div>
  </div>
  <?php else: ?>
  <!-- Empty right slot — keeps footer-left from pulling left when space-between is in effect -->
  <div class="footer-right"></div>
  <?php endif; ?>

</footer>

<script>
  /* ── Footer: year + optional browser detection ───────────────
     Browser detection is only wired up when the visitor-info
     block is present in the DOM (VISITOR_INFO_ENABLED = true).
     The year stamp runs on every page regardless.
  ─────────────────────────────────────────────────────────────── */

  // Set current year in the copyright line
  document.getElementById('copy-year').textContent = new Date().getFullYear();

  <?php if ($_show_visitor_info): ?>
  // Detect browser name and major version from the User-Agent string.
  // No external requests — reads navigator.userAgent only.
  (function () {
    var ua = navigator.userAgent;
    var v  = function (name, rx) {
      var m = ua.match(rx);
      return m ? name + ' ' + m[1].split('.')[0] : name;
    };
    var browser;
    if      (/Edg\//.test(ua))     browser = v('Edge',    /Edg\/([\d.]+)/);
    else if (/OPR\//.test(ua))     browser = v('Opera',   /OPR\/([\d.]+)/);
    else if (/Vivaldi/.test(ua))   browser = v('Vivaldi', /Vivaldi\/([\d.]+)/);
    else if (/Brave/.test(ua))     browser = 'Brave';
    else if (/Firefox\//.test(ua)) browser = v('Firefox', /Firefox\/([\d.]+)/);
    else if (/Chrome\//.test(ua))  browser = v('Chrome',  /Chrome\/([\d.]+)/);
    else if (/Safari\//.test(ua))  browser = v('Safari',  /Version\/([\d.]+)/);
    else                           browser = 'Unknown';

    var el = document.getElementById('visitor-browser');
    if (el) el.textContent = browser;
  })();
  <?php endif; ?>
</script>

</body>
</html>
