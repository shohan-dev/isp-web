<?= $this->extend('layout/main-layout'); ?>

<?= $this->section('css'); ?>
<?= saas_css('dashboard.css') ?>
<style>
  .ipb-suspend {
    max-width: 720px;
    margin: 24px auto;
  }
  .ipb-suspend-banner {
    background: linear-gradient(135deg, #dc2626, #b91c1c);
    color: #fff;
    font-size: 22px;
    font-weight: 800;
    padding: 18px 20px;
    border-radius: var(--radius);
    text-align: center;
    letter-spacing: -0.02em;
    margin-bottom: 16px;
  }
  .ipb-suspend-person {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 14px 16px;
    border: 1px solid var(--border);
    border-radius: 12px;
    margin-bottom: 12px;
    background: var(--surface-2);
  }
  .ipb-suspend-left { display: flex; align-items: center; gap: 12px; }
  .ipb-suspend-avatar {
    width: 44px; height: 44px; border-radius: 999px;
    background: var(--primary-50); color: var(--primary-600);
    display: flex; align-items: center; justify-content: center; font-size: 22px;
  }
  .ipb-suspend-icons { display: flex; gap: 14px; font-size: 22px; }
  .ipb-suspend-icons a.phone { color: var(--info-500); }
  .ipb-suspend-icons a.whatsapp { color: #25d366; }
  @media (max-width: 600px) {
    .ipb-suspend-person { flex-direction: column; align-items: flex-start; }
  }
</style>
<?= $this->endSection('css'); ?>

<?= $this->section('content'); ?>
<div class="content-wrapper">
  <section class="content">
    <div class="ipb-suspend fade-in">
      <?php if (!empty($isCustomPending)): ?>
        <div class="ipb-suspend-banner" style="background:linear-gradient(135deg,#2563eb,#1d4ed8)">CUSTOM PLAN PENDING</div>

        <div class="ipb-card" style="margin-bottom:16px;text-align:center">
          <p style="font-size:15px;color:var(--text-secondary);line-height:1.6;margin:0">
            Thanks for signing up! Our team is preparing your tailored plan.<br>
            Your account activates as soon as it is approved — usually within one business day.
          </p>
        </div>
      <?php elseif (!empty($isPayg)): ?>
        <div class="ipb-suspend-banner">SERVICE PAUSED — WALLET BALANCE LOW</div>

        <div class="ipb-card" style="margin-bottom:16px;text-align:center">
          <p style="font-size:15px;color:var(--text-secondary);line-height:1.6;margin:0">
            Your wallet balance (৳<?= number_format((float) $walletBalance, 2); ?>) could not cover this
            month's charge of ৳<?= number_format((float) $estimatedCharge, 2); ?>.<br>
            Top up now — your service reactivates automatically.
          </p>
          <a href="<?= route_to('route.wallet'); ?>" class="btn btn-primary" style="margin-top:16px">
            <i class="fas fa-wallet"></i> Top Up Wallet to Reactivate
          </a>
        </div>
      <?php else: ?>
        <div class="ipb-suspend-banner">ACCOUNT SUSPENDED</div>

        <div class="ipb-card" style="margin-bottom:16px;text-align:center">
          <p style="font-size:15px;color:var(--text-secondary);line-height:1.6;margin:0">
            Your account has been suspended for violating our terms of service.<br>
            Please contact support for further assistance.
          </p>
          <a href="<?= route_to('route.payment'); ?>" class="btn btn-primary" style="margin-top:16px">
            Pay to Activate your Account
          </a>
        </div>
      <?php endif; ?>

      <div class="ipb-card">
        <div class="ipb-card-head">
          <div class="ipb-card-title">Support team</div>
        </div>
        <?php
        $supportTeam = [
          ['name' => 'Support', 'phone' => '01781808231', 'start' => '2:00 PM', 'end' => '10:00 PM'],
          ['name' => 'Parvez Rahman', 'phone' => '01628856735', 'start' => '2:00 PM', 'end' => '3:00 AM'],
          ['name' => 'Md Sabbir Ahammed', 'phone' => '01610585100', 'start' => '10:00 AM', 'end' => '10:00 PM'],
        ];
        foreach ($supportTeam as $person):
          $phoneLink = preg_replace('/[^0-9]/', '', $person['phone']);
        ?>
          <div class="ipb-suspend-person">
            <div class="ipb-suspend-left">
              <div class="ipb-suspend-avatar"><i class="fas fa-user-circle"></i></div>
              <div>
                <strong style="display:block;font-size:15px">
                  <?= esc($person['name']); ?>
                  <span style="font-size:12px;font-weight:600;color:var(--text-muted)">
                    (<span style="color:var(--success-600)"><?= esc($person['start']); ?></span>
                    –
                    <span style="color:var(--error-600)"><?= esc($person['end']); ?></span>)
                  </span>
                </strong>
                <span style="color:var(--text-secondary)"><?= esc($person['phone']); ?></span>
              </div>
            </div>
            <div class="ipb-suspend-icons">
              <a href="tel:<?= esc($person['phone']); ?>" class="phone" title="Call"><i class="fas fa-phone"></i></a>
              <a href="https://wa.me/88<?= esc($phoneLink); ?>" class="whatsapp" title="WhatsApp" target="_blank" rel="noopener"><i class="fab fa-whatsapp"></i></a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
</div>
<?= $this->endSection('content'); ?>
