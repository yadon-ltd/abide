<?php
/*
  public/pages/profile.php — User profile
  ──────────────────────────────────────────────────────────────
  URL: yourdomain.com/profile

  Access: any authenticated user (all permission tiers).

  Sections:
    1. Account     — email, role, member since
    2. Remember Me — toggle persistent login on/off (shown only
                     when REMEMBER_ME_ENABLED = true in config.php)
    3. Sessions    — active token list with per-token revoke +
                     revoke-all button (shown when Remember Me is on)

  POST actions (field: _action):
    toggle_remember_me  — flip remember_me preference on/off
    revoke_token        — revoke a single token by ID
    revoke_all          — revoke all tokens for this user

  Note: When Remember Me is toggled OFF, all tokens are revoked
  immediately and the user is notified that this clears all devices.
  ──────────────────────────────────────────────────────────────
*/

// Gate: must be logged in
auth_require_login('/profile');

$user = auth_user();

// ── Handle POST ───────────────────────────────────────────────
$flash_type = '';
$flash_msg  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim($_POST['_action'] ?? '');

    if ($action === 'toggle_remember_me') {
        // Flip the preference
        $new_val = empty($_POST['remember_me']) ? 0 : 1;

        $pdo = db_connect();
        $pdo->prepare(
            'UPDATE users SET remember_me = ?, updated_at = NOW() WHERE id = ?'
        )->execute([$new_val, $user['id']]);

        if ($new_val === 0) {
            // Turning off — revoke all tokens and clear cookie
            auth_revoke_all_tokens($user['id']);
            auth_clear_token_cookie();
            $flash_type = 'ok';
            $flash_msg  = 'Remember Me disabled. All remembered devices have been signed out.';
        } else {
            // Turning on — issue a token for the current session
            auth_issue_token($user['id']);
            $flash_type = 'ok';
            $flash_msg  = 'Remember Me enabled. This device will stay signed in for 30 days.';
        }

    } elseif ($action === 'revoke_token') {
        $token_id = (int)($_POST['token_id'] ?? 0);
        if ($token_id > 0) {
            $revoked = auth_revoke_token($token_id, $user['id']);
            $flash_type = $revoked ? 'ok'  : 'bad';
            $flash_msg  = $revoked ? 'Session removed.' : 'Could not remove that session.';
        }

    } elseif ($action === 'revoke_all') {
        auth_revoke_all_tokens($user['id']);
        auth_clear_token_cookie();
        $flash_type = 'ok';
        $flash_msg  = 'All remembered sessions have been signed out.';
    }

    // PRG — redirect to avoid re-POST on refresh
    $encoded = urlencode($flash_type . ':' . $flash_msg);
    header('Location: /profile?msg=' . $encoded);
    exit;
}

// Pick up flash message from redirect
if (!empty($_GET['msg'])) {
    $parts = explode(':', urldecode($_GET['msg']), 2);
    if (count($parts) === 2) {
        [$flash_type, $flash_msg] = $parts;
    }
}

// Re-fetch user to get the current remember_me value
$user = auth_user();
$pdo  = db_connect();
$full_user = $pdo->prepare(
    'SELECT id, email, role, permissions, remember_me, created_at FROM users WHERE id = ? LIMIT 1'
);
$full_user->execute([$user['id']]);
$user = $full_user->fetch();

// Active tokens (only relevant if REMEMBER_ME_ENABLED)
$tokens = [];
if (defined('REMEMBER_ME_ENABLED') && REMEMBER_ME_ENABLED) {
    $tokens = auth_get_user_tokens($user['id']);
}

// Role display map
$role_labels = [
    'tap'  => 'Member',
    'node' => 'Node',
    'head' => 'Administrator',
];

// ── Page ──────────────────────────────────────────────────────
$page_title = 'Profile';
include ABIDE_CORE . '/header.php';
?>

