# 02 — Redis: What to Use & What NOT to Use

> Decision guide for developers and operators.  
> Companion: [01 — Current state](./01-REDIS-CURRENT-STATE-AND-ARCHITECTURE.md) | [03 — Roadmap](./03-DATABASE-REDIS-IMPROVEMENT-ROADMAP.md)

---

## 1. Golden rules

1. **Redis is not your primary database.** MySQL remains source of truth for users, payments, customers, packages.
2. **Cache read-through only for derived/read-heavy data** — permissions, settings, dashboard aggregates, router widget payloads.
3. **Every cache key must have a TTL** (already enforced by CI4 handler on write).
4. **Never cache money or auth secrets** in shared Redis (see §3).
5. **Prefer query fixes + indexes first** for one-off slow endpoints; add Redis when the same read repeats many times per second.
6. **zapi and web should share helpers** (`getSetting`, `routerClient`, `tokensRevokedAfter`) — do not duplicate cache logic inside zapi controllers.

---

## 2. ✅ USE Redis for (approved patterns)

### 2.1 Web sessions

| Use | Why |
|-----|-----|
| `PredisHandler` / `RedisHandler` for `session.driver` | Shared sessions across PHP-FPM workers; avoids file lock contention |

**Requirements:** Redis always available in production; monitor session errors.

---

### 2.2 JWT revocation stamp

| Use | Why |
|-----|-----|
| `jwt_revoke_after_{userId}` via `revokeUserTokens()` | Stateless JWT needs server-side logout on password change |

**Use on:** every `zapijwt` route (already wired).  
**Do not** store full JWT denylist per `jti` unless you add `jti` to issued tokens (not current design).

---

### 2.3 Permission decisions (web)

| Use | Why |
|-----|-----|
| L2 cache in `userHasPermission()` | Collapses 25+ sidebar checks to Redis hits |

**Upgrade path:** implement full map cache `perm:{user_id}` (design C1) — one key per user, not per menu item.

**Invalidate:** always call `bumpPermissionCacheVersion()` when changing `permissions` or `custom_access` (models already do).

---

### 2.4 Settings (non-sensitive)

| Use | Why |
|-----|-----|
| `getSetting()` L2 for branding, app name, charges display | Heavy resolver logic + DB fallback |

**Already excluded from cache:** keys matching `isSensitiveSettingKey()` (SMS, SMTP, bkash, api_key, etc.).

---

### 2.5 Time-bounded dashboard / widget payloads

| Use | TTL | Why |
|-----|-----|-----|
| `dash_sadmin_{id}` | 30–60s | ~35 SQL queries per poll |
| `traffic_router_*` / `traffic_user_*` | 5s | MikroTik poll every ~3s |
| Future: `zapi:dash:{reseller_id}` | 30–60s | Reseller app dashboard |

**Rule:** time-based expiry only — do not try to invalidate on every payment/customer write.

---

### 2.6 Router circuit breaker

| Use | Why |
|-----|-----|
| `router_down_{id}` 45s | One dead MikroTik must not exhaust FPM pool (~11s × N workers) |

**Use for:** all paths through `routerClient()` — web and zapi.

---

### 2.7 Action cooldowns (zapi)

| Use | Why |
|-----|-----|
| `rc_cooldown_{action}_{userId}` | Prevent reboot/wifi/reconnect spam |

**Not a cache of business data** — rate limit only.

---

### 2.8 Operator flags (kill switches)

| Flag | Default | Purpose |
|------|---------|---------|
| `degrade_mode` | false | Shed load — serve cached dashboard, skip live router widgets |
| `live_router_widgets` | true | Gate MikroTik I/O on web traffic endpoints |
| `dashboard_polling` | true | Gate fresh SQL on sadmin dashboard |
| `login_throttle` | false | Enable `ThrottleFilter` on login |

Set via `setFlag()` / Redis Inspector / `php spark` tinker.

---

### 2.9 Local development

| Use | Config |
|-----|--------|
| Local `redis-server` | `cache.redis.host=127.0.0.1`, `session.savePath=tcp://127.0.0.1:6379?…` |

**Good for:** fast iteration, Redis Inspector UI, no Upstash latency.

---

### 2.10 Production (managed Redis)

| Use | Config |
|-----|--------|
| Upstash / TLS | `cache.redis.host=tls://….upstash.io`, password in `.env` |

**Good for:** multi-server, no local Redis ops.  
**Watch:** ~10–30ms RTT per cache read vs &lt;1ms local — still beats 50 MySQL queries.

---

## 3. ❌ DO NOT use Redis for

### 3.1 Money and billing state (hard no)

| Data | Why |
|------|-----|
| `users.fund` | Stale balance = wrong UI and support incidents |
| `subscription_status`, `will_expire`, `conn_status` | Real-time service state |
| Payment rows, invoice totals | Financial correctness |
| Reseller wallet / commission totals | Same |

**If caching user rows (C2):** strip money columns before `cache()->save()` — see `docs/production-optimization/04-REDIS-ARCHITECTURE.md` §5.8.

---

### 3.2 Credentials and secrets

| Data | Why |
|------|-----|
| SMS API keys, SMTP passwords | Rotation must take effect immediately (`isSensitiveSettingKey` already skips L2) |
| PPPoE secrets in Redis | Use `user_router_data` MySQL table; Redis is less access-controlled |
| Full session dumps in logs/Inspector on prod | Ops UI is dev-only; do not widen access |

