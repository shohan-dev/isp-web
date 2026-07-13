# Traffic Monitor Change Log

## 2026-04-28 optimization upgrade
- Storage base moved to `writable/log_api/store` (raw logs + index + daily summaries).
- Added strict configurable ignore rules for noisy endpoints and high-frequency polling APIs.
- Added daily raw budget guard (default `10GB/day`) with write-throttling when the cap is reached.
- Added rolling daily summary artifacts under `_index/summary_day` for faster dashboard trend reads.
- Expanded monitor filters: `path_contains`, `method`, `status_min`, `status_max`, `client_source`.
- Enhanced dashboard controls with advanced filters and day-over-day hit comparison.
- Retention now uses monitor config defaults (`rawRetentionDays`, `summaryRetentionDays`) instead of fixed literals.

## 2026-04-28 never-down queue mode
- Request path monitor write switched to fail-open spool append only (very small per-request cost).
- Added spool queue writer service: `Monitoring/Services/TrafficSpoolQueueWriterService.php`.
- Added async spool flush/maintenance service: `Monitoring/Services/TrafficSpoolFlushService.php`.
- Added compact permanent read model: `Monitoring/Services/TrafficCompactReadService.php`.
- Snapshot now reads compact store instead of expensive request-path aggregates.
- Added queue operations endpoints:
  - `GET /api/monitor/flush-queue?max_files=120`
  - `GET /api/monitor/maintain-queue?max_spool_age_hours=48`
- Recommended ops schedule:
  - every minute: call `flush-queue`
  - hourly: call `maintain-queue`

## Implementation scope
- Added full request tracking pipeline for both website and API traffic.
- Kept core implementation inside `zapi` with minimal global filter wiring in `app/Config/Filters.php`.
- Added monitor dashboard and JSON endpoints under `/api/monitor/*`.
- Added local file-backed storage under `zapi/log` using `year/month/day/hour` directories and minute-level JSONL files.

## Files changed with exact lines

### Modified files
- `isp-core/app/Config/Filters.php`
  - Added imports for zapi filters: lines 21-25
  - Added aliases: lines 44-48
  - Added global `trafficstart` before filter: lines 77-84
  - Added global `trafficend` after filter: lines 90-97

- `isp-core/zapi/config/api_routes.php`
  - Added monitor routes: lines 15-20

### Added files
- `isp-core/zapi/Monitoring/Support/TrafficRequestContext.php` (lines 1-44)
- `isp-core/zapi/Monitoring/Domain/TrafficRecord.php` (lines 1-35)
- `isp-core/zapi/Monitoring/Storage/TrafficPathResolver.php` (lines 1-27)
- `isp-core/zapi/Monitoring/Storage/TrafficLogWriter.php` (lines 1-34)
- `isp-core/zapi/Monitoring/Filters/TrafficStartFilter.php` (lines 1-53)
- `isp-core/zapi/Monitoring/Filters/TrafficEndFilter.php` (lines 1-123)
- `isp-core/zapi/Monitoring/Services/TrafficQueryService.php` (lines 1-105)
- `isp-core/zapi/Monitoring/Services/TrafficAnalyticsService.php` (lines 1-124)
- `isp-core/zapi/Monitoring/Services/TrafficAggregationService.php` (lines 1-32)
- `isp-core/zapi/Controllers/Monitor/TrafficMonitorController.php` (lines 1-77)
- `isp-core/zapi/Views/monitor/traffic_dashboard.php` (lines 1-97)
- `isp-core/zapi/log/.gitkeep` (line 1)

## What each file does
- `TrafficStartFilter`: stores request start timestamp and request id.
- `TrafficEndFilter`: calculates duration, captures request/response metadata, resolves user id, and writes log records.
- `TrafficRequestContext`: lightweight per-request in-memory context based on `$_SERVER`.
- `TrafficRecord`: normalizes each log entry schema.
- `TrafficPathResolver`: maps write/read path into `zapi/log/YYYY/MM/DD/HH`.
- `TrafficLogWriter`: creates directories, appends JSONL lines with file lock.
- `TrafficQueryService`: scans and reads JSONL files with basic filtering.
- `TrafficAnalyticsService`: computes overview stats, top endpoints, and month/day/hour/minute timelines.
- `TrafficAggregationService`: combines query + analytics output for controller use.
- `TrafficMonitorController`: serves both HTML dashboard and JSON monitor endpoints.
- `traffic_dashboard.php`: basic user-friendly monitor portal with metrics and tables.
- `api_routes.php`: exposes monitor endpoints under `/api/monitor`.
- `app/Config/Filters.php`: globally enables start/end monitor filters for web + api requests.

## New routes
- `GET /api/monitor/traffic` -> HTML dashboard
- `GET /api/monitor/overview` -> JSON overview
- `GET /api/monitor/top-endpoints` -> JSON top endpoints
- `GET /api/monitor/timeline` -> JSON timeline buckets
- `GET /api/monitor/recent` -> JSON recent requests

## Log record format
Each JSON line stores:
- `request_id`
- `started_at`, `ended_at`, `duration_ms`
- `path`, `endpoint_group`, `method`, `status_code`
- `ip_address`, `user_agent`, `referer`
- `is_api`, `is_web`
- `user_id` (nullable)
- `route_name`
- `year`, `month`, `day`, `hour`, `minute`
- `created_at`

## Storage layout
- Base folder: `isp-core/zapi/log`
- Structure: `YYYY/MM/DD/HH/traffic_YYYYmmdd_HHii.jsonl`

## Query filters supported now
- `kind=api|web`
- `from=<ISO datetime>`
- `to=<ISO datetime>`

## Known limitations (current phase)
- File-backed storage can grow quickly on high-traffic systems.
- No retention cleanup job yet.
- JSON endpoints currently public for test phase.
- For huge log volumes, aggregated pre-computation and pagination should be added.

## Later database migration notes
- The file record fields are already aligned with future `api_logs` style schema.
- A future migration can stream existing JSONL records into DB table(s).
- Endpoint contracts can remain unchanged while storage backend switches from file to DB.

