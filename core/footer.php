<?php
/*
  core/footer.php — Shared site footer
  ──────────────────────────────────────────────────────────────
  Include this file at the bottom of every page.

  Usage:  <?php include ABIDE_CORE . '/footer.php'; ?>

  What it outputs:
    • <footer> bar (fixed bottom, full width)
    • Left:   site name → home link
    • Centre: copyright year (JS-populated) + site name
    • Right:  visitor IP + browser (JS-populated)
    • Closing </body> and </html> tags

  The copyright year and visitor info are populated client-side
  so no server-side date logic is needed here.

  The site name is resolved DB-first via abide_site_name(), which
  checks the settings table before falling back to the SITE_NAME
  constant in config.php.
  ──────────────────────────────────────────────────────────────
*/

// Resolve site name — DB-first, constant fallback, then 'Home'
$_footer_name = function_exists('abide_site_name')
    ? abide_site_name()
    : (defined('SITE_NAME') ? SITE_NAME : 'Home');
?>

<footer>

  <!-- Left: support link -->
  <div class="footer-home">
    <a href="/donate">Support</a>
  </div>

  <!-- Centre: copyright + site name -->
  <div class="footer-copy">
    <span>&copy; <span id="copy-year"></span> <?php echo htmlspecialchars($_footer_name); ?></span>
  </div>

  <!-- Right: visitor IP and browser -->
  <div class="visitor-info">
    <div class="info-item">
      <span class="label">ip</span>
      <span class="value" id="visitor-ip">…</span>
    </div>
    <div class="info-item">
      <span class="label">via</span>
      <span class="value" id="visitor-browser">…</span>
    </div>
  </div>

</footer>

<script>
  /* ── Footer: year, browser, IP ──────────────────────────────────
     Centralised here so every page that includes footer.php gets
     this behaviour automatically — no per-page copy required.
  ─────────────────────────────────────────────────────────────── */

  // Detect browser name and major version from the User-Agent string.
  function detectBrowser() {
    var ua = navigator.userAgent;
    var v  = function (name, rx) {
      var m = ua.match(rx);
      return m ? name + ' ' + m[1].split('.')[0] : name;
    };
    if (/Edg\//.test(ua))     return v('Edge',    /Edg\/([\d.]+)/);
    if (/OPR\//.test(ua))     return v('Opera',   /OPR\/([\d.]+)/);
    if (/Vivaldi/.test(ua))   return v('Vivaldi', /Vivaldi\/([\d.]+)/);
    if (/Brave/.test(ua))     return 'Brave';
    if (/Firefox\//.test(ua)) return v('Firefox', /Firefox\/([\d.]+)/);
    if (/Chrome\//.test(ua))  return v('Chrome',  /Chrome\/([\d.]+)/);
    if (/Safari\//.test(ua))  return v('Safari',  /Version\/([\d.]+)/);
    return 'Unknown';
  }

  // Detect the visitor's public IP address.
  // Prefers IPv4 (api.ipify.org); falls back to the dual-stack endpoint.
  async function detectIP() {
    try {
      return (await (await fetch('https://api.ipify.org?format=json')).json()).ip;
    } catch (e) {
      try {
        return (await (await fetch('https://api64.ipify.org?format=json')).json()).ip;
      } catch (e2) {
        return 'unavailable';
      }
    }
  }

  // Populate footer fields once the DOM is ready.
  (async function () {
    document.getElementById('copy-year').textContent       = new Date().getFullYear();
    document.getElementById('visitor-browser').textContent = detectBrowser();

    var ip   = await detectIP();
    var ipEl = document.getElementById('visitor-ip');
    if (ipEl) {
      ipEl.textContent = ip;
      // IPv6 addresses are long — shrink the font via inline style
      if (ip.includes(':')) ipEl.style.fontSize = '0.48rem';
    }
  })();
</script>

</body>
</html>
