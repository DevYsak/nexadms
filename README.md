# Laravel Biometric Attendance

Laravel 11 integration for eSSL / ZKTeco ADMS Push SDK devices such as `AIFACE-MAGNUM`.

## Current capabilities

- ADMS device routes:
  - `POST /iclock/cdata`
  - `GET /iclock/getrequest`
  - `POST /iclock/devicecmd`
- Raw device payload capture in `biometric_raw_logs`
- Detailed request logging in `storage/logs/biometric.log`
- Attendance storage with duplicate protection
- Live attendance page with 5-second auto-refresh
- Manual attendance simulation route: `/test-attendance`

## Main attendance URLs

- Attendance dashboard:
  - `http://localhost:8001/attendance`
- Live JSON feed:
  - `http://localhost:8001/attendance/feed`
- Attendance debug:
  - `http://localhost:8001/attendance/debug`
- Test simulator:
  - `http://localhost:8001/test-attendance`
  - `http://localhost:8001/test-attendance/bulk`

## Device flow

1. Device boots and calls `DEVICEINFO`
2. Device polls `GET /iclock/getrequest`
3. Device pushes punches via `ATTLOG`
4. Laravel parses, deduplicates, and stores rows in `biometric_attendances`
5. Attendance dashboard refreshes every 5 seconds and shows new punches without reloading the page

## Logging

### Files

- Laravel log:
  - `storage/logs/laravel.log`
- Biometric ADMS log:
  - `storage/logs/biometric.log`
- Raw payload table:
  - `biometric_raw_logs`

### What gets logged

- request method, path, serial number, headers, and body preview
- parser success and skipped-line warnings
- attendance inserts
- duplicate detection
- controller-level processing errors

## Manual test route

Use `/test-attendance` to simulate a real ATTLOG insert.

### Browser example

```text
http://localhost:8001/test-attendance?serial_number=TDBD253900118&employee_code=1&punch_state=0&verify_type=1
```

### Custom punch time example

```text
http://localhost:8001/test-attendance?serial_number=TDBD253900118&employee_code=1&punch_state=1&verify_type=1&punch_time=2026-05-28%2018:00:00
```

### POST body fields

- `serial_number`
- `employee_code`
- `punch_state`
- `verify_type`
- `punch_time`
- `payload`

If `payload` is omitted, Laravel generates a valid ATTLOG line automatically.

## Attendance dashboard live refresh

The `/attendance` page polls `/attendance/feed` every 5 seconds.

Live refresh updates:

- total punches
- check-ins
- check-outs
- unique employees
- latest attendance rows

No server restart is required for new rows to appear.

## Duplicate handling

Exact duplicates are blocked by:

- application-level checks
- database unique index on:
  - `device_id`
  - `employee_code`
  - `punch_time`
  - `punch_state`
  - `verify_type`

Valid IN and OUT rows are still allowed because they use different timestamps and can use different `punch_state` values.

Optional dedup window behavior is controlled by:

```dotenv
BIOMETRIC_DEDUP_WINDOW=0
```

- `0` means exact-second dedup only
- any value above `0` ignores repeated punches with the same employee, device, and punch state inside that many seconds

## Timezone

Set:

```dotenv
APP_TIMEZONE=Asia/Kolkata
BIOMETRIC_TIMEZONE=Asia/Kolkata
```

Attendance timestamps are parsed and displayed in `Asia/Kolkata`.

## Log tail commands

### PowerShell

```powershell
Get-Content .\storage\logs\biometric.log -Wait -Tail 80
Get-Content .\storage\logs\laravel.log -Wait -Tail 80
```

### CMD

```cmd
powershell -Command "Get-Content .\storage\logs\biometric.log -Wait -Tail 80"
powershell -Command "Get-Content .\storage\logs\laravel.log -Wait -Tail 80"
```

### Laravel

```bash
php artisan biometric:tail
php artisan biometric:tail laravel --lines=120
```

## Troubleshooting

### Device connected but no sync

- confirm device ADMS server points to the Laravel machine
- confirm the device is hitting:
  - `/iclock/cdata`
  - `/iclock/getrequest`
- tail `storage/logs/biometric.log`
- confirm requests appear in `biometric_raw_logs`

### Old data visible but new data missing

- open `/attendance?date=YYYY-MM-DD&employee=&device=`
- tail `storage/logs/biometric.log`
- confirm new `ATTLOG` requests are arriving
- inspect the latest `biometric_raw_logs.body`
- verify the pushed lines contain new timestamps

### Route not hit

- run:

```bash
php artisan route:list --path=iclock
```

- verify `bootstrap/app.php` is loading `biometric-attendance/routes/iclock.php`
- verify CSRF exclusion for `iclock/*`

### Database insert failing

- check `storage/logs/laravel.log`
- check `storage/logs/biometric.log`
- verify biometric migrations have been run:

```bash
php artisan migrate --path=../biometric-attendance/database/migrations --force
```

### Parser failing

- look for `Parser skipped record` in `biometric.log`
- compare the raw body from `biometric_raw_logs.body`
- test with `/test-attendance`

### Duplicate filtering issue

- confirm whether the second punch has the same timestamp
- check `BIOMETRIC_DEDUP_WINDOW`
- look for:
  - `Skipped exact duplicate attendance punch`
  - `Skipped duplicate attendance punch inside dedup window`

## Verification checklist

- `storage/logs` exists
- `laravel.log` exists or is creatable
- `biometric.log` exists or is creatable
- device serial is saved in `biometric_devices`
- `ATTLOG` requests create rows in `biometric_attendances`
- `/attendance` updates without full page reload

## Useful routes

- `GET /attendance`
- `GET /attendance/feed`
- `GET /attendance/debug`
- `GET|POST /test-attendance`
- `GET|POST /test-attendance/bulk`
- `POST /iclock/cdata`
- `GET /iclock/getrequest`
- `POST /iclock/devicecmd`
