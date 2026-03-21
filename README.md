# Abide

A flat PHP project scaffold. Not a framework. Not a CMS. A disciplined starting point.

---

## Phase 1 — Foundation

Phase 1 establishes the complete skeleton: routing, bootstrap, database layer, config template, CSS tokens, header, footer, nav, home page, and an annotated example page.

---

## Project Structure

```
abide/
├── config.php              # Your credentials — NEVER commit this
├── config.example.php      # Safe-to-commit template
├── core/
│   ├── init.php            # Bootstrap (loaded by .htaccess auto_prepend_file)
│   ├── db.php              # PDO singleton — call db_connect()
│   ├── header.php          # Shared header — outputs <html> through <header>
│   ├── footer.php          # Shared footer — outputs <footer>, closes </body></html>
│   └── nav.php             # Hamburger nav panel + JS
├── public/                 # Web root — point your server here
│   ├── .htaccess           # Routing, security headers, bot blocking
│   ├── index.php           # Home page
│   ├── style.css           # All design tokens + shared styles
│   ├── pages/              # Convention-routed pages
│   │   └── example.php     # Annotated example — read this before building pages
│   └── assets/
│       ├── img/
│       └── fonts/
├── modules/
│   └── auth/               # Optional drop-in (Phase 2)
└── setup/                  # Guided setup wizard (future)
```

---

## Setup

**1. Copy the config template**
```bash
cp config.example.php config.php
```

**2. Edit `config.php`**
Fill in `SITE_NAME`, `SITE_URL`, and (if needed) database credentials. Leave `DB_NAME` empty to skip the database entirely.

**3. Point your web server at `public/`**
The web root is `public/`, not the project root. Set your virtual host `DocumentRoot` (Apache) or `root` (nginx) accordingly.

**4. Verify the `auto_prepend_file` path**
In `public/.htaccess`, the line:
```
php_value auto_prepend_file "../core/init.php"
```
assumes a relative path. On some shared hosts, you'll need an absolute path:
```
php_value auto_prepend_file /home/username/abide/core/init.php
```

**5. Visit `/example`**
The annotated example page confirms routing, styles, and (optionally) the database connection are working.

---

## Routing

Files in `public/pages/` get clean URLs automatically.

| File | URL |
|---|---|
| `public/pages/about.php` | `/about` |
| `public/pages/contact.php` | `/contact` |
| `public/pages/my-tool.php` | `/my-tool` |

No routing config file needed. Slugs are lowercase, hyphens and underscores allowed.

---

## Building a Page

Copy `public/pages/example.php` and follow the checklist at the top of that file:

```php
<?php
$page_title = 'My Page';
include ABIDE_CORE . '/header.php';
?>

<style>
  /* Page-specific styles here */
</style>

<main>
  <div class="main-content">
    <!-- Your content -->
  </div>
</main>

<?php include ABIDE_CORE . '/footer.php'; ?>
```

---

## Configuration

`config.php` is the only behavioral knob. `public/style.css` is the only visual knob. Nothing else.

**config.php controls:**
- Site identity (`SITE_NAME`, `SITE_URL`, `SITE_TAGLINE`)
- Database (`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`)
- SMTP / email (for the auth module)
- Feature flags (`AUTH_ENABLED`, `NAV_STYLE`)

**style.css controls:**
- Layout dimensions (`--header-height`, `--footer-height`, `--main-max-width`)
- Colour palette (`--bg`, `--bg2`, `--bg3`, `--accent`, `--text`, etc.)
- Typography (`--mono`, `--sans`)
- All shared component styles

---

## Layout Contract

The chrome never moves. The page is a three-zone flex column:

```
┌───────────────────────────────────┐  ← sticky header (--header-height)
│  ☰  Page Title                    │
├───────────────────────────────────┤
│                                   │
│  main  (fills remaining space,    │
│         scrolls internally)       │
│                                   │
├───────────────────────────────────┤  ← fixed footer (--footer-height)
│  Site · © Year · ip / browser     │
└───────────────────────────────────┘
```

---

## Database

`db_connect()` returns a PDO singleton. Call it anywhere after `init.php` has loaded (i.e., on any page). No `require_once` needed.

```php
$pdo  = db_connect();
$stmt = $pdo->prepare('SELECT * FROM items WHERE id = ?');
$stmt->execute([$id]);
$row  = $stmt->fetch();
```

Set `DB_NAME = ''` in `config.php` to disable the database entirely.

---

## Auth Module (Phase 2)

The auth module lives in `modules/auth/` and is loaded only when `AUTH_ENABLED = true` in `config.php`. It is a self-contained drop-in and does not affect pages that don't use it.

Permission flags (bit field):

| Constant | Value | Role |
|---|---|---|
| `PERM_NODE` | 1 | Active subscriber |
| `PERM_BACKBONE` | 2 | Administrator |
| `PERM_HEADEND` | 4 | Owner |

---

## License

GPL v3. Commercial license available. All copyright owned by Jim Yadon.  
PHPMailer dependency: LGPL 2.1 (compatible).  
CLA required before accepting outside contributions.
