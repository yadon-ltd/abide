<?php
/*
  public/pages/admin.php — Site configuration (Head End only)
  ──────────────────────────────────────────────────────────────
  URL: yourdomain.com/admin

  Access: PERM_HEADEND only (bit flag 4).
  Any attempt to load this page without the correct permission
  is silently redirected to the home page.

  Sections:
    1. Appearance   — CSS custom property overrides stored in the
                      settings table and served by /assets/css.php
    2. Identity     — Site name, tagline, logo mode/file/alt
                      Values are saved to the DB and take effect
                      immediately via the abide_* helper functions
                      in modules/settings/settings.php.

  Save mechanism:
    POST to self. Each named field maps to a settings key.
    settings_set() upserts the value. Flash message on success.
    PRG pattern — redirects after save to prevent re-POST on refresh.
  ──────────────────────────────────────────────────────────────
*/


// ── Gate: head end only ───────────────────────────────────────
auth_require_permission(PERM_HEADEND, '/');


// ── CSS token defaults ────────────────────────────────────────
// These mirror the :root values in public/style.css.
// Used to pre-fill inputs when no override has been saved yet.
$css_defaults = [
    'css.accent'      => '#ffb340',
    'css.accent-dim'  => 'rgba(255, 179, 64, 0.55)',
    'css.accent-glow' => 'rgba(255, 179, 64, 0.08)',
    'css.bg'          => '#0c0b09',
    'css.bg2'         => '#131210',
    'css.bg3'         => '#1a1916',
    'css.border'      => 'rgba(255, 179, 64, 0.12)',
    'css.border-hi'   => 'rgba(255, 179, 64, 0.35)',
    'css.text'        => '#d8cfc4',
    'css.text-dim'    => 'rgba(216, 207, 196, 0.68)',
    'css.text-dimmer' => 'rgba(216, 207, 196, 0.45)',
];

// Identity defaults — resolved DB-first via helpers, then constants
$identity_defaults = [
    'site.name'      => abide_site_name(),
    'site.tagline'   => abide_tagline(),
    'site.logo_mode' => abide_logo_mode(),
    'site.logo_file' => abide_logo_file(),
    'site.logo_alt'  => abide_logo_alt(),
];


// ── Handle POST ───────────────────────────────────────────────
$flash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $allowed_keys = array_merge(
        array_keys($css_defaults),
        array_keys($identity_defaults)
    );

    $saved = 0;
    foreach ($allowed_keys as $key) {
        $field = str_replace(['.', '-'], '_', $key);
        if (isset($_POST[$field])) {
            $value = trim($_POST[$field]);
            if (settings_set($key, $value)) {
                $saved++;
            }
        }
    }

    $flash = $saved > 0
        ? 'ok:Settings saved.'
        : 'bad:Nothing was saved — check your database connection.';

    $flash_encoded = urlencode($flash);
    header("Location: /admin?saved={$flash_encoded}");
    exit;
}

// Pick up flash message from redirect
if (!empty($_GET['saved'])) {
    $flash = urldecode($_GET['saved']);
}

$flash_type = '';
$flash_msg  = '';
if ($flash) {
    [$flash_type, $flash_msg] = explode(':', $flash, 2);
}


// ── Load current saved values ─────────────────────────────────
$current = settings_get_all();

// Helper: get display value for a field — saved value wins, then default
function admin_val(string $key, array $current, array $defaults): string {
    if (isset($current[$key]) && $current[$key] !== '') {
        return $current[$key];
    }
    return $defaults[$key] ?? '';
}


// ── Page ──────────────────────────────────────────────────────
$page_title = 'Configuration';
include ABIDE_CORE . '/header.php';
?>

