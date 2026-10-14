# nikniq.com

A Laravel 11 application that serves as a configurable public site with several swappable frontpage themes, a games catalogue, a product shop, a software license portal with self-serve checkout, and an admin console. Features are toggled by environment variables so one codebase can run as a games hub, a license server, a WHOIS tool, or a plain marketing page.

## Features

### Frontpage themes
The `/` route renders `resources/views/themes/{theme}/frontpage.blade.php`, selected by `FRONTPAGE_THEME`:

| Theme | Purpose |
| --- | --- |
| `default` | Marketing landing page |
| `games` | Games catalogue with search and favourites (also at `/games`) |
| `license` | License validator front and centre |
| `login` | Login-first landing |
| `whois` | Domain WHOIS lookup (`POST /whois/lookup`) |
| `cert` | TLS certificate lookup (`POST /cert/lookup`) |
| `subdomains` | Subdomain enumeration (`POST /subdomains/lookup`) |
| `logs` | Event log viewer |

### Games
- Games are `products` rows whose `category` matches `GAMES_CATEGORY` (default `Game`). Each game may link to an external `url` and carry a thumbnail via the media library.
- `GamesSeeder` seeds the starter catalogue: Space Runner, Puzzle Grove, Neon Racers, Chopper, and Blockworld. The same list lives in `config/games.php` as an optional fallback (`GAMES_FALLBACK_TO_CONFIG`).
- Signed-in users can favourite games (`POST /favorites`). Favourites sort to the top of the games frontpage.
- `/games` supports `?q=` search and returns JSON when requested with an `Accept: application/json` header.

### Shop and licensing
- `/shop` lists products; `/shop/{product_code}` shows one.
- Authenticated users buy licenses from `/dashboard` with PayPal (`POST /paypal/orders` then `POST /dashboard/licenses`) or Stripe (`POST /stripe/intents` then `POST /stripe/complete`). Totals are `price × seats`; duration comes from the product's `duration_months`.
- Every license gets a unique identifier such as `ABCD-EFGH-IJKL` and an optional allow-list of domains (`license_domains`).
- Public validation: `GET /license/{code}` (HTML) and `GET /license/validate/{code}?seats_requested=1&domain=example.com` (JSON). API: `POST /api/licenses/validate` with `license_code` and `seats_requested`.
- `/api-lab` lets prospects try the validation API without signing in.

### Authentication
- Email/password login and registration, with session regeneration.
- Optional email two-factor code (`LOGIN_TWO_FACTOR_ENABLED`).
- Social login via Laravel Socialite for Google and Meta (`GOOGLE_OAUTH_ENABLED`, `META_OAUTH_ENABLED`).
- Profile page with password change at `/profile`.

### Admin console (`/admin`, requires `is_admin`)
- CRUD for products, licenses, users, and servers.
- Media library for uploading thumbnails and assigning them to products.
- Log viewers: application logs, event logs (logins, purchases), and external logs received via `POST /api/logs`.
- License validation tester at `/admin/tools/license-validation`.

### Posts
When `POSTS_ENABLED` is on, `/posts` and `/posts/{slug}` read published posts from a WordPress database configured through the `WP_DB_*` variables.

## Requirements

- PHP 8.2+
- Composer 2.x
- MySQL 8+ or MariaDB
- Node.js 18+ (only for the optional Vite/Tailwind build)

## Setup

```bash
composer install
npm install            # optional
cp .env.example .env
php artisan key:generate
```

Set `DB_*` in `.env`, then:

```bash
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS nikniq CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

The web root is `public_html`, not `public`. Point your web server there if you are not using `artisan serve`.

### Demo account
The seeder creates `demo@example.com` / `password` with `is_admin=true`.

To promote another user:

```bash
php artisan tinker --execute="App\\Models\\User::where('email','you@example.com')->update(['is_admin' => true]);"
```

## Configuration

All feature flags live in `.env`. Key ones:

| Variable | Default | Effect |
| --- | --- | --- |
| `FRONTPAGE_THEME` | `default` | Which theme renders `/` |
| `GAMES_ENABLED` / `GAMES_CATEGORY` | `true` / `Game` | Games routes and the product category treated as games |
| `SHOP_ENABLED` | `true` | `/shop` routes |
| `APP_LICENSES_ENABLED` | `true` | All license features |
| `APP_LICENSES_PUBLIC_VALIDATION` | `true` | Public `/license/*` routes |
| `APP_LICENSES_PURCHASE_ENABLED` | `true` | Self-serve checkout |
| `APP_LICENSES_ADMIN` | `true` | Admin license CRUD |
| `API_LAB_ENABLED` | `true` | `/api-lab` |
| `POSTS_ENABLED` | `true` | WordPress posts bridge |
| `ADMIN_SERVERS_ENABLED` | `true` | Admin servers CRUD |
| `ADMIN_EXTERNAL_LOGS_ENABLED` | `true` | External log receiver and viewer |
| `LOGIN_TWO_FACTOR_ENABLED` | `false` | Email 2FA |
| `PAYPAL_*` | | PayPal REST credentials, `sandbox` or `live` |
| `STRIPE_SECRET` / `STRIPE_PUBLIC_KEY` | | Stripe credentials |
| `GOOGLE_*` / `META_*` | | OAuth credentials |
| `WP_DB_*` | | WordPress database for posts |

## Artisan commands

- `php artisan games:migrate-assets [--move]` copies game thumbnails from `public_html/assets/games` into storage.
- `php artisan media:check` verifies that media rows point at files that exist.

## Tests

```bash
php artisan test
```

Tests use the database configured in `.env`. Uncomment the sqlite lines in `phpunit.xml` to run against an in-memory database instead.

## Project layout

```
app/Http/Controllers/          public controllers (Games, Shop, Whois, Cert, Subdomain, payments, licenses)
app/Http/Controllers/Admin/    admin CRUD and log viewers
app/Http/Controllers/Api/      license validation and external log receiver
app/Services/                  PayPalClient, StripeClient, EventLogger, ShopService
config/                        one file per feature flag group (games, shop, license, social, ...)
resources/views/themes/        one folder per frontpage theme
resources/views/admin/         admin views
database/seeders/              DatabaseSeeder (demo user, licenses) and GamesSeeder
public_html/                   web root
```

## License

Built on the [Laravel](https://laravel.com) framework and inherits its MIT License.
