<?php
/*
  modules/auth/auth.php — Authentication functions
  ──────────────────────────────────────────────────────────────
  Include on any page that needs auth context.

  core/init.php loads this automatically when AUTH_ENABLED = true.
  You should not need to include it manually in most cases.

  Provides:
    auth_session_start()           — start session with secure settings
    auth_user()                    — current user array or null
    auth_require_login()           — redirect to login if not authenticated
    auth_require_permission()      — redirect if missing a permission bit
    auth_login()                   — validate credentials, create session
    auth_logout()                  — destroy session
    auth_register()                — create account, send verification email
    auth_send_verify_email()       — dispatch verification email (PHPMailer)
    auth_verify_email()            — redeem a verification token
    auth_regenerate_verify()       — resend a fresh verification email
    auth_request_password_reset()  — generate reset token, send reset email
    auth_send_reset_email()        — dispatch password reset email (PHPMailer)
    auth_check_reset_token()       — validate a reset token without consuming it
    auth_reset_password()          — redeem reset token, update password

  Permission constants (bit flags — keep in sync with schema.sql):
    PERM_NODE     = 1   active subscriber
    PERM_BACKBONE = 2   site administrator
    PERM_HEADEND  = 4   owner / full access

  PHPMailer:
    Requires three files in modules/auth/phpmailer/src/:
      Exception.php  |  PHPMailer.php  |  SMTP.php
    Download from: https://github.com/PHPMailer/PHPMailer/releases
    SMTP credentials are read from config.php constants.

  Dependencies:
    config.php   — SITE_NAME, SITE_URL, SMTP_* constants
    core/db.php  — db_connect()
  ──────────────────────────────────────────────────────────────
*/

// Block direct browser access
if (basename($_SERVER['PHP_SELF']) === 'auth.php') {
    http_response_code(403);
    exit('Forbidden');
}

// Load config if not already loaded (handles edge cases outside init.php)
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../config.php';
}

// Load database connection
require_once __DIR__ . '/../../core/db.php';

// ── PHPMailer ─────────────────────────────────────────────────
// Three required source files. Paths are relative to this file
// so they resolve correctly regardless of which page triggers
// the include chain.
require_once __DIR__ . '/phpmailer/src/Exception.php';
require_once __DIR__ . '/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as PHPMailerException;


// ── Permission constants ──────────────────────────────────────
// Integer bit flags for the `permissions` column in the users table.
// Combine with bitwise OR:
//   $permissions = PERM_NODE | PERM_BACKBONE;  // administrator = 3
// Check with bitwise AND:
//   if ($user['permissions'] & PERM_NODE) { ... }
define('PERM_NODE',     1);   // active subscriber
define('PERM_BACKBONE', 2);   // administrator
define('PERM_HEADEND',  4);   // owner / full access


// ── Session ───────────────────────────────────────────────────

/**
 * Start the session with secure settings.
 * Call once near the top of any page that needs auth.
 * Idempotent — checks session status before calling session_start().
 */
function auth_session_start(): void {
    if (session_status() !== PHP_SESSION_NONE) {
        return; // already started
    }

    session_set_cookie_params([
        'lifetime' => 0,        // browser-session cookie (expires on close)
        'path'     => '/',
        'domain'   => '',       // current domain
        'secure'   => true,     // HTTPS only
        'httponly' => true,     // inaccessible to JavaScript
        'samesite' => 'Lax',    // CSRF protection
    ]);

    session_start();
}


// ── Current user ─────────────────────────────────────────────

/**
 * Returns the current user row (associative array) or null.
 *
 * Re-fetches from the database on every call so that permission
 * changes take effect immediately without requiring re-login.
 * The query is intentionally minimal — only the columns needed
 * for auth checks.
 *
 * @return array|null  User row, or null if not authenticated
 */
