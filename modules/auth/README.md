# Abide — Auth Module

Self-contained, optional authentication drop-in for Abide projects.  
Located at `modules/auth/`. Enable with one line in `config.php`.

---

## What it provides

- Session management with secure cookie settings
- Registration with email verification (24-hour token)
- Sign-in with constant-time credential checks
- Password reset via email (1-hour token)
- Permission-based access control using bit flags
- PHPMailer integration for all transactional email

---

## File map

```
modules/auth/
├── auth.php          Function library — all auth logic lives here
├── auth.css          Shared card/form styles for all auth pages
├── schema.sql        users table DDL + owner seed row
└── phpmailer/        PHPMailer dependency (not included — see below)
    └── src/
        ├── Exception.php
        ├── PHPMailer.php
        └── SMTP.php

public/pages/
├── login.php
├── register.php
├── logout.php
├── forgot-password.php
├── reset-password.php
└── verify-email.php
```

---

## Setup

### 1. Install PHPMailer

Download from https://github.com/PHPMailer/PHPMailer/releases and place
the three source files at:

```
modules/auth/phpmailer/src/Exception.php
modules/auth/phpmailer/src/PHPMailer.php
modules/auth/phpmailer/src/SMTP.php
```

PHPMailer is licensed LGPL 2.1 — compatible with Abide's GPL v3.

### 2. Create the database

Run `modules/auth/schema.sql` against your database.  
Replace the placeholder email and password hash in the seed INSERT before running.

Generate a bcrypt hash:
```bash
php -r "echo password_hash('YourPasswordHere', PASSWORD_BCRYPT);"
```

### 3. Configure

In `config.php`:

```php
// Enable the module
define('AUTH_ENABLED',           true);
define('AUTH_REGISTRATION_OPEN', true);  // false to disable public sign-up

// Database (must match your DB)
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');

// SMTP (required for verification and reset emails)
define('SMTP_HOST', 'mail.example.com');
define('SMTP_USER', 'noreply@example.com');
define('SMTP_PASS', 'your_smtp_password');
define('SMTP_PORT', 465);   // 465 = SSL, 587 = TLS
define('SMTP_FROM', 'noreply@example.com');
define('SMTP_NAME', 'My Site');

// Used in email body links
define('SITE_URL',  'https://example.com');
define('SITE_NAME', 'My Site');
```

### 4. Include auth styles

Either import into your main stylesheet:
```css
@import '../modules/auth/auth.css';
```

Or link directly on auth pages — the pages themselves don't inline styles;
they rely on this file.

### 5. Update .htaccess path

In `public/.htaccess`, set the correct absolute path:
```
php_value auto_prepend_file /home/youruser/public_html/core/init.php
```

---

## Permission constants

Defined in `auth.php`. Used as bit flags on the `permissions` column.

| Constant       | Value | Role slug  | Description         |
|----------------|-------|------------|---------------------|
| `PERM_NODE`    | 1     | node       | Active subscriber   |
| `PERM_BACKBONE`| 2     | backbone   | Administrator       |
| `PERM_HEADEND` | 4     | headend    | Owner / full access |

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

| Function | Description |
|---|---|
| `auth_session_start()` | Start session with secure cookie settings (idempotent) |
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

---

## Clean URLs

Auth pages are routed by `public/.htaccess`:

| URL | File |
|---|---|
| `/login` | `public/pages/login.php` |
| `/register` | `public/pages/register.php` |
| `/logout` | `public/pages/logout.php` |
| `/forgot-password` | `public/pages/forgot-password.php` |
| `/reset-password` | `public/pages/reset-password.php` |
| `/verify-email` | `public/pages/verify-email.php` |

---

## License

Abide is GPL v3. PHPMailer is LGPL 2.1 (compatible).  
Copyright Jim Yadon. CLA required before accepting outside contributions.
