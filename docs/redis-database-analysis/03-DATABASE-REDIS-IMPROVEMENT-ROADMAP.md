# 03 — Database & Redis Improvement Roadmap (`isp-core`)

> Full gap analysis and prioritized plan combining **MySQL query fixes** and **Redis caching**.  
> Companion: [01 — Current state](./01-REDIS-CURRENT-STATE-AND-ARCHITECTURE.md) | [02 — Do / Don't](./02-REDIS-WHAT-TO-USE-AND-NOT-USE.md)

---

## 1. Executive summary

| Layer | Current health | Target |
|-------|----------------|--------|
| **Redis infra** | Live (Predis + local/Upstash) | Stable prod config + monitoring |
| **Redis usage** | ~40–50% of designed cache plan | 80%+ on hot paths |
| **MySQL queries** | Heavy N+1 on lists, dashboard loops | Batched queries + indexes |
| **zapi parity** | Weaker than web for cache | Match web traffic/dashboard patterns |

**Highest ROI next steps (in order):**

1. Add **5s traffic cache** to `zapi/Modules/Customer/User/Controllers/RouterTrafficController.php`
2. Implement **`getUserById` Redis L2** (money-free projection)
3. **sAdmin dashboard** read-through cache (not write-only)
4. **Customer::fetch** N+1 batching + indexes
5. **Bandwidth trend** single `GROUP BY` query
6. **Reseller zapi dashboard** 30–60s cache

---

## 2. Redis plan — designed vs implemented

Design reference: `report/database_performance_analysis.md` §8 (C1–C8) and `docs/production-optimization/04-REDIS-ARCHITECTURE.md`.

| ID | Feature | Key pattern | Designed TTL | Implemented? | Gap / action |
|----|---------|-------------|--------------|--------------|--------------|
| **C1** | Full permission map | `perm:{user_id}` | 1h | ⚠️ Partial | Per-decision `perm_{ver}_{hash}` only; build `getPermissionMap()` |
| **C2** | User row (no money) | `user:{id}` | 5 min | ❌ No | `getUserById()` always hits MySQL |
| **C3** | sAdmin hierarchy | `sadmin_of:{user_id}` | 1h | ⚠️ Partial | Per-request static only in `getSAdminIdForUser()` |
| **C4** | Settings | `set2_{ver}_{hash}` | 60s | ✅ Yes | `getSetting()` + version bust |
| **C5** | sAdmin dashboard | `dash_sadmin:{id}` | 30s | ⚠️ Partial | Saved on compute; read only under degrade flags |
| **C6** | Bandwidth 7-day trend | `dash:bw:{admin}:{router}` | 60s | ❌ No | Still 8 queries in loop |
| **C7** | Reseller zapi dashboard | `zapi:dash:{reseller_id}` | 30–60s | ❌ No | `DashboardService` uncached |
| **C8** | Per-request memo | `static` arrays | request | ✅ Yes | `userHasPermission`, `getSetting`, `getSAdminIdForUser` |

### 2.1 Additional implemented items (not in original C-table)

| Feature | Status | Notes |
|---------|--------|-------|
| JWT revoke stamp | ✅ | `token_helper.php` |
| Router circuit breaker | ✅ | `router_down_{id}` |
| Web traffic cache | ✅ | 5s |
| zapi cooldowns | ✅ | `rc_cooldown_*` |
| Kill-switch flags | ✅ | `flag_*` |
| Login throttle | ⚠️ | Coded, disabled by default |
| Redis Inspector UI | ✅ | `/system/redis-cache` |
| Queue on Redis | ❌ | MySQL `jobs` by design |

---

## 3. Database — critical issues affecting cache ROI

From `report/database_performance_analysis.md` and `docs/production-optimization/02-DATABASE-FIX-PLAN.md`.

> **Cache reduces frequency; indexes reduce cost of misses.** Both are required.

### 3.1 Schema / correctness (fix before scaling cache)

| Issue | Impact | Priority |
|-------|--------|----------|
| `payments.month` string vs cross-year billing | Wrong dedup + slow scans | P0 |
| `user_data_usage.admin_id` mislabeled (stores customer id) | Wrong joins in dashboard | P0 |
| No FK constraints | Orphans, planner weakness | P1 |
| `reseller_transaction.admin_id` VARCHAR vs INT | Index skip | P1 |
| Polymorphic `package_id` COALESCE joins | 2 joins where 1 suffices | P1 |

### 3.2 Missing indexes (high value)

| Index | Table | Helps |
|-------|-------|-------|
| `idx_pay_user_month_status` | `payments` | Billing lists, dashboard revenue |
| `idx_users_admin_role` | `users` | Customer lists per reseller |
| `idx_udu_date` / `idx_udu_admin_date` | `user_data_usage` | Bandwidth trend, cron |
| `idx_urd_user_id` | `user_router_data` | Customer router cache lookups |
| `idx_conn_user_id` | connections | Device/session views |

### 3.3 Query rewrites (bigger than Redis for one-off slowness)

| Endpoint | Before | After | File |
|----------|--------|-------|------|
| Bandwidth 7-day trend | 8 `SUM` loops | 1 `GROUP BY date` | `Dashboard.php` |
| Ticket status counts | 4× `JSON_CONTAINS` | 1 grouped query (+ normalize `ticket_admins`) | `Dashboard.php` |
| Reseller payment details | N× `first()` | 1 `selectSum` + `whereIn` | `Reseller.php` |
| Customer fetch (100 rows) | 400–600 queries | ~100 batched | `Customer.php` |
| zapi reseller list | ~800 queries | ~8 batched | `CustomerService` segments |

---

## 4. zapi-specific improvement backlog

### 4.1 Cache parity (copy from web)

| zapi route | Copy from | Effort |
|------------|-----------|--------|
| `GET api/customer/routers/load-traffic/{id}` | `Routers::loadTraffic` cache block | S |
| `GET api/customer/users-load-traffic/{id}` | `Routers::UsersloadTraffic` | S |
| `GET api/reseller/users-load-traffic/{id}` | same | S |
| `GET api/reseller/dashboard/{id}` | new `zapi:dash:{id}` 30s TTL | M |

**Implementation pattern:**

```php
$cacheKey = 'traffic_router_' . $id . '_' . md5((string)$interface . (string)$userRole . (string)$userId);
if ($cached = cache($cacheKey)) {
    return $this->respondSuccess($cached);
}
// ... compute ...
cache()->save($cacheKey, $payload, 5);
```

Place in `RouterTrafficController` or extract to `app/Libraries/RouterTrafficCache.php` shared by web + zapi.

### 4.2 SQL batching (no Redis)

| Area | File(s) | Fix |
|------|---------|-----|
| Reseller customer enrich | `CustomerServicePart0*.php` | `whereIn` prefetch packages, routers, areas |
| Subscription sync | `SubscriptionServicePart*.php` | Batch user lookups |
| Dashboard aggregates | `DashboardServicePart01Segment.php` | Single queries per metric group |

### 4.3 Security (blocks safe caching)

| Issue | Why it matters for cache |
|-------|------------------------|
| BOLA on `resellerId` URL param | Cannot cache per-reseller keys safely until scoped by JWT `sub` | 

See `docs/production-optimization/01-CRITICAL-ISSUES.md` S5.

---

## 5. Web (`app/`) improvement backlog

### 5.1 Redis

| Task | File | Priority |
|------|------|----------|
| Full permission map C1 | `user_helper.php` | P1 |
| `getUserById` L2 (strip fund) | `user_helper.php` | P1 |
| sAdmin dashboard read-through | `Dashboard::sadminData` | P1 |
| Bandwidth trend cache C6 | `Dashboard.php` | P2 |
| `getSAdminIdForUser` L2 C3 | `user_helper.php` | P2 |

### 5.2 SQL (no Redis)

| Task | File | Priority |
|------|------|----------|
| Remove hot-path `getCompiledSelect` logging | `Customer.php` | P1 |
| Cap DataTables `length=-1` | Controllers + JS | P1 |
| Server-side pagination rollout | Customer, Reseller views | P2 |

---

## 6. Phased rollout plan

### Phase A — Quick wins (1–3 days)

| # | Task | Type | Expected gain |
|---|------|------|---------------|
| A1 | zapi traffic 5s cache | Redis | 60–80% fewer MikroTik calls from mobile |
| A2 | sAdmin dashboard read cache (always serve if &lt;30s old) | Redis | ~35 SQL/poll → 0 on hit |
| A3 | Bandwidth trend → single GROUP BY | SQL | 8 queries → 1 |
| A4 | Enable `login_throttle` flag in staging only | Redis | Brute-force protection |

### Phase B — Core cache completion (1 week)

| # | Task | Type | Expected gain |
|---|------|------|---------------|
| B1 | `getPermissionMap()` + C1 | Redis | Sidebar 50–125 → 1 query worth of work |
| B2 | `getUserById` L2 stripped | Redis | Cuts hierarchy walk DB hits |
| B3 | Reseller zapi dashboard cache C7 | Redis | Mobile reseller home faster |
| B4 | Add missing indexes (§3.2) | SQL | Faster cache misses |

### Phase C — Data layer hardening (2–4 weeks)

| # | Task | Type |
|---|------|------|
| C1 | `payments.period` migration | SQL schema |
| C2 | `user_data_usage` rollup + retention | SQL schema + cron |
| C3 | Customer::fetch batching | SQL refactor |
| C4 | Ticket `admin_ids` normalization | SQL schema |
| C5 | BOLA fix — JWT-scoped reseller id | Security + enables safe cache |

### Phase D — Scale-out (when multi-server)

| # | Task |
|---|------|
| D1 | Upstash or dedicated Redis with TLS |
| D2 | phpredis on production (faster than Predis) |
| D3 | Redis memory alerts + `INFO` monitoring |
| D4 | Read replica for reporting (only after query bounds) |

---

## 7. Performance scoreboard (target state)

From `docs/production-optimization/03-PERFORMANCE-OPTIMIZATION.md` §6 — updated with current status.

| # | Hot path | Before | Target | Lever | Status |
|---|----------|--------|--------|-------|--------|
| 1 | Sidebar permissions | 50–125 | ~0–1 | C1 + C8 | ⚠️ C8 only |
| 2 | Customer::fetch 100 rows | 400–600 | ~100 | batch + index | ❌ |
| 3 | Reseller payment_details | ~2001 | 3 | set-based SQL | ❌ |
| 4 | CustomerPayment user_fetch | 500–600 | ~5–10 | batch | ❌ |
| 5 | zapi reseller list | ~800 | ~8 | whereIn | ❌ |
| 6 | sadminData + bandwidth | ~35+8 | ~0 cached / 1 SQL | C5+C6 | ⚠️ partial |
| 7 | customer_data_usages cron | ~20k | ~2/router | prefetch | ❌ |
| 8 | zapi traffic poll | MikroTik each | 5s cache | parity w/ web | ❌ |

---

## 8. Metrics to track (prove improvements)

| Metric | How to measure | Goal |
|--------|----------------|------|
| MySQL queries/request | Debug toolbar / slow log | −50% on dashboard + customer list |
| Redis hit rate | `INFO stats` / custom counter on `cache()->get` miss | &gt;70% on perm/settings |
| MikroTik connect attempts | Log lines `Connected to Router` vs `Circuit OPEN` | Drop under poll load |
| p95 page TTFB | APM / nginx log | −30% admin pages |
| FPM busy workers | `php-fpm` status | Fewer stalls on dead routers |
| Redis memory | `used_memory_human` | Stable below 80% maxmemory |

---

## 9. Testing checklist (before prod deploy)

| Test | Command / action |
|------|------------------|
| Permission bust | Edit permission → sidebar updates within 30s |
| Settings bust | `setSetting()` → new value within 60s |
| JWT revoke | Password change → old mobile token 401 |
| Circuit breaker | Stop MikroTik → fast fail 45s |
| Cache degrade | Stop Redis → web still loads (file fallback); sessions may fail |
| zapi traffic cache | Two requests &lt;5s apart → second skips MikroTik |
| PHPUnit | `tests/unit/PermissionCacheTest.php`, `TokenRevocationTest.php`, `SettingsCacheTest.php` |

---

## 10. File change map (where to edit)

| Goal | Primary files |
|------|---------------|
| zapi traffic cache | `zapi/Modules/Customer/User/Controllers/RouterTrafficController.php` |
| Web traffic cache | `app/Controllers/Routers.php` (existing) |
| Permission map | `app/Helpers/user_helper.php`, `app/Models/Permission.php` |
| User row cache | `app/Helpers/user_helper.php`, `app/Models/User.php` (bust on update) |
| Dashboard cache | `app/Controllers/Dashboard.php` |
| Reseller dashboard | `zapi/Modules/Reseller/Dashboard/Services/` |
| Settings cache | `app/Helpers/utility_helper.php` (existing) |
| JWT revoke | `app/Helpers/token_helper.php`, `zapi/Core/Filters/JwtAuthFilter.php` |
| Redis config | `app/Config/Cache.php`, `.env` |
| DB indexes | new migration in `app/Database/Migrations/` |
| Ops UI | `app/Libraries/RedisInspector.php`, `app/Views/system/redis_inspector.php` |

---

## 11. Risk register

| Risk | Mitigation |
|------|------------|
| Stale permissions after cache | Version bust + 30s TTL cap |
| Stale fund balance if C2 done wrong | Never cache money columns |
| Thundering herd on dashboard cache expiry | Soft TTL / single-flight lock (see `04-REDIS-ARCHITECTURE.md` §5.13) |
| Upstash latency | phpredis + connection pooling; keep TTLs meaningful |
| `.env` secrets in git | Rotate keys; use `.env.example` for template |
| Redis Inspector on prod | Env-gate + mask session values |

---

## 12. Definition of done (program complete)

- [ ] All C1–C7 items implemented or explicitly rejected with reason  
- [ ] zapi traffic cache matches web behavior  
- [ ] Top-6 scoreboard rows at target or documented exception  
- [ ] Indexes from §3.2 applied in production DB  
- [ ] `payments.period` migration shipped  
- [ ] No P0 BOLA issues blocking per-tenant cache keys  
- [ ] Runbook: local Redis + Upstash switch documented in `.env.example`  
- [ ] `/healthz` cache check green in production  

---

*Index: [README](./README.md)*