function auth_user(): ?array {
    auth_session_start();

    if (empty($_SESSION['user_id'])) {
        return null;
    }

    $pdo  = db_connect();
    $stmt = $pdo->prepare(
        'SELECT id, email, role, permissions, email_verified, last_login, created_at
           FROM users
          WHERE id = ?
          LIMIT 1'
    );
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        // User was deleted since session was issued — clear the stale session
        auth_logout();
        return null;
    }

    return $user;
}


/**
 * Redirect to login if the visitor is not authenticated.
 *
 * @param string $redirect_after  URL to return to after successful login
 */
function auth_require_login(string $redirect_after = '/'): void {
    if (auth_user() === null) {
        $target = '/login?redirect=' . urlencode($redirect_after);
        header('Location: ' . $target);
        exit;
    }
}


/**
 * Redirect if the current user is missing a required permission bit.
 *
 * @param int    $perm         One of the PERM_* constants
 * @param string $redirect_to  Where to send users who lack the permission
 */
function auth_require_permission(int $perm, string $redirect_to = '/'): void {
    $user = auth_user();
    if ($user === null || !($user['permissions'] & $perm)) {
        header('Location: ' . $redirect_to);
        exit;
    }
}


// ── Login / logout ────────────────────────────────────────────

/**
 * Attempt to log in with email + password.
 *
 * On success: sets the session and updates last_login.
 * On failure: returns an error string.
 *
 * A dummy hash is verified even when the email doesn't exist so
 * that the response time is constant — no timing oracle for
 * enumerating valid addresses.
 *
 * @return array  ['ok' => true]
 *                ['ok' => false, 'error' => string]
 *                ['ok' => false, 'error' => string, 'unverified_email' => string]
 */
function auth_login(string $email, string $password): array {
    $email = trim(strtolower($email));

    if ($email === '' || $password === '') {
        return ['ok' => false, 'error' => 'Email and password are required.'];
    }

    $pdo  = db_connect();
    $stmt = $pdo->prepare(
        'SELECT id, email, password_hash, role, permissions, email_verified, remember_me
           FROM users
          WHERE email = ?
          LIMIT 1'
    );
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Always run password_verify — constant-time regardless of whether
    // the email exists. This prevents timing-based account enumeration.
    $dummy_hash = '$2y$12$aFc0aIgTa/e7VAxYe0vAOeN1OaIzxt47RLe04f96Fn6jdZMB6/4gm';
    $hash       = $user ? $user['password_hash'] : $dummy_hash;

    if (!password_verify($password, $hash) || !$user) {
        return ['ok' => false, 'error' => 'Invalid email or password.'];
    }

    if (!$user['email_verified']) {
        return [
            'ok'               => false,
            'error'            => 'Please verify your email before signing in.',
            'unverified_email' => $user['email'],
        ];
    }

    // Rotate session ID on privilege change (login) to prevent session fixation
    auth_session_start();
    session_regenerate_id(true);

    $_SESSION['user_id'] = $user['id'];

    // Issue a persistent token if the user has Remember Me enabled
    // and the feature is active in config.php
    if (defined('REMEMBER_ME_ENABLED') && REMEMBER_ME_ENABLED && !empty($user['remember_me'])) {
        auth_issue_token($user['id']);
    }

    // Record last login timestamp
    $upd = $pdo->prepare(
        'UPDATE users SET last_login = NOW(), updated_at = NOW() WHERE id = ?'
    );
    $upd->execute([$user['id']]);

    return ['ok' => true];
}


/**
 * Log out the current user and destroy the session.
 * Clears the session cookie in addition to destroying server-side data.
 */
function auth_logout(): void {
    auth_session_start();

    // Capture user ID before clearing the session — needed for token revocation
    $logout_user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

    $_SESSION = [];

    // Expire the session cookie in the browser
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(
            session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']
        );
    }

    session_destroy();

    // Revoke ALL persistent tokens for this user across all devices.
    // Paranoid by design — logout means logout everywhere.
    if ($logout_user_id !== null) {
        auth_revoke_all_tokens($logout_user_id);
    }
    auth_clear_token_cookie();
}


