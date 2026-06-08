# calendar.ifsc.stream

[![Deploy to Prod](https://github.com/sportclimbing/calendar.ifsc.stream/actions/workflows/deploy-prod.yml/badge.svg)](https://github.com/sportclimbing/calendar.ifsc.stream/actions/workflows/deploy-prod.yml)

Backend API for **calendar.ifsc.stream** — serves the [IFSC](https://www.ifsc-climbing.org/) competition calendar as iCalendar (.ics) files. Acts as a caching proxy in front of the [ifsc-calendar](https://github.com/sportclimbing/ifsc-calendar) GitHub releases.

## Features

- **Full ICS feed** — returns the complete IFSC competition calendar on `GET /`
- **Filtered feeds** — filter by discipline, kind, and category via query parameters; generates a fresh ICS on the fly
- **Caching** — filesystem cache (60s TTL) for both ICS and JSON sources to reduce upstream load

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
  - `GitHubCalendarRepository` — fetches ICS/JSON from GitHub releases
  - `CachingCalendarRepository` — filesystem cache decorator
  - `GoogleAnalyticsAdapter` — GA4 Measurement Protocol
  - `SportClimbingIcsGenerator` — wraps `ifsc-ics-generator` library

## Configuration

Environment variables:

| Variable              | Default | Description                              |
| --------------------- | ------- | ---------------------------------------- |
| `GA_MEASUREMENT_ID`   | `''`    | Google Analytics 4 measurement ID        |
| `GA_API_SECRET`       | `''`    | GA4 Measurement Protocol API secret      |

Application settings in `config/settings.php`:

| Key                  | Default                     | Description              |
| -------------------- | --------------------------- | ------------------------ |
| `cache.seconds`      | `60`                        | Cache TTL in seconds     |
| `calendar.base_url`  | GitHub releases latest URL  | Upstream ICS/JSON source |

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
4. Build artifact (public/, src/, config/, vendor/, var/)
5. Upload to production server via Deployer + SSH

Requires GitHub Secrets: `SSH_PRIVATE_KEY`, `REMOTE_USER`.

## License

MIT
