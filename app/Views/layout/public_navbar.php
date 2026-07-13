<style>
    .navbar-public {
        position: sticky;
        top: 0;
        z-index: var(--z-sticky, 20);
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 2.2rem 6rem; /* Substantially more padding */
        background-color: #001f57;
        box-shadow: var(--shadow-2, 0 8px 30px rgba(0, 0, 0, 0.3));
        margin: 0 0 50px;
        border-radius: 0 0 25px 25px;
        width: 100%;
    }

    .navbar-public .logo-img img {
        height: 75px; /* Much larger logo */
        width: auto;
    }

    .navbar-public .nav-links {
        display: flex;
        gap: 3rem; /* More gap between links */
        align-items: center;
    }

    .navbar-public .nav-links a {
        text-decoration: none;
        color: white;
        font-size: 1.3rem; /* Larger font */
        font-weight: 700;
        transition: color 0.2s ease;
    }

    .navbar-public .nav-links a:hover {
        color: #f75803 !important;
    }

    .navbar-public .cta-button {
        background-color: #f75803;
        color: white;
        padding: 1.2rem 2.5rem; /* Larger buttons */
        border-radius: 12px;
        font-weight: 800;
        text-decoration: none;
        transition: all 0.3s ease;
        font-size: 1.2rem; /* Larger button text */
    }

    .navbar-public .cta-button:hover {
        background: linear-gradient(90deg, #f6ad55, #f6e05e);
        color: #000;
    }

    @media (max-width: 768px) {
        .navbar-public {
            margin: 0 10px 20px;
            padding: 1rem;
        }
        .navbar-public .nav-links {
            display: none;
        }
    }
</style>

<nav class="navbar-public">
    <a href="<?= base_url('/') ?>" class="logo-img">
        <img src="<?= base_url('assets/img/logo/' . getSetting('app_logo')); ?>" alt="ISP Pay Bd">
    </a>
    <div class="nav-links">
        <a href="<?= base_url('/') ?>">Home</a>
        <a href="<?= base_url('/#core-features') ?>">Features</a>
        <a href="<?= base_url('plugins') ?>" style="color: var(--primary-500, #f75803); border-bottom: 2px solid #f75803;">Plugins</a>
        <a href="<?= base_url('/#pricing-section') ?>">Pricing</a>
        <a href="<?= base_url('/#about_us') ?>">About Us</a>
        <?php if (!empty(getSession('user_id')) && !empty(getSession('user_role'))): ?>
            <a href="<?= route_to('route.dashboard'); ?>" class="cta-button">Dashboard</a>
        <?php else: ?>
            <a href="<?= route_to('route.auth.login'); ?>" class="cta-button">Login/Signup</a>
        <?php endif; ?>
    </div>
    <a href="<?= base_url('/#contact_us') ?>" class="cta-button">Demo Request</a>
</nav>