// ── Registration ─────────────────────────────────────────────

/**
 * Register a new user.
 *
 * Creates the user row with role='tap' and permissions=0,
 * then sends a verification email. If the email send fails,
 * the insert is rolled back so the user can try again cleanly.
 *
 * Registration can be disabled site-wide by setting
 * AUTH_REGISTRATION_OPEN = false in config.php.
 *
 * @return array  ['ok' => true]
 *                ['ok' => false, 'error' => string]
 */
function auth_register(string $email, string $password): array {
    // Check whether public registration is allowed
    if (defined('AUTH_REGISTRATION_OPEN') && !AUTH_REGISTRATION_OPEN) {
        return ['ok' => false, 'error' => 'Registration is not currently open.'];
    }

    $email = trim(strtolower($email));

    // ── Validation ────────────────────────────────────────────
    if ($email === '' || $password === '') {
        return ['ok' => false, 'error' => 'Email and password are required.'];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'Please enter a valid email address.'];
    }

    if (strlen($password) < 8) {
        return ['ok' => false, 'error' => 'Password must be at least 8 characters.'];
    }

    $pdo = db_connect();

    // ── Duplicate check ───────────────────────────────────────
    $check = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $check->execute([$email]);
    if ($check->fetch()) {
        return ['ok' => false, 'error' => 'An account with that email already exists.'];
    }

    // ── Create user ───────────────────────────────────────────
    $hash    = password_hash($password, PASSWORD_BCRYPT);
    $token   = bin2hex(random_bytes(32));            // 64-char hex token
    $expires = date('Y-m-d H:i:s', time() + 86400); // 24-hour TTL

    $ins = $pdo->prepare(
        'INSERT INTO users
            (email, password_hash, role, permissions,
             email_verified, email_verify_token, email_verify_expires,
             created_at, updated_at)
         VALUES (?, ?, \'tap\', 0, 0, ?, ?, NOW(), NOW())'
    );
    $ins->execute([$email, $hash, $token, $expires]);

    $user_id = (int)$pdo->lastInsertId();

    // ── Send verification email ───────────────────────────────
    $send = auth_send_verify_email($email, $token);
    if (!$send['ok']) {
        // Roll back the insert so the user can retry with a clean state
        $del = $pdo->prepare('DELETE FROM users WHERE id = ?');
        $del->execute([$user_id]);
        return ['ok' => false, 'error' => 'Could not send verification email. Please try again.'];
    }

    return ['ok' => true];
}


// ── Shared mailer ─────────────────────────────────────────────

/**
 * Send a plain-text email via PHPMailer/SMTP.
 *
 * All transactional email in this module routes through here.
 * SMTP credentials are read from constants in config.php.
 *
 * Port/encryption mapping:
 *   465 → SMTPS  (direct SSL)
 *   587 → STARTTLS
 *
 * @return array  ['ok' => true]
 *                ['ok' => false, 'error' => string]
 */
function auth_mailer_send(string $to_email, string $subject, string $body_text): array {
    $mail = new PHPMailer(true); // true = throw exceptions on error

    try {
        // ── Server settings ───────────────────────────────────
        $mail->isSMTP();
        $mail->Host     = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->Port     = SMTP_PORT;

        // Select encryption based on port number
        $mail->SMTPSecure = (SMTP_PORT === 465)
            ? PHPMailer::ENCRYPTION_SMTPS
            : PHPMailer::ENCRYPTION_STARTTLS;

        // ── Addresses ─────────────────────────────────────────
        $mail->setFrom(SMTP_FROM, SMTP_NAME);
        $mail->addAddress($to_email);

        // ── Content ───────────────────────────────────────────
        $mail->isHTML(false);       // plain text only
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $subject;
        $mail->Body    = $body_text;

        $mail->send();
        return ['ok' => true];

    } catch (PHPMailerException $e) {
        // Log the full diagnostic server-side; surface a generic message
        error_log('PHPMailer error [' . $to_email . ']: ' . $mail->ErrorInfo);
        return ['ok' => false, 'error' => 'Mail delivery failed: ' . $mail->ErrorInfo];
    }
}


