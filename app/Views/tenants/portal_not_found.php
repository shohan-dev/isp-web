<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title>Portal not found</title>
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
      background: linear-gradient(160deg, #f8fafc 0%, #eef2ff 100%);
      color: #0f172a; padding: 24px;
    }
    .card {
      width: 100%; max-width: 440px; background: #fff; border: 1px solid #e2e8f0;
      border-radius: 16px; padding: 32px 28px; box-shadow: var(--shadow-2, 0 10px 30px rgba(15,23,42,.06));
      text-align: center;
    }
    .icon {
      width: 56px; height: 56px; border-radius: 14px; margin: 0 auto 16px;
      display: flex; align-items: center; justify-content: center;
      background: #fee2e2; color: #dc2626; font-size: 22px;
    }
    h1 { margin: 0 0 8px; font-size: 22px; font-weight: 800; }
    p { margin: 0 0 10px; color: #64748b; line-height: 1.55; font-size: 14px; }
    code { background: #f1f5f9; padding: 2px 8px; border-radius: 6px; font-size: 13px; }
  </style>
</head>
<body>
  <main class="card" role="main">
    <div class="icon" aria-hidden="true"><i class="fa fa-globe"></i></div>
    <h1>Portal not found</h1>
    <p>No active ISP portal is registered for this address.</p>
    <?php if (!empty($host)): ?>
      <p><code><?= esc($host); ?></code></p>
    <?php endif; ?>
    <p>If you believe this is a mistake, contact your platform administrator.</p>
  </main>
</body>
</html>
