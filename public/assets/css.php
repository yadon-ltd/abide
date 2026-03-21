<?php
/*
  public/assets/css.php — Dynamic CSS custom property overrides
  ──────────────────────────────────────────────────────────────
  Outputs a :root {} block containing only the CSS custom properties
  that have been saved via the admin config page.

  Loaded in core/header.php AFTER public/style.css, so these values
  override the defaults defined there. If no overrides are saved,
  this file outputs an empty comment and the site uses the defaults.

  This file is intentionally tiny. All default values live in
  public/style.css. This file only carries the deltas.

  The settings module (modules/settings/settings.php) provides the
  settings_get_css() function used here. It is loaded by core/init.php
  when AUTH_ENABLED = true and the DB is configured.
  ──────────────────────────────────────────────────────────────
*/

// Output CSS content type before any whitespace
header('Content-Type: text/css; charset=utf-8');

// Cache for 5 minutes — long enough to avoid per-request DB hits
// during normal browsing, short enough to pick up admin changes quickly.
header('Cache-Control: public, max-age=300');

// If the settings function is not available (DB off, or module not loaded),
// output nothing and exit cleanly. The site falls back to style.css defaults.
if (!function_exists('settings_get_css')) {
    echo '/* Abide: settings module not loaded — using style.css defaults */';
    exit;
}

// Fetch all saved CSS overrides from the database
$overrides = settings_get_css();

// If nothing has been customised yet, output nothing
if (empty($overrides)) {
    echo '/* Abide: no CSS overrides saved — using style.css defaults */';
    exit;
}

// Output the :root block with only the saved overrides
echo ':root {' . PHP_EOL;
foreach ($overrides as $property => $value) {
    // Sanitise both the property name and the value.
    // Property names come from our own key map, but sanitise anyway.
    // Values come from the database (originally from the admin form).
    $safe_prop  = preg_replace('/[^a-z0-9\-]/', '', $property);  // --accent, --bg, etc.
    $safe_value = str_replace(['"', "'", ';', '{', '}'], '', $value);

    if ($safe_prop !== '' && $safe_value !== '') {
        echo '  ' . $safe_prop . ': ' . $safe_value . ';' . PHP_EOL;
    }
}
echo '}' . PHP_EOL;
