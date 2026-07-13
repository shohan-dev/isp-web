<?php

namespace App\Controllers;

use App\Models\SidebarPinModel;

class SidebarPin extends BaseController
{
    protected SidebarPinModel $pinModel;

    public function __construct()
    {
        $this->pinModel = model(SidebarPinModel::class);
        helper('utility');
    }

    public function toggle()
    {
        $userId = (int) getSession('user_id');
        if ($userId <= 0) {
            return requestResponse('error', 'Not logged in', 401);
        }

        $pinKey = trim((string) getRawInput('pin_key'));
        $label  = trim((string) getRawInput('label'));
        $href   = trim((string) getRawInput('href'));
        $icon   = trim((string) (getRawInput('icon') ?? ''));

        if ($pinKey === '' || $label === '' || $href === '') {
            return requestResponse('error', 'Missing pin details', 400);
        }

        // Only ever store a same-origin relative path. Allowlist, not denylist —
        // a scheme denylist (blocking "javascript:", "//") is bypassable via case
        // ("JAVASCRIPT:") or embedded control characters browsers strip when
        // parsing a URL ("java\tscript:..."), and esc('attr') on output doesn't
        // neutralize a javascript: URI's danger, only attribute breakout. Strip
        // control chars first, then require a single leading "/" and reject "//"
        // (protocol-relative, which would still be off-site).
        $href = preg_replace('/[\x00-\x1F\x7F]/', '', $href);
        if ($href === '' || $href[0] !== '/' || (isset($href[1]) && $href[1] === '/')) {
            return requestResponse('error', 'Invalid link', 400);
        }

        $result = $this->pinModel->toggle($userId, substr($pinKey, 0, 191), substr($label, 0, 150), substr($icon, 0, 60), substr($href, 0, 255));

        if (! $result['pinned'] && ($result['reason'] ?? '') === 'limit') {
            return requestResponse('error', 'You can pin up to ' . SidebarPinModel::MAX_PINS_PER_USER . ' items — unpin something first.', 422);
        }

        return requestResponse('success', ['pinned' => $result['pinned']], 200);
    }
}