// ── Email verification ────────────────────────────────────────

/**
 * Send an email verification link.
 *
 * Link format: SITE_URL/verify-email?token=<hex>
 *
 * @return array  ['ok' => true] | ['ok' => false, 'error' => string]
 */
function auth_send_verify_email(string $to_email, string $token): array {
    $site_name = defined('SITE_NAME') ? SITE_NAME : 'This site';
    $site_url  = defined('SITE_URL')  ? SITE_URL  : '';
    $link      = $site_url . '/verify-email?token=' . urlencode($token);
    $subject   = 'Verify your email address — ' . $site_name;

    $body = "Welcome to " . $site_name . ".\n\n"
          . "Click the link below to verify your email address and activate your account.\n"
          . "This link expires in 24 hours.\n\n"
          . $link . "\n\n"
          . "If you did not create an account, you can safely ignore this message.\n\n"
          . $site_name;

    return auth_mailer_send($to_email, $subject, $body);
}


/**
 * Redeem an email verification token.
 *
 * Marks the account verified and clears the token so it cannot
 * be reused. Checks expiry before accepting.
 *
 * @return array  ['ok' => true, 'email' => string]
 *                ['ok' => false, 'error' => string]
 */
function auth_verify_email(string $token): array {
    if ($token === '') {
        return ['ok' => false, 'error' => 'Missing verification token.'];
    }

    $pdo  = db_connect();
    $stmt = $pdo->prepare(
        'SELECT id, email, email_verified, email_verify_expires
           FROM users
          WHERE email_verify_token = ?
          LIMIT 1'
    );
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user) {
        return ['ok' => false, 'error' => 'Verification link is invalid or has already been used.'];
    }

    if ($user['email_verified']) {
        return ['ok' => false, 'error' => 'This email address has already been verified.'];
    }

    if (strtotime($user['email_verify_expires']) < time()) {
        return ['ok' => false, 'error' => 'Verification link has expired. Please request a new one.'];
    }

    // Mark verified, clear token
    $upd = $pdo->prepare(
        'UPDATE users
            SET email_verified       = 1,
                email_verify_token   = NULL,
                email_verify_expires = NULL,
                updated_at           = NOW()
          WHERE id = ?'
    );
    $upd->execute([$user['id']]);

    return ['ok' => true, 'email' => $user['email']];
}


/**
 * Issue a fresh verification token and resend the email.
 *
 * Used when a token has expired or the user never received it.
 * Returns ['ok' => true] regardless of whether the email exists
 * to prevent account enumeration.
 *
 * @return array  ['ok' => true] always (errors logged server-side)
 */
function auth_regenerate_verify(string $email): array {
    $email = trim(strtolower($email));

    $pdo  = db_connect();
    $stmt = $pdo->prepare(
        'SELECT id, email_verified FROM users WHERE email = ? LIMIT 1'
    );
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Return ok whether or not the email exists — no enumeration
    if (!$user || $user['email_verified']) {
        return ['ok' => true];
    }

    $token   = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', time() + 86400); // 24-hour TTL

    $upd = $pdo->prepare(
        'UPDATE users
            SET email_verify_token   = ?,
                email_verify_expires = ?,
                updated_at           = NOW()
          WHERE id = ?'
    );
    $upd->execute([$token, $expires, $user['id']]);

    auth_send_verify_email($email, $token); // best-effort; errors are logged

    return ['ok' => true];
}


// ── Password reset ────────────────────────────────────────────

