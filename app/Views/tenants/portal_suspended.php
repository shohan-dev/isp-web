<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title>Portal suspended</title>
  <!-- 08 §10 / 07 F3 — self-hosted Font Awesome (was cdnjs; a blocked CDN silently drops every icon) -->
  <link rel="stylesheet" href="<?= base_url('assets/vendor/fontawesome/all.min.css'); ?>">
  <!-- 08 §2(b) — decided theme-exempt: base.css's body.ipb rule sets a
       literal background/font-family/color that would out-specificity
       and silently override this page's own gradient body{} below.
       Self-contained inline styles stay authoritative here. -->
  <style>
    :root { color-scheme: light; }
    * { box-sizing: border-box; }
    body {
      margin: 0; min-height: 100vh; min-height: 100dvh; display: flex; align-items: center; justify-content: center;
      font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
      background: linear-gradient(160deg, #fff7ed 0%, #fef3c7 100%);
      color: #0f172a; padding: 24px;
    }
    .card {
      width: 100%; max-width: 440px; background: #fff; border: 1px solid #fde68a;
      border-radius: 16px; padding: 32px 28px; box-shadow: var(--shadow-2, 0 10px 30px rgba(15,23,42,.06));
      text-align: center;
    }
    .icon {
      width: 56px; height: 56px; border-radius: 14px; margin: 0 auto 16px;
      display: flex; align-items: center; justify-content: center;
      background: #ffedd5; color: #ea580c; font-size: 22px;
    }
    h1 { margin: 0 0 8px; font-size: 22px; font-weight: 800; }
    p { margin: 0 0 10px; color: #64748b; line-height: 1.55; font-size: 14px; }
  </style>
</head>
<body>
  <main class="card" role="main">
    <div class="icon" aria-hidden="true"><i class="fa fa-ban"></i></div>
    <h1>Portal suspended</h1>
    <?php if (!empty($tenant->name)): ?>
      <p><strong><?= esc($tenant->name); ?></strong> is currently suspended.</p>
    <?php else: ?>
      <p>This ISP portal is currently suspended.</p>
    <?php endif; ?>
    <p>Please contact the platform administrator to restore access.</p>
  </main>
</body>
</html>
