<?php
/*
  setup/index.php — Abide setup wizard
  ─────────────────────────────────────────────────────────────
  Guides the developer through first-run configuration. Writes
  config.php to the project root on completion, then locks itself.

  DOCUMENT ROOT NOTE:
    During setup, point your web server's document root at the
    project root (the folder containing setup/, core/, public/,
    etc.) so this wizard is reachable at /setup/.

    After setup, reconfigure your document root to point at
    public/ for production. The wizard will then be structurally
    unreachable — it lives outside the web root.

  SELF-DISABLE:
    On every request the wizard checks whether config.php exists
    in the project root. If it does, the wizard locks and refuses
    to proceed. To re-run the wizard, delete config.php manually.

  FLOW:
    Step 1  — Site (name, base URL)
    Step 2  — Database (credentials)
    Step 3  — Auth (on/off + SMTP credentials if enabled)
    Step 4  — Review → writes config.php
    Done    — Success screen with next-step checklist

  STATE:
    Wizard form data accumulates in $_SESSION['wizard'] across
    steps. Session is destroyed on successful completion.

  PATTERN:
    POST → validate → store in session → redirect (PRG pattern)
    Errors fall through to re-render the current step inline.

  DEPENDENCIES:
    None. Standalone — does not require core/, public/style.css,
    or any other Abide files to be configured yet.
  ─────────────────────────────────────────────────────────────
*/

// ── Session ───────────────────────────────────────────────────
// Must be started before any output.
session_start();

// ── Paths ─────────────────────────────────────────────────────
// setup/index.php lives one level below the project root.
// Config file is written to the project root.
define('ABIDE_ROOT',  dirname(__DIR__));
define('CONFIG_FILE', ABIDE_ROOT . '/config.php');

// ── Self-disable check ────────────────────────────────────────
// If config.php exists in the project root, setup has already
// run. Delete config.php manually to re-run the wizard.
$is_locked = file_exists(CONFIG_FILE);

// ── Done screen ───────────────────────────────────────────────
// Set via ?done=1 redirect after successful config write.
$is_done = $is_locked && isset($_GET['done']);

// ── Step routing ──────────────────────────────────────────────
// Clamp step to valid range 1–4. Locked and done states bypass
// step rendering entirely.
$step = $is_locked ? 0 : (int) ($_GET['step'] ?? 1);
$step = max(1, min(4, $step));

// ── Initialize session bucket ────────────────────────────────
if (!isset($_SESSION['wizard']) || !is_array($_SESSION['wizard'])) {
    $_SESSION['wizard'] = [];
}

