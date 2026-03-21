<?php
/*
  public/pages/example.php — Annotated example page
  ──────────────────────────────────────────────────────────────
  This file is the canonical reference for how to build a new
  page in Abide. Read the comments, then copy this file to
  public/pages/your-page-name.php and replace the content.

  URL: yourdomain.com/example  (routed by public/.htaccess)

  ABIDE PAGE CHECKLIST
  ─────────────────────
  1. Set $page_title before including header.php
  2. Include core/header.php  (opens <html>, <head>, <body>, <header>)
  3. Open <main> with an inner .main-content wrapper (optional)
  4. Write your page content — styles go in an inline <style> block
  5. Close </main>
  6. Include core/footer.php  (outputs footer, closes </body>, </html>)

  NAMING CONVENTION
  ──────────────────
  File name = URL slug. Keep it lowercase, hyphenated, no spaces.
  pages/my-tool.php → yourdomain.com/my-tool

  DATABASE ACCESS
  ────────────────
  If DB_NAME is set in config.php, db_connect() is available on
  any page without an explicit require_once:
    $pdo  = db_connect();
    $stmt = $pdo->prepare('SELECT * FROM items WHERE id = ?');
    $stmt->execute([$id]);
    $row  = $stmt->fetch();

  AUTH ACCESS
  ────────────
  If AUTH_ENABLED is true in config.php, auth_user() is available:
    $user = auth_user();   // returns user array or null if not logged in
    if (!$user) {
        header('Location: /login');
        exit;
    }
  ──────────────────────────────────────────────────────────────
*/


// ── 1. Set the page title ─────────────────────────────────────
// This appears in the browser tab, header bar, and <title> tag.
$page_title = 'Example Page';


// ── 2. Include the header ─────────────────────────────────────
// Outputs: <!DOCTYPE html> ... <header> ... </header>
// ABIDE_CORE is defined in core/init.php (loaded by .htaccess).
include ABIDE_CORE . '/header.php';
?>


<!-- ── 3. Page-specific styles ──────────────────────────────────
     Inline <style> after the header so it loads with the page.
     Only styles specific to this page go here.
     Shared tokens (colours, fonts) come from public/style.css.
─────────────────────────────────────────────────────────────── -->
<style>
  /* ── Example page layout ─────────────────────────────────── */

  /* A simple card-style content block */
  .example-card {
    background: var(--bg2);
    border: 1px solid var(--border);
    border-radius: 6px;
    padding: 2rem;
    margin-bottom: 1.5rem;
  }

  .example-card h2 {
    font-family: var(--mono);
    font-size: 0.75rem;
    letter-spacing: 0.18em;
    text-transform: uppercase;
    color: var(--accent-dim);
    margin-bottom: 1rem;
  }

  .example-card p {
    font-size: 0.9rem;
    color: var(--text-dim);
    line-height: 1.7;
    max-width: 60ch;
  }

  /* A small data grid example */
  .example-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
  }

  .example-stat {
    background: var(--bg3);
    border: 1px solid var(--border);
    border-radius: 4px;
    padding: 1rem 1.25rem;
  }

  .example-stat .stat-label {
    font-family: var(--mono);
    font-size: 0.58rem;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: var(--text-dimmer);
    display: block;
    margin-bottom: 0.4rem;
  }

  .example-stat .stat-value {
    font-family: var(--mono);
    font-size: 1.4rem;
    color: var(--accent);
  }

  /* Status colour examples */
  .ok   { color: var(--ok);   }
  .warn { color: var(--warn); }
  .bad  { color: var(--bad);  }
</style>