/**
 * Request a password reset for the given email address.
 *
 * Generates a reset token with a 1-hour TTL, stores it on the
 * user row, and sends a reset link. Only works for verified
 * accounts (unverified accounts cannot reset via email).
 *
 * Always returns ['ok' => true] — prevents address enumeration.
 *
 * @return array  ['ok' => true] always
 */
function auth_request_password_reset(string $email): array {
    $email = trim(strtolower($email));

    // Invalid format — return ok silently
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => true];
    }

    $pdo  = db_connect();
    $stmt = $pdo->prepare(
        'SELECT id FROM users WHERE email = ? AND email_verified = 1 LIMIT 1'
    );
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // No verified account found — return ok silently
    if (!$user) {
        return ['ok' => true];
    }

    // Generate token — 1-hour TTL (tighter window than email verification)
    $token   = bin2hex(random_bytes(32));            // 64-char hex token
    $expires = date('Y-m-d H:i:s', time() + 3600);  // 1-hour TTL

    $upd = $pdo->prepare(
        'UPDATE users
            SET password_reset_token   = ?,
                password_reset_expires = ?,
                updated_at             = NOW()
          WHERE id = ?'
    );
    $upd->execute([$token, $expires, $user['id']]);

    auth_send_reset_email($email, $token); // best-effort; errors are logged

    return ['ok' => true];
}


/**
 * Send a password reset link.
 *
 * Link format: SITE_URL/reset-password?token=<hex>
 *
 * @return array  ['ok' => true] | ['ok' => false, 'error' => string]
 */
function auth_send_reset_email(string $to_email, string $token): array {
    $site_name = defined('SITE_NAME') ? SITE_NAME : 'This site';
    $site_url  = defined('SITE_URL')  ? SITE_URL  : '';
    $link      = $site_url . '/reset-password?token=' . urlencode($token);
    $subject   = 'Reset your password — ' . $site_name;

    $body = "You requested a password reset for your " . $site_name . " account.\n\n"
          . "Click the link below to choose a new password.\n"
          . "This link expires in 1 hour.\n\n"
          . $link . "\n\n"
          . "If you did not request a password reset, you can safely ignore this message.\n"
          . "Your password has not been changed.\n\n"
          . $site_name;

    return auth_mailer_send($to_email, $subject, $body);
}


/**
 * Validate a reset token without consuming it.
 *
 * Used on GET to verify the token before rendering the
 * new-password form. Does not modify any database state.
 *
 * @return array  ['ok' => true, 'user_id' => int]
 *                ['ok' => false, 'error' => string]
 */
function auth_check_reset_token(string $token): array {
    if ($token === '') {
        return ['ok' => false, 'error' => 'Missing reset token.'];
    }

    $pdo  = db_connect();
    $stmt = $pdo->prepare(
        'SELECT id, password_reset_expires
           FROM users
          WHERE password_reset_token = ?
          LIMIT 1'
    );
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user) {
        return ['ok' => false, 'error' => 'This reset link is invalid or has already been used.'];
    }

    if (strtotime($user['password_reset_expires']) < time()) {
        return ['ok' => false, 'error' => 'This reset link has expired. Please request a new one.'];
    }

    return ['ok' => true, 'user_id' => (int)$user['id']];
}


/**
 * Redeem a reset token and set a new password.
 *
 * Re-validates the token at submit time (it may have expired
 * between the GET and POST). Clears the token after use so it
 * cannot be reused.
 *
 * @return array  ['ok' => true]
 *                ['ok' => false, 'error' => string]
 */
