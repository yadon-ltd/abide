# Abide

A flat PHP project scaffold with an optional self-contained auth module and a database-backed admin configuration page.  
No framework. No build step. No Google Fonts.

---

## What it provides

- Convention-based clean URL routing — no config file required
- Shared header, footer, and nav via `core/`
- CSS custom property system for consistent theming
- Database-backed admin configuration page — change colors and identity without touching code
- Setup wizard that writes `config.php` and locks itself
- Optional auth module with login, registration, email verification, and password reset

---

## File map

```
abide/
├── config.php              # Never committed — written by the setup wizard
├── config.example.php      # Safe committed template
├── core/
│   ├── init.php            # Auto-prepended before every request
│   ├── db.php              # PDO connection helper
│   ├── header.php          # Opens <html>, <head>, <body>, renders header
│   ├── footer.php          # Closes </body>, </html>, renders footer
│   └── nav.php             # Hamburger nav panel
├── modules/
│   ├── auth/
│   │   ├── auth.php        # All auth logic — functions only, no output
│   │   ├── auth.css        # Auth page styles (card, fields, buttons)
│   │   ├── schema.sql      # users table DDL + owner seed row
│   │   └── phpmailer/      # PHPMailer dependency (not included — see below)
│   │       └── src/
│   │           ├── Exception.php
│   │           ├── PHPMailer.php
│   │           └── SMTP.php
│   └── settings/
│       ├── settings.php    # DB-backed settings store + identity helpers
│       └── schema.sql      # settings table DDL
├── public/                 # Web root — point your document root here
│   ├── .htaccess           # Routing, security headers, bot blocking
│   ├── index.php           # Home page
│   ├── style.css           # Global stylesheet — all CSS custom properties live here
│   ├── 404.php             # Custom 404 error page
│   ├── assets/
│   │   ├── auth.css        # Copied from modules/auth/auth.css — served from web root
│   │   ├── css.php         # Dynamic CSS endpoint — serves DB token overrides
│   │   ├── fonts/
│   │   └── img/
│   └── pages/              # Convention-routed pages
│       ├── admin.php       # Site configuration — PERM_HEADEND only
│       ├── login.php
│       ├── register.php
│       ├── logout.php
│       ├── forgot-password.php
│       ├── reset-password.php
│       └── verify-email.php
└── setup/
    └── index.php           # Setup wizard — locks itself after first run
```

---

## Routing convention

Files in `public/pages/` get clean URLs automatically via `.htaccess`.  
`pages/about.php` is reachable at `/about`. No routing config required.

---

## Setup

### 1. Install PHPMailer

Required by the auth module for transactional email. Download from  
https://github.com/PHPMailer/PHPMailer/releases and place the three source  
files at:

```
modules/auth/phpmailer/src/Exception.php
modules/auth/phpmailer/src/PHPMailer.php
modules/auth/phpmailer/src/SMTP.php
```

Add `modules/auth/phpmailer/` to `.gitignore`. PHPMailer is LGPL 2.1 — compatible with Abide's GPL v3.

### 2. Run the setup wizard

Upload the project to your server with the document root temporarily pointing  
at the **project root** (the folder containing `setup/`, `core/`, `public/`, etc.)  
so the wizard is reachable at `/setup/`.

The wizard collects your site name, base URL, database credentials, and SMTP  
credentials, then writes `config.php` to the project root. It locks itself on  
completion — delete `config.php` to re-run it.

### 3. Switch the document root to public/

After the wizard runs, reconfigure your web server or hosting control panel  
to point the document root at `public/`. The setup wizard will then be outside  
the web root and structurally unreachable.

### 4. Update .htaccess

`public/.htaccess` uses `auto_prepend_file` to load `core/init.php` before  
every request. On shared hosting, relative paths often don't resolve correctly.  
Replace the default value with the absolute server path:

```
php_value auto_prepend_file /home/yourusername/abide/core/init.php
```

Find your absolute path in your hosting control panel or by checking the  
breadcrumb in File Manager.

### 5. Create the database

In your hosting control panel (e.g. cPanel → MySQL Databases):

- Create a new database
- Create a new user
- Grant the user full privileges on that database only

### 6. Import the settings schema

In phpMyAdmin, select your database and import `modules/settings/schema.sql`.  
This creates the `settings` table and enables the admin configuration page  
at `/admin`.

### 7. Prepare and import the auth schema

Before importing, edit `modules/auth/schema.sql` and replace the placeholder  
email and password hash in the seed INSERT with your real values.

