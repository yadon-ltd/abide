<?php
/*
  public/pages/logout.php — Session teardown
  ──────────────────────────────────────────────────────────────
  Clean URL: /logout  (routed by public/.htaccess)

  No HTML output. Destroys the session and redirects to /.
  Link or POST to /logout to sign out.
  ──────────────────────────────────────────────────────────────
*/

auth_logout();

header('Location: /');
exit;
