<?php
/*
  public/pages/login.php — User sign-in
  ──────────────────────────────────────────────────────────────
  Clean URL: /login  (routed by public/.htaccess)

  GET:  display sign-in form
  POST: validate credentials; on success redirect to ?redirect= target
        or site root; on failure re-display form with error

  ?resend=<email>  — trigger a fresh verification email for an
                     unverified account without requiring a separate page
  ──────────────────────────────────────────────────────────────
*/

// core/init.php is loaded automatically via auto_prepend_file.
// auth_session_start() was already called there; calling it here
// is safe because it is idempotent.
auth_session_start();

// Already signed in — nothing to do here
if (auth_user() !== null) {
    header('Location: /');
    exit;
}

// Accept only relative paths to block open-redirect attacks.
// Anything that starts with '//' (protocol-relative) is rejected.
$redirect_raw = $_GET['redirect'] ?? $_POST['redirect'] ?? '/';
$redirect     = (str_starts_with($redirect_raw, '/') && !str_starts_with($redirect_raw, '//'))
    ? $redirect_raw
    : '/';

$error            = '';
$unverified_email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = auth_login(
        trim($_POST['email']    ?? ''),
        trim($_POST['password'] ?? '')
    );

    if ($result['ok']) {
        header('Location: ' . $redirect);
        exit;
    }

    $error = $result['error'];

    if (!empty($result['unverified_email'])) {
        $unverified_email = $result['unverified_email'];
    }
}

// ?resend=<email> — resend verification without a dedicated page
$resend_sent = false;
if (isset($_GET['resend']) && $_GET['resend'] !== '') {
    auth_regenerate_verify(trim($_GET['resend']));
    $resend_sent = true;
}

// ── Output ────────────────────────────────────────────────────
$page_title = 'Sign in';
require_once __DIR__ . '/../../core/header.php';
?>

<div class="auth-wrap">
  <div class="auth-card">

    <h1>Sign in</h1>

    <?php if ($resend_sent): ?>
      <div class="auth-notice">
        Verification email sent. Check your inbox and spam folder.
      </div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
      <div class="auth-error">
        <?php echo htmlspecialchars($error); ?>
        <?php if ($unverified_email !== ''): ?>
          &nbsp;
          <a href="/login?resend=<?php echo urlencode($unverified_email); ?>">
            Resend verification email
          </a>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <form method="post" action="/login?redirect=<?php echo urlencode($redirect); ?>" novalidate>
      <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect); ?>">

      <div class="auth-field">
        <label for="email">Email</label>
        <input
          type="email"
          id="email"
          name="email"
          autocomplete="email"
          required
          value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
        >
      </div>

      <div class="auth-field">
        <label for="password">Password</label>
        <input
          type="password"
          id="password"
          name="password"
          autocomplete="current-password"
          required
        >
      </div>

      <button type="submit" class="auth-submit">Sign in</button>
    </form>

    <div class="auth-links">
      <a href="/forgot-password">Forgot password?</a>
      <?php if (!defined('AUTH_REGISTRATION_OPEN') || AUTH_REGISTRATION_OPEN): ?>
        <a href="/register">Create an account</a>
      <?php endif; ?>
    </div>

  </div>
</div>

<?php require_once __DIR__ . '/../../core/footer.php'; ?>