function auth_reset_password(string $token, string $new_password): array {
    if ($token === '') {
        return ['ok' => false, 'error' => 'Missing reset token.'];
    }

    if (strlen($new_password) < 8) {
        return ['ok' => false, 'error' => 'Password must be at least 8 characters.'];
    }

    // Re-validate in case the token expired between GET and POST
    $check = auth_check_reset_token($token);
    if (!$check['ok']) {
        return $check;
    }

    $user_id = $check['user_id'];
    $hash    = password_hash($new_password, PASSWORD_BCRYPT);

    $pdo = db_connect();
    $upd = $pdo->prepare(
        'UPDATE users
            SET password_hash          = ?,
                password_reset_token   = NULL,
                password_reset_expires = NULL,
                updated_at             = NOW()
          WHERE id = ?'
    );
    $upd->execute([$hash, $user_id]);

    return ['ok' => true];
}


// ── Remember Me ───────────────────────────────────────────────
/*
  Persistent login token system.
  Requires: REMEMBER_ME_ENABLED = true in config.php
            modules/auth/user_tokens.sql imported into the database
            remember_me column on the users table (same schema file)

  Security model:
    • Raw tokens are never stored server-side — only SHA-256 hashes.
    • Tokens are rotated on every successful use (delete old, issue new).
    • On logout, ALL tokens for the user are revoked (all devices).
    • If a user disables Remember Me, all tokens are immediately revoked.
    • Paranoid reuse: if a token is presented that no longer exists in
      the DB (already rotated), the cookie is cleared. We cannot
      distinguish a stolen rotated token from a stale one in v1 —
      logged as a known gap.

  Cookie name: abide_remember
  Cookie TTL:  30 days
  Token value: 32 random bytes → 64-char hex string (stored hashed)
*/

// Cookie name used for the persistent login token
if (!defined('REMEMBER_ME_COOKIE')) define('REMEMBER_ME_COOKIE', 'abide_remember');

// Token lifetime in seconds (30 days)
if (!defined('REMEMBER_ME_TTL')) define('REMEMBER_ME_TTL', 30 * 24 * 60 * 60);


/**
 * Issue a persistent login token for the given user.
 *
 * Generates a cryptographically random token, stores its SHA-256
 * hash in the user_tokens table, and sets a 30-day cookie.
 * No-op if REMEMBER_ME_ENABLED is false.
 *
 * @param int $user_id  The authenticated user's ID
 */
