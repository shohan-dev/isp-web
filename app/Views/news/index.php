<?= $this->extend('layout/main-layout'); ?>

<?= $this->section('css'); ?>
<style>
  .ipb-news {
    max-width: 820px;
  }

  .ipb-news-intro {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    margin-bottom: 16px;
    padding: 16px 18px;
    background: var(--surface, #fff);
    border: 1px solid var(--border, #e6eaf0);
    border-radius: var(--radius-lg, 14px);
    box-shadow: var(--shadow-1, 0 1px 2px rgba(15, 23, 42, 0.04));
  }

  .ipb-news-intro-icon {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    background: var(--primary-50, #fff4ed);
    color: var(--primary-600, #d94601);
    font-size: 18px;
  }

  .ipb-news-intro h2 {
    margin: 0 0 4px;
    font-size: 15px;
    font-weight: 800;
    color: var(--text-primary, #0f172a);
    letter-spacing: -0.02em;
  }

  .ipb-news-intro p {
    margin: 0;
    font-size: 13px;
    font-weight: 600;
    color: var(--text-muted, #94a3b8);
    line-height: 1.45;
  }

  .ipb-news-feed {
    display: grid;
    gap: 14px;
  }

  .ipb-news-card {
    background: var(--surface, #fff);
    border: 1px solid var(--border, #e6eaf0);
    border-radius: var(--radius-lg, 14px);
    box-shadow: var(--shadow-1, 0 1px 2px rgba(15, 23, 42, 0.04));
    overflow: hidden;
    position: relative;
  }

  .ipb-news-card::before {
    content: "";
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 3px;
    background: linear-gradient(180deg, var(--primary-500, #f75803), #ffb38a);
  }

  .ipb-news-card-body {
    padding: 18px 20px 18px 22px;
  }

  .ipb-news-meta {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px 12px;
    margin-bottom: 10px;
  }

  .ipb-news-date {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    font-weight: 700;
    color: var(--text-muted, #94a3b8);
  }

  .ipb-news-badge {
    display: inline-flex;
    align-items: center;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    background: var(--primary-50, #fff4ed);
    color: var(--primary-600, #d94601);
  }

  .ipb-news-title {
    margin: 0 0 10px;
    font-size: clamp(17px, 2.4vw, 20px);
    font-weight: 800;
    color: var(--text-primary, #0f172a);
    letter-spacing: -0.02em;
    line-height: 1.3;
  }

  .ipb-news-body {
    font-size: 14px;
    font-weight: 600;
    line-height: 1.65;
    color: var(--text-secondary, #51607a);
    word-break: break-word;
  }

  .ipb-news-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-top: 14px;
    min-height: 40px;
    padding: 0 14px;
    border-radius: 10px;
    background: var(--primary-500, #f75803);
    color: #fff !important;
    font-size: 13px;
    font-weight: 800;
    text-decoration: none !important;
    box-shadow: var(--shadow-brand, 0 8px 18px rgba(247, 88, 3, 0.28));
  }

  .ipb-news-link:hover {
    background: var(--primary-600, #d94601);
    color: #fff !important;
  }

  .ipb-news-empty {
    text-align: center;
    padding: 48px 20px;
    background: var(--surface, #fff);
    border: 1px dashed var(--border-strong, #d7dee7);
    border-radius: var(--radius-lg, 14px);
  }

  .ipb-news-empty-icon {
    width: 56px;
    height: 56px;
    margin: 0 auto 14px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--surface-2, #f8fafc);
    color: var(--text-muted, #94a3b8);
    font-size: 24px;
  }

  .ipb-news-empty h3 {
    margin: 0 0 6px;
    font-size: 16px;
    font-weight: 800;
    color: var(--text-primary, #0f172a);
  }

  .ipb-news-empty p {
    margin: 0;
    font-size: 13.5px;
    font-weight: 600;
    color: var(--text-muted, #94a3b8);
    line-height: 1.45;
  }

  body.ipb[data-theme="dark"] .ipb-news-card,
  body.ipb.dark-mode .ipb-news-card,
  body.ipb[data-theme="dark"] .ipb-news-intro,
  body.ipb.dark-mode .ipb-news-intro,
  body.ipb[data-theme="dark"] .ipb-news-empty,
  body.ipb.dark-mode .ipb-news-empty {
    background: var(--surface);
    border-color: var(--border);
  }

  body.ipb[data-theme="dark"] .ipb-news-empty-icon,
  body.ipb.dark-mode .ipb-news-empty-icon {
    background: var(--surface-2);
  }

  @media (max-width: 767px) {
    .ipb-news-intro {
      padding: 14px;
    }

    .ipb-news-card-body {
      padding: 14px 14px 14px 16px;
    }

    .ipb-news-link {
      width: 100%;
      justify-content: center;
      min-height: 44px;
    }
  }
</style>
<?= $this->endSection('css'); ?>

<?= $this->section('content'); ?>

<div class="content-wrapper">
  <section class="content ipb-saas-list">

    <?= $this->include('components/page-header', [
      'title' => 'News & Notices',
      'subtitle' => 'Stay updated with important announcements from your provider.',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'News & Notices'],
      ],
    ]); ?>

    <div class="ipb-news">
      <div class="ipb-news-intro">
        <div class="ipb-news-intro-icon" aria-hidden="true">
          <i class="fa fa-bullhorn"></i>
        </div>
        <div>
          <h2>Provider announcements</h2>
          <p>Latest notices and updates from your ISP. Check here regularly for service information.</p>
        </div>
      </div>

      <?php if (!empty($notices)): ?>
        <div class="ipb-news-feed">
          <?php foreach ($notices as $notice): ?>
            <article class="ipb-news-card">
              <div class="ipb-news-card-body">
                <div class="ipb-news-meta">
                  <span class="ipb-news-date">
                    <i class="far fa-calendar-alt" aria-hidden="true"></i>
                    <?= date('d M Y', strtotime($notice->created_at)); ?>
                  </span>
                  <span class="ipb-news-badge">Announcement</span>
                </div>
                <h3 class="ipb-news-title"><?= esc($notice->name); ?></h3>
                <div class="ipb-news-body">
                  <?= nl2br(esc($notice->details ?? '')); ?>
                </div>
                <?php if (!empty($notice->url)): ?>
                  <a class="ipb-news-link" href="<?= esc($notice->url, 'attr'); ?>" target="_blank" rel="noopener noreferrer">
                    <i class="fas fa-external-link-alt" aria-hidden="true"></i>
                    View more details
                  </a>
                <?php endif; ?>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="ipb-news-empty">
          <div class="ipb-news-empty-icon" aria-hidden="true">
            <i class="fa fa-newspaper"></i>
          </div>
          <h3>No notices yet</h3>
          <p>Your service provider hasn’t posted any announcements. Check back later.</p>
        </div>
      <?php endif; ?>
    </div>

  </section>
</div>

<?= $this->endSection('content'); ?>
