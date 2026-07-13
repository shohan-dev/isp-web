<?php

namespace Zapi\Modules\Reseller\Core\Services;

use Zapi\Core\Base\BaseApiController;
use Zapi\Core\Support\Auth\JwtToken;

/**
 * Shared base for reseller-portal reward/referral services.
 *
 * Reseller endpoints receive the reseller id as a route :num and scope every
 * query by it. This base adds JWT actor/role resolution + ownership checks so
 * a reseller can only act on their own scope, while sAdmin/admin may act on any.
 */
class ResellerBaseService extends BaseApiController
{
    protected $user_model;
    protected $payment_model;
    protected ?array $rawInputCache = null;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->user_model    = model('App\Models\User');
        $this->payment_model = model('App\Models\Payment');
    }

    protected function getInputValue(string $key, $default = null)
    {
        if ($this->rawInputCache === null) {
            $parsed = $this->request->getJSON(true);
            if (!is_array($parsed)) {
                $parsed = $this->request->getRawInput();
            }
            $this->rawInputCache = is_array($parsed) ? $parsed : [];
        }

        $postValue = $this->request->getPost($key);
        if ($postValue !== null && $postValue !== '') {
            return $postValue;
        }
        if (array_key_exists($key, $this->rawInputCache) && $this->rawInputCache[$key] !== '') {
            return $this->rawInputCache[$key];
        }
        $getValue = $this->request->getGet($key);
        if ($getValue !== null && $getValue !== '') {
            return $getValue;
        }
        return $default;
    }

    protected function jwtPayload(): ?array
    {
        $authHeader = (string) $this->request->getHeaderLine('Authorization');
        if ($authHeader === '' || stripos($authHeader, 'Bearer ') !== 0) {
            return null;
        }
        $token = trim(substr($authHeader, 7));
        if (stripos($token, 'bearer ') === 0) {
            $token = trim(substr($token, 7));
        }
        $payload = JwtToken::verify($token, \Zapi\utils\JwtToken::secret());
        return is_array($payload) ? $payload : null;
    }

    protected function actorId(): ?int
    {
        $p = $this->jwtPayload();
        if (!$p) {
            return null;
        }
        $uid = $p['sub'] ?? $p['user_id'] ?? null;
        return ($uid !== null && $uid !== '') ? (int) $uid : null;
    }

    protected function actorRole(): string
    {
        $p = $this->jwtPayload();
        return strtolower((string) ($p['role'] ?? ''));
    }

    /**
     * Tenant-or-platform admin tier — used by canAccessReseller() so an
     * ISP-owner tenant admin ('admin', post-rename) can act on any reseller
     * under their scope, same as the platform owner ('super_admin').
     */
    protected function isSuperAdmin(): bool
    {
        return in_array($this->actorRole(), ['admin', 'super_admin'], true);
    }

    /**
     * Strictly the platform/SaaS owner — post role-rename this is 'super_admin'
     * only. 'admin' is now the tenant tier and must NOT pass this gate (unlike
     * isSuperAdmin(), which is deliberately broader for reseller-scoped access).
     * Use this for truly platform-global actions (e.g. SaaS-wide config).
     */
    protected function isPlatformOwner(): bool
    {
        return $this->actorRole() === 'super_admin';
    }

    /**
     * A reseller may act on their own id; the platform owner may act on any;
     * a tenant admin may act on any reseller belonging to THEIR OWN tenant
     * only. When no token is resolvable (auth disabled for the env) the route
     * filter is the gate.
     *
     * Previously this fell through to isSuperAdmin() (true for either 'admin'
     * or 'super_admin' with no tenant check at all), letting any tenant admin
     * act on ANY reseller system-wide — a cross-tenant privilege escalation.
     * getSAdminIdForUser() walks the admin_id chain to the owning tenant
     * admin, mirroring the same ownership pattern used elsewhere in this
     * codebase (e.g. CustomerBaseService::actorCanAccessUser()).
     */
    protected function canAccessReseller(int $resellerId): bool
    {
        $actor = $this->actorId();
        if ($actor === null) {
            return true;
        }
        if ($actor === $resellerId) {
            return true;
        }
        if ($this->isPlatformOwner()) {
            return true;
        }
        if ($this->actorRole() === 'admin') {
            helper('user');

            return (int) getSAdminIdForUser($resellerId) === $actor;
        }

        return false;
    }
}