function auth_issue_token(int $user_id): void {
    if (!defined('REMEMBER_ME_ENABLED') || !REMEMBER_ME_ENABLED) return;

    $raw_token  = bin2hex(random_bytes(32)); // 64-char hex
    $hash       = hash('sha256', $raw_token);
    $expires_at = date('Y-m-d H:i:s', time() + REMEMBER_ME_TTL);

    try {
        $pdo  = db_connect();
        $stmt = $pdo->prepare(
            'INSERT INTO user_tokens (user_id, token_hash, expires_at)
             VALUES (?, ?, ?)'
        );
        $stmt->execute([$user_id, $hash, $expires_at]);
    } catch (PDOException $e) {
        error_log('auth_issue_token error: ' . $e->getMessage());
        return; // Do not set the cookie if the DB write failed
    }

    setcookie(REMEMBER_ME_COOKIE, $raw_token, [
        'expires'  => time() + REMEMBER_ME_TTL,
        'path'     => '/',
        'secure'   => true,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}


/**
 * Check for a valid persistent login cookie and restore the session.
 *
 * Called from core/init.php when AUTH_ENABLED and REMEMBER_ME_ENABLED
 * are both true and no active session exists.
 *
 * On a valid token: rotates the token (delete + reissue), regenerates
 * the session ID, and sets $_SESSION['user_id'].
 *
 * On an invalid or missing token: clears the cookie and returns.
 */
function auth_check_token(): void {
    if (!defined('REMEMBER_ME_ENABLED') || !REMEMBER_ME_ENABLED) return;
    if (!isset($_COOKIE[REMEMBER_ME_COOKIE])) return;

    $raw_token = $_COOKIE[REMEMBER_ME_COOKIE];

    // Basic sanity check — token should be 64 hex chars
    if (!ctype_xdigit($raw_token) || strlen($raw_token) !== 64) {
        auth_clear_token_cookie();
        return;
    }

    $hash = hash('sha256', $raw_token);

    try {
        $pdo  = db_connect();
        $stmt = $pdo->prepare(
            'SELECT t.id, t.user_id, t.expires_at,
                    u.remember_me
               FROM user_tokens t
               JOIN users u ON u.id = t.user_id
              WHERE t.token_hash = ?
              LIMIT 1'
        );
        $stmt->execute([$hash]);
        $row = $stmt->fetch();
    } catch (PDOException $e) {
        error_log('auth_check_token DB error: ' . $e->getMessage());
        return;
    }

    if (!$row) {
        // Token not found — expired, revoked, or already rotated
        auth_clear_token_cookie();
        return;
    }

    if (strtotime($row['expires_at']) < time()) {
        // Expired — clean up the DB row
        $pdo->prepare('DELETE FROM user_tokens WHERE id = ?')->execute([$row['id']]);
        auth_clear_token_cookie();
        return;
    }

    if (!$row['remember_me']) {
        // User has since disabled Remember Me — revoke everything
        auth_revoke_all_tokens($row['user_id']);
        auth_clear_token_cookie();
        return;
    }

    // ── Valid token — rotate ──────────────────────────────────
    // Delete the current token row and issue a fresh one.
    // This limits the useful window for a stolen cookie.
    $pdo->prepare('DELETE FROM user_tokens WHERE id = ?')->execute([$row['id']]);

    // Update last login
    $pdo->prepare(
        'UPDATE users SET last_login = NOW(), updated_at = NOW() WHERE id = ?'
    )->execute([$row['user_id']]);

    // Restore session
    auth_session_start();
    session_regenerate_id(true);
    $_SESSION['user_id'] = $row['user_id'];

    // Issue replacement token
    auth_issue_token($row['user_id']);
}


/**
 * Revoke a single token by ID.
 *
 * The user_id check prevents one user from revoking another's tokens.
 * Used from the profile page's per-token revoke button.
 *
 * @param int $token_id  Row ID from user_tokens
 * @param int $user_id   Must match the token's owner
 * @return bool          True if a row was deleted
 */
function auth_revoke_token(int $token_id, int $user_id): bool {
    try {
        $pdo  = db_connect();
        $stmt = $pdo->prepare(
            'DELETE FROM user_tokens WHERE id = ? AND user_id = ?'
        );
        $stmt->execute([$token_id, $user_id]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log('auth_revoke_token error: ' . $e->getMessage());
        return false;
    }
}


/**
 * Revoke ALL persistent tokens for a user.
 *
 * Called on logout (all devices) and when Remember Me is disabled.
 *
 * @param int $user_id
 */
function auth_revoke_all_tokens(int $user_id): void {
    try {
        $pdo  = db_connect();
        $stmt = $pdo->prepare('DELETE FROM user_tokens WHERE user_id = ?');
        $stmt->execute([$user_id]);
    } catch (PDOException $e) {
        error_log('auth_revoke_all_tokens error: ' . $e->getMessage());
    }
}


/**
 * Expire and remove the persistent login cookie from the browser.
 */
function auth_clear_token_cookie(): void {
    setcookie(REMEMBER_ME_COOKIE, '', [
        'expires'  => time() - 42000,
        'path'     => '/',
        'secure'   => true,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}


/**
 * Fetch all active tokens for a user, newest first.
 *
 * Used on the profile page to display the active sessions list.
 *
 * @param int $user_id
 * @return array  Array of rows: id, created_at, expires_at, last_used_at
 */
function auth_get_user_tokens(int $user_id): array {
    try {
        $pdo  = db_connect();
        $stmt = $pdo->prepare(
            'SELECT id, created_at, expires_at, last_used_at
               FROM user_tokens
              WHERE user_id = ?
              ORDER BY created_at DESC'
        );
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('auth_get_user_tokens error: ' . $e->getMessage());
        return [];
    }
}
