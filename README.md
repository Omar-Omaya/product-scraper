# Product Scraper Service

A small scraping service: a Laravel backend scrapes products from an eCommerce
store, a Go microservice rotates user-agents (mimicking proxy rotation), and a
Next.js frontend shows the products in a grid that refreshes every 30 seconds.

## Requirements

- PHP 8.2+ and Composer
- Node.js 18+ and npm
- Go 1.22+
- MySQL 8

## Certificate setup (important on Windows)

PHP on Windows ships without a CA bundle, so HTTPS scraping fails with
`cURL error 60: SSL certificate ... unable to get local issuer certificate`, and
the built-in server does not reliably read `curl.cainfo` from `php.ini`. To avoid
this, the scraper reads a CA bundle from config. Do this once:

1. Download the bundle: https://curl.se/ca/cacert.pem
2. Save it somewhere stable (for example next to your PHP install).
3. In `backend/.env`, set the absolute path:

```
SCRAPER_CA_BUNDLE="C:/path/to/cacert.pem"
```

On macOS/Linux this is usually unnecessary; leave `SCRAPER_CA_BUNDLE` empty to use
the system default.

## Setup

### 1. Database

Create a MySQL database and make sure the server is running:

```sql
CREATE DATABASE product_scraper CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 2. Backend (Laravel)

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
```

Then edit `backend/.env`:

- `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` for your MySQL
- `SCRAPER_CA_BUNDLE` as described above (Windows)
- `FRONTEND_URL` (default `http://localhost:3000`) and `PROXY_SERVICE_URL`
  (default `http://127.0.0.1:8081`) are already set

Run the migrations:

```bash
php artisan migrate
```

Optionally seed 12 sample products so the page has data before you scrape:

```bash
php artisan db:seed --class=ProductSeeder
```

### 3. Proxy service (Go)

```bash
cd proxy-service
cp proxies.example.json proxies.json
```

The example file has placeholder proxies and five real user-agents, which is all
that is needed for user-agent rotation. To use real proxies, put them in
`proxies.json` and set `SCRAPER_USE_PROXY=true` in `backend/.env`.

### 4. Frontend (Next.js)

```bash
cd frontend
npm install
```

Create `frontend/.env.local` so the page reads the real API:

```
NEXT_PUBLIC_API_URL=http://localhost:8000/api/products
```

## Running the app

Start each in its own terminal (MySQL should already be running).

Proxy service (listens on `:8081`):

```bash
cd proxy-service
go run .
```

Backend API (listens on `:8000`). The worker setting lets it handle the scrape and
the fetch at the same time so the page never freezes:

```bash
cd backend
PHP_CLI_SERVER_WORKERS=6 php artisan serve --no-reload
```

On Windows PowerShell:

```powershell
cd backend
$env:PHP_CLI_SERVER_WORKERS=6
php artisan serve --no-reload
```

Frontend (listens on `:3000`):

```bash
cd frontend
npm run dev
```

Then open http://localhost:3000. Type a brand or product type in the filter box
(`samsung`, `lenovo`, `tv`, `printer`) and it scrapes that filter and shows the
results, refreshing every 30 seconds.

## Endpoints

- `GET /api/products` - stored products as JSON (paginated; `?q=` filters by title,
  `?per_page=` sets page size)
- `POST /api/products/scrape` - scrapes products for a filter and stores them; body
  `{ "q": "samsung", "limit": 8 }`

You can also scrape from the command line:

```bash
cd backend
php artisan scrape:products --sitemap="https://www.rayashop.com/en-sitemap.xml" --limit=20
```

## Notes

- The default target is Raya (`rayashop.com`), which serves plain HTML. Jumia and
  many others sit behind Cloudflare and block direct requests, so they need real
  residential proxies.
- The filter matches keywords in the store's product URLs, so brand and type words
  work best. Generic words like `laptop` return little because products are named by
  brand - use `lenovo`, `dell`, or `hp` instead.
- Run the proxy service so user-agent rotation is active. If it is down, the backend
  logs a warning and makes a direct request instead of failing.
