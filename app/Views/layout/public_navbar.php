<link rel="stylesheet" href="<?= base_url('assets/css/landing/fonts.css') ?>">
<style>
    .navbar-public {
        --pnav-primary-900: #001033;
        --pnav-primary-800: #001F57;
        --pnav-accent: #F75803;
        --pnav-accent-hover: #d94601;
        --pnav-cyan: #06B6D4;
        --pnav-gold: #F6AD55;
        position: sticky;
        top: 0;
        z-index: var(--z-sticky, 20);
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 24px;
        padding: 1.1rem 3rem;
        background: rgba(0, 16, 51, 0.72);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        box-shadow: 0 4px 30px rgba(0, 0, 0, 0.2);
        margin: 0 0 0;
        width: 100%;
        font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', sans-serif;
    }

    .navbar-public .logo-img {
        display: flex;
        align-items: center;
    }

    .navbar-public .logo-img img {
        height: 44px;
        width: auto;
        object-fit: contain;
    }

    .navbar-public .nav-links {
        display: flex;
        gap: 2.25rem;
        align-items: center;
    }

    .navbar-public .nav-links a {
        text-decoration: none;
        color: rgba(255, 255, 255, 0.78) !important;
        font-size: 0.9375rem;
        font-weight: 600;
        transition: color 0.2s ease;
        position: relative;
    }

    .navbar-public .nav-links a:hover {
        color: #fff !important;
    }

    .navbar-public .nav-links a.is-active {
        color: #fff !important;
    }

    .navbar-public .nav-links a.is-active::after {
        content: '';
        position: absolute;
        left: 0;
        right: 0;
        bottom: -6px;
        height: 2px;
        background: var(--pnav-accent);
        border-radius: 2px;
    }

    .navbar-public .cta-group {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .navbar-public .btn-ghost {
        text-decoration: none;
        color: #fff !important;
        font-weight: 600;
        font-size: 0.9375rem;
        padding: 0.7rem 1.1rem;
        border-radius: 10px;
        border: 1px solid rgba(255, 255, 255, 0.28);
        transition: all 0.25s ease;
        white-space: nowrap;
    }

    .navbar-public .btn-ghost:hover {
        border-color: #fff;
        background: rgba(255, 255, 255, 0.08);
        color: #fff !important;
    }

    .navbar-public .cta-button {
        background: var(--pnav-accent);
        color: #fff !important;
        padding: 0.75rem 1.5rem;
        border-radius: 10px;
        font-weight: 700;
        text-decoration: none;
        transition: all 0.25s ease;
        font-size: 0.9375rem;
        white-space: nowrap;
        box-shadow: 0 4px 16px rgba(247, 88, 3, 0.3);
    }

    .navbar-public .cta-button:hover {
        background: var(--pnav-accent-hover);
        color: #fff !important;
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(247, 88, 3, 0.4);
    }

    .navbar-public .mobile-toggle {
        display: none;
        background: none;
        border: none;
        color: #fff;
        font-size: 1.4rem;
        cursor: pointer;
        padding: 6px;
    }

    .navbar-public .mobile-panel {
        display: none;
    }

    @media (max-width: 992px) {
        .navbar-public {
            padding: 1rem 1.5rem;
        }
        .navbar-public .nav-links {
            gap: 1.4rem;
        }
        .navbar-public .btn-ghost {
            display: none;
        }
    }

    @media (max-width: 768px) {
        .navbar-public {
            padding: 0.85rem 1.25rem;
            flex-wrap: wrap;
        }
        .navbar-public .nav-links {
            display: none;
        }
        .navbar-public .cta-group .btn-ghost {
            display: none;
        }
        .navbar-public .mobile-toggle {
            display: inline-flex;
            align-items: center;
        }
        .navbar-public .cta-button {
            padding: 0.6rem 1.1rem;
            font-size: 0.875rem;
        }
        .navbar-public .mobile-panel {
            order: 3;
            width: 100%;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
            flex-direction: column;
            gap: 0;
        }
        .navbar-public .mobile-panel.is-open {
            display: flex;
            max-height: 400px;
            margin-top: 1rem;
        }
        .navbar-public .mobile-panel a {
            text-decoration: none;
            color: rgba(255, 255, 255, 0.85) !important;
            font-weight: 600;
            font-size: 0.9375rem;
            padding: 0.85rem 0;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        .navbar-public .mobile-panel a:first-child {
            border-top: none;
        }
        .navbar-public .mobile-panel a.is-active {
            color: var(--pnav-accent) !important;
        }
        .navbar-public .mobile-panel .btn-ghost {
            display: inline-block;
            text-align: center;
            margin-top: 0.75rem;
        }
    }
</style>

<nav class="navbar-public">
    <a href="<?= base_url('/') ?>" class="logo-img">
        <img src="<?= base_url('assets/img/logo/' . getSetting('app_logo')); ?>" alt="ISP Pay Bd">
    </a>
    <div class="nav-links">
        <a href="<?= base_url('/') ?>">Home</a>
        <a href="<?= base_url('/#lp-features') ?>">Features</a>
        <a href="<?= base_url('plugins') ?>" class="is-active">Plugins</a>
        <a href="<?= base_url('/#lp-pricing') ?>">Pricing</a>
        <a href="<?= base_url('/#lp-about') ?>">About Us</a>
    </div>
    <div class="cta-group">
        <a href="<?= base_url('/#lp-contact') ?>" class="btn-ghost">Demo Request</a>
        <?php if (!empty(getSession('user_id')) && !empty(getSession('user_role'))): ?>
            <a href="<?= route_to('route.dashboard'); ?>" class="cta-button">Dashboard</a>
        <?php else: ?>
            <a href="<?= route_to('route.auth.login'); ?>" class="cta-button">Login/Signup</a>
        <?php endif; ?>
    </div>
    <button type="button" class="mobile-toggle" id="pnavToggle" aria-label="Open menu" aria-expanded="false" aria-controls="pnavPanel">
        <i class="fas fa-bars"></i>
    </button>
    <div class="mobile-panel" id="pnavPanel">
        <a href="<?= base_url('/') ?>">Home</a>
        <a href="<?= base_url('/#lp-features') ?>">Features</a>
        <a href="<?= base_url('plugins') ?>" class="is-active">Plugins</a>
        <a href="<?= base_url('/#lp-pricing') ?>">Pricing</a>
        <a href="<?= base_url('/#lp-about') ?>">About Us</a>
        <a href="<?= base_url('/#lp-contact') ?>" class="btn-ghost">Demo Request</a>
        <?php if (!empty(getSession('user_id')) && !empty(getSession('user_role'))): ?>
            <a href="<?= route_to('route.dashboard'); ?>" class="cta-button">Dashboard</a>
        <?php else: ?>
            <a href="<?= route_to('route.auth.login'); ?>" class="cta-button">Login/Signup</a>
        <?php endif; ?>
    </div>
</nav>
<script>
    (function () {
        var toggle = document.getElementById('pnavToggle');
        var panel = document.getElementById('pnavPanel');
        if (!toggle || !panel) return;
        toggle.addEventListener('click', function () {
            var isOpen = panel.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            toggle.innerHTML = isOpen ? '<i class="fas fa-times"></i>' : '<i class="fas fa-bars"></i>';
        });
    })();
</script>
