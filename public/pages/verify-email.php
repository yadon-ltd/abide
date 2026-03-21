<?php
/*
  public/pages/verify-email.php — Email verification token handler
  ──────────────────────────────────────────────────────────────
  Clean URL: /verify-email  (routed by public/.htaccess)

  Accepts ?token=<hex> from the verification email link.
  Immediately redeems the token on load — no form required.

  Success: marks account verified, shows confirmation + sign-in link
  Failure: shows appropriate error with recovery links
  ──────────────────────────────────────────────────────────────
*/

// Redeem the token immediately — no form needed for this flow
$token  = trim($_GET['token'] ?? '');
$result = auth_verify_email($token);

// ── Output ────────────────────────────────────────────────────
$page_title = 'Email Verification';
require_once __DIR__ . '/../../core/header.php';
?>

<div class="auth-wrap">
  <div class="auth-card">

    <?php if ($result['ok']): ?>

      <!-- ── Verified ─────────────────────────────────────────── -->
      <h1>Email verified</h1>
      <p class="auth-sub">Account activated</p>

      <div class="auth-success">
        <strong>You're in.</strong>
        <?php echo htmlspecialchars($result['email']); ?> has been verified.
        Your account is active and ready to use.
      </div>

      <div class="auth-links">
        <a href="/login">Sign in to your account</a>
      </div>

    <?php else: ?>

      <!-- ── Error ────────────────────────────────────────────── -->
      <h1>Verification failed</h1>
      <p class="auth-sub">Something went wrong</p>

      <div class="auth-error">
        <?php echo htmlspecialchars($result['error']); ?>
      </div>

      <div class="auth-links">
        <a href="/login">Back to sign in</a>
        <?php if (!defined('AUTH_REGISTRATION_OPEN') || AUTH_REGISTRATION_OPEN): ?>
          <a href="/register">Create a new account</a>
        <?php endif; ?>
        <?php if (str_contains($result['error'], 'expired')): ?>
          <!-- Expired token — direct them to the resend flow via login -->
          <a href="/login">Sign in to request a new verification link</a>
        <?php endif; ?>
      </div>

    <?php endif; ?>

  </div>
</div>

<?php require_once __DIR__ . '/../../core/footer.php'; ?>
