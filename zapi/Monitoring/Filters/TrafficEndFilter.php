<?php

namespace Zapi\Monitoring\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use Zapi\Monitoring\Config\TrafficMonitorConfig;
use Zapi\Monitoring\Domain\TrafficRecord;
use Zapi\Monitoring\Services\TrafficIgnoreService;
use Zapi\Monitoring\Storage\TrafficLogWriter;
use Zapi\Monitoring\Support\TrafficRequestContext;
use Zapi\utils\JwtToken;

class TrafficEndFilter implements FilterInterface
{
    // Cached JWT payload for this request (avoid re-verify on each accessor).
    private ?array $cachedJwtPayload = null;
    private TrafficIgnoreService $ignoreService;

    public function __construct()
    {
        $this->ignoreService = new TrafficIgnoreService();
    }

    public function before(RequestInterface $request, $arguments = null)
    {
        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        if (!TrafficMonitorConfig::enabled() || $this->ignoreService->shouldSkip($request)) {
            return null;
        }

        // One timestamp for everything — no redundant gmdate() calls.
        $end  = microtime(true);
        $stamp = new \DateTimeImmutable('now');

        $durationMs = max(0, (int) round(($end - TrafficRequestContext::getStartTime()) * 1000));
        $path       = '/' . ltrim($request->getUri()->getPath(), '/');
        $isApi      = strpos(trim($path, '/'), 'api/') === 0;

        // Resolve user/platform — cheap operations; JWT only decoded if Bearer present.
        $clientMeta = $this->resolveClientMeta($request);
        $userId     = $this->resolveUserId($request);
        $actorType  = $this->resolveActorType($request);
        $actorLabel = $this->resolveActorLabel($request);

        $record = TrafficRecord::make([
            'request_id'    => TrafficRequestContext::getRequestId(),
            'duration_ms'   => $durationMs,
            'path'          => $path,
            'endpoint_group'=> $isApi ? 'api' : 'web',
            'method'        => $request->getMethod(),
            'status_code'   => $response->getStatusCode(),
            'ip_address'    => $request->getIPAddress(),
            'user_agent'    => $request->getHeaderLine('User-Agent'),
            'referer'       => $request->getHeaderLine('Referer'),
            'is_api'        => $isApi,
            'is_web'        => !$isApi,
            'is_noise'      => false, // already passed shouldSkip above
            'client_source' => $clientMeta['client_source'],
            'device_type'   => $clientMeta['device_type'],
            'device_os'     => $clientMeta['device_os'],
            'device_browser'=> $clientMeta['device_browser'],
            'device_name'   => $clientMeta['device_name'],
            'user_id'       => $userId,
            'actor_type'    => $actorType,
            'actor_label'   => $actorLabel,
            'route_name'    => $this->resolveRouteName(),
            // Derive all time parts from one stamp — one allocation, zero extra syscalls.
            'year'          => $stamp->format('Y'),
            'month'         => $stamp->format('m'),
            'day'           => $stamp->format('d'),
            'hour'          => $stamp->format('H'),
            'minute'        => $stamp->format('i'),
            'started_at'    => (new \DateTimeImmutable('@' . (string) (int) TrafficRequestContext::getStartTime()))->format('c'),
            'ended_at'      => $stamp->format('c'),
            'created_at'    => $stamp->format('c'),
            '__stamp'       => $stamp, // passed to writer to avoid re-parse
        ]);

        TrafficLogWriter::appendWithStamp($record, $stamp);
        return null;
    }

    private function resolveUserId(RequestInterface $request): ?int
    {
        $session = Services::session();
        if ($session !== null) {
            $id = $session->get('id') ?? $session->get('user_id') ?? null;
            if (is_numeric($id)) {
                return (int) $id;
            }
        }

        $payload = $this->resolveJwtPayload($request);
        $id = is_array($payload) ? ($payload['sub'] ?? $payload['id'] ?? $payload['user_id'] ?? null) : null;
        return is_numeric($id) ? (int) $id : null;
    }

    private function resolveActorType(RequestInterface $request): string
    {
        $session = Services::session();
        if ($session !== null) {
            $role = $session->get('role') ?? $session->get('user_type') ?? '';
            if (is_string($role) && trim($role) !== '') {
                return strtolower(trim($role));
            }
        }

        $payload = $this->resolveJwtPayload($request);
        $role = is_array($payload) ? ($payload['role'] ?? '') : '';
        return is_string($role) ? strtolower(trim($role)) : '';
    }

