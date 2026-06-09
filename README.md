# calendar.ifsc.stream

[![Deploy to Prod](https://github.com/sportclimbing/calendar.ifsc.stream/actions/workflows/deploy-prod.yml/badge.svg)](https://github.com/sportclimbing/calendar.ifsc.stream/actions/workflows/deploy-prod.yml)

Backend API for **calendar.ifsc.stream** — serves the [IFSC](https://www.ifsc-climbing.org/) competition calendar as iCalendar (.ics) files. Acts as a caching proxy in front of the [ifsc-calendar](https://github.com/sportclimbing/ifsc-calendar) GitHub releases.

## Features

- **Full ICS feed** — returns the complete IFSC competition calendar on `GET /`
- **Filtered feeds** — filter by discipline, kind, and category via query parameters; generates a fresh ICS on the fly
- **Background data refresh** — cron-style loop fetches upstream JSON periodically; requests never wait on upstream HTTP

## API

### `GET /`

Returns an iCalendar file (`Content-Type: text/calendar`).

**Query parameters** (optional, comma-separated):

| Parameter    | Example                    | Description                     |
| ------------ | -------------------------- | ------------------------------- |
| `discipline` | `boulder,lead`             | Filter by climbing discipline   |
| `kind`       | `qualification,final`      | Filter by round kind            |
| `category`   | `men,women`               | Filter by category              |

**Examples:**

```sh
# Full calendar
curl https://calendar.ifsc.stream/

# Boulder finals only
curl "https://calendar.ifsc.stream/?discipline=boulder&kind=final"

# Men's lead qualification + final
curl "https://calendar.ifsc.stream/?discipline=lead&kind=qualification,final&category=men"
```

Discipline `speed` auto-expands to include `speed_relay`.

Filters apply at the round level — a `discipline=boulder` filter keeps only boulder rounds within each event.

## Tech Stack

| Layer          | Technology                                       |
| -------------- | ------------------------------------------------ |
| Language       | PHP 8.5                                          |
| Framework      | [Slim 4](https://www.slimframework.com/)         |
| DI Container   | [PHP-DI](https://php-di.org/)                    |
| ICS Generation | [`eluceo/ical`](https://github.com/markuspoerschke/iCal) + `sportclimbing/ifsc-ics-generator` |
| Testing        | PHPUnit 11, Mockery 2                            |
| Static Analysis| PHPStan 2                                        |
| Deployment     | [Deployer](https://deployer.org/) via GitHub Actions |
| Runtime        | Docker (php:8.5-fpm + nginx + supervisord)        |

## Architecture

Hexagonal / Clean Architecture:

```
Request → CalendarController → ServeCalendarUseCase → Ports (interfaces)
                                                           ↓
                                                    Adapters (implementations)
```

- **Ports** (`src/Port/`) — interfaces: `CalendarRepository`, `CalendarGenerator`, `AnalyticsClient`, `HttpClient`
- **Application** (`src/Application/`) — use cases: `ServeCalendarUseCase`, `TrackDownloadUseCase`
- **Adapters** (`src/Adapter/`) — concrete implementations:
  - `LocalCalendarRepository` — reads pre-fetched JSON from local disk
  - `GitHubCalendarRepository` — fetches JSON from GitHub releases (used by CLI fetch script)
  - `GoogleAnalyticsAdapter` — GA4 Measurement Protocol
  - `SportClimbingIcsGenerator` — wraps `ifsc-ics-generator` library

## Data Flow

Upstream calendar JSON is fetched periodically in the background (every `FETCH_INTERVAL` seconds, default 300) by `bin/fetch-calendar-data`, managed by supervisor. HTTP requests only read from the local cache file — they never wait on an upstream fetch.

```
cron loop (supervisor)  →  bin/fetch-calendar-data  →  GitHub Releases
                                                           ↓
                                                    /app/var/cache/calendar.json
                                                           ↓
                        HTTP request  →  LocalCalendarRepository  →  filter + generate ICS
```

## Configuration

Environment variables:

| Variable              | Default | Description                              |
| --------------------- | ------- | ---------------------------------------- |
| `GA_MEASUREMENT_ID`   | `''`    | Google Analytics 4 measurement ID        |
| `GA_API_SECRET`       | `''`    | GA4 Measurement Protocol API secret      |
| `FETCH_INTERVAL`      | `300`   | Seconds between upstream data fetches    |

Application settings in `config/settings.php`:

| Key                  | Default                     | Description              |
| -------------------- | --------------------------- | ------------------------ |
| `calendar.base_url`  | GitHub releases latest URL  | Upstream JSON source     |

## Development

```sh
# Install dependencies
composer install

# Run tests
vendor/bin/phpunit

# Static analysis
vendor/bin/phpstan analyse
```

### Docker

```sh
# Start locally (port 8080)
docker compose up

# Test
curl http://localhost:8080/
```

### Docker image

```sh
docker build -t calendar-ifsc-stream .
docker run -p 8080:8080 \
  -e GA_MEASUREMENT_ID=G-XXXXXXXXXX \
  -e GA_API_SECRET=xxxxxx \
  calendar-ifsc-stream
```

## Deployment

Push to `main` triggers the [deploy-prod](.github/workflows/deploy-prod.yml) workflow:

1. Checkout + PHP 8.5 setup
2. Composer install (no-dev)
3. Run tests
4. Build artifact (bin/, public/, src/, config/, vendor/, var/)
5. Upload to production server via Deployer + SSH

Requires GitHub Secrets: `SSH_PRIVATE_KEY`, `REMOTE_USER`.

## License

MIT
