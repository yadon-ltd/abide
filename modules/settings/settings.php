<?php
/*
  modules/settings/settings.php — Site settings store
  ──────────────────────────────────────────────────────────────
  Provides a simple key/value settings store backed by the
  `settings` database table.

  Loaded automatically by core/init.php when the DB is configured.
  You should not need to include it manually.

  Key naming convention (dotted namespaces):
    css.*     CSS custom property overrides  (e.g. css.accent)
    site.*    Site identity settings         (e.g. site.name)
    nav.*     Navigation settings            (e.g. nav.style)

  CSS key → CSS property mapping:
    css.accent       →  --accent
    css.accent-dim   →  --accent-dim
    css.accent-glow  →  --accent-glow
    css.bg           →  --bg
    css.bg2          →  --bg2
    css.bg3          →  --bg3
    css.border       →  --border
    css.border-hi    →  --border-hi
    css.text         →  --text
    css.text-dim     →  --text-dim
    css.text-dimmer  →  --text-dimmer

  Core functions:
    settings_get($key, $default)   — fetch a single value
    settings_set($key, $value)     — write a single value (upsert)
    settings_get_all()             — fetch all settings as key→value array
    settings_get_css()             — fetch only css.* keys, mapped to --property names

  Identity helpers (DB-first, config.php constant fallback):
    abide_site_name()   — resolved SITE_NAME
    abide_tagline()     — resolved SITE_TAGLINE
    abide_logo_mode()   — resolved LOGO_MODE
    abide_logo_file()   — resolved LOGO_FILE
    abide_logo_alt()    — resolved LOGO_ALT

  Schema:
    See modules/settings/schema.sql
  ──────────────────────────────────────────────────────────────
*/

// Block direct browser access
if (basename($_SERVER['PHP_SELF']) === 'settings.php') {
    http_response_code(403);
    exit('Forbidden');
}


// ── Core store functions ──────────────────────────────────────

/**
 * Fetch a single setting value from the database.
 *
 * @param string $key      The setting key (e.g. 'css.accent')
 * @param string $default  Value to return if the key does not exist
 * @return string
 */
function settings_get(string $key, string $default = ''): string {
    try {
        $pdo  = db_connect();
        $stmt = $pdo->prepare(
            'SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1'
        );
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        return $row ? (string) $row['setting_value'] : $default;
    } catch (PDOException $e) {
        // If the table doesn't exist yet, fail silently and return the default.
        return $default;
    }
}


/**
 * Write (or update) a single setting value.
 *
 * Uses INSERT ... ON DUPLICATE KEY UPDATE so callers don't need to
 * know whether the key already exists.
 *
 * @param string $key    The setting key
 * @param string $value  The value to store
 * @return bool          True on success, false on failure
 */
function settings_set(string $key, string $value): bool {
    try {
        $pdo  = db_connect();
        $stmt = $pdo->prepare(
            'INSERT INTO settings (setting_key, setting_value)
             VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value),
                                     updated_at    = CURRENT_TIMESTAMP'
        );
        $stmt->execute([$key, $value]);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}


/**
 * Fetch all settings as a flat key→value array.
 *
 * @return array  ['key' => 'value', ...]
 */
function settings_get_all(): array {
    try {
        $pdo  = db_connect();
        $stmt = $pdo->query(
            'SELECT setting_key, setting_value FROM settings ORDER BY setting_key'
        );
        $rows = $stmt->fetchAll();

        $out = [];
        foreach ($rows as $row) {
            $out[$row['setting_key']] = $row['setting_value'];
        }
        return $out;
    } catch (PDOException $e) {
        return [];
    }
}


/**
 * Fetch all css.* settings, returned as a CSS property→value array.
 *
 * Maps the dotted key format to CSS custom property names:
 *   'css.accent' → '--accent'
 *
 * Used by public/assets/css.php to build the :root {} override block.
 *
 * @return array  ['--accent' => '#ffb340', '--bg' => '#0c0b09', ...]
 */
function settings_get_css(): array {
    try {
        $pdo  = db_connect();
        $stmt = $pdo->prepare(
            "SELECT setting_key, setting_value
               FROM settings
              WHERE setting_key LIKE 'css.%'
                AND setting_value != ''"
        );
        $stmt->execute();
        $rows = $stmt->fetchAll();

        $out = [];
        foreach ($rows as $row) {
            // 'css.accent' → '--accent'
            $prop       = '--' . substr($row['setting_key'], 4);
            $out[$prop] = $row['setting_value'];
        }
        return $out;
    } catch (PDOException $e) {
        return [];
    }
}


// ── Identity helpers ──────────────────────────────────────────
/*
  Each helper checks the settings table first. If the DB value is
  non-empty, it wins. If it's empty or the DB is unavailable, the
  helper falls back to the corresponding config.php constant, then
  to a hardcoded default of last resort.

  This is the DB-first, config-fallback pattern that allows the
  admin config page to be the live source of truth for identity
  values without requiring config.php to be edited after setup.
*/

/**
 * Resolved site name.
 * DB: site.name  →  constant: SITE_NAME  →  default: ''
 */
function abide_site_name(): string {
    $db = settings_get('site.name');
    if ($db !== '') return $db;
    return defined('SITE_NAME') ? SITE_NAME : '';
}

/**
 * Resolved site tagline.
 * DB: site.tagline  →  constant: SITE_TAGLINE  →  default: ''
 */
function abide_tagline(): string {
    $db = settings_get('site.tagline');
    if ($db !== '') return $db;
    return defined('SITE_TAGLINE') ? SITE_TAGLINE : '';
}

/**
 * Resolved logo mode.
 * DB: site.logo_mode  →  constant: LOGO_MODE  →  default: 'page_title'
 */
function abide_logo_mode(): string {
    $db = settings_get('site.logo_mode');
    if ($db !== '') return $db;
    return defined('LOGO_MODE') ? LOGO_MODE : 'page_title';
}

/**
 * Resolved logo file path (web-root-relative).
 * DB: site.logo_file  →  constant: LOGO_FILE  →  default: '/assets/img/logo.png'
 */
function abide_logo_file(): string {
    $db = settings_get('site.logo_file');
    if ($db !== '') return $db;
    return defined('LOGO_FILE') ? LOGO_FILE : '/assets/img/logo.png';
}

/**
 * Resolved logo alt text.
 * DB: site.logo_alt  →  constant: LOGO_ALT  →  fallback: abide_site_name()
 */
function abide_logo_alt(): string {
    $db = settings_get('site.logo_alt');
    if ($db !== '') return $db;
    if (defined('LOGO_ALT') && LOGO_ALT !== '') return LOGO_ALT;
    return abide_site_name();
}