<style>
  /* ── Admin page layout ───────────────────────────────────── */

  .admin-wrap {
    max-width: 720px;
    margin: 0 auto;
    animation: fadeUp 0.3s ease both;
  }

  .admin-section {
    background: var(--bg2);
    border: 1px solid var(--border);
    border-radius: 6px;
    padding: 1.75rem 2rem;
    margin-bottom: 1.5rem;
  }

  .admin-section-title {
    font-family: var(--mono);
    font-size: 0.68rem;
    letter-spacing: 0.2em;
    text-transform: uppercase;
    color: var(--accent-dim);
    margin-bottom: 0.25rem;
  }

  .admin-section-desc {
    font-size: 0.8rem;
    color: var(--text-dimmer);
    margin-bottom: 1.5rem;
    line-height: 1.6;
  }

  .admin-section-desc code {
    font-family: var(--mono);
    font-size: 0.78em;
    color: var(--accent-dim);
  }

  .field-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1rem;
  }

  .field-row.full { grid-template-columns: 1fr; }

  @media (max-width: 560px) {
    .field-row { grid-template-columns: 1fr; }
  }

  .field {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
  }

  .field label {
    font-family: var(--mono);
    font-size: 0.6rem;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: var(--text-dimmer);
  }

  .field input[type="text"],
  .field input[type="url"],
  .field select {
    background: var(--bg3);
    border: 1px solid var(--border);
    border-radius: 3px;
    color: var(--text);
    font-family: var(--mono);
    font-size: 0.75rem;
    padding: 0.5rem 0.7rem;
    width: 100%;
    transition: border-color 0.2s ease;
    outline: none;
  }

  .field input[type="text"]:focus,
  .field input[type="url"]:focus,
  .field select:focus {
    border-color: var(--border-hi);
  }

  .color-field {
    display: flex;
    gap: 0.5rem;
    align-items: center;
  }

  .color-field input[type="color"] {
    width: 2.4rem;
    height: 2.2rem;
    padding: 0.15rem;
    background: var(--bg3);
    border: 1px solid var(--border);
    border-radius: 3px;
    cursor: pointer;
    flex-shrink: 0;
  }

  .color-field input[type="text"] {
    flex: 1;
    min-width: 0;
  }

  .field .hint {
    font-size: 0.58rem;
    color: var(--text-dimmer);
    font-style: italic;
  }

  .flash {
    padding: 0.7rem 1rem;
    border-radius: 3px;
    font-family: var(--mono);
    font-size: 0.72rem;
    letter-spacing: 0.06em;
    margin-bottom: 1.25rem;
  }

  .flash.ok  { background: rgba(68, 221, 136, 0.08); border: 1px solid rgba(68, 221, 136, 0.25); color: var(--ok);  }
  .flash.bad { background: rgba(255, 85,  85,  0.08); border: 1px solid rgba(255, 85,  85,  0.25); color: var(--bad); }

  .field-divider {
    border: none;
    border-top: 1px solid var(--border);
    margin: 1.25rem 0;
  }

  .save-bar {
    display: flex;
    align-items: center;
    justify-content: flex-end;
  }

  .btn-save {
    background: var(--accent-glow);
    border: 1px solid var(--accent-dim);
    border-radius: 3px;
    color: var(--accent);
    cursor: pointer;
    font-family: var(--mono);
    font-size: 0.68rem;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    padding: 0.6rem 1.6rem;
    transition: background 0.2s ease, border-color 0.2s ease, color 0.2s ease;
  }

  .btn-save:hover {
    background: rgba(255, 179, 64, 0.14);
    border-color: var(--accent);
    color: #fff;
  }
</style>


