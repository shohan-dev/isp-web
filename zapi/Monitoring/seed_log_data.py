#!/usr/bin/env python3
"""
Traffic data auto-populator (no CLI arguments).

Edit only CONFIG values below, then run:
    python "isp-core/zapi/log/populate_traffic_logs.py"
"""

from __future__ import annotations

import json
import random
from collections import defaultdict
from dataclasses import dataclass
from datetime import date, datetime, time, timedelta, timezone
from pathlib import Path


# ====================================
# DEFAULT CONFIG (used if not provided)
# ====================================
PERIOD = "day"  # "day" | "week" | "month"
START_DATE = "2026-04-05"  # YYYY-MM-DD
LOG_ROOT = Path("../log") # isp-core/zapi/log
OVERWRITE_EXISTING = True

RECORDS_PER_MINUTE = 10 # 10 requests per minute
USER_ID_MIN = 1000
USER_ID_MAX = 25000
API_RATIO = 0.70
ERROR_RATIO = 0.06
RANDOM_SEED = None  # None = fully random every run

# Keep recent index short like PHP service (MAX_RECENT_LINES = 5000)
RECENT_LIMIT = 50000

# ======================================================
# RUN PARAMS (edit this only when you want custom run)
# Leave as {} -> default 1 day + random seed data
# ======================================================
RUN_PARAMS = {
    # "period": "month",          # "day" | "week" | "month"
    # "days": 10,                 # custom day count (overrides period)
    # "start_date": "2026-04-01", # YYYY-MM-DD
    # "total_requests": 50000,    # exact total requests to generate
    # "seed": 20260426,           # fixed seed; omit/None for random
}


BD_TZ = timezone(timedelta(hours=6))
UTC_TZ = timezone.utc

API_PATHS = [
    "/api/monitor/traffic",
    "/api/monitor/snapshot",
    "/api/monitor/overview",
    "/api/monitor/top-endpoints",
    "/api/monitor/timeline",
]
WEB_PATHS = [
    "/dashboard",
    "/routers/load-traffic/35",
    "/routers/load-traffic/47",
    "/billing/invoices",
    "/profile/settings",
]
UAS = [
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36",
    "Mozilla/5.0 (X11; Linux x86_64; rv:145.0) Gecko/20100101 Firefox/145.0",
    "Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Mobile Safari/537.36",
    "Mozilla/5.0 (iPhone; CPU iPhone OS 18_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.3 Mobile/15E148 Safari/604.1",
]
DEVICES = [
    ("desktop", "Windows", "Chrome", "Windows Chrome"),
    ("desktop", "Linux", "Firefox", "Linux Firefox"),
    ("mobile", "Android", "Chrome Mobile", "Android Chrome"),
    ("mobile", "iOS", "Safari", "iPhone Safari"),
]


@dataclass
class Summary:
    minute_files_written: int = 0
    minute_files_skipped: int = 0
    records_total: int = 0


def period_minutes(period: str, start: date) -> int:
    if period == "day":
        return 24 * 60
    if period == "week":
        return 7 * 24 * 60
    if period != "month":
        raise ValueError("PERIOD must be day/week/month")
    next_month = (start.replace(day=28) + timedelta(days=4)).replace(day=1)
    days_in_month = (next_month - start.replace(day=1)).days
    return days_in_month * 24 * 60


def resolve_total_minutes(period: str, start: date, days: int | None) -> int:
    if days is not None:
        return max(1, int(days)) * 24 * 60
    return period_minutes(period, start)


def make_request_id() -> str:
    left = "".join(random.choices("0123456789abcdef", k=16))
    right = "".join(random.choices("0123456789abcdef", k=13))
    return f"{left}-{right}"


def choose_status() -> int:
    if random.random() >= ERROR_RATIO:
        return 200
    return random.choice([400, 401, 403, 404, 429, 500, 502, 503])


def route_name_for(path: str, is_api: bool) -> str:
    if is_api and path.endswith("/traffic"):
        return "\\Zapi\\Modules\\Legacy\\Controllers\\Monitor\\TrafficMonitorController::traffic"
    if is_api and path.endswith("/snapshot"):
        return "\\Zapi\\Modules\\Legacy\\Controllers\\Monitor\\TrafficMonitorController::snapshot"
    if path.startswith("/routers/load-traffic"):
        return "\\App\\Controllers\\Routers::loadTraffic"
    return ""


def choose_method(path: str) -> str:
    if path.endswith("/traffic") or path.endswith("/snapshot"):
        return "GET"
    return random.choices(["GET", "POST", "PUT"], weights=[84, 12, 4], k=1)[0]


