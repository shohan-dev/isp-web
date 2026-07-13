<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title>Maintenance — ISP Pay BD</title>
  <!-- 08 §2(b) — decided theme-exempt: base.css's body.ipb rule sets a
       literal background/font-family/color that would out-specificity
       and silently override this page's own gradient body{} below.
       Self-contained inline styles stay authoritative here. -->
  <style>
    :root { color-scheme: light dark; }
    * { box-sizing: border-box; }
    body {
      margin: 0; min-height: 100vh; min-height: 100dvh; display: flex; align-items: center; justify-content: center;
      font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
      background: linear-gradient(160deg, #0f172a 0%, #1e293b 55%, #0f172a 100%);
      color: #f8fafc; padding: 24px;
    }
    .card {
      width: 100%; max-width: 460px; background: rgba(255,255,255,.06);
      border: 1px solid rgba(255,255,255,.12); border-radius: 16px; padding: 36px 28px;
      box-shadow: var(--shadow-3, 0 24px 48px rgba(0,0,0,.35)); text-align: center;
    }
    .icon {
      width: 56px; height: 56px; border-radius: 14px; margin: 0 auto 16px;
      display: flex; align-items: center; justify-content: center;
      background: rgba(247,88,3,.18); color: #fb923c; font-size: 28px;
    }
    h1 { margin: 0 0 8px; font-size: 24px; font-weight: 800; }
    p { margin: 0 0 12px; color: #94a3b8; line-height: 1.6; font-size: 15px; }
    .bn { color: #cbd5e1; font-size: 14px; margin-top: 16px; }
  </style>
</head>
<body>
  <main class="card" role="main">
    <div class="icon" aria-hidden="true">&#9881;</div>
    <h1>We'll be right back</h1>
    <p>ISP Pay BD is undergoing scheduled maintenance. Your data is safe — please check back in a few minutes.</p>
    <p class="bn">আমরা শীঘ্রই ফিরে আসছি। কিছুক্ষণ পর আবার চেষ্টা করুন।</p>
  </main>
</body>
</html>