<style>
  /* ── Profile page layout ─────────────────────────────────── */

  .profile-wrap {
    max-width: 640px;
    margin: 0 auto;
    animation: fadeUp 0.3s ease both;
  }

  .profile-section {
    background: var(--bg2);
    border: 1px solid var(--border);
    border-radius: 6px;
    padding: 1.75rem 2rem;
    margin-bottom: 1.5rem;
  }

  .profile-section-title {
    font-family: var(--mono);
    font-size: 0.68rem;
    letter-spacing: 0.2em;
    text-transform: uppercase;
    color: var(--accent-dim);
    margin-bottom: 1.25rem;
  }

  /* ── Account info rows ───────────────────────────────────── */
  .info-row {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    padding: 0.5rem 0;
    border-bottom: 1px solid var(--border);
    font-size: 0.8rem;
  }

  .info-row:last-child { border-bottom: none; }

  .info-label {
    font-family: var(--mono);
    font-size: 0.6rem;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: var(--text-dimmer);
  }

  .info-value {
    color: var(--text);
    font-family: var(--mono);
    font-size: 0.78rem;
  }

  /* ── Remember Me toggle ──────────────────────────────────── */
  .toggle-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1.5rem;
  }

  .toggle-desc {
    font-size: 0.8rem;
    color: var(--text-dim);
    line-height: 1.6;
  }

  .toggle-desc strong {
    color: var(--text);
    font-weight: 600;
  }

  .toggle-note {
    font-size: 0.68rem;
    color: var(--text-dimmer);
    margin-top: 0.4rem;
    font-style: italic;
  }

  /* Toggle switch */
  .toggle-switch {
    position: relative;
    display: inline-block;
    width: 2.6rem;
    height: 1.4rem;
    flex-shrink: 0;
  }

  .toggle-switch input { display: none; }

  .toggle-track {
    position: absolute;
    inset: 0;
    background: var(--bg3);
    border: 1px solid var(--border);
    border-radius: 1.4rem;
    cursor: pointer;
    transition: background 0.2s ease, border-color 0.2s ease;
  }

  .toggle-track::before {
    content: '';
    position: absolute;
    top: 0.15rem;
    left: 0.15rem;
    width: 1rem;
    height: 1rem;
    background: var(--text-dimmer);
    border-radius: 50%;
    transition: transform 0.2s ease, background 0.2s ease;
  }

  input:checked + .toggle-track {
    background: var(--accent-glow);
    border-color: var(--accent-dim);
  }

  input:checked + .toggle-track::before {
    transform: translateX(1.2rem);
    background: var(--accent);
  }

  /* ── Sessions list ───────────────────────────────────────── */
  .sessions-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
  }

  .sessions-title {
    font-family: var(--mono);
    font-size: 0.6rem;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: var(--text-dimmer);
  }

  .btn-revoke-all {
    background: none;
    border: 1px solid var(--border);
    border-radius: 3px;
    color: var(--text-dimmer);
    cursor: pointer;
    font-family: var(--mono);
    font-size: 0.58rem;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    padding: 0.3rem 0.75rem;
    transition: border-color 0.2s ease, color 0.2s ease;
  }

  .btn-revoke-all:hover {
    border-color: var(--bad, #f55);
    color: var(--bad, #f55);
  }

  .session-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.6rem 0;
    border-bottom: 1px solid var(--border);
    gap: 1rem;
  }

  .session-item:last-child { border-bottom: none; }

  .session-meta {
    font-size: 0.72rem;
    color: var(--text-dim);
    font-family: var(--mono);
  }

  .session-meta .session-date { color: var(--text); }

  .session-meta .session-sub {
    font-size: 0.6rem;
    color: var(--text-dimmer);
    margin-top: 0.1rem;
  }

  .btn-revoke {
    background: none;
    border: 1px solid var(--border);
    border-radius: 3px;
    color: var(--text-dimmer);
    cursor: pointer;
    font-family: var(--mono);
    font-size: 0.58rem;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    padding: 0.25rem 0.6rem;
    white-space: nowrap;
    flex-shrink: 0;
    transition: border-color 0.2s ease, color 0.2s ease;
  }

  .btn-revoke:hover {
    border-color: var(--bad, #f55);
    color: var(--bad, #f55);
  }

  .sessions-empty {
    font-size: 0.75rem;
    color: var(--text-dimmer);
    font-style: italic;
    padding: 0.5rem 0;
  }

  /* ── Flash message ───────────────────────────────────────── */
  .flash {
    padding: 0.7rem 1rem;
    border-radius: 3px;
    font-family: var(--mono);
    font-size: 0.72rem;
    letter-spacing: 0.06em;
    margin-bottom: 1.25rem;
  }

  .flash.ok  {
    background: rgba(68, 221, 136, 0.08);
    border: 1px solid rgba(68, 221, 136, 0.25);
    color: var(--ok, #4d4);
  }

  .flash.bad {
    background: rgba(255, 85, 85, 0.08);
    border: 1px solid rgba(255, 85, 85, 0.25);
    color: var(--bad, #f55);
  }
</style>


<main>
<div class="main-content">
<div class="profile-wrap">

  <?php if ($flash_msg): ?>
    <div class="flash <?php echo htmlspecialchars($flash_type); ?>">
      <?php echo htmlspecialchars($flash_msg); ?>
    </div>
  <?php endif; ?>


  <!-- ── Section: Account ─────────────────────────────────── -->
  <div class="profile-section">
    <div class="profile-section-title">Account</div>

    <div class="info-row">
      <span class="info-label">Email</span>
      <span class="info-value"><?php echo htmlspecialchars($user['email']); ?></span>
    </div>

    <div class="info-row">
      <span class="info-label">Role</span>
      <span class="info-value">
        <?php echo htmlspecialchars($role_labels[$user['role']] ?? ucfirst($user['role'])); ?>
      </span>
    </div>

    <div class="info-row">
      <span class="info-label">Member since</span>
      <span class="info-value">
        <?php echo htmlspecialchars(date('F j, Y', strtotime($user['created_at']))); ?>
      </span>
    </div>
  </div><!-- /.profile-section account -->


  <?php if (defined('REMEMBER_ME_ENABLED') && REMEMBER_ME_ENABLED): ?>

  <!-- ── Section: Remember Me ─────────────────────────────── -->
  <div class="profile-section">
    <div class="profile-section-title">Remember Me</div>

    <form method="post" action="/profile">
      <input type="hidden" name="_action" value="toggle_remember_me" />

      <div class="toggle-row">
        <div>
          <div class="toggle-desc">
            <strong>Stay signed in for 30 days</strong><br />
            When enabled, you'll be kept signed in on this device even after
            closing your browser. Each sign-in rotates the token for security.
          </div>
          <div class="toggle-note">
            Signing out always clears remembered sessions on all devices.
          </div>
        </div>

        <label class="toggle-switch" title="Toggle Remember Me">
          <input
            type="checkbox"
            name="remember_me"
            value="1"
            <?php echo $user['remember_me'] ? 'checked' : ''; ?>
            onchange="this.form.submit()"
          />
          <span class="toggle-track"></span>
        </label>
      </div>
    </form>
  </div><!-- /.profile-section remember-me -->


  <?php if ($user['remember_me']): ?>

  <!-- ── Section: Active Sessions ─────────────────────────── -->
  <div class="profile-section">

    <div class="sessions-header">
      <div class="sessions-title">Active remembered sessions</div>
      <?php if (!empty($tokens)): ?>
        <form method="post" action="/profile" style="margin:0;">
          <input type="hidden" name="_action" value="revoke_all" />
          <button type="submit" class="btn-revoke-all"
            onclick="return confirm('Sign out all remembered devices?')">
            Sign out all
          </button>
        </form>
      <?php endif; ?>
    </div>

    <?php if (empty($tokens)): ?>
      <div class="sessions-empty">No active remembered sessions.</div>
    <?php else: ?>
      <?php foreach ($tokens as $token): ?>
        <div class="session-item">
          <div class="session-meta">
            <div class="session-date">
              Started <?php echo htmlspecialchars(date('M j, Y g:i a', strtotime($token['created_at']))); ?>
            </div>
            <div class="session-sub">
              Expires <?php echo htmlspecialchars(date('M j, Y', strtotime($token['expires_at']))); ?>
              <?php if ($token['last_used_at']): ?>
                &nbsp;·&nbsp; Last used <?php echo htmlspecialchars(date('M j, Y', strtotime($token['last_used_at']))); ?>
              <?php endif; ?>
            </div>
          </div>

          <form method="post" action="/profile" style="margin:0;">
            <input type="hidden" name="_action"  value="revoke_token" />
            <input type="hidden" name="token_id" value="<?php echo (int)$token['id']; ?>" />
            <button type="submit" class="btn-revoke">Remove</button>
          </form>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>

  </div><!-- /.profile-section active-sessions -->

  <?php endif; // remember_me on ?>
  <?php endif; // REMEMBER_ME_ENABLED ?>

</div><!-- /.profile-wrap -->
</div><!-- /.main-content -->
</main>


<?php include ABIDE_CORE . '/footer.php'; ?>
