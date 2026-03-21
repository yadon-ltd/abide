<?php
/*
  public/404.php — Custom 404 error page
  ──────────────────────────────────────────────────────────────
  Served automatically by Apache when a requested resource is
  not found, via ErrorDocument 404 /404.php in public/.htaccess.

  core/init.php runs first (via auto_prepend_file), so ABIDE_CORE,
  SITE_NAME, and all other config constants are available here.

  HTTP status: Apache sets 404 before handing off to this file,
  so no header() call is needed. The response code is already
  correct by the time the browser sees it.
  ──────────────────────────────────────────────────────────────
*/

$page_title = '404 — Not Found';
include ABIDE_CORE . '/header.php';
?>

<style>
  /*
    404-specific styles. Kept inline here per Abide convention:
    page-specific rules live with the page, not in style.css.
  */

  .error-page {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 60vh;
    text-align: center;
    gap: 1.25rem;
    animation: fadeUp 0.4s ease both;
  }

  .error-code {
    font-family: var(--mono);
    font-size: clamp(4rem, 14vw, 7rem);
    font-weight: 700;
    letter-spacing: 0.05em;
    color: var(--accent);
    /* Amber glow behind the number */
    text-shadow:
      0 0 40px rgba(255, 179, 64, 0.35),
      0 0 80px rgba(255, 179, 64, 0.12);
    line-height: 1;
  }

  .error-label {
    font-family: var(--mono);
    font-size: 0.72rem;
    letter-spacing: 0.22em;
    text-transform: uppercase;
    color: var(--text-dim);
  }

  .error-message {
    font-size: 0.95rem;
    color: var(--text-dimmer);
    max-width: 36ch;
    line-height: 1.65;
  }

  .error-home {
    display: inline-block;
    margin-top: 0.5rem;
    padding: 0.55rem 1.4rem;
    font-family: var(--mono);
    font-size: 0.68rem;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: var(--accent-dim);
    border: 1px solid var(--border);
    border-radius: 3px;
    text-decoration: none;
    transition: color 0.2s ease, border-color 0.2s ease, background 0.2s ease;
  }

  .error-home:hover {
    color: var(--accent);
    border-color: var(--border-hi);
    background: var(--accent-glow);
  }
</style>

<main>
  <div class="main-content">
    <div class="error-page">

      <div class="error-code">404</div>
      <div class="error-label">Page not found</div>

      <p class="error-message">
        The page you're looking for doesn't exist or has been moved.
      </p>

      <a href="/" class="error-home">← Back to home</a>

    </div>
  </div>
</main>

<?php include ABIDE_CORE . '/footer.php'; ?>
