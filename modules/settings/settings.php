<?php
/*
  modules/settings/settings.php — Site settings store
  ──────────────────────────────────────────────────────────────
  Provides a simple key/value settings store backed by the
  `settings` database table.

  Loaded automatically by core/init.php when AUTH_ENABLED = true
  and the DB is configured. You should not need to include it
  manually in most cases.

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

  Functions:
    settings_get($key, $default)   — fetch a single value
    settings_set($key, $value)     — write a single value (upsert)
    settings_get_all()             — fetch all settings as key→value array
    settings_get_css()             — fetch only css.* keys, mapped to --property names

  Schema:
    See modules/settings/schema.sql
  ──────────────────────────────────────────────────────────────
*/

// Block direct browser access
if (basename($_SERVER['PHP_SELF']) === 'settings.php') {
    http_response_code(403);
    exit('Forbidden');
}


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
        // This prevents fatal errors before the schema is imported.
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
            $prop        = '--' . substr($row['setting_key'], 4);
            $out[$prop]  = $row['setting_value'];
        }
        return $out;
    } catch (PDOException $e) {
        return [];
    }
}
