# nikniq.com

Laravel 11 app. One codebase, many faces: the frontpage theme, games, shop, licensing, posts, and admin tools are all switched on and off with env flags. Read `config/*.php` before assuming a feature is present.

## Commands

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed          # demo@example.com / password (admin)
php artisan storage:link            # public_html/storage -> storage/app/public
php artisan serve                   # web root is public_html, not public
php artisan test
./vendor/bin/pint                   # code style
```

Deployment: `deploy.sh` runs on the server (pull, composer if lockfile moved, migrate, optimize). `cx.sh` runs locally and drives the server over SSH; it needs a gitignored `vars.sh` copied from `vars.sh.example`.

## Layout

- `routes/web.php` holds every public and admin route, wrapped in `config(...)` checks. The `/` closure picks `resources/views/themes/{FRONTPAGE_THEME}/frontpage.blade.php`.
- `app/Http/Controllers/` public controllers; `Admin/` admin CRUD and log viewers; `Api/` license validation and external log receiver.
- `app/Services/` PayPalClient, StripeClient, EventLogger, ShopService. `NikniqClient.php` is intentionally empty and `FeedClient` is a stub.
- `resources/views/layouts/app.blade.php` carries the shared inline CSS. Tailwind and Vite are configured but views do not depend on a build.
- `database/seeders/GamesSeeder.php` seeds the games catalogue (Space Runner, Puzzle Grove, Neon Racers, Chopper, Blockworld). `config/games.php` mirrors the same list as an optional fallback.

## Domain notes

- Games are `products` rows with `category = GAMES_CATEGORY` (default `Game`), price 0, duration 0. Thumbnails come from the `media` table via `products.media_id`; `GamesController` falls back to keyword-matched SVGs.
- Licenses carry a unique `identifier` (`ABCD-EFGH-IJKL`) and optional `license_domains` allow-list. Validation lives in two places: `Api\LicenseValidationController` (POST, seats only) and `LicenseValidatorJsonController` (GET, seats and domain). Keep their response shapes aligned.
- PayPal checkout caches the order server-side for 30 minutes and forgets it on capture. Stripe checkout verifies amount, currency, and metadata against the payment intent.
- Admin access is the `users.is_admin` flag enforced by `EnsureUserIsAdmin`. Admin routes live under `/admin` with the `admin.` name prefix.
- Posts read from a separate WordPress database (`WP_DB_*` connection, `WpPost` model).

## Conventions

- Feature flags: one `config/{feature}.php` per feature, each reading an env var with a default. Register new routes inside the matching `if (config(...))` block.
- Form validation goes in `app/Http/Requests`. Purchase and login events go through `EventLogger::log`.
- Migrations are additive; never edit a shipped migration.
- Tests use the `.env` database unless the sqlite lines in `phpunit.xml` are uncommented.

## Known gaps

- The media routes are duplicated inside the plain `auth` group in `routes/web.php`, exposing media management to non-admins. Remove them when touching that file.
- Stripe `complete` does not record used payment intent IDs, so a succeeded intent can be replayed.
- No `throttle` middleware on the public lookup endpoints or the license API.
- Test coverage is minimal (three feature tests).
