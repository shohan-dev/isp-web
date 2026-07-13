# isp-core — Redis & Database Analysis (Index)

> **Scope:** `isp-core/` web app (`app/`) + mobile API (`zapi/`) + MySQL + Redis.  
> **Last updated:** 2026-06-23 (verified against live code in this repo).  
> **Audience:** developers and operators working on performance, caching, and DB tuning.

---

## Document map

| # | File | What it covers |
|---|------|----------------|
| **1** | [01-REDIS-CURRENT-STATE-AND-ARCHITECTURE.md](./01-REDIS-CURRENT-STATE-AND-ARCHITECTURE.md) | How Redis is configured, every key prefix, every code path that reads/writes cache, routes, `app` vs `zapi`, sessions, fail-over behavior, Redis Inspector UI |
| **2** | [02-REDIS-WHAT-TO-USE-AND-NOT-USE.md](./02-REDIS-WHAT-TO-USE-AND-NOT-USE.md) | Decision guide: what **should** be cached, what **must never** be cached, security rules, local vs Upstash, when Redis helps vs when indexes/query fixes help more |
| **3** | [03-DATABASE-REDIS-IMPROVEMENT-ROADMAP.md](./03-DATABASE-REDIS-IMPROVEMENT-ROADMAP.md) | Gap analysis (planned vs implemented), prioritized roadmap, query wins, indexes, zapi parity, metrics, phased rollout |

---

## Quick answers

| Question | Answer | Details in |
|----------|--------|------------|
| Does the app always check Redis before MySQL? | **No** | Doc 1 §2, Doc 2 §1 |
| Is Redis enabled? | **Yes** when `.env` sets `cache.handler=predis` and session uses `PredisHandler` | Doc 1 §1 |
| Where is Redis strongest? | Web `app/` — permissions, settings, router traffic, sessions | Doc 1 §4 |
| Where is Redis weakest? | Most `zapi/` CRUD and traffic endpoints | Doc 1 §5, Doc 3 §4 |
| What must never be cached? | `users.fund`, payment status, gateway secrets | Doc 2 §3 |
| Biggest missing cache items? | `getUserById`, zapi traffic, reseller dashboard | Doc 3 §2 |

---

## Related existing docs (repo root)

These older docs informed this analysis; prefer **this folder** for isp-core current-state truth:

- `docs/production-optimization/04-REDIS-ARCHITECTURE.md` — canonical design (some items not yet coded)
- `docs/production-optimization/02-DATABASE-FIX-PLAN.md` — DDL and schema fixes
- `docs/production-optimization/03-PERFORMANCE-OPTIMIZATION.md` — query batching and ROI table
- `report/database_performance_analysis.md` — original DB audit + cache plan C1–C8

---

## Ops cheat sheet

```bash
# Local Redis (WSL)
sudo service redis-server start
redis-cli ping                    # → PONG
redis-cli --scan --pattern 'ispc:*'
redis-cli --scan --pattern 'isp_sess:*'

# App health
curl http://localhost:8080/healthz

# Connection check (needs phpredis ext)
cd isp-core && php spark redis:check

# Web UI (login as info@isppaybd.com only)
http://localhost:8080/system/redis-cache
```