// ── POST handlers ─────────────────────────────────────────────
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$is_locked && !$is_done) {

    $posted_step = (int) ($_POST['wizard_step'] ?? 0);

    // ── Step 1: Site identity ──────────────────────────────────
    if ($posted_step === 1) {

        $site_name = trim($_POST['site_name'] ?? '');
        $site_url  = rtrim(trim($_POST['site_url'] ?? ''), '/');

        if ($site_name === '') {
            $errors[] = 'Site name is required.';
        }
        if ($site_url === '') {
            $errors[] = 'Base URL is required.';
        } elseif (!filter_var($site_url, FILTER_VALIDATE_URL)) {
            $errors[] = 'Base URL must be a valid URL — e.g. https://example.com';
        }

        if (empty($errors)) {
            $_SESSION['wizard']['site_name'] = $site_name;
            $_SESSION['wizard']['site_url']  = $site_url;
            header('Location: ?step=2');
            exit;
        }
    }

    // ── Step 2: Database ──────────────────────────────────────
    if ($posted_step === 2) {

        $db_enabled = !empty($_POST['db_enabled']);
        $db_host    = trim($_POST['db_host'] ?? 'localhost');
        $db_name    = trim($_POST['db_name'] ?? '');
        $db_user    = trim($_POST['db_user'] ?? '');
        $db_pass    =       $_POST['db_pass'] ?? '';   // preserve as-is
        $db_char    = 'utf8mb4';

        // DB fields are only required when DB is enabled
        if ($db_enabled) {
            if ($db_name === '') $errors[] = 'Database name is required.';
            if ($db_user === '') $errors[] = 'Database username is required.';
        }

        if (empty($errors)) {
            $_SESSION['wizard']['db_enabled'] = $db_enabled;
            $_SESSION['wizard']['db_host']    = $db_host ?: 'localhost';
            $_SESSION['wizard']['db_name']    = $db_name;
            $_SESSION['wizard']['db_user']    = $db_user;
            $_SESSION['wizard']['db_pass']    = $db_pass;
            header('Location: ?step=3');
            exit;
        }
    }

    // ── Step 3: Auth module + SMTP ────────────────────────────
    if ($posted_step === 3) {

        $auth_enabled = !empty($_POST['auth_enabled']);

        $smtp_host = trim($_POST['smtp_host'] ?? '');
        $smtp_user = trim($_POST['smtp_user'] ?? '');
        $smtp_pass =       $_POST['smtp_pass'] ?? '';
        $smtp_port = (int)($_POST['smtp_port'] ?? 465);
        $smtp_from = trim($_POST['smtp_from'] ?? '');
        $smtp_name = trim($_POST['smtp_name'] ?? '');

        if ($auth_enabled) {
            if ($smtp_host === '') $errors[] = 'SMTP host is required when auth is enabled.';
            if ($smtp_user === '') $errors[] = 'SMTP username is required.';
            if ($smtp_pass === '') $errors[] = 'SMTP password is required.';
            if ($smtp_from === '') {
                $errors[] = 'SMTP From address is required.';
            } elseif (!filter_var($smtp_from, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'SMTP From address must be a valid email — e.g. noreply@example.com';
            }
            if ($smtp_port < 1 || $smtp_port > 65535) {
                $errors[] = 'SMTP port must be between 1 and 65535.';
            }
        }

        if (empty($errors)) {
            $_SESSION['wizard']['auth_enabled'] = $auth_enabled;
            $_SESSION['wizard']['smtp_host']    = $smtp_host;
            $_SESSION['wizard']['smtp_user']    = $smtp_user;
            $_SESSION['wizard']['smtp_pass']    = $smtp_pass;
            $_SESSION['wizard']['smtp_port']    = $smtp_port;
            $_SESSION['wizard']['smtp_from']    = $smtp_from;
            $_SESSION['wizard']['smtp_name']    = $smtp_name;
            header('Location: ?step=4');
            exit;
        }
    }

    // ── Step 4: Write config.php ──────────────────────────────
    if ($posted_step === 4) {

        $w = $_SESSION['wizard'];
        if (empty($w['site_name']) || empty($w['site_url'])) {
            header('Location: ?step=1');
            exit;
        }

        $auth_val  = !empty($w['auth_enabled']) ? 'true' : 'false';
        $timestamp = date('Y-m-d H:i:s');

        $cfg  = "<?php\n";
        $cfg .= "/*\n";
        $cfg .= "  config.php — Abide site configuration\n";
        $cfg .= "  Generated by setup wizard on {$timestamp}.\n";
        $cfg .= "\n";
        $cfg .= "  Do not commit this file. Add it to .gitignore.\n";
        $cfg .= "  See config.example.php for a safe committed template.\n";
        $cfg .= "  Delete this file to re-run the setup wizard.\n";
        $cfg .= "*/\n";
        $cfg .= "\n";

        // ── Setup flag ──────────────────────────────────────────
        $cfg .= "// ── Setup ────────────────────────────────────────────────────\n";
        $cfg .= "define('SETUP_COMPLETE', true);\n";
        $cfg .= "\n";

        // ── Site Identity ──────────────────────────────────────
        $cfg .= "// ── Site Identity ─────────────────────────────────────────────\n";
        $cfg .= "define('SITE_NAME',    " . var_export($w['site_name'], true) . ");\n";
        $cfg .= "define('SITE_URL',     " . var_export($w['site_url'],  true) . ");\n";
        $cfg .= "define('SITE_TAGLINE', '');   // optional — shown on the home page\n";
        $cfg .= "\n";

        // ── Database ───────────────────────────────────────────
        $cfg .= "// ── Database ──────────────────────────────────────────────────\n";
        $cfg .= "// Leave DB_NAME empty ('') to skip the database entirely.\n";
        if (!empty($w['db_enabled'])) {
            $cfg .= "define('DB_HOST', " . var_export($w['db_host'], true) . ");\n";
            $cfg .= "define('DB_NAME', " . var_export($w['db_name'], true) . ");\n";
            $cfg .= "define('DB_USER', " . var_export($w['db_user'], true) . ");\n";
            $cfg .= "define('DB_PASS', " . var_export($w['db_pass'], true) . ");\n";
            $cfg .= "define('DB_CHAR', 'utf8mb4');\n";
        } else {
            $cfg .= "define('DB_HOST', 'localhost');\n";
            $cfg .= "define('DB_NAME', '');          // '' = database disabled\n";
            $cfg .= "define('DB_USER', '');\n";
            $cfg .= "define('DB_PASS', '');\n";
            $cfg .= "define('DB_CHAR', 'utf8mb4');\n";
        }
        $cfg .= "\n";

        // ── SMTP ───────────────────────────────────────────────
        $cfg .= "// ── Email / SMTP ─────────────────────────────────────────────\n";
        $cfg .= "// Only required if AUTH_ENABLED is true.\n";
        $cfg .= "// Port 465 = SSL   |   Port 587 = TLS (STARTTLS)\n";
        if (!empty($w['auth_enabled'])) {
            $cfg .= "define('SMTP_HOST', " . var_export($w['smtp_host'], true) . ");\n";
            $cfg .= "define('SMTP_USER', " . var_export($w['smtp_user'], true) . ");\n";
            $cfg .= "define('SMTP_PASS', " . var_export($w['smtp_pass'], true) . ");\n";
            $cfg .= "define('SMTP_PORT', " . (int) $w['smtp_port'] . ");\n";
            $cfg .= "define('SMTP_FROM', " . var_export($w['smtp_from'], true) . ");\n";
            if (!empty($w['smtp_name'])) {
                $cfg .= "define('SMTP_NAME', " . var_export($w['smtp_name'], true) . ");\n";
            } else {
                $cfg .= "define('SMTP_NAME', SITE_NAME);  // falls back to site name\n";
            }
        } else {
            $cfg .= "define('SMTP_HOST', '');\n";
            $cfg .= "define('SMTP_USER', '');\n";
            $cfg .= "define('SMTP_PASS', '');\n";
            $cfg .= "define('SMTP_PORT', 465);\n";
            $cfg .= "define('SMTP_FROM', '');\n";
            $cfg .= "define('SMTP_NAME', SITE_NAME);\n";
        }
        $cfg .= "\n";

        // ── Auth ───────────────────────────────────────────────
        $cfg .= "// ── Auth Module ───────────────────────────────────────────────\n";
        $cfg .= "// Set to true only after modules/auth/ is in place and the\n";
        $cfg .= "// schema has been imported. Requires DB and SMTP above.\n";
        $cfg .= "define('AUTH_ENABLED', {$auth_val});\n";
        $cfg .= "\n";

        // ── Navigation ─────────────────────────────────────────
        $cfg .= "// ── Navigation ────────────────────────────────────────────────\n";
        $cfg .= "// 'hamburger' is the only option in v1.\n";
        $cfg .= "define('NAV_STYLE', 'hamburger');\n";
        $cfg .= "\n";

        // ── Header Branding ────────────────────────────────────
        // Written with defaults — developer customises after setup.
        // LOGO_MODE options: 'page_title' | 'logo' | 'wordmark' | 'both'
        $cfg .= "// ── Header Branding ──────────────────────────────────────────\n";
        $cfg .= "// Controls the centre column of the site header.\n";
        $cfg .= "// 'page_title' = current page name (default)\n";
        $cfg .= "// 'logo'       = logo image only\n";
        $cfg .= "// 'wordmark'   = SITE_NAME text only\n";
        $cfg .= "// 'both'       = logo + SITE_NAME text side by side\n";
        $cfg .= "define('LOGO_MODE', 'page_title');\n";
        $cfg .= "define('LOGO_FILE', '/assets/img/logo.png');   // web-root-relative path\n";
        $cfg .= "define('LOGO_ALT',  SITE_NAME);                // defaults to site name\n";
        $cfg .= "\n";

        // ── Write the file ─────────────────────────────────────
        $write_result = @file_put_contents(CONFIG_FILE, $cfg);

        if ($write_result === false) {
            $_SESSION['wizard']['manual_config'] = $cfg;
            $errors[] = 'Could not write config.php — the web server may not have write permission on the project root.';
            $errors[] = 'Fix the permissions and click "Write config.php" again, or copy the generated content below and save it manually as config.php in the project root, then refresh this page.';
        } else {
            $_SESSION['wizard'] = [];
            header('Location: ?done=1');
            exit;
        }
    }

    if (!empty($errors)) {
        $_SESSION['wizard']['errors'] = $errors;
    }
}

// ── Retrieve and clear session errors ────────────────────────
$errors = $_SESSION['wizard']['errors'] ?? [];
unset($_SESSION['wizard']['errors']);

// ── Manual config content (step 4 write failure) ─────────────
$manual_config = $_SESSION['wizard']['manual_config'] ?? '';

// ── Helpers ───────────────────────────────────────────────────
function esc(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function wval(string $key, string $default = ''): string {
    return esc($_SESSION['wizard'][$key] ?? $default);
}

?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Setup &mdash; Abide</title>

  <style>
    /* ── Reset ───────────────────────────────────────────────── */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    /* ── CSS custom properties ───────────────────────────────── */
    /* Amber palette — matches the main site default.            */
    :root {
      --bg:          #0c0b09;
      --bg2:         #131210;
      --bg3:         #1a1916;
      --border:      rgba(255, 179, 64, 0.12);
      --border-hi:   rgba(255, 179, 64, 0.30);
      --accent:      #ffb340;
      --accent-dim:  rgba(255, 179, 64, 0.15);
      --text:        #d8cfc4;
      --text-dim:    rgba(216, 207, 196, 0.62);
      --ok:          #44dd88;
      --warn:        #ffaa33;
      --bad:         #ff5555;
      --mono:        ui-monospace, Menlo, Consolas, 'Courier New', monospace;
      --sans:        system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    }

    /* ── Page shell ──────────────────────────────────────────── */
    html, body { height: 100%; }

    body {
      background: var(--bg);
      color: var(--text);
      font-family: var(--sans);
      font-weight: 300;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 3.5rem 1.5rem 6rem;
      /* Subtle dot grid — matches the main site aesthetic */
      background-image:
        linear-gradient(rgba(255, 179, 64, 0.025) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255, 179, 64, 0.025) 1px, transparent 1px);
      background-size: 40px 40px;
    }

    /* ── Wizard card ─────────────────────────────────────────── */
    .wizard-card {
      width: 100%;
      max-width: 540px;
      animation: fadeUp 0.45s ease both;
    }

    /* ── Branding ────────────────────────────────────────────── */
    .wizard-brand {
      font-family: var(--mono);
      font-size: 0.72rem;
      letter-spacing: 0.15em;
      color: var(--accent);
      text-transform: uppercase;
      margin-bottom: 0.4rem;
    }
    .wizard-heading {
      font-size: 1.45rem;
      font-weight: 400;
      margin-bottom: 0.3rem;
    }
    .wizard-subhead {
      font-size: 0.85rem;
      color: var(--text-dim);
      margin-bottom: 2.25rem;
    }

    /* ── Step indicator ──────────────────────────────────────── */
    .step-indicator {
      display: flex;
      align-items: center;
      gap: 0;
      margin-bottom: 2rem;
    }
    .step-node {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 0.3rem;
    }
    .step-dot {
      width: 28px;
      height: 28px;
      border-radius: 50%;
      border: 1px solid var(--border-hi);
      background: var(--bg2);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 0.72rem;
      font-family: var(--mono);
      color: var(--text-dim);
      transition: background 0.2s, border-color 0.2s, color 0.2s;
    }
    .step-dot.active   { background: var(--accent-dim); border-color: var(--accent); color: var(--accent); }
    .step-dot.complete { background: rgba(68, 221, 136, 0.12); border-color: var(--ok); color: var(--ok); }
    .step-label {
      font-size: 0.68rem;
      font-family: var(--mono);
      letter-spacing: 0.06em;
      color: var(--text-dim);
    }
    .step-connector {
      flex: 1;
      height: 1px;
      background: var(--border);
      margin: 0 0.5rem;
      margin-bottom: 1.1rem;
    }

    /* ── Form panel ──────────────────────────────────────────── */
    .form-panel {
      background: var(--bg2);
      border: 1px solid var(--border);
      border-radius: 8px;
      padding: 1.75rem 2rem;
    }
    .panel-title {
      font-size: 0.68rem;
      font-family: var(--mono);
      letter-spacing: 0.12em;
      text-transform: uppercase;
      color: var(--accent);
      padding-bottom: 0.85rem;
      margin-bottom: 1.5rem;
      border-bottom: 1px solid var(--border);
    }

    /* ── Form fields ─────────────────────────────────────────── */
    .field { margin-bottom: 1.2rem; }
    .field:last-of-type { margin-bottom: 0; }

    .field > label {
      display: block;
      font-size: 0.78rem;
      color: var(--text-dim);
      margin-bottom: 0.35rem;
      letter-spacing: 0.02em;
    }
    .field > label .req  { color: var(--accent); margin-left: 2px; }
    .field > label .hint {
      display: block;
      font-size: 0.7rem;
      color: var(--text-dim);
      opacity: 0.7;
      margin-top: 0.12rem;
      font-weight: 300;
    }

    input[type="text"],
    input[type="url"],
    input[type="email"],
    input[type="password"],
    input[type="number"] {
      width: 100%;
      background: var(--bg3);
      border: 1px solid var(--border-hi);
      border-radius: 4px;
      color: var(--text);
      font-family: var(--mono);
      font-size: 0.85rem;
      padding: 0.55rem 0.75rem;
      outline: none;
      transition: border-color 0.15s;
      -webkit-appearance: none;
    }
    input:focus { border-color: var(--accent); }
    input[type="number"] { width: 110px; }

    /* ── Toggle switch (checkbox) ────────────────────────────── */
    .toggle-row {
      display: flex;
      align-items: flex-start;
      gap: 0.9rem;
      padding: 0.5rem 0;
    }

    input[type="checkbox"].toggle {
      -webkit-appearance: none;
      appearance: none;
      flex-shrink: 0;
      width: 38px;
      height: 20px;
      background: var(--bg3);
      border: 1px solid var(--border-hi);
      border-radius: 20px;
      cursor: pointer;
      position: relative;
      margin-top: 3px;
      transition: background 0.2s, border-color 0.2s;
    }
    input[type="checkbox"].toggle::before {
      content: '';
      position: absolute;
      width: 14px; height: 14px;
      border-radius: 50%;
      background: var(--text-dim);
      top: 2px; left: 2px;
      transition: transform 0.18s, background 0.18s;
    }
    input[type="checkbox"].toggle:checked {
      background: var(--accent-dim);
      border-color: var(--accent);
    }
    input[type="checkbox"].toggle:checked::before {
      background: var(--accent);
      transform: translateX(18px);
    }

    .toggle-body { line-height: 1; }
    .toggle-title {
      font-size: 0.875rem;
      color: var(--text);
      display: block;
      margin-bottom: 0.25rem;
    }
    .toggle-desc {
      font-size: 0.75rem;
      color: var(--text-dim);
    }

    /* ── DB and SMTP sub-sections ────────────────────────────── */
    .sub-section {
      margin-top: 1.25rem;
      padding-top: 1.25rem;
      border-top: 1px solid var(--border);
    }
    .sub-section-label {
      font-size: 0.68rem;
      font-family: var(--mono);
      letter-spacing: 0.1em;
      text-transform: uppercase;
      color: var(--text-dim);
      margin-bottom: 1.1rem;
    }
    .field-row {
      display: grid;
      grid-template-columns: 1fr auto;
      gap: 0.75rem;
      align-items: end;
    }
    .field-row .field { margin-bottom: 0; }

    /* ── Error list ──────────────────────────────────────────── */
    .error-list {
      background: rgba(255, 85, 85, 0.08);
      border: 1px solid rgba(255, 85, 85, 0.28);
      border-radius: 6px;
      padding: 0.85rem 1rem;
      margin-bottom: 1.5rem;
    }
    .error-list p {
      font-size: 0.8rem;
      color: #ff8888;
      line-height: 1.55;
    }
    .error-list p + p { margin-top: 0.3rem; }

    /* ── Review table ────────────────────────────────────────── */
    .review-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.8rem;
      margin-top: 0.5rem;
    }
    .review-table td {
      padding: 0.5rem 0;
      vertical-align: top;
    }
    .review-table tr:not(:last-child) td {
      border-bottom: 1px solid var(--border);
    }
    .review-table .col-key {
      width: 38%;
      padding-right: 1rem;
      font-family: var(--mono);
      font-size: 0.72rem;
      color: var(--text-dim);
    }
    .review-table .col-val {
      color: var(--text);
      word-break: break-all;
    }
    .review-section-row td {
      font-size: 0.65rem;
      font-family: var(--mono);
      letter-spacing: 0.1em;
      text-transform: uppercase;
      color: var(--accent);
      padding-top: 1.1rem;
      padding-bottom: 0.2rem;
      border-bottom: none !important;
    }
    .badge-on  { font-family: var(--mono); font-size: 0.75rem; color: var(--ok); }
    .badge-off { font-family: var(--mono); font-size: 0.75rem; color: var(--text-dim); }
    .masked    { font-family: var(--mono); font-size: 0.75rem; color: var(--text-dim); letter-spacing: 0.08em; }

    /* ── Manual copy fallback ────────────────────────────────── */
    .manual-copy { margin-top: 1.5rem; }
    .manual-copy-label {
      font-size: 0.75rem;
      color: var(--text-dim);
      font-family: var(--mono);
      margin-bottom: 0.5rem;
      line-height: 1.5;
    }
    .manual-copy textarea {
      width: 100%;
      min-height: 200px;
      background: var(--bg3);
      border: 1px solid var(--border-hi);
      border-radius: 4px;
      color: var(--text);
      font-family: var(--mono);
      font-size: 0.72rem;
      line-height: 1.5;
      padding: 0.75rem;
      resize: vertical;
      outline: none;
    }

    /* ── Buttons ─────────────────────────────────────────────── */
    .btn-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-top: 1.75rem;
    }
    .btn {
      display: inline-flex;
      align-items: center;
      gap: 0.35rem;
      padding: 0.55rem 1.2rem;
      border-radius: 4px;
      border: 1px solid var(--border-hi);
      background: transparent;
      color: var(--text);
      font-family: var(--sans);
      font-size: 0.85rem;
      cursor: pointer;
      text-decoration: none;
      transition: background 0.15s, border-color 0.15s;
      white-space: nowrap;
    }
    .btn:hover { background: var(--bg3); }
    .btn-primary {
      background: var(--accent-dim);
      border-color: var(--accent);
      color: var(--accent);
    }
    .btn-primary:hover { background: rgba(255, 179, 64, 0.25); }
    .btn-back {
      border: none;
      background: none;
      color: var(--text-dim);
      font-size: 0.8rem;
      padding-left: 0;
    }
    .btn-back:hover { color: var(--text); background: none; }

    /* ── Done / locked screens ───────────────────────────────── */
    .status-icon {
      font-size: 2rem;
      margin-bottom: 0.75rem;
      display: block;
    }
    .next-steps {
      display: flex;
      flex-direction: column;
      gap: 0.75rem;
      margin-top: 1.25rem;
    }
    .next-step {
      display: flex;
      align-items: flex-start;
      gap: 0.75rem;
      font-size: 0.83rem;
      color: var(--text-dim);
      line-height: 1.55;
    }
    .step-num {
      font-family: var(--mono);
      font-size: 0.68rem;
      color: var(--accent);
      background: var(--accent-dim);
      border: 1px solid rgba(255, 179, 64, 0.2);
      border-radius: 50%;
      width: 20px; height: 20px;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      margin-top: 2px;
    }
    code {
      font-family: var(--mono);
      font-size: 0.78em;
      color: var(--accent);
      background: var(--accent-dim);
      border-radius: 3px;
      padding: 0.1em 0.35em;
    }
    .locked-notice {
      background: rgba(255, 170, 51, 0.07);
      border: 1px solid rgba(255, 170, 51, 0.22);
      border-radius: 6px;
      padding: 0.85rem 1rem;
      margin-bottom: 1.5rem;
      font-size: 0.82rem;
      color: #f5d08a;
      line-height: 1.6;
    }
    .divider {
      border: none;
      border-top: 1px solid var(--border);
      margin: 1.5rem 0;
    }
    .note {
      font-size: 0.78rem;
      color: var(--text-dim);
      line-height: 1.6;
    }

    /* ── Animation ───────────────────────────────────────────── */
    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(8px); }
      to   { opacity: 1; transform: translateY(0); }
    }
  </style>