def make_record(created_local: datetime) -> dict:
    is_api = random.random() < API_RATIO
    path = random.choice(API_PATHS if is_api else WEB_PATHS)
    status = choose_status()
    duration_ms = random.randint(3, 2800)
    ended_local = created_local + timedelta(milliseconds=duration_ms)
    started_utc = created_local.astimezone(UTC_TZ)
    ended_utc = ended_local.astimezone(UTC_TZ)
    device_type, device_os, device_browser, device_name = random.choice(DEVICES)

    referer = ""
    if is_api and random.random() < 0.45:
        referer = "http://localhost:8080/api/monitor/traffic"
    if (not is_api) and random.random() < 0.55:
        referer = "http://localhost:8080/dashboard"

    return {
        "request_id": make_request_id(),
        "started_at": started_utc.isoformat(timespec="seconds"),
        "ended_at": ended_utc.isoformat(timespec="seconds"),
        "duration_ms": duration_ms,
        "path": path,
        "endpoint_group": "api" if is_api else "web",
        "method": choose_method(path),
        "status_code": status,
        "ip_address": random.choice(["127.0.0.1", "103.25.44.12", "203.76.119.7", "10.20.1.5"]),
        "user_agent": random.choice(UAS),
        "referer": referer,
        "is_api": is_api,
        "is_web": not is_api,
        "client_source": random.choice(["web", "app"]) if is_api else "web",
        "device_type": device_type,
        "device_os": device_os,
        "device_browser": device_browser,
        "device_name": device_name,
        "user_id": random.randint(USER_ID_MIN, USER_ID_MAX),
        "actor_type": "",
        "actor_label": "",
        "route_name": route_name_for(path, is_api),
        "year": created_local.strftime("%Y"),
        "month": created_local.strftime("%m"),
        "day": created_local.strftime("%d"),
        "hour": created_local.strftime("%H"),
        "minute": created_local.strftime("%M"),
        "created_at": created_local.isoformat(timespec="seconds"),
    }


def write_json(file_path: Path, payload: dict) -> None:
    file_path.parent.mkdir(parents=True, exist_ok=True)
    file_path.write_text(json.dumps(payload, indent=4, ensure_ascii=False) + "\n", encoding="utf-8")


def update_indexes(all_rows: list[dict], root: Path) -> None:
    index_root = root / "_index"
    minute_agg = defaultdict(
        lambda: defaultdict(
            lambda: {
                "hits": 0,
                "api_hits": 0,
                "web_hits": 0,
                "errors": 0,
                "errors_4xx": 0,
                "errors_5xx": 0,
                "total_latency_ms": 0,
                "max_latency_ms": 0,
                "avg_latency_ms": 0,
            }
        )
    )
    endpoint_agg = defaultdict(lambda: defaultdict(lambda: defaultdict(lambda: {"hits": 0, "errors": 0, "total_latency_ms": 0})))
    device_agg = defaultdict(
        lambda: defaultdict(
            lambda: {
                "source": defaultdict(int),
                "device_type": defaultdict(int),
                "device_os": defaultdict(int),
                "device_browser": defaultdict(int),
            }
        )
    )
    recent_rows = []
    monthly_total = defaultdict(int)
    last_minute = ""

    for row in all_rows:
        created = datetime.fromisoformat(row["created_at"])
        hour_key = created.strftime("%Y%m%d_%H")
        minute_key = created.strftime("%Y-%m-%d %H:%M")
        month_key = created.strftime("%Y-%m")
        path = row["path"]
        status = int(row["status_code"])
        duration = int(row["duration_ms"])
        source = row.get("client_source") or ("app" if row.get("is_api") else "web")

        m = minute_agg[hour_key][minute_key]
        m["hits"] += 1
        m["api_hits"] += 1 if row.get("is_api") else 0
        m["web_hits"] += 1 if row.get("is_web", True) else 0
        m["errors"] += 1 if status >= 400 else 0
        m["errors_4xx"] += 1 if 400 <= status < 500 else 0
        m["errors_5xx"] += 1 if status >= 500 else 0
        m["total_latency_ms"] += duration
        m["max_latency_ms"] = max(m["max_latency_ms"], duration)
        m["avg_latency_ms"] = round(m["total_latency_ms"] / m["hits"]) if m["hits"] > 0 else 0

        e = endpoint_agg[hour_key][minute_key][path]
        e["hits"] += 1
        e["errors"] += 1 if status >= 400 else 0
        e["total_latency_ms"] += duration

        d = device_agg[hour_key][minute_key]
        d["source"][source] += 1
        d["device_type"][row.get("device_type", "unknown")] += 1
        d["device_os"][row.get("device_os", "unknown")] += 1
        d["device_browser"][row.get("device_browser", "unknown")] += 1

        monthly_total[month_key] += 1
        recent_rows.append(row)
        last_minute = minute_key

    # write agg_minute
    for hour_key, minutes in minute_agg.items():
        write_json(index_root / "agg_minute" / f"{hour_key}.json", {"version": 1, "minutes": dict(minutes)})

    # write agg_endpoint
    for hour_key, minutes in endpoint_agg.items():
        clean_minutes = {}
        for minute_key, paths in minutes.items():
            clean_minutes[minute_key] = dict(paths)
        write_json(index_root / "agg_endpoint" / f"{hour_key}.json", {"version": 1, "minutes": clean_minutes})

    # write agg_device
    for hour_key, minutes in device_agg.items():
        clean_minutes = {}
        for minute_key, buckets in minutes.items():
            clean_minutes[minute_key] = {
                "source": dict(buckets["source"]),
                "device_type": dict(buckets["device_type"]),
                "device_os": dict(buckets["device_os"]),
                "device_browser": dict(buckets["device_browser"]),
            }
        write_json(index_root / "agg_device" / f"{hour_key}.json", {"version": 1, "minutes": clean_minutes})

    # write recent
    recent_file = index_root / "recent.jsonl"
    recent_file.parent.mkdir(parents=True, exist_ok=True)
    with recent_file.open("w", encoding="utf-8") as f:
        for row in sorted(recent_rows, key=lambda x: x["created_at"], reverse=True)[:RECENT_LIMIT]:
            f.write(json.dumps(row, ensure_ascii=False, separators=(",", ":")) + "\n")

    # write meta
    write_json(
        index_root / "meta.json",
        {
            "version": 1,
            "updated_at": datetime.now(tz=UTC_TZ).isoformat(timespec="seconds"),
            "last_minute": last_minute,
            "monthly_total_requests": dict(sorted(monthly_total.items())),
        },
    )


