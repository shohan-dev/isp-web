<?php

namespace App\Services;

use App\Models\AuditLogModel;
use App\Models\User;

/**
 * Single insertion point for audit trail writes (F1 scaffold).
 */
class AuditService
{
    /**
     * @param int|string|null $actorUserId Explicit actor id — required for callers with
     *                                     no native CI session (e.g. zapi's stateless JWT
     *                                     auth), where getSession() has nothing to read.
     * @param string|null $actorName Explicit display name; resolved from the users table
     *                               when omitted (no session key ever stores a display
     *                               name, so falling back to session lookups always
     *                               produced 'system' regardless of who acted).
     */
    public function record(
        string $action,
        ?string $entity = null,
        array $details = [],
        $client = null,
        $router = null,
        $actorUserId = null,
        ?string $actorName = null
    ): void {
        $userId = $actorUserId ?? (function_exists('getSession') ? getSession('user_id') : null);
        $actor  = $actorName ?? $this->resolveActorName($userId);

        $request = service('request');
        $ip = $request ? (string) $request->getIPAddress() : '';
        $ua = $request ? (string) $request->getUserAgent() : '';

        $payload = $details;
        if ($client !== null) {
            $payload['client'] = $client;
        }
        if ($router !== null) {
            $payload['router'] = $router;
        }

        (new AuditLogModel())->log([
            'actor'      => $actor,
            'user_id'    => $userId,
            'action'     => $action,
            'entity'     => $entity,
            'ip_address' => $ip,
            'user_agent' => $ua,
            'details'    => json_encode($payload),
        ]);
    }

    private function resolveActorName($userId): string
    {
        if (empty($userId)) {
            return 'system';
        }

        try {
            $user = (new User())->select('name')->find($userId);
        } catch (\Throwable $e) {
            return 'system';
        }

        return (string) ($user->name ?? 'system');
    }
}
