<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Landing page smoke tests (Item 9).
 *
 * @internal
 */
final class LandingTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;

    protected $migrate = false;

    private function requireSqlite(): void
    {
        if (!extension_loaded('sqlite3')) {
            $this->markTestSkipped('SQLite3 extension required for landing feature tests.');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        if (!extension_loaded('sqlite3')) {
            return;
        }
        // AuthController::home() wraps every real DB touch in try/catch with
        // safe fallbacks, so most of this is defensive breadth rather than a
        // strict requirement — but users/landing_testimonials/admin_packages
        // are queried via query-builder (DBGroup-prefix-aware).
        $p = $this->db->DBPrefix;
        foreach (['users', 'landing_testimonials', 'admin_packages', 'plugins'] as $name) {
            $this->db->query('DROP TABLE IF EXISTS ' . $p . $name);
        }
        $this->db->query('CREATE TABLE ' . $p . 'users (id INTEGER PRIMARY KEY, admin_id INTEGER, role TEXT, name TEXT, status TEXT)');
        $this->db->query('CREATE TABLE ' . $p . 'landing_testimonials (
            id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, role TEXT, company TEXT, quote TEXT,
            avatar_initials TEXT, rating INTEGER, sort_order INTEGER, is_active INTEGER, created_at TEXT
        )');
        $this->db->query('CREATE TABLE ' . $p . 'admin_packages (
            id INTEGER PRIMARY KEY AUTOINCREMENT, package_name TEXT, price REAL, duration INTEGER,
            user_limit INTEGER, is_active INTEGER, sort_order INTEGER, tier_key TEXT
        )');
        $this->db->query('CREATE TABLE ' . $p . 'plugins (
            id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT, category TEXT, description TEXT,
            price_type TEXT, billing_cycle TEXT, image TEXT, status INTEGER, created_at TEXT, updated_at TEXT
        )');
    }

    public function testHomeViewUsesSelfHostedFontAndIconAssets(): void
    {
        $home = (string) file_get_contents(APPPATH . 'Views/dashboard/home.php');

        $this->assertStringContainsString('assets/css/landing/fonts.css', $home);
        $this->assertStringContainsString('assets/vendor/fontawesome/all.min.css', $home);
        $this->assertStringNotContainsString('fonts.googleapis.com', $home);
        $this->assertStringNotContainsString('cdnjs.cloudflare.com', $home);
    }

    public function testFontsCssDeclaresLocalWoff2Faces(): void
    {
        $fontsCss = (string) file_get_contents(FCPATH . 'assets/css/landing/fonts.css');

        $this->assertStringContainsString("font-family: 'Inter'", $fontsCss);
        $this->assertStringContainsString("font-family: 'Plus Jakarta Sans'", $fontsCss);
        $this->assertStringContainsString('../../fonts/inter-latin.woff2', $fontsCss);
        $this->assertStringContainsString('font-display: swap', $fontsCss);
    }

    public function testHomeReturns200WithSectionAnchors(): void
    {
        $this->requireSqlite();

        $result = $this->get('/');

        $this->assertSame(200, $result->response()->getStatusCode());
        $body = (string) $result->getBody();
        $this->assertStringContainsString('id="lp-hero"', $body);
        $this->assertStringContainsString('id="lp-pricing"', $body);
        $this->assertStringContainsString('id="lp-contact"', $body);
    }

    public function testHomeRenderedBodyHasNoExternalFontOrCdnCss(): void
    {
        $this->requireSqlite();

        $result = $this->get('/');
        $body = (string) $result->getBody();

        $this->assertStringNotContainsString('fonts.googleapis.com', $body);
        $this->assertStringNotContainsString('fonts.gstatic.com', $body);
        $this->assertStringNotContainsString('cdnjs.cloudflare.com', $body);
    }

    public function testHomeHidesFabricatedTestimonialsWhenTableEmpty(): void
    {
        $this->requireSqlite();

        $result = $this->get('/');
        $body = (string) $result->getBody();

        $this->assertStringNotContainsString('Rajib Ahmed', $body);
        $this->assertStringNotContainsString('Salma Khatun', $body);
        $this->assertStringNotContainsString('Mohammad Rahim', $body);
    }

    public function testContactStoreWithInvalidRecaptchaRedirectsWithError(): void
    {
        $this->requireSqlite();

        // CI4's 'session' CSRF mode relies on the file session driver actually
        // persisting between this pre-computed hash and the simulated inner
        // request's session state, which FeatureTestTrait's blank-session
        // reset doesn't guarantee — mock the Security service so the CSRF
        // check itself always passes, isolating this test to its actual
        // subject (the reCAPTCHA-failure redirect), not CSRF plumbing.
        \Config\Services::injectMock('security', new class (new \Config\App()) extends \CodeIgniter\Security\Security {
            public function verify($request)
            {
                return $this;
            }
        });

        $result = $this->post('auth/storesubmit', [
            'csrf_test_name' => 'bypassed',
            'name' => 'Test User',
            'phone' => '01700000000',
            'email' => 'test@example.com',
            'message' => 'Hello',
            'inquiryType' => 'demo',
            'g-recaptcha-response' => 'invalid-token',
        ]);

        $this->assertTrue($result->isRedirect());
        $this->assertStringContainsString('#lp-contact', (string) $result->response()->getHeaderLine('Location'));
        $this->assertStringContainsString('recaptcha', strtolower((string) session()->getFlashdata('error')));
    }

    public function testHomeIncludesProductTourTabsAndAutoReconciliation(): void
    {
        $this->requireSqlite();

        $result = $this->get('/');
        $body = (string) $result->getBody();

        $this->assertSame(200, $result->response()->getStatusCode());
        $this->assertStringContainsString('id="lp-product"', $body);
        $this->assertStringContainsString('lp-product__tab', $body);
        $this->assertStringContainsString('data-bullets', $body);
        $this->assertStringContainsString('id="lp-auto-reconcile"', $body);
    }

    public function testHomeHidesPluginsSectionWhenNoActivePlugins(): void
    {
        $this->requireSqlite();

        $result = $this->get('/');
        $body = (string) $result->getBody();

        // The standalone "#lp-plugins" partial was merged into the integrations
        // section's "Plugins & Addons" sub-block (landing/partials/connects.php,
        // class="lp-connect-plugins") during the 13-beats redesign — #lp-plugins
        // itself no longer exists on any render. #lp-integrations is the (always
        // present) parent section; the plugins sub-block is what's conditional.
        $this->assertStringNotContainsString('lp-connect-plugins', $body);
    }

    public function testHomeShowsActivePluginAsLandingCard(): void
    {
        $this->requireSqlite();

        $p = $this->db->DBPrefix;
        $this->db->query(
            "INSERT INTO {$p}plugins (title, category, description, price_type, billing_cycle, image, status, created_at, updated_at) " .
            "VALUES ('Bongo OTT', 'VAS', 'Subscribe to Bongo OTT from your dashboard.', 'Free', 'monthly', NULL, 1, '2026-07-16 00:00:00', '2026-07-16 00:00:00')"
        );

        $result = $this->get('/');
        $body = (string) $result->getBody();

        $this->assertStringContainsString('lp-connect-plugins', $body);
        $this->assertStringContainsString('Bongo OTT', $body);
        $this->assertStringContainsString('href="' . route_to('route.plugins.index') . '"', $body);
    }

    public function testHomeTruncatesMultibytePluginDescriptionSafely(): void
    {
        $this->requireSqlite();

        // Multibyte (Bangla + Taka sign) description, well past the 110-char
        // truncation threshold, to guard against byte-based substr() mangling
        // UTF-8 (the bug this truncation logic was originally fixed for).
        $desc = str_repeat('বাংলা টেক্সট পরীক্ষা এবং বিবরণ ৳ ', 10);
        $this->assertGreaterThan(110, mb_strlen($desc, 'UTF-8'));

        $p = $this->db->DBPrefix;
        $this->db->query(
            "INSERT INTO {$p}plugins (title, category, description, price_type, billing_cycle, image, status, created_at, updated_at) " .
            'VALUES (' . $this->db->escape('Bangla Plugin') . ', ' . $this->db->escape('VAS') . ', ' .
            $this->db->escape($desc) . ", 'Free', 'monthly', NULL, 1, '2026-07-16 00:00:00', '2026-07-16 00:00:00')"
        );

        $result = $this->get('/');
        $body = (string) $result->getBody();

        $this->assertStringContainsString('lp-connect-plugins', $body);
        $this->assertStringContainsString('...', $body);
        $this->assertTrue(mb_check_encoding($body, 'UTF-8'), 'Response body must remain valid UTF-8 after multibyte truncation.');
    }
}