def main(
    period: str = PERIOD,
    days: int | None = None,
    start_date: str = START_DATE,
    total_requests: int | None = None,
    seed: int | None = RANDOM_SEED,
) -> None:
    # None seed = random system seed, as requested.
    random.seed(seed)

    start = date.fromisoformat(start_date)
    total_minutes = resolve_total_minutes(period, start, days)
    start_dt = datetime.combine(start, time.min, tzinfo=BD_TZ)
    summary = Summary()
    all_rows: list[dict] = []
    remaining_requests = total_requests if (total_requests is not None and total_requests > 0) else None

    for i in range(total_minutes):
        minute_dt = start_dt + timedelta(minutes=i)
        year = minute_dt.strftime("%Y")
        month = minute_dt.strftime("%m")
        day = minute_dt.strftime("%d")
        hour = minute_dt.strftime("%H")
        minute = minute_dt.strftime("%M")

        out_dir = LOG_ROOT / year / month / day / hour
        out_file = out_dir / f"traffic_{year}{month}{day}_{hour}{minute}.jsonl"
        out_dir.mkdir(parents=True, exist_ok=True)

        if out_file.exists() and not OVERWRITE_EXISTING:
            summary.minute_files_skipped += 1
            continue

        if remaining_requests is None:
            count = max(1, int(random.gauss(RECORDS_PER_MINUTE, max(1, RECORDS_PER_MINUTE // 2))))
        else:
            minutes_left = total_minutes - i
            if minutes_left <= 1:
                count = remaining_requests
            else:
                avg_need = remaining_requests / minutes_left
                spread = max(1, int(avg_need * 0.35))
                count = int(random.gauss(avg_need, spread))
                count = max(0, min(count, remaining_requests))
            if count <= 0 and remaining_requests > 0:
                continue

        rows = []
        for _ in range(count):
            ts = minute_dt.replace(second=random.randint(0, 59), microsecond=random.randint(0, 999_999))
            rows.append(make_record(ts))

        rows.sort(key=lambda x: x["created_at"])
        with out_file.open("w", encoding="utf-8") as f:
            for row in rows:
                f.write(json.dumps(row, ensure_ascii=False, separators=(",", ":")) + "\n")

        summary.minute_files_written += 1
        summary.records_total += len(rows)
        all_rows.extend(rows)
        if remaining_requests is not None:
            remaining_requests -= len(rows)
            if remaining_requests <= 0:
                break

    # rebuild dashboard index from rows in this run
    update_indexes(all_rows, LOG_ROOT)

    month_totals = defaultdict(int)
    for row in all_rows:
        month_totals[row["created_at"][:7]] += 1

    print(f"Done. PERIOD={period}, START_DATE={start_date}")
    if days is not None:
        print(f"Custom days: {days}")
    print(f"Minute files written: {summary.minute_files_written}")
    print(f"Minute files skipped: {summary.minute_files_skipped}")
    print(f"Total generated requests: {summary.records_total}")
    if month_totals:
        print("Auto monthly totals:")
        for k, v in sorted(month_totals.items()):
            print(f"  {k}: {v}")
    print(f"Index updated: {LOG_ROOT / '_index'}")


if __name__ == "__main__":
    main(**RUN_PARAMS)
