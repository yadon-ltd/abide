<?php
/*
  public/pages/register.php — New account registration
  ──────────────────────────────────────────────────────────────
  Clean URL: /register  (routed by public/.htaccess)

  GET:  display registration form (or 403 if registration is closed)
  POST: create account + send verification email
        success → show "check your email" confirmation state
        failure → re-display form with error message
  ──────────────────────────────────────────────────────────────
*/

auth_session_start();

// Already signed in
if (auth_user() !== null) {
    header('Location: /');
    exit;
}

// Hard stop if registration is disabled
if (defined('AUTH_REGISTRATION_OPEN') && !AUTH_REGISTRATION_OPEN) {
    http_response_code(403);
    $page_title = 'Registration Closed';
    require_once __DIR__ . '/../../core/header.php';
    echo '<p class="auth-error">Registration is not currently open.</p>';
    require_once __DIR__ . '/../../core/footer.php';
    exit;
}

$error     = '';
$success   = false;
$reg_email = '';  // retained for display in the success state

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email     = trim($_POST['email']    ?? '');
    $password  = trim($_POST['password'] ?? '');
    $confirm   = trim($_POST['confirm']  ?? '');
    $reg_email = $email;

    if ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $result = auth_register($email, $password);

        if ($result['ok']) {
            $success = true;
        } else {
            $error = $result['error'];
        }
    }
}

// ── Output ────────────────────────────────────────────────────
$page_title = 'Create Account';
require_once __DIR__ . '/../../core/header.php';
?>

<div class="auth-wrap">
  <div class="auth-card">

    <?php if ($success): ?>

      <!-- ── Success state ────────────────────────────────────── -->
      <h1>Check your email</h1>

      <div class="auth-success">
        <strong>Verification email sent.</strong>
        We sent a link to <strong><?php echo htmlspecialchars($reg_email); ?></strong>.
        Click it to activate your account. The link expires in 24 hours.
      </div>

      <div class="auth-links">
        <a href="/login">Back to sign in</a>
        <a href="/login?resend=<?php echo urlencode($reg_email); ?>">Resend verification email</a>
      </div>

    <?php else: ?>

      <!-- ── Registration form ────────────────────────────────── -->
      <h1>Create account</h1>

      <?php if ($error !== ''): ?>
        <div class="auth-error"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>

      <form method="post" action="/register" novalidate>

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
            autocomplete="new-password"
            required
            minlength="8"
          >
          <span class="auth-hint">Minimum 8 characters</span>
        </div>

        <div class="auth-field">
          <label for="confirm">Confirm password</label>
          <input
            type="password"
            id="confirm"
            name="confirm"
            autocomplete="new-password"
            required
          >
        </div>

        <button type="submit" class="auth-submit">Create account</button>

      </form>

      <div class="auth-links">
        <a href="/login">Already have an account? Sign in</a>
      </div>

    <?php endif; ?>

  </div>
</div>

<?php require_once __DIR__ . '/../../core/footer.php'; ?>
