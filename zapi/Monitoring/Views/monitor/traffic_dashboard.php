<?php
$esc = static fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$scaleNames = [
    3 => 'Thousand',
    6 => 'Million',
    9 => 'Billion',
    12 => 'Trillion',
    15 => 'Quadrillion',
    18 => 'Quintillion',
    21 => 'Sextillion',
    24 => 'Septillion',
    27 => 'Octillion',
    30 => 'Nonillion',
    33 => 'Decillion',
    36 => 'Undecillion',
    39 => 'Duodecillion',
    42 => 'Tredecillion',
    45 => 'Quattuordecillion',
    48 => 'Quindecillion',
    51 => 'Sexdecillion',
    54 => 'Septendecillion',
    57 => 'Octodecillion',
    60 => 'Novemdecillion',
    63 => 'Vigintillion',
    66 => 'Unvigintillion',
    69 => 'Duovigintillion',
    72 => 'Tresvigintillion',
    75 => 'Quattuorvigintillion',
    78 => 'Quinvigintillion',
    81 => 'Sesvigintillion',
    84 => 'Septemvigintillion',
    87 => 'Octovigintillion',
    90 => 'Novemvigintillion',
    93 => 'Trigintillion',
    96 => 'Untrigintillion',
    99 => 'Duotrigintillion',
    100 => 'Googol',
];
$formatScale = static function ($v) use ($scaleNames): array {
    $n = is_numeric($v) ? (float) $v : 0.0;
    $abs = abs($n);
    $trim = static fn (float $x): string => rtrim(rtrim(number_format($x, 1, '.', ''), '0'), '.');
    if ($abs < 1_000) {
        return [
            'short' => number_format($n, 0, '.', ''),
            'full' => number_format($n, 0, '.', ','),
        ];
    }

    $shortSuffix = [
        3 => 'K', 6 => 'M', 9 => 'B', 12 => 'T',
        15 => 'Qa', 18 => 'Qi', 21 => 'Sx', 24 => 'Sp',
        27 => 'Oc', 30 => 'No', 33 => 'Dc', 36 => 'Ud',
        39 => 'Dd', 42 => 'Td', 45 => 'Qad', 48 => 'Qid',
        51 => 'Sxd', 54 => 'Spd', 57 => 'Od', 60 => 'Nd',
        63 => 'Vg', 66 => 'Uvg', 69 => 'Dvg', 72 => 'Tvg',
        75 => 'Qavg', 78 => 'Qivg', 81 => 'Svg', 84 => 'Spvg',
        87 => 'Ovg', 90 => 'Nvg', 93 => 'Tg', 96 => 'Utg',
        99 => 'Dtg', 100 => 'Googol',
    ];

    if ($abs >= 1e100) {
        $scaled = $n / 1e100;
        return [
            'short' => $trim($scaled) . 'Googol',
            'full' => $scaleNames[100] . ' (10^100): ' . number_format($n, 0, '.', ','),
        ];
    }

    $exp = (int) floor(log10($abs));
    $groupExp = (int) (floor($exp / 3) * 3);

    if (isset($scaleNames[$groupExp])) {
        $scaled = $n / (10 ** $groupExp);
        $suffix = $shortSuffix[$groupExp] ?? ('10^' . $groupExp);
        return [
            'short' => $trim($scaled) . $suffix,
            'full' => $scaleNames[$groupExp] . ' (10^' . $groupExp . '): ' . number_format($n, 0, '.', ','),
        ];
    }

    if ($exp >= 3 && $exp < 100) {
        $scaled = $n / (10 ** $exp);
        return [
            'short' => $trim($scaled) . 'e' . $exp,
            'full' => '10^' . $exp . ': ' . number_format($n, 0, '.', ','),
        ];
    }

    return [
        'short' => number_format($n, 0, '.', ''),
        'full' => number_format($n, 0, '.', ','),
    ];
};
$compact = static fn ($v): string => $formatScale($v)['short'];
$full = static fn ($v): string => $formatScale($v)['full'];
$currentKind = (string) ($activeFilters['kind'] ?? '');
$currentFrom = (string) ($activeFilters['from'] ?? '');
$currentTo = (string) ($activeFilters['to'] ?? '');
$currentUserId = isset($activeFilters['user_id']) && $activeFilters['user_id'] !== null ? (string) $activeFilters['user_id'] : '';
$currentPathContains = (string) ($activeFilters['path_contains'] ?? '');
$currentMethod = (string) ($activeFilters['method'] ?? '');
$currentStatusMin = isset($activeFilters['status_min']) && $activeFilters['status_min'] !== null ? (string) $activeFilters['status_min'] : '';
$currentStatusMax = isset($activeFilters['status_max']) && $activeFilters['status_max'] !== null ? (string) $activeFilters['status_max'] : '';
$currentClientSource = (string) ($activeFilters['client_source'] ?? '');
$comparison = is_array($comparison ?? null) ? $comparison : [];
$queueStats = is_array($queueStats ?? null) ? $queueStats : [];
$pendingFiles = (int) ($queueStats['pending_files'] ?? 0);
$pendingBytes = (int) ($queueStats['pending_bytes'] ?? 0);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Traffic Monitor Control Center</title>
    <style>
        :root {
            color-scheme: dark;
            --bg: #0b1220;
            --bg-soft: #111b2f;
            --card: #121f36;
            --line: #213453;
            --text: #e2ebff;
            --muted: #92a5c6;
            --primary: #5ea3ff;
            --success: #3dd598;
            --danger: #ff6b7b;
            --warning: #ffca6a;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Inter, Segoe UI, Roboto, Arial, sans-serif;
            background: radial-gradient(circle at 20% 0%, #172a4d 0%, var(--bg) 45%);
            color: var(--text);
        }
        .container {
            width: min(1480px, 100%);
            margin: 0 auto;
            padding: 18px;
        }
        .panel {
            background: linear-gradient(180deg, #142440 0%, #101c32 100%);
            border: 1px solid var(--line);
            border-radius: 14px;
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.25);
            animation: riseIn 320ms ease-out;
        }
        .header {
            padding: 16px 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        .title { margin: 0; font-size: 22px; font-weight: 700; letter-spacing: 0.2px; }
        .subtitle { margin: 6px 0 0; color: var(--muted); font-size: 13px; }
        .badge {
            border: 1px solid var(--line);
            border-radius: 999px;
            padding: 6px 12px;
            font-size: 12px;
            color: var(--muted);
            background: rgba(255, 255, 255, 0.03);
        }
        .controls {
            margin-top: 14px;
            display: grid;
            grid-template-columns: repeat(12, minmax(0, 1fr));
            gap: 10px;
            padding: 14px;
            border-top: 1px solid var(--line);
        }
        .control {
            display: flex;
            flex-direction: column;
            gap: 6px;
            grid-column: span 2;
        }
        .control.col-1 { grid-column: span 1; }
        .control.col-3 { grid-column: span 3; }
        .control label { color: var(--muted); font-size: 12px; }
        .control input, .control select, .control button {
            border: 1px solid #2a4063;
            background: #0e1930;
            color: var(--text);
            border-radius: 8px;
            padding: 9px 10px;
            font-size: 13px;
        }
        .control button { cursor: pointer; font-weight: 600; }
        .control .btn-primary { background: #1b4f93; border-color: #356ab0; }
        .control .btn-soft { background: #162742; }
        .metrics {
            margin-top: 14px;
            display: grid;
            grid-template-columns: repeat(7, minmax(150px, 1fr));
            gap: 10px;
        }
        .metric {
            padding: 12px;
            border: 1px solid var(--line);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.03);
        }
        .metric .k { color: var(--muted); font-size: 12px; }
        .metric .v { margin-top: 8px; font-size: 24px; font-weight: 700; }
        .metric .hint { margin-top: 5px; font-size: 11px; color: var(--muted); }
        .v.success { color: var(--success); }
        .v.danger { color: var(--danger); }
        .section-grid {
            margin-top: 14px;
            display: grid;
            grid-template-columns: 2fr 1.1fr;
            gap: 12px;
        }
        .card { padding: 14px; }
        .card h2 {
            margin: 0 0 10px;
            font-size: 15px;
            letter-spacing: 0.2px;
        }
        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 8px;
        }
        .toolbar select {
            border: 1px solid #2a4063;
            background: #0e1930;
            color: var(--text);
            border-radius: 8px;
            padding: 7px 9px;
            font-size: 12px;
        }
        .chart-wrap { border: 1px solid var(--line); border-radius: 10px; overflow: hidden; background: #0f1c34; }
        #timelineChart { width: 100%; height: 280px; display: block; }
        .table-wrap {
            border: 1px solid var(--line);
            border-radius: 10px;
            overflow: auto;
            max-height: 420px;
            background: #0f1c34;
        }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th, td { padding: 8px 10px; border-bottom: 1px solid #1f3150; text-align: left; white-space: nowrap; }
        th { position: sticky; top: 0; z-index: 1; background: #132343; color: #b7cbeb; }
        tr:hover td { background: #132746; }
        tbody tr { transition: transform 120ms ease, background-color 120ms ease; }
        tbody tr:hover { transform: translateY(-1px); }
        .status-pill {
            border-radius: 999px;
            padding: 2px 7px;
            font-weight: 700;
            font-size: 11px;
            display: inline-block;
        }
        .s-ok { background: rgba(61, 213, 152, 0.17); color: var(--success); }
        .s-err { background: rgba(255, 107, 123, 0.2); color: var(--danger); }
        .s-warn { background: rgba(255, 202, 106, 0.2); color: var(--warning); }
        .client-pill {
            border-radius: 999px;
            padding: 2px 8px;
            font-weight: 700;
            font-size: 11px;
            display: inline-block;
            border: 1px solid #2a4063;
            background: #162742;
            color: #c9daff;
            cursor: help;
        }
        .client-pill.app { background: rgba(94, 163, 255, 0.15); color: #88beff; border-color: #3d5f91; }
        .client-pill.web { background: rgba(61, 213, 152, 0.15); color: #82e8bd; border-color: #35694f; }
        .footer-note { margin-top: 10px; color: var(--muted); font-size: 12px; }
        @keyframes riseIn {
            from { transform: translateY(8px); opacity: 0.6; }
            to { transform: translateY(0); opacity: 1; }
        }
        @media (max-width: 1260px) {
            .controls { grid-template-columns: repeat(6, minmax(0, 1fr)); }
            .metrics { grid-template-columns: repeat(4, minmax(150px, 1fr)); }
            .section-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 820px) {
            .controls { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .metrics { grid-template-columns: repeat(2, minmax(140px, 1fr)); }
        }
    </style>
</head>
<body>
<div class="container">
    <section class="panel">
        <div class="header">
            <div>
                <h1 class="title">Traffic Monitor Control Center</h1>
                <p class="subtitle">Real-time request telemetry for API and web traffic.</p>
            </div>
            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                <div class="badge">Queue: <?= $esc($pendingFiles) ?> files / <?= $esc(number_format($pendingBytes)) ?> bytes</div>
                <div class="badge" id="lastUpdated">Generated: <?= $esc($generatedAt) ?></div>
            </div>
        </div>
        <div class="controls">
            <div class="control">
                <label for="kindFilter">Traffic Type</label>
                <select id="kindFilter">
                    <option value="" <?= $currentKind === '' ? 'selected' : '' ?>>All</option>
                    <option value="api" <?= $currentKind === 'api' ? 'selected' : '' ?>>API only</option>
                    <option value="web" <?= $currentKind === 'web' ? 'selected' : '' ?>>Web only</option>
                </select>
            </div>
            <div class="control">
                <label for="fromFilter">From</label>
                <input id="fromFilter" type="datetime-local" value="<?= $esc($currentFrom !== '' ? substr($currentFrom, 0, 16) : '') ?>">
            </div>
            <div class="control">
                <label for="toFilter">To</label>
                <input id="toFilter" type="datetime-local" value="<?= $esc($currentTo !== '' ? substr($currentTo, 0, 16) : '') ?>">
            </div>
            <div class="control col-3">
                <label for="pathContainsFilter">Path Contains</label>
                <input id="pathContainsFilter" type="text" value="<?= $esc($currentPathContains) ?>" placeholder="/api/customer">
            </div>
            <div class="control">
                <label for="methodFilter">Method</label>
                <select id="methodFilter">
                    <option value="" <?= $currentMethod === '' ? 'selected' : '' ?>>All</option>
                    <option value="GET" <?= strtoupper($currentMethod) === 'GET' ? 'selected' : '' ?>>GET</option>
                    <option value="POST" <?= strtoupper($currentMethod) === 'POST' ? 'selected' : '' ?>>POST</option>
                    <option value="PUT" <?= strtoupper($currentMethod) === 'PUT' ? 'selected' : '' ?>>PUT</option>
                    <option value="DELETE" <?= strtoupper($currentMethod) === 'DELETE' ? 'selected' : '' ?>>DELETE</option>
                </select>
            </div>
            <div class="control">
                <label for="sourceFilter">Source</label>
                <select id="sourceFilter">
                    <option value="" <?= $currentClientSource === '' ? 'selected' : '' ?>>All</option>
                    <option value="app" <?= strtolower($currentClientSource) === 'app' ? 'selected' : '' ?>>App</option>
                    <option value="web" <?= strtolower($currentClientSource) === 'web' ? 'selected' : '' ?>>Web</option>
                </select>
            </div>
            <div class="control col-1">
                <label for="statusMinFilter">Status Min</label>
                <input id="statusMinFilter" type="number" min="100" max="599" value="<?= $esc($currentStatusMin) ?>" placeholder="100">
            </div>
            <div class="control col-1">
                <label for="statusMaxFilter">Status Max</label>
                <input id="statusMaxFilter" type="number" min="100" max="599" value="<?= $esc($currentStatusMax) ?>" placeholder="599">
            </div>
            <div class="control">
                <label for="refreshEvery">Auto Refresh</label>
                <select id="refreshEvery">
                    <option value="0">Off</option>
                    <option value="5000">5 sec</option>
                    <option value="10000">10 sec</option>
                    <option value="30000" selected>30 sec</option>
                    <option value="60000">60 sec</option>
                </select>
            </div>
            <div class="control">
                <label for="userIdFilter">User ID</label>
                <input id="userIdFilter" type="number" min="1" step="1" value="<?= $esc($currentUserId) ?>" placeholder="e.g. 7971">
            </div>
            <div class="control">
                <label>&nbsp;</label>
                <button class="btn-primary" id="applyFilters">Apply Filters</button>
            </div>
            <div class="control">
                <label>&nbsp;</label>
                <button class="btn-soft" id="resetFilters">Reset</button>
            </div>
            <div class="control">
                <label>&nbsp;</label>
                <button class="btn-soft" id="refreshNow">Refresh Now</button>
            </div>
        </div>
    </section>

    <section class="metrics" id="metricsGrid">
        <article class="metric"><div class="k">Total Hits</div><div class="v" id="metricTotal" title="<?= $esc($full($overview['total_hits'] ?? 0)) ?>"><?= $esc($compact($overview['total_hits'] ?? 0)) ?></div><div class="hint">Filtered request count</div></article>
        <article class="metric"><div class="k">API Hits</div><div class="v success" id="metricApi" title="<?= $esc($full($overview['api_hits'] ?? 0)) ?>"><?= $esc($compact($overview['api_hits'] ?? 0)) ?></div><div class="hint">Requests marked as API</div></article>
        <article class="metric"><div class="k">Web Hits</div><div class="v" id="metricWeb" title="<?= $esc($full($overview['web_hits'] ?? 0)) ?>"><?= $esc($compact($overview['web_hits'] ?? 0)) ?></div><div class="hint">Requests marked as web</div></article>
        <article class="metric"><div class="k">Errors</div><div class="v danger" id="metricErrors" title="<?= $esc($full($overview['errors'] ?? 0)) ?>"><?= $esc($compact($overview['errors'] ?? 0)) ?></div><div class="hint">HTTP status &gt;= 400</div></article>
        <article class="metric"><div class="k">Error Rate</div><div class="v danger" id="metricErrorRate"><?= $esc($overview['error_rate_percent'] ?? 0) ?>%</div><div class="hint">Error ratio percentage</div></article>
        <article class="metric"><div class="k">Avg Latency</div><div class="v" id="metricAvg"><?= $esc($overview['avg_latency_ms'] ?? 0) ?>ms</div><div class="hint">Mean request duration</div></article>
        <article class="metric"><div class="k">P95 Latency</div><div class="v" id="metricP95"><?= $esc($overview['p95_latency_ms'] ?? 0) ?>ms</div><div class="hint">95th percentile latency</div></article>
    </section>
    <section class="panel card" style="margin-top:12px;">
        <h2>Daily Trend Comparison</h2>
        <div id="dayCompareText">
            <?php if (!empty($comparison)): ?>
                Today (<?= $esc($comparison['today_date'] ?? '-') ?>): <?= $esc($comparison['today_hits'] ?? 0) ?> hits |
                Yesterday (<?= $esc($comparison['yesterday_date'] ?? '-') ?>): <?= $esc($comparison['yesterday_hits'] ?? 0) ?> hits |
                Delta: <?= $esc($comparison['delta_hits'] ?? 0) ?> (<?= $esc($comparison['delta_percent'] ?? 0) ?>%)
            <?php else: ?>
                Daily comparison will appear after at least two summary days are available.
            <?php endif; ?>
        </div>
    </section>

    <section class="panel card" style="margin-top:12px;">
        <h2>Client & Device Summary</h2>
        <div class="table-wrap" style="max-height:none;">
            <table>
                <thead>
                <tr><th>Category</th><th>Top Values</th></tr>
                </thead>
                <tbody>
                <tr><td>Source</td><td id="deviceSummarySource">-</td></tr>
                <tr><td>Device Type</td><td id="deviceSummaryType">-</td></tr>
                <tr><td>OS</td><td id="deviceSummaryOs">-</td></tr>
                <tr><td>Browser</td><td id="deviceSummaryBrowser">-</td></tr>
                </tbody>
            </table>
        </div>
    </section>

    <section class="section-grid">
        <article class="panel card">
            <div class="toolbar">
                <h2>Traffic Timeline</h2>
                <div>
                    <select id="bucketSelect">
                        <option value="minute" selected>Minute</option>
                        <option value="hourly">Hourly</option>
                        <option value="daily">Daily</option>
                        <option value="monthly">Monthly</option>
                    </select>
                </div>
            </div>
            <div class="chart-wrap">
                <canvas id="timelineChart" width="1000" height="280"></canvas>
            </div>
            <div class="footer-note">Dragless compact line chart; updates automatically with selected refresh interval.</div>
        </article>
        <article class="panel card">
            <h2>Top Endpoints</h2>
            <div class="chart-wrap" style="margin-bottom:10px;">
                <canvas id="endpointBarChart" width="700" height="180"></canvas>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr><th>Path</th><th>Hits</th><th>Errors</th><th>Avg Latency</th></tr>
                    </thead>
                    <tbody id="topEndpointsBody">
                    <?php foreach ($topEndpoints as $item): ?>
                        <tr>
                            <td><?= $esc($item['path'] ?? '') ?></td>
                            <td><?= $esc($item['hits'] ?? 0) ?></td>
                            <td><?= $esc($item['errors'] ?? 0) ?></td>
                            <td><?= $esc($item['avg_latency_ms'] ?? 0) ?>ms</td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </article>
    </section>

    <section class="panel card" style="margin-top:12px;">
        <h2>Recent Requests</h2>
        <div class="table-wrap" style="max-height:500px;">
            <table>
                <thead>
                <tr>
                    <th>Time</th><th>Method</th><th>Path</th><th>Status</th><th>Duration</th><th>IP</th><th>User</th><th>Client</th>
                </tr>
                </thead>
                <tbody id="recentBody">
                <?php foreach ($recent as $row): ?>
                    <?php $status = (int) ($row['status_code'] ?? 0); ?>
                    <?php
                        $source = (string) ($row['client_source'] ?? (!empty($row['is_api']) ? 'app' : 'web'));
                        $deviceName = (string) ($row['device_name'] ?? 'Unknown Device');
                        $deviceType = (string) ($row['device_type'] ?? 'unknown');
                        $deviceOs = (string) ($row['device_os'] ?? 'unknown');
                        $deviceBrowser = (string) ($row['device_browser'] ?? 'unknown');
                        $uaText = (string) ($row['user_agent'] ?? '');
                        $hoverTitle = 'Device: ' . $deviceName . ' | Type: ' . $deviceType . ' | OS: ' . $deviceOs . ' | Browser: ' . $deviceBrowser . ' | UA: ' . $uaText;
                    ?>
                    <tr>
                        <td><?= $esc($row['created_at'] ?? '') ?></td>
                        <td><?= $esc($row['method'] ?? '') ?></td>
                        <td><?= $esc($row['path'] ?? '') ?></td>
                        <td>
                            <span class="status-pill <?= $status >= 500 ? 's-err' : ($status >= 400 ? 's-warn' : 's-ok') ?>">
                                <?= $esc($status) ?>
                            </span>
                        </td>
                        <td><?= $esc($row['duration_ms'] ?? 0) ?>ms</td>
                        <td><?= $esc($row['ip_address'] ?? '') ?></td>
                        <td><?= $esc($row['user_id'] ?? '-') ?></td>
                        <td>
                            <span
                                class="client-pill <?= $source === 'app' ? 'app' : 'web' ?>"
                                title="<?= $esc($hoverTitle) ?>"
                            >
                                <?= $esc($source) ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
<script>
(() => {
    const state = {
        timeline: <?= json_encode($timeline, JSON_UNESCAPED_SLASHES) ?>,
        topEndpoints: <?= json_encode($topEndpoints, JSON_UNESCAPED_SLASHES) ?>,
        deviceSummary: <?= json_encode($deviceSummary ?? [], JSON_UNESCAPED_SLASHES) ?>,
        comparison: <?= json_encode($comparison ?? [], JSON_UNESCAPED_SLASHES) ?>,
        refreshTimer: null,
    };
    const scaleNames = {
        3: 'Thousand', 6: 'Million', 9: 'Billion', 12: 'Trillion', 15: 'Quadrillion', 18: 'Quintillion',
        21: 'Sextillion', 24: 'Septillion', 27: 'Octillion', 30: 'Nonillion', 33: 'Decillion', 36: 'Undecillion',
        39: 'Duodecillion', 42: 'Tredecillion', 45: 'Quattuordecillion', 48: 'Quindecillion', 51: 'Sexdecillion',
        54: 'Septendecillion', 57: 'Octodecillion', 60: 'Novemdecillion', 63: 'Vigintillion', 66: 'Unvigintillion',
        69: 'Duovigintillion', 72: 'Tresvigintillion', 75: 'Quattuorvigintillion', 78: 'Quinvigintillion',
        81: 'Sesvigintillion', 84: 'Septemvigintillion', 87: 'Octovigintillion', 90: 'Novemvigintillion',
        93: 'Trigintillion', 96: 'Untrigintillion', 99: 'Duotrigintillion', 100: 'Googol'
    };
    const shortSuffix = {
        3: 'K', 6: 'M', 9: 'B', 12: 'T', 15: 'Qa', 18: 'Qi', 21: 'Sx', 24: 'Sp', 27: 'Oc', 30: 'No',
        33: 'Dc', 36: 'Ud', 39: 'Dd', 42: 'Td', 45: 'Qad', 48: 'Qid', 51: 'Sxd', 54: 'Spd', 57: 'Od',
        60: 'Nd', 63: 'Vg', 66: 'Uvg', 69: 'Dvg', 72: 'Tvg', 75: 'Qavg', 78: 'Qivg', 81: 'Svg',
        84: 'Spvg', 87: 'Ovg', 90: 'Nvg', 93: 'Tg', 96: 'Utg', 99: 'Dtg', 100: 'Googol'
    };
    const base = '/api/monitor';
    const els = {
        kind: document.getElementById('kindFilter'),
        from: document.getElementById('fromFilter'),
        to: document.getElementById('toFilter'),
        userId: document.getElementById('userIdFilter'),
        pathContains: document.getElementById('pathContainsFilter'),
        method: document.getElementById('methodFilter'),
        source: document.getElementById('sourceFilter'),
        statusMin: document.getElementById('statusMinFilter'),
        statusMax: document.getElementById('statusMaxFilter'),
        refresh: document.getElementById('refreshEvery'),
        apply: document.getElementById('applyFilters'),
        reset: document.getElementById('resetFilters'),
        refreshNow: document.getElementById('refreshNow'),
        lastUpdated: document.getElementById('lastUpdated'),
        bucket: document.getElementById('bucketSelect'),
        topBody: document.getElementById('topEndpointsBody'),
        recentBody: document.getElementById('recentBody'),
        chart: document.getElementById('timelineChart'),
        endpointChart: document.getElementById('endpointBarChart'),
        deviceSummarySource: document.getElementById('deviceSummarySource'),
        deviceSummaryType: document.getElementById('deviceSummaryType'),
        deviceSummaryOs: document.getElementById('deviceSummaryOs'),
        deviceSummaryBrowser: document.getElementById('deviceSummaryBrowser'),
        dayCompareText: document.getElementById('dayCompareText'),
    };

    function qs() {
        const params = new URLSearchParams();
        if (els.kind.value) params.set('kind', els.kind.value);
        if (els.from.value) params.set('from', new Date(els.from.value).toISOString());
        if (els.to.value) params.set('to', new Date(els.to.value).toISOString());
        if (els.userId.value) params.set('user_id', String(els.userId.value));
        if (els.pathContains.value) params.set('path_contains', els.pathContains.value);
        if (els.method.value) params.set('method', els.method.value);
        if (els.source.value) params.set('client_source', els.source.value);
        if (els.statusMin.value) params.set('status_min', String(els.statusMin.value));
        if (els.statusMax.value) params.set('status_max', String(els.statusMax.value));
        return params.toString();
    }

    function endpoint(path) {
        const query = qs();
        return query ? `${base}/${path}?${query}` : `${base}/${path}`;
    }

    function fmtNumber(v) {
        const n = Number(v || 0);
        return Number.isFinite(n) ? n.toLocaleString() : '0';
    }

    function fmtCompact(v, decimals = 1) {
        const n = Number(v || 0);
        if (!Number.isFinite(n)) return '0';
        const abs = Math.abs(n);
        const sign = n < 0 ? '-' : '';

        if (abs >= 1e100) {
            const scaled = abs / 1e100;
            return `${sign}${trimFixed(scaled, decimals)}Googol`;
        }
        if (abs >= 1e3) {
            const exp = Math.floor(Math.log10(abs));
            const group = Math.floor(exp / 3) * 3;
            const suffix = shortSuffix[group];
            if (suffix) {
                const scaled = abs / (10 ** group);
                return `${sign}${trimFixed(scaled, decimals)}${suffix}`;
            }
            const scaled = abs / (10 ** exp);
            return `${sign}${trimFixed(scaled, decimals)}e${exp}`;
        }

        return `${n.toFixed(0)}`;
    }

    function describeScale(v) {
        const n = Number(v || 0);
        if (!Number.isFinite(n)) return '0';
        const abs = Math.abs(n);
        if (abs < 1e3) return fmtNumber(n);
        if (abs >= 1e100) return `Googol (10^100): ${fmtNumber(n)}`;

        const exp = Math.floor(Math.log10(abs));
        const group = Math.floor(exp / 3) * 3;
        const name = scaleNames[group];
        if (name) return `${name} (10^${group}): ${fmtNumber(n)}`;
        return `10^${exp}: ${fmtNumber(n)}`;
    }

    function trimFixed(value, decimals) {
        return value.toFixed(decimals).replace(/\.?0+$/, '');
    }

    function setText(id, value) {
        const node = document.getElementById(id);
        if (node) node.textContent = value;
    }

    function setMetric(id, rawValue) {
        const node = document.getElementById(id);
        if (!node) return;
        node.textContent = fmtCompact(rawValue);
        node.title = describeScale(rawValue);
        node.dataset.full = describeScale(rawValue);
        node.onclick = () => { node.title = node.dataset.full || ''; };
    }

    function renderOverview(data) {
        setMetric('metricTotal', data.total_hits);
        setMetric('metricApi', data.api_hits);
        setMetric('metricWeb', data.web_hits);
        setMetric('metricErrors', data.errors);
        setText('metricErrorRate', `${data.error_rate_percent || 0}%`);
        setText('metricAvg', `${data.avg_latency_ms || 0}ms`);
        setText('metricP95', `${data.p95_latency_ms || 0}ms`);
    }

    function renderTopEndpoints(rows) {
        els.topBody.innerHTML = '';
        if (!Array.isArray(rows) || rows.length === 0) {
            els.topBody.innerHTML = '<tr><td colspan="4">No endpoint data found for selected filters.</td></tr>';
            return;
        }
        rows.forEach((row) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `<td>${escapeHtml(row.path || '')}</td><td>${fmtNumber(row.hits)}</td><td>${fmtNumber(row.errors)}</td><td>${fmtNumber(row.avg_latency_ms)}ms</td>`;
            els.topBody.appendChild(tr);
        });
        state.topEndpoints = rows;
        renderEndpointBarChart(rows);
    }

    function renderEndpointBarChart(rows) {
        const c = els.endpointChart;
        const ctx = c.getContext('2d');
        const w = c.width;
        const h = c.height;
        ctx.clearRect(0, 0, w, h);
        ctx.fillStyle = '#0f1c34';
        ctx.fillRect(0, 0, w, h);
        const top = (Array.isArray(rows) ? rows : []).slice(0, 8);
        if (top.length === 0) {
            ctx.fillStyle = '#92a5c6';
            ctx.font = '12px Segoe UI';
            ctx.fillText('No endpoint data for bar chart.', 16, 24);
            return;
        }
        const padL = 24;
        const padR = 16;
        const padT = 12;
        const padB = 24;
        const chartW = w - padL - padR;
        const chartH = h - padT - padB;
        const max = Math.max(...top.map((r) => Number(r.hits || 0)), 1);
        const gap = 8;
        const barW = Math.floor((chartW - gap * (top.length - 1)) / top.length);
        top.forEach((row, idx) => {
            const value = Number(row.hits || 0);
            const bh = Math.max(2, Math.round((value / max) * chartH));
            const x = padL + idx * (barW + gap);
            const y = padT + (chartH - bh);
            ctx.fillStyle = '#5ea3ff';
            ctx.fillRect(x, y, barW, bh);
            ctx.fillStyle = '#b7cbeb';
            ctx.font = '10px Segoe UI';
            ctx.fillText(String(value), x, Math.max(10, y - 2));
        });
    }

    function formatSummaryBucket(bucket) {
        if (!bucket || typeof bucket !== 'object') return '-';
        const rows = Object.entries(bucket)
            .sort((a, b) => Number(b[1] || 0) - Number(a[1] || 0))
            .slice(0, 5)
            .map(([k, v]) => `${k} (${fmtNumber(v)})`);
        return rows.length > 0 ? rows.join(' | ') : '-';
    }

    function renderDeviceSummary(summary) {
        state.deviceSummary = summary || {};
        els.deviceSummarySource.textContent = formatSummaryBucket(state.deviceSummary.source);
        els.deviceSummaryType.textContent = formatSummaryBucket(state.deviceSummary.device_type);
        els.deviceSummaryOs.textContent = formatSummaryBucket(state.deviceSummary.device_os);
        els.deviceSummaryBrowser.textContent = formatSummaryBucket(state.deviceSummary.device_browser);
    }

    function renderComparison(comparison) {
        state.comparison = comparison || {};
        if (!state.comparison.today_date || !state.comparison.yesterday_date) {
            els.dayCompareText.textContent = 'Daily comparison will appear after at least two summary days are available.';
            return;
        }
        els.dayCompareText.textContent = `Today (${state.comparison.today_date}): ${fmtNumber(state.comparison.today_hits)} hits | Yesterday (${state.comparison.yesterday_date}): ${fmtNumber(state.comparison.yesterday_hits)} hits | Delta: ${fmtNumber(state.comparison.delta_hits)} (${state.comparison.delta_percent || 0}%)`;
    }

    function statusClass(code) {
        if (code >= 500) return 's-err';
        if (code >= 400) return 's-warn';
        return 's-ok';
    }

    function renderRecent(rows) {
        els.recentBody.innerHTML = '';
        if (!Array.isArray(rows) || rows.length === 0) {
            els.recentBody.innerHTML = '<tr><td colspan="8">No recent requests found for selected filters.</td></tr>';
            return;
        }
        rows.forEach((row) => {
            const sc = Number(row.status_code || 0);
            const client = detectClientMeta(row);
            const hover = `Device: ${client.deviceName} | Type: ${client.deviceType} | OS: ${client.deviceOs} | Browser: ${client.deviceBrowser} | UA: ${row.user_agent || ''}`;
            const tr = document.createElement('tr');
            tr.innerHTML = `<td>${escapeHtml(row.created_at || '')}</td>
                <td>${escapeHtml(row.method || '')}</td>
                <td>${escapeHtml(row.path || '')}</td>
                <td><span class="status-pill ${statusClass(sc)}">${sc}</span></td>
                <td>${fmtNumber(row.duration_ms)}ms</td>
                <td>${escapeHtml(row.ip_address || '')}</td>
                <td>${escapeHtml(row.user_id ?? '-')}</td>
                <td><span class="client-pill ${client.source === 'app' ? 'app' : 'web'}" title="${escapeHtml(hover)}">${escapeHtml(client.source)}</span></td>`;
            els.recentBody.appendChild(tr);
        });
    }

    function detectClientMeta(row) {
        const sourceRaw = String(row.client_source || '').toLowerCase();
        const uaRaw = String(row.user_agent || '');
        const ua = uaRaw.toLowerCase();
        const source = sourceRaw || (
            ua.includes('okhttp')
            || ua.includes('dalvik')
            || ua.includes('flutter')
            || ua.includes('dart')
            || ua.includes('cfnetwork')
            ? 'app'
            : (row.is_api ? 'app' : 'web')
        );

        let deviceType = String(row.device_type || '').toLowerCase();
        if (!deviceType) {
            if (ua.includes('ipad') || ua.includes('tablet')) deviceType = 'tablet';
            else if (ua.includes('mobile') || ua.includes('android') || ua.includes('iphone')) deviceType = 'mobile';
            else deviceType = 'desktop';
        }

        const deviceOs = String(row.device_os || '').trim() || guessOs(ua);
        const deviceBrowser = String(row.device_browser || '').trim() || guessBrowser(ua);
        const deviceName = String(row.device_name || '').trim() || `${deviceOs} ${deviceBrowser}`.trim() || 'Unknown Device';

        return {
            source,
            deviceType,
            deviceOs,
            deviceBrowser,
            deviceName,
        };
    }

    function guessOs(ua) {
        if (ua.includes('windows')) return 'Windows';
        if (ua.includes('android')) return 'Android';
        if (ua.includes('iphone') || ua.includes('ipad') || ua.includes('ios')) return 'iOS';
        if (ua.includes('mac os') || ua.includes('macintosh')) return 'macOS';
        if (ua.includes('linux')) return 'Linux';
        return 'unknown';
    }

    function guessBrowser(ua) {
        if (ua.includes('edg/')) return 'Edge';
        if (ua.includes('chrome/')) return 'Chrome';
        if (ua.includes('firefox/')) return 'Firefox';
        if (ua.includes('safari/') && !ua.includes('chrome/')) return 'Safari';
        if (ua.includes('okhttp')) return 'OkHttp';
        return 'unknown';
    }

    function renderChart() {
        const bucket = els.bucket.value;
        const rows = Array.isArray(state.timeline[bucket]) ? state.timeline[bucket] : [];
        const points = rows.slice(-40);
        const c = els.chart;
        const ctx = c.getContext('2d');
        const w = c.width;
        const h = c.height;
        ctx.clearRect(0, 0, w, h);
        ctx.fillStyle = '#0f1c34';
        ctx.fillRect(0, 0, w, h);

        const pad = { t: 16, r: 16, b: 30, l: 44 };
        const cw = w - pad.l - pad.r;
        const ch = h - pad.t - pad.b;
        ctx.strokeStyle = '#2a3f62';
        ctx.lineWidth = 1;
        for (let i = 0; i <= 4; i++) {
            const y = pad.t + (ch / 4) * i;
            ctx.beginPath();
            ctx.moveTo(pad.l, y);
            ctx.lineTo(w - pad.r, y);
            ctx.stroke();
        }
        if (points.length === 0) {
            ctx.fillStyle = '#92a5c6';
            ctx.font = '12px Segoe UI';
            ctx.fillText('No timeline data for selected filters.', 16, 22);
            return;
        }
        const maxHits = Math.max(...points.map((p) => Number(p.hits || 0)), 1);
        const xStep = points.length > 1 ? cw / (points.length - 1) : cw;

        ctx.beginPath();
        points.forEach((p, i) => {
            const x = pad.l + i * xStep;
            const y = pad.t + ch - ((Number(p.hits || 0) / maxHits) * ch);
            if (i === 0) ctx.moveTo(x, y); else ctx.lineTo(x, y);
        });
        ctx.strokeStyle = '#5ea3ff';
        ctx.lineWidth = 2;
        ctx.stroke();

        ctx.fillStyle = '#b7cbeb';
        ctx.font = '11px Segoe UI';
        ctx.fillText('0', 22, pad.t + ch + 3);
        ctx.fillText(String(maxHits), 14, pad.t + 3);
        const first = points[0].period || '';
        const last = points[points.length - 1].period || '';
        ctx.fillText(String(first), pad.l, h - 10);
        const tw = ctx.measureText(String(last)).width;
        ctx.fillText(String(last), w - pad.r - tw, h - 10);
    }

    function escapeHtml(v) {
        return String(v)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    async function refreshAll() {
        try {
            const snapshot = await fetch(endpoint('snapshot')).then((r) => r.json());
            renderOverview(snapshot.overview || {});
            renderTopEndpoints(snapshot.top_endpoints || []);
            state.timeline = snapshot.timeline || {};
            renderChart();
            renderRecent(snapshot.recent || []);
            renderDeviceSummary(snapshot.device_summary || {});
            renderComparison(snapshot.comparison || {});
            els.lastUpdated.textContent = `Updated: ${new Date().toLocaleString()}`;
        } catch (err) {
            console.error(err);
            els.lastUpdated.textContent = 'Update failed: check monitor endpoints';
        }
    }

    function restartRefreshTimer() {
        if (state.refreshTimer) {
            clearInterval(state.refreshTimer);
            state.refreshTimer = null;
        }
        const ms = Number(els.refresh.value || 0);
        if (ms > 0) {
            state.refreshTimer = setInterval(refreshAll, ms);
        }
    }

    els.apply.addEventListener('click', () => {
        refreshAll();
    });
    els.reset.addEventListener('click', () => {
        els.kind.value = '';
        els.from.value = '';
        els.to.value = '';
        els.userId.value = '';
        els.pathContains.value = '';
        els.method.value = '';
        els.source.value = '';
        els.statusMin.value = '';
        els.statusMax.value = '';
        refreshAll();
    });
    els.refreshNow.addEventListener('click', refreshAll);
    els.refresh.addEventListener('change', restartRefreshTimer);
    els.bucket.addEventListener('change', renderChart);
    window.addEventListener('resize', renderChart);

    restartRefreshTimer();
    renderChart();
    renderEndpointBarChart(state.topEndpoints || []);
    renderDeviceSummary(state.deviceSummary);
    renderComparison(state.comparison);
})();
</script>
</body>
</html>

