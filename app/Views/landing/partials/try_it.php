<?php
/* Live-demo credential cards. Both "Open" buttons hit the SINGLE login page
   (base_url('auth/login')) with ?user=&pass= — login.php prefills both fields so
   a visitor just presses "Sign in". These must map to real demo accounts on this
   install; change the values here to rotate them. */
$lpDemoLoginUrl = base_url('auth/login');
$lpDemoLoginHost = preg_replace('#^https?://#', '', $lpDemoLoginUrl);
$lpDemos = [
    [
        'variant' => 'admin',
        'icon'    => 'fa-shield-halved',
        'title'   => 'Admin Panel',
        'sub'     => 'Full ISP management dashboard',
        'user'    => 'admin',
        'pass'    => '12345678',
        'chips'   => ['Subscribers', 'Billing', 'Network', 'Reports', 'Settings'],
    ],
    [
        'variant' => 'subscriber',
        'icon'    => 'fa-user',
        'title'   => 'Subscriber Portal',
        'sub'     => 'Self-service client dashboard',
        'user'    => 'subscriber',
        'pass'    => '12345678',
        'chips'   => ['Dashboard', 'Invoices', 'Payments', 'Tickets', 'Profile'],
    ],
];
?>
<section class="lp-section lp-section--dark" id="lp-try-it" data-lp-section>
    <div class="lp-container">
        <div class="lp-section__header lp-reveal">
            <span class="lp-section__label">Live Demo</span>
            <h2 class="lp-section__title">Try it before you buy it.</h2>
            <p class="lp-section__desc">Explore the full admin panel and subscriber portal &mdash; no setup, no registration, completely free. The credentials are pre-filled; just press <strong>Sign in</strong>.</p>
        </div>

        <div class="lp-trydemo lp-reveal">
            <?php foreach ($lpDemos as $demo):
                $openUrl = $lpDemoLoginUrl . '?user=' . rawurlencode($demo['user']) . '&pass=' . rawurlencode($demo['pass']);
            ?>
            <div class="lp-trydemo__card lp-trydemo__card--<?= esc($demo['variant'], 'attr') ?>">
                <div class="lp-trydemo__head">
                    <div class="lp-trydemo__icon"><i class="fas <?= esc($demo['icon'], 'attr') ?>" aria-hidden="true"></i></div>
                    <div>
                        <h3 class="lp-trydemo__title"><?= esc($demo['title']) ?></h3>
                        <p class="lp-trydemo__sub"><?= esc($demo['sub']) ?></p>
                    </div>
                </div>

                <div class="lp-trydemo__cred">
                    <span class="lp-trydemo__cred-label">Username</span>
                    <span class="lp-trydemo__cred-val"><?= esc($demo['user']) ?></span>
                    <button type="button" class="lp-trydemo__copy" data-copy="<?= esc($demo['user'], 'attr') ?>" aria-label="Copy username">Copy</button>
                </div>
                <div class="lp-trydemo__cred">
                    <span class="lp-trydemo__cred-label">Password</span>
                    <span class="lp-trydemo__cred-val"><?= esc($demo['pass']) ?></span>
                    <button type="button" class="lp-trydemo__copy" data-copy="<?= esc($demo['pass'], 'attr') ?>" aria-label="Copy password">Copy</button>
                </div>

                <a class="lp-trydemo__cta" href="<?= esc($openUrl, 'attr') ?>">
                    <i class="fas fa-circle-play" aria-hidden="true"></i> Open <?= esc($demo['title']) ?>
                </a>

                <div class="lp-trydemo__chips">
                    <?php foreach ($demo['chips'] as $chip): ?>
                        <span class="lp-trydemo__chip"><?= esc($chip) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<script>
/* Copy-to-clipboard for the demo credential chips. */
(function () {
  var sec = document.getElementById('lp-try-it');
  if (!sec) return;
  sec.addEventListener('click', function (e) {
    var btn = e.target.closest('.lp-trydemo__copy');
    if (!btn) return;
    var val = btn.getAttribute('data-copy') || '';
    var flash = function () {
      var old = btn.textContent;
      btn.textContent = 'Copied';
      btn.classList.add('is-copied');
      setTimeout(function () { btn.textContent = old; btn.classList.remove('is-copied'); }, 1400);
    };
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(val).then(flash).catch(flash);
    } else {
      var ta = document.createElement('textarea');
      ta.value = val; document.body.appendChild(ta); ta.select();
      try { document.execCommand('copy'); } catch (err) {}
      document.body.removeChild(ta); flash();
    }
  });
})();
</script>