---

### 3.3 Authoritative lists that change often

| Data | Why |
|------|-----|
| Customer DataTables (`Customer::fetch`) | Pagination + filters — cache key explosion; fix N+1 instead |
| Single customer detail on edit screen | Must be fresh |
| Permission **writes** | Write to MySQL, bust cache — never write permissions to Redis only |

---

### 3.4 Long-lived “set and forget” without TTL

| Anti-pattern | Fix |
|--------------|-----|
| `cache()->save($k, $v)` with no TTL | Always pass TTL seconds |
| `FLUSHDB` / `cache()->clean()` on shared DB | Deletes sessions |
| Caching `length=-1` unbounded query results | Cap page size server-side |

---

### 3.5 Queue on Redis (not current architecture)

| Item | Current |
|------|---------|
| Background jobs | **MySQL `jobs` table** via `JobQueue` |

**Do not** move queue to Redis DB 3 unless you implement the full worker design in `05-QUEUE-AND-CRON-REDESIGN.md`. Mixed queue systems = double complexity.

---

### 3.6 zapi response cache without auth scoping

| Anti-pattern | Why |
|--------------|-----|
| Cache reseller A's dashboard under key without `reseller_id` | BOLA / data leak |
| Cache traffic without `user_id` + role in key hash | Wrong PPPoE filter |

**Web pattern to copy:** `traffic_router_{id}_{md5(interface+role+userId)}`.

---

## 4. app/ vs zapi/ — responsibility split

| Concern | Belongs in | Reason |
|---------|------------|--------|
| New cache key design | `app/Helpers/` or `app/Libraries/` | Shared by web + zapi |
| Route-specific TTL | Controller or service | Caller knows poll interval |
| zapi-only cooldown | `zapi/Modules/Customer/Core/Services/CustomerBaseService` | OK — already there |
| Permission cache | `app/Helpers/user_helper.php` | Web only today; zapi uses JWT roles |
| API response shape | `zapi/` controllers | But call shared cache helpers, don't reimplement |

**Rule:** zapi should not grow its own parallel Redis layer — extend shared helpers.

---

## 5. When Redis helps vs when MySQL fixes help more

| Symptom | Fix first | Add Redis when |
|---------|-----------|----------------|
| Slow customer list (400+ queries) | Batch `whereIn`, kill N+1, add indexes | Same list requested 100+/sec unchanged |
| One slow report once per day | SQL rewrite | — |
| Sidebar slow every page | Permission map cache (partially done) | Full C1 map |
| Dashboard poll 3s | GROUP BY bandwidth + 30s cache | Already partial |
| Dead router melts server | Circuit breaker (done) | — |
| zapi traffic hammers MikroTik | **Add 5s cache** (missing) | High priority |

**Formula:** `Redis ROI = (reads_per_second × query_cost) − (reads_per_second × redis_rtt)`. If reads are rare, fix SQL only.

---

## 6. Security checklist

| Item | Status | Action |
|------|--------|--------|
| `.env` in git | ⚠️ staged | Remove secrets before public push; use `.env.example` |
| Redis Inspector | ✅ email-gated | Keep `info@isppaybd.com` only or env flag |
| Upstash token in commented `.env` | ⚠️ | Rotate if repo is public |
| JWT revoke fail-open | ✅ | By design — cache miss allows token |
| Permission cache fail-open | ✅ | Cache miss → MySQL |
| HTTPS on prod | required | Session cookie + Redis traffic |

---

## 7. Environment matrix

| Environment | cache.handler | session | Redis host | Notes |
|-------------|---------------|---------|------------|-------|
| Local XAMPP/WSL | `predis` | `PredisHandler` | `127.0.0.1` | Current setup |
| Production VPS + local Redis | `redis` (phpredis) | `RedisHandler` | `127.0.0.1` | Faster than Predis |
| Production Upstash | `predis` | `PredisHandler` | `tls://…` | Single DB, prefix separation |

---

## 8. Quick decision tree

```
Need to speed up a read?
├─ Is it money/payment/auth secret? → DO NOT CACHE
├─ Is it called 10+ times per request? → Per-request static (L1) first
├─ Is it same across requests for 30s+? → Redis L2 with TTL
├─ Is it zapi list endpoint? → Fix SQL batching first
├─ Is it MikroTik poll? → 5s Redis cache + circuit breaker
└─ Is it dashboard aggregate? → 30–60s Redis cache, degrade flags
```

---

## 9. Approved vs deferred summary table

| Feature | Use Redis? | Status |
|---------|------------|--------|
| Web sessions | ✅ Yes | Live |
| JWT revoke | ✅ Yes | Live |
| Permission L2 | ✅ Yes | Partial (not full map) |
| Settings L2 | ✅ Yes | Live |
| Web router traffic | ✅ Yes | Live |
| zapi router traffic | ✅ Should | **Not implemented** |
| getUserById | ✅ Should (stripped) | **Not implemented** |
| sAdmin dashboard read-through | ✅ Should | Write-only cache today |
| Reseller zapi dashboard | ✅ Should | **Not implemented** |
| Customer list | ❌ No (fix SQL) | — |
| users.fund | ❌ Never | — |
| Job queue | ❌ No (MySQL queue) | By design |

---

*Next: [03 — Database & Redis improvement roadmap](./03-DATABASE-REDIS-IMPROVEMENT-ROADMAP.md)*
