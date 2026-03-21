<?php
/*
  public/pages/reset-password.php — Password reset token handler
  ──────────────────────────────────────────────────────────────
  Clean URL: /reset-password  (routed by public/.htaccess)

  Accepts ?token=<hex> from the reset email link.

  GET:  validate token — show new-password form if valid,
        error state if invalid or expired
  POST: validate token again (may expire between GET and POST),
        update password, show success state

  Three rendering states: 'form' | 'success' | 'error'
  ──────────────────────────────────────────────────────────────
*/

auth_session_start();

// Already signed in — no reset needed
if (auth_user() !== null) {
    header('Location: /');
    exit;
}

$token = trim($_GET['token'] ?? '');
$state = 'form';    // form | success | error
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Token comes from the hidden field, not the query string
    $token        = trim($_POST['token']    ?? '');
    $new_password = $_POST['password']      ?? '';
    $confirm      = $_POST['confirm']       ?? '';

    if ($new_password !== $confirm) {
        $error = 'Passwords do not match.';
        $state = 'form';
    } else {
        $result = auth_reset_password($token, $new_password);

        if ($result['ok']) {
            $state = 'success';
        } else {
            $error = $result['error'];
            $state = 'error';
        }
    }

} else {
    // GET — validate token before rendering the form
    $check = auth_check_reset_token($token);

    if (!$check['ok']) {
        $error = $check['error'];
        $state = 'error';
    }
    // If valid: $state stays 'form', $token is already set
}

// ── Output ────────────────────────────────────────────────────
$page_title = 'Reset Password';
require_once __DIR__ . '/../../core/header.php';
?>

<div class="auth-wrap">
  <div class="auth-card">

    <?php if ($state === 'success'): ?>

      <!-- ── Success ──────────────────────────────────────────── -->
      <h1>Password updated</h1>

      <div class="auth-success">
        <strong>All set.</strong>
        Your password has been changed. You can now sign in with your new credentials.
      </div>

      <div class="auth-links">
        <a href="/login">Sign in</a>
      </div>


    <?php elseif ($state === 'error'): ?>

      <!-- ── Error (invalid or expired token) ────────────────── -->
      <h1>Link unavailable</h1>

      <div class="auth-error">
        <?php echo htmlspecialchars($error); ?>
      </div>

      <div class="auth-links">
        <a href="/forgot-password">Request a new reset link</a>
        <a href="/login">Back to sign in</a>
      </div>


    <?php else: ?>

      <!-- ── New password form ──────────────────────────────── -->
      <h1>Choose a new password</h1>

      <?php if ($error !== ''): ?>
        <div class="auth-error"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>

      <form method="post" action="/reset-password" novalidate>

        <!-- Token carried as hidden field so it is available on POST -->
        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

        <div class="auth-field">
          <label for="password">New password</label>
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
          <label for="confirm">Confirm new password</label>
          <input
            type="password"
            id="confirm"
            name="confirm"
            autocomplete="new-password"
            required
          >
        </div>

        <button type="submit" class="auth-submit">Set new password</button>

      </form>

      <div class="auth-links">
        <a href="/login">Back to sign in</a>
      </div>

    <?php endif; ?>

  </div>
</div>

<?php require_once __DIR__ . '/../../core/footer.php'; ?>