    private function resolveActorLabel(RequestInterface $request): string
    {
        $session = Services::session();
        if ($session !== null) {
            $name = $session->get('name') ?? $session->get('username') ?? $session->get('email') ?? '';
            if (is_string($name) && trim($name) !== '') {
                return trim($name);
            }
        }

        $payload = $this->resolveJwtPayload($request);
        if (!is_array($payload)) {
            return '';
        }

        $label = $payload['email'] ?? $payload['name'] ?? $payload['username'] ?? null;
        if (is_string($label) && trim($label) !== '') {
            return trim($label);
        }

        $sub = $payload['sub'] ?? null;
        return is_numeric($sub) ? ('user:' . (int) $sub) : '';
    }

    private function resolveRouteName(): string
    {
        $router = Services::router();
        $route  = $router->controllerName() . '::' . $router->methodName();
        return trim($route, ':');
    }

    private function resolveClientMeta(RequestInterface $request): array
    {
        $uaRaw = (string) $request->getHeaderLine('User-Agent');
        $ua    = strtolower($uaRaw);

        // Client source: app (Flutter/OkHttp/Bearer) vs web browser.
        if (
            strpos($ua, 'okhttp') !== false
            || strpos($ua, 'dalvik') !== false
            || strpos($ua, 'cfnetwork') !== false
            || strpos($ua, 'flutter') !== false
            || strpos($ua, 'dart') !== false
            || $request->getHeaderLine('X-Requested-With') === 'com.isppaybd.app'
            || stripos((string) $request->getHeaderLine('Authorization'), 'Bearer ') === 0
        ) {
            $clientSource = 'app';
        } else {
            $clientSource = 'web';
        }

        // Device type from UA.
        if (strpos($ua, 'tablet') !== false || strpos($ua, 'ipad') !== false) {
            $deviceType = 'tablet';
        } elseif (strpos($ua, 'mobile') !== false || strpos($ua, 'android') !== false || strpos($ua, 'iphone') !== false) {
            $deviceType = 'mobile';
        } else {
            $deviceType = 'desktop';
        }

        $os      = $this->guessOs($ua);
        $browser = $this->guessBrowser($ua);

        return [
            'client_source'  => $clientSource,
            'device_type'    => $deviceType,
            'device_os'      => $os !== '' ? $os : 'unknown',
            'device_browser' => $browser !== '' ? $browser : 'unknown',
            'device_name'    => trim($os . ' ' . $browser) ?: 'Unknown Device',
        ];
    }

    private function guessOs(string $ua): string
    {
        if (strpos($ua, 'android') !== false) {
            return 'Android';
        }
        if (strpos($ua, 'iphone') !== false || strpos($ua, 'ipad') !== false || strpos($ua, 'ios') !== false) {
            return 'iOS';
        }
        if (strpos($ua, 'windows') !== false) {
            return 'Windows';
        }
        if (strpos($ua, 'mac os') !== false || strpos($ua, 'macintosh') !== false) {
            return 'macOS';
        }
        if (strpos($ua, 'linux') !== false) {
            return 'Linux';
        }
        return '';
    }

    private function guessBrowser(string $ua): string
    {
        if (strpos($ua, 'edg/') !== false) {
            return 'Edge';
        }
        if (strpos($ua, 'chrome/') !== false) {
            return 'Chrome';
        }
        if (strpos($ua, 'firefox/') !== false) {
            return 'Firefox';
        }
        if (strpos($ua, 'safari/') !== false) {
            return 'Safari';
        }
        if (strpos($ua, 'okhttp') !== false) {
            return 'OkHttp';
        }
        return '';
    }

    /** @return array<string,mixed>|null */
    private function resolveJwtPayload(RequestInterface $request): ?array
    {
        if ($this->cachedJwtPayload !== null) {
            return $this->cachedJwtPayload;
        }

        $authHeader = (string) $request->getHeaderLine('Authorization');
        if ($authHeader === '' || stripos($authHeader, 'Bearer ') !== 0) {
            return null;
        }

        $token = trim(substr($authHeader, 7));
        if ($token === '') {
            return null;
        }

        $payload = JwtToken::verify($token, JwtToken::secret());
        if (!is_array($payload)) {
            return null;
        }

        $this->cachedJwtPayload = $payload;
        return $this->cachedJwtPayload;
    }
}