<main>
<div class="main-content">
<div class="admin-wrap">

  <form method="post" action="/admin">

    <?php if ($flash_msg) : ?>
      <div class="flash <?php echo htmlspecialchars($flash_type); ?>">
        <?php echo htmlspecialchars($flash_msg); ?>
      </div>
    <?php endif; ?>


    <!-- ── Section: Appearance ───────────────────────────────── -->
    <div class="admin-section">

      <div class="admin-section-title">Appearance</div>
      <p class="admin-section-desc">
        Overrides for the CSS custom properties defined in
        <code>public/style.css</code>. Changes are served by
        <code>/assets/css.php</code> and take effect within 5&nbsp;minutes
        (browser cache). Leave a field blank to use the stylesheet default.
      </p>

      <!-- Accent -->
      <div class="field-row">
        <div class="field">
          <label for="css_accent">--accent</label>
          <div class="color-field">
            <input type="color"
                   id="css_accent_picker"
                   value="<?php echo htmlspecialchars(admin_val('css.accent', $current, $css_defaults)); ?>"
                   oninput="document.getElementById('css_accent').value = this.value" />
            <input type="text"
                   id="css_accent"
                   name="css_accent"
                   value="<?php echo htmlspecialchars(admin_val('css.accent', $current, $css_defaults)); ?>"
                   oninput="syncPicker(this, 'css_accent_picker')" />
          </div>
        </div>
        <div class="field">
          <label for="css_accent_dim">--accent-dim</label>
          <input type="text"
                 id="css_accent_dim"
                 name="css_accent_dim"
                 value="<?php echo htmlspecialchars(admin_val('css.accent-dim', $current, $css_defaults)); ?>" />
          <span class="hint">rgba or hex</span>
        </div>
      </div>

      <div class="field-row">
        <div class="field">
          <label for="css_accent_glow">--accent-glow</label>
          <input type="text"
                 id="css_accent_glow"
                 name="css_accent_glow"
                 value="<?php echo htmlspecialchars(admin_val('css.accent-glow', $current, $css_defaults)); ?>" />
          <span class="hint">subtle background wash, usually rgba at low opacity</span>
        </div>
      </div>

      <hr class="field-divider" />

      <!-- Backgrounds -->
      <div class="field-row">
        <div class="field">
          <label for="css_bg">--bg (page)</label>
          <div class="color-field">
            <input type="color"
                   id="css_bg_picker"
                   value="<?php echo htmlspecialchars(admin_val('css.bg', $current, $css_defaults)); ?>"
                   oninput="document.getElementById('css_bg').value = this.value" />
            <input type="text"
                   id="css_bg"
                   name="css_bg"
                   value="<?php echo htmlspecialchars(admin_val('css.bg', $current, $css_defaults)); ?>"
                   oninput="syncPicker(this, 'css_bg_picker')" />
          </div>
        </div>
        <div class="field">
          <label for="css_bg2">--bg2 (card)</label>
          <div class="color-field">
            <input type="color"
                   id="css_bg2_picker"
                   value="<?php echo htmlspecialchars(admin_val('css.bg2', $current, $css_defaults)); ?>"
                   oninput="document.getElementById('css_bg2').value = this.value" />
            <input type="text"
                   id="css_bg2"
                   name="css_bg2"
                   value="<?php echo htmlspecialchars(admin_val('css.bg2', $current, $css_defaults)); ?>"
                   oninput="syncPicker(this, 'css_bg2_picker')" />
          </div>
        </div>
      </div>

      <div class="field-row">
        <div class="field">
          <label for="css_bg3">--bg3 (elevated)</label>
          <div class="color-field">
            <input type="color"
                   id="css_bg3_picker"
                   value="<?php echo htmlspecialchars(admin_val('css.bg3', $current, $css_defaults)); ?>"
                   oninput="document.getElementById('css_bg3').value = this.value" />
            <input type="text"
                   id="css_bg3"
                   name="css_bg3"
                   value="<?php echo htmlspecialchars(admin_val('css.bg3', $current, $css_defaults)); ?>"
                   oninput="syncPicker(this, 'css_bg3_picker')" />
          </div>
        </div>
      </div>

      <hr class="field-divider" />

      <!-- Borders -->
      <div class="field-row">
        <div class="field">
          <label for="css_border">--border</label>
          <input type="text"
                 id="css_border"
                 name="css_border"
                 value="<?php echo htmlspecialchars(admin_val('css.border', $current, $css_defaults)); ?>" />
          <span class="hint">rgba recommended</span>
        </div>
        <div class="field">
          <label for="css_border_hi">--border-hi (hover/focus)</label>
          <input type="text"
                 id="css_border_hi"
                 name="css_border_hi"
                 value="<?php echo htmlspecialchars(admin_val('css.border-hi', $current, $css_defaults)); ?>" />
          <span class="hint">rgba recommended</span>
        </div>
      </div>

      <hr class="field-divider" />

      <!-- Text -->
      <div class="field-row">
        <div class="field">
          <label for="css_text">--text</label>
          <div class="color-field">
            <input type="color"
                   id="css_text_picker"
                   value="<?php echo htmlspecialchars(admin_val('css.text', $current, $css_defaults)); ?>"
                   oninput="document.getElementById('css_text').value = this.value" />
            <input type="text"
                   id="css_text"
                   name="css_text"
                   value="<?php echo htmlspecialchars(admin_val('css.text', $current, $css_defaults)); ?>"
                   oninput="syncPicker(this, 'css_text_picker')" />
          </div>
        </div>
        <div class="field">
          <label for="css_text_dim">--text-dim</label>
          <input type="text"
                 id="css_text_dim"
                 name="css_text_dim"
                 value="<?php echo htmlspecialchars(admin_val('css.text-dim', $current, $css_defaults)); ?>" />
          <span class="hint">rgba</span>
        </div>
      </div>

      <div class="field-row">
        <div class="field">
          <label for="css_text_dimmer">--text-dimmer</label>
          <input type="text"
                 id="css_text_dimmer"
                 name="css_text_dimmer"
                 value="<?php echo htmlspecialchars(admin_val('css.text-dimmer', $current, $css_defaults)); ?>" />
          <span class="hint">rgba</span>
        </div>
      </div>

    </div><!-- /.admin-section appearance -->


    <!-- ── Section: Identity ─────────────────────────────────── -->
    <div class="admin-section">

      <div class="admin-section-title">Identity</div>
      <p class="admin-section-desc">
        Site name, tagline, and logo configuration. Changes take effect
        immediately — values are resolved from the database before
        <code>config.php</code> constants.
      </p>

      <div class="field-row">
        <div class="field">
          <label for="site_name">Site name</label>
          <input type="text"
                 id="site_name"
                 name="site_name"
                 value="<?php echo htmlspecialchars(admin_val('site.name', $current, $identity_defaults)); ?>" />
        </div>
        <div class="field">
          <label for="site_tagline">Tagline</label>
          <input type="text"
                 id="site_tagline"
                 name="site_tagline"
                 value="<?php echo htmlspecialchars(admin_val('site.tagline', $current, $identity_defaults)); ?>" />
        </div>
      </div>

      <hr class="field-divider" />

      <!-- Logo mode -->
      <div class="field-row">
        <div class="field">
          <label for="site_logo_mode">Logo mode</label>
          <select id="site_logo_mode" name="site_logo_mode">
            <?php
            $logo_mode_current = admin_val('site.logo_mode', $current, $identity_defaults);
            $logo_modes = [
                'page_title' => 'Page title (text)',
                'logo'       => 'Logo image only',
                'wordmark'   => 'Wordmark (site name) only',
                'both'       => 'Logo + wordmark',
            ];
            foreach ($logo_modes as $val => $label) {
                $sel = ($logo_mode_current === $val) ? ' selected' : '';
                echo '<option value="' . $val . '"' . $sel . '>'
                   . htmlspecialchars($label)
                   . '</option>';
            }
            ?>
          </select>
        </div>
      </div>

      <div class="field-row">
        <div class="field">
          <label for="site_logo_file">Logo file path</label>
          <input type="text"
                 id="site_logo_file"
                 name="site_logo_file"
                 value="<?php echo htmlspecialchars(admin_val('site.logo_file', $current, $identity_defaults)); ?>" />
          <span class="hint">relative to web root, e.g. /assets/img/logo.png</span>
        </div>
        <div class="field">
          <label for="site_logo_alt">Logo alt text</label>
          <input type="text"
                 id="site_logo_alt"
                 name="site_logo_alt"
                 value="<?php echo htmlspecialchars(admin_val('site.logo_alt', $current, $identity_defaults)); ?>" />
          <span class="hint">defaults to site name if blank</span>
        </div>
      </div>

    </div><!-- /.admin-section identity -->


    <!-- ── Save ──────────────────────────────────────────────── -->
    <div class="save-bar">
      <button type="submit" class="btn-save">Save settings</button>
    </div>

  </form>

</div><!-- /.admin-wrap -->
</div><!-- /.main-content -->
</main>


<script>
  function syncPicker(textInput, pickerId) {
    var val = textInput.value.trim();
    if (/^#[0-9a-fA-F]{3}$|^#[0-9a-fA-F]{6}$/.test(val)) {
      document.getElementById(pickerId).value = val;
    }
  }
</script>


<?php include ABIDE_CORE . '/footer.php'; ?>
