<?php
$variant = $variant ?? 'login';
$brandMarkLogo = $logoUrl ?? null;
?>
<div class="ipb-auth-brand-inner">
  <?php if ($variant === 'register'): ?>
    <div class="ipb-brand-mark"><?php if ($brandMarkLogo): ?><img src="<?= esc($brandMarkLogo, 'attr'); ?>" alt="" loading="eager" decoding="async"><?php else: ?><i class="fa fa-rocket" aria-hidden="true"></i><?php endif; ?></div>
    <h1>Launch your ISP on modern SaaS</h1>
    <p class="ipb-brand-lead">Join ISPs across Bangladesh who run billing, network, and support from one secure cloud platform.</p>
    <ul class="ipb-auth-features">
      <li><i class="fa fa-gift" aria-hidden="true"></i><span>14-day free trial with full platform access</span></li>
      <li><i class="fa fa-shield-halved" aria-hidden="true"></i><span>Secure multi-tenant architecture</span></li>
      <li><i class="fa fa-headset" aria-hidden="true"></i><span>Onboarding support from our team</span></li>
      <li><i class="fa fa-arrows-rotate" aria-hidden="true"></i><span>Upgrade or change plan anytime</span></li>
    </ul>
    <div class="ipb-auth-trust">
      <div class="ipb-auth-trust-item"><strong>500+</strong>ISPs served</div>
      <div class="ipb-auth-trust-item"><strong>99.9%</strong>Uptime SLA</div>
    </div>
  <?php elseif ($isTenantPortal): ?>
    <div class="ipb-brand-mark"><?php if ($brandMarkLogo): ?><img src="<?= esc($brandMarkLogo, 'attr'); ?>" alt="" loading="eager" decoding="async"><?php else: ?><i class="fa fa-bolt" aria-hidden="true"></i><?php endif; ?></div>
    <h1><?= esc($brandTitle); ?></h1>
    <p class="ipb-brand-lead"><?= esc($appSlogan ?: 'Sign in to manage your network, customers, and billing in one place.'); ?></p>
  <?php else: ?>
    <div class="ipb-brand-mark"><?php if ($brandMarkLogo): ?><img src="<?= esc($brandMarkLogo, 'attr'); ?>" alt="" loading="eager" decoding="async"><?php else: ?><i class="fa fa-bolt" aria-hidden="true"></i><?php endif; ?></div>
    <h1>The complete billing &amp; network platform for ISPs</h1>
    <p class="ipb-brand-lead">Billing, CRM, Mikrotik, OLT monitoring and reseller management — one connected platform built for growing ISPs.</p>
    <ul class="ipb-auth-features">
      <li><i class="fa fa-chart-line" aria-hidden="true"></i><span>Automated billing, invoicing and payment collection</span></li>
      <li><i class="fa fa-network-wired" aria-hidden="true"></i><span>Mikrotik PPPoE, hotspot and network automation</span></li>
      <li><i class="fa fa-users" aria-hidden="true"></i><span>Reseller portals, referrals and customer CRM</span></li>
    </ul>
    <div class="ipb-auth-trust">
      <div class="ipb-auth-trust-item"><strong>14 days</strong>Free trial</div>
      <div class="ipb-auth-trust-item"><strong>24/7</strong>Support ready</div>
      <div class="ipb-auth-trust-item"><strong>100%</strong>Cloud SaaS</div>
    </div>
  <?php endif; ?>
</div>
