<?php
/*
  public/pages/donate.php — Support the project
  ──────────────────────────────────────────────────────────────
  URL: yourdomain.com/donate

  Access: public — no auth required.

  To add a payment method:
    1. Add a .donate-method block in the methods section below
    2. Follow the existing Cash App block as a pattern
  ──────────────────────────────────────────────────────────────
*/

$page_title = 'Support';
include ABIDE_CORE . '/header.php';
?>

<style>
  /* ── Donate page layout ──────────────────────────────────── */

  .donate-wrap {
    max-width: 560px;
    margin: 0 auto;
    animation: fadeUp 0.3s ease both;
  }

  .donate-intro {
    margin-bottom: 2rem;
  }

  .donate-intro h1 {
    font-family: var(--mono);
    font-size: 1rem;
    letter-spacing: 0.18em;
    text-transform: uppercase;
    color: var(--text);
    margin-bottom: 1rem;
    font-weight: 400;
  }

  .donate-intro p {
    font-size: 0.85rem;
    color: var(--text-dim);
    line-height: 1.8;
    margin-bottom: 0.85rem;
  }

  /* ── Methods ─────────────────────────────────────────────── */
  .donate-methods {
    display: flex;
    flex-direction: column;
    gap: 1rem;
  }

  .donate-method {
    background: var(--bg2);
    border: 1px solid var(--border);
    border-radius: 6px;
    padding: 1.75rem 2rem;
    display: flex;
    align-items: center;
    gap: 2rem;
  }

  @media (max-width: 480px) {
    .donate-method {
      flex-direction: column;
      align-items: flex-start;
      gap: 1.25rem;
    }
  }

  .donate-method-info {
    flex: 1;
  }

  .donate-method-name {
    font-family: var(--mono);
    font-size: 0.68rem;
    letter-spacing: 0.2em;
    text-transform: uppercase;
    color: var(--accent-dim);
    margin-bottom: 0.4rem;
  }

  .donate-method-desc {
    font-size: 0.8rem;
    color: var(--text-dim);
    line-height: 1.6;
    margin-bottom: 0.85rem;
  }

  .donate-link {
    display: inline-block;
    font-family: var(--mono);
    font-size: 0.72rem;
    letter-spacing: 0.1em;
    color: var(--accent);
    text-decoration: none;
    border: 1px solid var(--accent-dim);
    border-radius: 3px;
    padding: 0.45rem 1rem;
    transition: background 0.2s ease, border-color 0.2s ease, color 0.2s ease;
  }

  .donate-link:hover {
    background: var(--accent-glow);
    border-color: var(--accent);
    color: #fff;
  }

  /* QR code */
  .donate-qr {
    flex-shrink: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
  }

  .donate-qr img {
    display: block;
    width: 120px;
    height: 120px;
    border-radius: 4px;
    opacity: 0.9;
  }

  .donate-qr-label {
    font-family: var(--mono);
    font-size: 0.58rem;
    letter-spacing: 0.1em;
    color: var(--text-dimmer);
    text-transform: uppercase;
  }

  /* Coming soon placeholder */
  .donate-method.coming-soon {
    opacity: 0.45;
    pointer-events: none;
  }

  .donate-method.coming-soon .donate-method-name::after {
    content: ' — coming soon';
    color: var(--text-dimmer);
    font-size: 0.58rem;
    letter-spacing: 0.1em;
  }
</style>


<main>
<div class="main-content">
<div class="donate-wrap">

  <div class="donate-intro">
    <h1>Support Abide</h1>
    <p>
      Abide is free, open source, and always will be. No paywalls, no ads,
      no tracking, no framework overhead.
    </p>
    <p>
      It's built by one person — on personal time, on personal hardware, on a
      server that sends a bill every month regardless. If Abide saves you setup
      time, becomes the bones of something you ship, or just gives you a cleaner
      place to think — a contribution goes directly toward keeping the
      infrastructure running and the development continuing.
    </p>
    <p>
      Anything helps. Nothing is expected. Thank you for being here.
    </p>
  </div>

  <div class="donate-methods">

    <!-- ── Cash App ───────────────────────────────────────── -->
    <div class="donate-method">
      <div class="donate-method-info">
        <div class="donate-method-name">Cash App</div>
        <p class="donate-method-desc">
          Send any amount via Cash App. Scan the QR code on mobile
          or tap the button to open Cash App directly.
        </p>
        <a href="https://cash.app/$yltdabide" class="donate-link" target="_blank" rel="noopener">
          $yltdabide
        </a>
      </div>

      <div class="donate-qr">
        <a href="https://cash.app/$yltdabide" target="_blank" rel="noopener">
          <img
            src="https://chart.cashapp.com/v1/qr/cashtag?data=%24yltdabide&size=240"
            alt="Scan to pay via Cash App"
          />
        </a>
        <span class="donate-qr-label">Scan to pay</span>
      </div>
    </div>

    <!-- ── Future methods ─────────────────────────────────── -->
    <!--
      To add a new payment method, duplicate the block above and
      update the name, description, and link. Remove the
      'coming-soon' class when the method is active.
    -->

  </div><!-- /.donate-methods -->

</div><!-- /.donate-wrap -->
</div><!-- /.main-content -->
</main>


<?php include ABIDE_CORE . '/footer.php'; ?>