</head>
<body>

<div class="wizard-card">

  <!-- Branding -->
  <div class="wizard-brand">Abide</div>
  <h1 class="wizard-heading">Setup Wizard</h1>
  <p class="wizard-subhead">Configure your scaffold. This runs once.</p>

  <?php if ($is_locked && !$is_done): ?>
  <!-- ═══════════════════════════════════════════════════════════
       LOCKED
  ════════════════════════════════════════════════════════════ -->
  <div class="locked-notice">
    <strong>Setup has already run.</strong> config.php was found in the project root.
    To re-run this wizard, delete <code>config.php</code> from the project root manually.
  </div>
  <div class="form-panel" style="text-align:center; padding:2.5rem 2rem;">
    <span class="status-icon" style="color:var(--text-dim);">&#9632;</span>
    <p class="note">
      Your site is configured and this wizard is locked.<br>
      Return to your project to continue building.
    </p>
  </div>

  <?php elseif ($is_done): ?>
  <!-- ═══════════════════════════════════════════════════════════
       DONE
  ════════════════════════════════════════════════════════════ -->
  <div class="form-panel">
    <div style="text-align:center; margin-bottom:1.25rem;">
      <span class="status-icon" style="color:var(--ok);">&#10003;</span>
      <p style="font-size:1rem; color:var(--text);">config.php written.</p>
      <p class="note" style="margin-top:0.35rem;">Setup complete. Here is what to do next.</p>
    </div>
    <hr class="divider" style="margin-top:0;">
    <div class="next-steps">
      <div class="next-step">
        <span class="step-num">1</span>
        <span>Add <code>config.php</code> to your <code>.gitignore</code> if you have not already. Never commit it.</span>
      </div>
      <div class="next-step">
        <span class="step-num">2</span>
        <span>Reconfigure your web server's document root to point at <code>public/</code>. The setup wizard will then be outside the web root and structurally unreachable.</span>
      </div>
      <div class="next-step">
        <span class="step-num">3</span>
        <span>If the database is enabled, import <code>modules/settings/schema.sql</code> to create the settings table. This enables the admin configuration page at <code>/admin</code>.</span>
      </div>
      <div class="next-step">
        <span class="step-num">4</span>
        <span>If the auth module is enabled, drop PHPMailer into <code>modules/auth/phpmailer/src/</code> and import <code>modules/auth/schema.sql</code> against your database.</span>
      </div>
      <div class="next-step">
        <span class="step-num">5</span>
        <span>Start building in <code>public/pages/</code>. Any file at <code>pages/about.php</code> is reachable at <code>/about</code> — no routing config required.</span>
      </div>
    </div>
  </div>

  <?php else: ?>
  <!-- ═══════════════════════════════════════════════════════════
       STEP INDICATOR
  ════════════════════════════════════════════════════════════ -->
  <div class="step-indicator" aria-label="Setup progress, step <?= $step ?> of 4">
    <?php
    $step_labels = [1 => 'Site', 2 => 'Database', 3 => 'Auth', 4 => 'Review'];
    foreach ($step_labels as $n => $label):
        $cls = $n < $step ? 'complete' : ($n === $step ? 'active' : '');
    ?>
    <?php if ($n > 1): ?><div class="step-connector"></div><?php endif; ?>
    <div class="step-node">
      <span class="step-dot <?= $cls ?>">
        <?= $n < $step ? '&#10003;' : $n ?>
      </span>
      <span class="step-label"><?= $label ?></span>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Error display -->
  <?php if (!empty($errors)): ?>
  <div class="error-list" role="alert">
    <?php foreach ($errors as $e): ?>
    <p><?= esc($e) ?></p>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>


  <?php if ($step === 1): ?>
  <!-- ─────────────────────────────────────────────────────────
       STEP 1 — Site identity
  ───────────────────────────────────────────────────────────── -->
  <form method="POST" action="?step=1" novalidate>
    <input type="hidden" name="wizard_step" value="1">

    <div class="form-panel">
      <div class="panel-title">Step 1 of 4 &mdash; Site</div>

      <div class="field">
        <label for="site_name">
          Site name <span class="req">*</span>
          <span class="hint">Used in page titles, emails, and SITE_NAME throughout your project. e.g. "My Site"</span>
        </label>
        <input
          type="text"
          id="site_name"
          name="site_name"
          value="<?= wval('site_name') ?>"
          placeholder="My Site"
          autocomplete="off"
          required
          autofocus
        >
      </div>

      <div class="field">
        <label for="site_url">
          Base URL <span class="req">*</span>
          <span class="hint">Root URL with scheme, no trailing slash. e.g. "https://example.com"</span>
        </label>
        <input
          type="url"
          id="site_url"
          name="site_url"
          value="<?= wval('site_url') ?>"
          placeholder="https://example.com"
          autocomplete="off"
          required
        >
      </div>
    </div>

    <div class="btn-row">
      <span></span>
      <button type="submit" class="btn btn-primary">Continue &rarr;</button>
    </div>
  </form>


  <?php elseif ($step === 2): ?>
  <!-- ─────────────────────────────────────────────────────────
       STEP 2 — Database
  ───────────────────────────────────────────────────────────── -->
  <form method="POST" action="?step=2" novalidate>
    <input type="hidden" name="wizard_step" value="2">

    <div class="form-panel">
      <div class="panel-title">Step 2 of 4 &mdash; Database</div>

      <!-- Toggle: DB on/off -->
      <div class="toggle-row">
        <input
          type="checkbox"
          class="toggle"
          id="db_enabled"
          name="db_enabled"
          <?= !empty($_SESSION['wizard']['db_enabled']) ? 'checked' : '' ?>
          onchange="toggleDb(this.checked)"
        >
        <div class="toggle-body">
          <label for="db_enabled" class="toggle-title">Enable database</label>
          <span class="toggle-desc">
            Required for the auth module and the admin configuration page.
            Uses MySQL / MariaDB via PDO.
          </span>
        </div>
      </div>

      <!-- DB credentials — hidden when disabled -->
      <div
        class="sub-section"
        id="db-section"
        style="<?= empty($_SESSION['wizard']['db_enabled']) ? 'display:none;' : '' ?>"
      >
        <div class="sub-section-label">MySQL / MariaDB credentials</div>

        <div class="field">
          <label for="db_name">
            Database name <span class="req">*</span>
          </label>
          <input
            type="text"
            id="db_name"
            name="db_name"
            value="<?= wval('db_name') ?>"
            placeholder="my_database"
            autocomplete="off"
          >
        </div>

        <div class="field">
          <label for="db_user">
            Username <span class="req">*</span>
          </label>
          <input
            type="text"
            id="db_user"
            name="db_user"
            value="<?= wval('db_user') ?>"
            placeholder="db_user"
            autocomplete="off"
          >
        </div>

        <div class="field">
          <label for="db_pass">Password</label>
          <input
            type="password"
            id="db_pass"
            name="db_pass"
            value="<?= wval('db_pass') ?>"
            autocomplete="new-password"
          >
        </div>

        <div class="field">
          <label for="db_host">
            Host
            <span class="hint">Usually localhost. Change only if your DB is on a different server.</span>
          </label>
          <input
            type="text"
            id="db_host"
            name="db_host"
            value="<?= wval('db_host', 'localhost') ?>"
            placeholder="localhost"
            autocomplete="off"
          >
        </div>

      </div><!-- /db-section -->
    </div><!-- /form-panel -->

    <div class="btn-row">
      <a href="?step=1" class="btn btn-back">&larr; Back</a>
      <button type="submit" class="btn btn-primary">Continue &rarr;</button>
    </div>
  </form>

  <script>
    function toggleDb(enabled) {
      document.getElementById('db-section').style.display = enabled ? '' : 'none';
    }
  </script>


  <?php elseif ($step === 3): ?>
  <!-- ─────────────────────────────────────────────────────────
       STEP 3 — Auth module + SMTP
  ───────────────────────────────────────────────────────────── -->
  <form method="POST" action="?step=3" novalidate>
    <input type="hidden" name="wizard_step" value="3">

    <div class="form-panel">
      <div class="panel-title">Step 3 of 4 &mdash; Auth</div>

      <!-- Toggle: auth on/off -->
      <div class="toggle-row">
        <input
          type="checkbox"
          class="toggle"
          id="auth_enabled"
          name="auth_enabled"
          <?= !empty($_SESSION['wizard']['auth_enabled']) ? 'checked' : '' ?>
          onchange="toggleSmtp(this.checked)"
        >
        <div class="toggle-body">
          <label for="auth_enabled" class="toggle-title">Enable auth module</label>
          <span class="toggle-desc">
            Login, registration, password reset, and permission checking.
            Requires PHPMailer and a database.
          </span>
        </div>
      </div>

      <!-- SMTP fields — hidden when auth is disabled -->
      <div
        class="sub-section"
        id="smtp-section"
        style="<?= empty($_SESSION['wizard']['auth_enabled']) ? 'display:none;' : '' ?>"
      >
        <div class="sub-section-label">SMTP &mdash; transactional email</div>

        <div class="field">
          <label for="smtp_host">
            Host <span class="req">*</span>
            <span class="hint">Mail server hostname. e.g. "mail.example.com"</span>
          </label>
          <input
            type="text"
            id="smtp_host"
            name="smtp_host"
            value="<?= wval('smtp_host') ?>"
            placeholder="mail.example.com"
          >
        </div>

        <div class="field">
          <label for="smtp_user">
            Username <span class="req">*</span>
            <span class="hint">Usually the full email address used to authenticate.</span>
          </label>
          <input
            type="email"
            id="smtp_user"
            name="smtp_user"
            value="<?= wval('smtp_user') ?>"
            placeholder="noreply@example.com"
          >
        </div>

        <div class="field">
          <label for="smtp_pass">Password <span class="req">*</span></label>
          <input
            type="password"
            id="smtp_pass"
            name="smtp_pass"
            value="<?= wval('smtp_pass') ?>"
            autocomplete="new-password"
          >
        </div>

        <div class="field-row">
          <div class="field">
            <label for="smtp_from">
              From address <span class="req">*</span>
              <span class="hint">Address shown in the From field of outgoing email.</span>
            </label>
            <input
              type="email"
              id="smtp_from"
              name="smtp_from"
              value="<?= wval('smtp_from') ?>"
              placeholder="noreply@example.com"
            >
          </div>
          <div class="field">
            <label for="smtp_port">
              Port
              <span class="hint">465 = SSL, 587 = TLS</span>
            </label>
            <input
              type="number"
              id="smtp_port"
              name="smtp_port"
              value="<?= wval('smtp_port', '465') ?>"
              min="1"
              max="65535"
              placeholder="465"
            >
          </div>
        </div>

        <div class="field" style="margin-top:1.1rem;">
          <label for="smtp_name">
            From name
            <span class="hint">Display name in the From field. Defaults to site name if blank.</span>
          </label>
          <input
            type="text"
            id="smtp_name"
            name="smtp_name"
            value="<?= wval('smtp_name') ?>"
            placeholder="My Site"
          >
        </div>

      </div><!-- /smtp-section -->
    </div><!-- /form-panel -->

    <div class="btn-row">
      <a href="?step=2" class="btn btn-back">&larr; Back</a>
      <button type="submit" class="btn btn-primary">Continue &rarr;</button>
    </div>
  </form>

  <script>
    function toggleSmtp(enabled) {
      document.getElementById('smtp-section').style.display = enabled ? '' : 'none';
    }
  </script>


  <?php elseif ($step === 4): ?>
  <!-- ─────────────────────────────────────────────────────────
       STEP 4 — Review & write
  ───────────────────────────────────────────────────────────── -->
  <?php
  if (empty($_SESSION['wizard']['site_name'])):
      header('Location: ?step=1');
      exit;
  endif;
  $w = $_SESSION['wizard'];
  ?>
  <form method="POST" action="?step=4" novalidate>
    <input type="hidden" name="wizard_step" value="4">

    <div class="form-panel">
      <div class="panel-title">Step 4 of 4 &mdash; Review</div>

      <p class="note" style="margin-bottom:1.25rem;">
        Review your settings. Clicking <strong style="color:var(--text);">Write config.php</strong>
        creates the file in the project root and locks this wizard.
      </p>

      <table class="review-table">

        <!-- Site -->
        <tr class="review-section-row"><td colspan="2">Site</td></tr>
        <tr>
          <td class="col-key">SITE_NAME</td>
          <td class="col-val"><?= esc($w['site_name']) ?></td>
        </tr>
        <tr>
          <td class="col-key">SITE_URL</td>
          <td class="col-val"><?= esc($w['site_url']) ?></td>
        </tr>

        <!-- Database -->
        <tr class="review-section-row"><td colspan="2">Database</td></tr>
        <tr>
          <td class="col-key">DB_NAME</td>
          <td class="col-val">
            <?php if (!empty($w['db_enabled'])): ?>
              <?= esc($w['db_name']) ?>
            <?php else: ?>
              <span class="badge-off">disabled</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php if (!empty($w['db_enabled'])): ?>
        <tr>
          <td class="col-key">DB_USER</td>
          <td class="col-val"><?= esc($w['db_user']) ?></td>
        </tr>
        <tr>
          <td class="col-key">DB_HOST</td>
          <td class="col-val"><?= esc($w['db_host'] ?? 'localhost') ?></td>
        </tr>
        <?php endif; ?>

        <!-- Auth -->
        <tr class="review-section-row"><td colspan="2">Auth</td></tr>
        <tr>
          <td class="col-key">AUTH_ENABLED</td>
          <td class="col-val">
            <?php if (!empty($w['auth_enabled'])): ?>
              <span class="badge-on">true</span>
            <?php else: ?>
              <span class="badge-off">false</span>
            <?php endif; ?>
          </td>
        </tr>

        <?php if (!empty($w['auth_enabled'])): ?>
        <tr class="review-section-row"><td colspan="2">SMTP</td></tr>
        <tr>
          <td class="col-key">SMTP_HOST</td>
          <td class="col-val"><?= esc($w['smtp_host']) ?></td>
        </tr>
        <tr>
          <td class="col-key">SMTP_USER</td>
          <td class="col-val"><?= esc($w['smtp_user']) ?></td>
        </tr>
        <tr>
          <td class="col-key">SMTP_PASS</td>
          <td class="col-val">
            <span class="masked"><?= str_repeat('&bull;', min(14, strlen($w['smtp_pass']))) ?></span>
          </td>
        </tr>
        <tr>
          <td class="col-key">SMTP_PORT</td>
          <td class="col-val"><?= (int) $w['smtp_port'] ?></td>
        </tr>
        <tr>
          <td class="col-key">SMTP_FROM</td>
          <td class="col-val"><?= esc($w['smtp_from']) ?></td>
        </tr>
        <tr>
          <td class="col-key">SMTP_NAME</td>
          <td class="col-val">
            <?= esc(!empty($w['smtp_name']) ? $w['smtp_name'] : $w['site_name']) ?>
            <?php if (empty($w['smtp_name'])): ?>
              <span style="font-size:0.7rem;color:var(--text-dim);"> (defaults to site name)</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endif; // auth_enabled ?>

      </table>

      <?php if (!empty($manual_config)): ?>
      <div class="manual-copy">
        <p class="manual-copy-label">
          Write failed. Copy the content below and save it manually as
          <strong>config.php</strong> in the project root, then refresh this page.
        </p>
        <textarea readonly onclick="this.select()"><?= esc($manual_config) ?></textarea>
      </div>
      <?php endif; ?>

    </div><!-- /form-panel -->

    <div class="btn-row">
      <a href="?step=3" class="btn btn-back">&larr; Back</a>
      <button type="submit" class="btn btn-primary">Write config.php</button>
    </div>
  </form>

  <?php endif; // step switch ?>
  <?php endif; // locked / done / steps ?>

</div><!-- /wizard-card -->

</body>
</html>
