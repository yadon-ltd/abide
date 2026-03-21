<?php
/*
  public/pages/forgot-password.php — Password reset request
  ──────────────────────────────────────────────────────────────
  Clean URL: /forgot-password  (routed by public/.htaccess)

  GET:  display the request form
  POST: accept email, generate reset token, send reset link

  Always shows a generic confirmation after POST regardless of
  whether the email matched an account — prevents enumeration.
  ──────────────────────────────────────────────────────────────
*/

auth_session_start();

// Already signed in — password reset isn't needed
if (auth_user() !== null) {
    header('Location: /');
    exit;
}

$submitted = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    auth_request_password_reset($email); // always returns ok; errors logged server-side
    $submitted = true;
}

// ── Output ────────────────────────────────────────────────────
$page_title = 'Forgot Password';
require_once __DIR__ . '/../../core/header.php';
?>

<div class="auth-wrap">
  <div class="auth-card">

    <?php if ($submitted): ?>

      <!-- ── Confirmation state ───────────────────────────────────
           Generic message regardless of whether the email matched.
           Prevents account enumeration.
      ─────────────────────────────────────────────────────────── -->
      <h1>Check your email</h1>

      <div class="auth-notice">
        <strong>Reset link sent.</strong>
        If that address is associated with a verified account, you'll
        receive a password reset link shortly. Check your spam folder
        if it doesn't arrive within a few minutes.
      </div>

      <div class="auth-links">
        <a href="/login">Back to sign in</a>
      </div>

    <?php else: ?>

      <!-- ── Request form ─────────────────────────────────────── -->
      <h1>Reset password</h1>
      <p class="auth-sub">
        Enter the email address on your account and we'll send you a reset link.
      </p>

      <form method="post" action="/forgot-password" novalidate>

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

        <button type="submit" class="auth-submit">Send reset link</button>

      </form>

      <div class="auth-links">
        <a href="/login">Back to sign in</a>
        <?php if (!defined('AUTH_REGISTRATION_OPEN') || AUTH_REGISTRATION_OPEN): ?>
          <a href="/register">Create an account</a>
        <?php endif; ?>
      </div>

    <?php endif; ?>

  </div>
</div>

<?php require_once __DIR__ . '/../../core/footer.php'; ?>