Generate a bcrypt hash:
```bash
php -r "echo password_hash('YourPasswordHere', PASSWORD_BCRYPT);"
```

Import the file via phpMyAdmin. Add `modules/auth/schema.sql` to `.gitignore` —  
it contains real credentials and must never be committed.

### 8. Copy auth.css into the web root

`modules/auth/auth.css` lives above `public/` and cannot be served directly.  
Copy it to `public/assets/auth.css`. When `auth.css` is updated in `modules/auth/`,  
copy it again.

---

## Configuration reference

All baseline configuration lives in `config.php`, generated by the setup wizard.  
See `config.example.php` for a full template with comments.

Key constants:

```php
define('AUTH_ENABLED', true);    // loads modules/auth/auth.php
define('LOGO_MODE',    'both');  // 'page_title' | 'logo' | 'wordmark' | 'both'
define('LOGO_FILE',    '/assets/img/logo.png');
define('LOGO_ALT',     SITE_NAME);
```

Identity values (site name, tagline, logo) can also be managed via the admin  
configuration page at `/admin`. DB values take precedence over `config.php`  
constants when both are set.

---

## Admin configuration page

Located at `/admin`. Accessible to `PERM_HEADEND` users only.

Provides UI controls for:

- **Appearance** — CSS custom property overrides (accent color, backgrounds, borders, text). Changes are served by `/assets/css.php` and cached for 5 minutes.
- **Identity** — Site name, tagline, logo mode, logo file path, logo alt text. Changes take effect immediately.

---

## Permission constants

Defined in `auth.php`. Used as bit flags on the `permissions` column.

| Constant        | Value | Role      | Description         |
|-----------------|-------|-----------|---------------------|
| `PERM_NODE`     | 1     | node      | Active subscriber   |
| `PERM_BACKBONE` | 2     | backbone  | Administrator       |
| `PERM_HEADEND`  | 4     | headend   | Owner / full access |

Combine with bitwise OR:
```php
$permissions = PERM_NODE | PERM_BACKBONE;  // = 3 (administrator)
```

Check on a page:
```php
auth_require_permission(PERM_NODE);           // subscriber+
auth_require_permission(PERM_HEADEND, '/');   // owner only, redirect home
```

---

## Function reference

### Auth

| Function | Description |
|---|---|
| `auth_user()` | Current user array or null |
| `auth_require_login($redirect)` | Redirect to /login if not authenticated |
| `auth_require_permission($perm, $redirect)` | Redirect if missing permission bit |
| `auth_login($email, $password)` | Validate credentials, set session |
| `auth_logout()` | Destroy session and expire cookie |
| `auth_register($email, $password)` | Create account, send verification email |
| `auth_verify_email($token)` | Redeem verification token |
| `auth_regenerate_verify($email)` | Resend fresh verification email |
| `auth_request_password_reset($email)` | Generate reset token, send reset email |
| `auth_check_reset_token($token)` | Validate reset token without consuming it |
| `auth_reset_password($token, $new_password)` | Redeem reset token, update password |

### Settings

| Function | Description |
|---|---|
| `settings_get($key, $default)` | Fetch a single setting value |
| `settings_set($key, $value)` | Write or update a single setting |
| `settings_get_all()` | Fetch all settings as a key→value array |
| `settings_get_css()` | Fetch css.* keys mapped to CSS property names |
| `abide_site_name()` | Resolved site name (DB-first, constant fallback) |
| `abide_tagline()` | Resolved tagline |
| `abide_logo_mode()` | Resolved logo mode |
| `abide_logo_file()` | Resolved logo file path |
| `abide_logo_alt()` | Resolved logo alt text |

---

## Clean URLs

| URL | File |
|---|---|
| `/admin` | `public/pages/admin.php` |
| `/login` | `public/pages/login.php` |
| `/register` | `public/pages/register.php` |
| `/logout` | `public/pages/logout.php` |
| `/forgot-password` | `public/pages/forgot-password.php` |
| `/reset-password` | `public/pages/reset-password.php` |
| `/verify-email` | `public/pages/verify-email.php` |

---

## Known v1 gaps

- No CSRF tokens on auth forms
- No rate limiting on login or reset endpoints
- `auth.css` must be manually copied to `public/assets/` — it is not symlinked

These are documented here, not bugs.

---

## License

Abide is GPL v3. PHPMailer is LGPL 2.1 (compatible).  
Copyright Jim Yadon. CLA required before accepting outside contributions.  
See `CONTRIBUTING.md` and `CLA.md`.
