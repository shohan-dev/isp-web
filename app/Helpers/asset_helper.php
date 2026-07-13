<?php

/**
 * SaaS asset cache-busting (08 §5) — durable replacement for the hand-incremented
 * `?v=N` query string. `saas_css()` stamps the file's own `filemtime()` as the
 * version, so editing a file bumps its version automatically and every loader
 * agrees — no more `tokens.css` served at three different `?v=` across shells
 * (02 §13 / 08 §5 documents the drift this fixes).
 *
 * Usage in a view: <?= saas_css('tokens.css') ?>  instead of a hand-written
 * <link ... ?v=N"> tag. Falls back to today's date if the file is missing so a
 * broken path still emits a cacheable, changing value rather than a fatal error.
 */

if (! function_exists('saas_asset_version')) {
    function saas_asset_version(string $relativePath): string
    {
        $abs = FCPATH . $relativePath;

        return is_file($abs) ? (string) filemtime($abs) : date('Ymd');
    }
}

if (! function_exists('saas_css')) {
    function saas_css(string $file): string
    {
        $rel = 'assets/css/saas/' . $file;
        $v   = saas_asset_version($rel);

        return '<link rel="stylesheet" href="' . base_url($rel) . '?v=' . $v . '">';
    }
}

if (! function_exists('saas_js')) {
    function saas_js(string $file): string
    {
        $rel = 'assets/js/saas/' . $file;
        $v   = saas_asset_version($rel);

        return '<script src="' . base_url($rel) . '?v=' . $v . '"></script>';
    }
}

/**
 * Cache-busted URL for any asset, given its path relative to public/.
 *
 * public/.htaccess serves .css/.js/.woff2 with `Cache-Control: immutable,
 * max-age=31536000`. That is only safe when the URL changes whenever the file
 * does. Assets loaded as a bare base_url('assets/...') — or with a hand-pinned
 * ?v=14 that nobody remembers to bump — would otherwise be frozen in returning
 * users' browsers for a year after an edit.
 *
 * Usage: <script src="<?= asset_url('assets/js/script.js') ?>"></script>
 */
if (! function_exists('asset_url')) {
    function asset_url(string $relativePath): string
    {
        $rel = ltrim($relativePath, '/');

        return base_url($rel) . '?v=' . saas_asset_version($rel);
    }
}