<!-- ── 4. Open <main> ─────────────────────────────────────────── -->
<main>

  <!-- .main-content constrains the width — remove it for full-width layouts -->
  <div class="main-content">


    <!-- ── SECTION: What is this file? ───────────────────────── -->
    <div class="example-card">
      <h2>What is this file?</h2>
      <p>
        This is the annotated example page for Abide. Read the source
        comments, then copy this file to <code>public/pages/your-page.php</code>
        and replace the content. The URL for this page is
        <code>/example</code> — routed automatically by <code>public/.htaccess</code>.
      </p>
    </div>


    <!-- ── SECTION: Design tokens ────────────────────────────── -->
    <div class="example-card">
      <h2>Design Tokens</h2>
      <p>All colours and typography are CSS custom properties defined in
         <code>public/style.css</code>. Use them anywhere in your page styles.</p>

      <!-- Stat grid — demonstrates the token colour ramp -->
      <div class="example-grid">
        <div class="example-stat">
          <span class="stat-label">--accent</span>
          <span class="stat-value">#00d2ff</span>
        </div>
        <div class="example-stat">
          <span class="stat-label">--bg / --bg2 / --bg3</span>
          <span class="stat-value" style="font-size:0.85rem; color:var(--text-dim);">Layers</span>
        </div>
        <div class="example-stat">
          <span class="stat-label">Status</span>
          <span class="stat-value" style="font-size:1rem;">
            <span class="ok">ok</span> &nbsp;
            <span class="warn">warn</span> &nbsp;
            <span class="bad">bad</span>
          </span>
        </div>
      </div>
    </div>


    <!-- ── SECTION: Database example ─────────────────────────── -->
    <div class="example-card">
      <h2>Database (optional)</h2>
      <p>
        If <code>DB_NAME</code> is set in <code>config.php</code>,
        <code>db_connect()</code> is available on every page
        without an explicit <code>require_once</code>. Example:
      </p>
      <pre style="
        margin-top: 1rem;
        font-family: var(--mono);
        font-size: 0.72rem;
        color: var(--text-dim);
        line-height: 1.7;
        background: var(--bg3);
        border: 1px solid var(--border);
        border-radius: 4px;
        padding: 1rem 1.25rem;
        overflow-x: auto;
      "><?php echo htmlspecialchars(
'$pdo  = db_connect();
$stmt = $pdo->prepare(\'SELECT * FROM items WHERE active = 1\');
$stmt->execute();
$rows = $stmt->fetchAll();'
); ?></pre>

      <?php
      // ── Live DB check (only if a database is configured) ────
      // This block demonstrates conditional DB usage and can be
      // removed once you have real database queries on this page.
      if (defined('DB_NAME') && DB_NAME !== '') {
          try {
              $pdo = db_connect();
              echo '<p style="margin-top:1rem; color:var(--ok); font-family:var(--mono); font-size:0.75rem; letter-spacing:0.08em;">'
                 . '✓ Database connection OK'
                 . '</p>';
          } catch (PDOException $e) {
              echo '<p style="margin-top:1rem; color:var(--bad); font-family:var(--mono); font-size:0.75rem; letter-spacing:0.08em;">'
                 . '✗ Database error: ' . htmlspecialchars($e->getMessage())
                 . '</p>';
          }
      } else {
          echo '<p style="margin-top:1rem; color:var(--text-dimmer); font-family:var(--mono); font-size:0.75rem; letter-spacing:0.08em; font-style:italic;">'
             . 'Database not configured (DB_NAME is empty in config.php)'
             . '</p>';
      }
      ?>
    </div>


    <!-- ── SECTION: Auth example ──────────────────────────────── -->
    <div class="example-card">
      <h2>Auth (optional)</h2>
      <p>
        If <code>AUTH_ENABLED = true</code> in <code>config.php</code>
        and <code>modules/auth/</code> is in place,
        <code>auth_user()</code> returns the current user array or
        <code>null</code> if not logged in.
      </p>

      <?php
      // ── Live auth check ──────────────────────────────────────
      if (defined('AUTH_ENABLED') && AUTH_ENABLED && function_exists('auth_user')) {
          $user = auth_user();
          if ($user) {
              echo '<p style="margin-top:1rem; color:var(--ok); font-family:var(--mono); font-size:0.75rem; letter-spacing:0.08em;">'
                 . '✓ Signed in as ' . htmlspecialchars($user['email'])
                 . '</p>';
          } else {
              echo '<p style="margin-top:1rem; color:var(--warn); font-family:var(--mono); font-size:0.75rem; letter-spacing:0.08em;">'
                 . '— Not signed in'
                 . '</p>';
          }
      } else {
          echo '<p style="margin-top:1rem; color:var(--text-dimmer); font-family:var(--mono); font-size:0.75rem; letter-spacing:0.08em; font-style:italic;">'
             . 'Auth not enabled (AUTH_ENABLED is false in config.php)'
             . '</p>';
      }
      ?>
    </div>


  </div><!-- /.main-content -->

</main><!-- end main -->


<!-- ── 6. Include the footer ─────────────────────────────────────
     Outputs: <footer>...</footer> </body> </html>
─────────────────────────────────────────────────────────────── -->
<?php include ABIDE_CORE . '/footer.php'; ?>
