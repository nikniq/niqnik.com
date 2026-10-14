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

### Game SSO and stats
Nikniq is the central login for the games on its subdomains and keeps their statistics.

- Admins register each game at `/admin/game-clients`. A client gets a `client_id`, a secret (shown once), a slug, and a list of allowed redirect URIs. Browser-only games are marked **public** and use PKCE instead of a secret.
- Sign-in is a standard authorization-code flow: the game sends the player to `/sso/authorize`, Nikniq signs them in and redirects back with a one-time code, and the game exchanges it at `POST /api/sso/token` for a Bearer player token.
- Games report activity to `POST /api/games/{slug}/events` with that token. Nikniq stores every event and keeps per-player aggregates: plays, wins, best and total score, time played, last level.
- `GET /api/sso/me` returns the player, their stats for that game, and their rank. `GET /api/games/{slug}/leaderboard` is public.
- Players see all their games on `/dashboard` and each game's standings at `/games/{slug}/leaderboard`.

See [Integrating a game](#integrating-a-game) below.

### Shop and licensing
- `/shop` lists products; `/shop/{product_code}` shows one.
- Authenticated users buy licenses from `/dashboard` with PayPal (`POST /paypal/orders` then `POST /dashboard/licenses`) or Stripe (`POST /stripe/intents` then `POST /stripe/complete`). Totals are `price × seats`; duration comes from the product's `duration_months`.
- Every license gets a unique identifier such as `ABCD-EFGH-IJKL` and an optional allow-list of domains (`license_domains`).
- Public validation: `GET /license/{code}` (HTML) and `GET /license/validate/{code}?seats_requested=1&domain=example.com` (JSON). API: `POST /api/licenses/validate` with `license_code` and `seats_requested`.
- `/api-lab` lets prospects try the validation API without signing in.

### Authentication
- Email/password login and registration, with session regeneration.
- Optional email two-factor code (`LOGIN_TWO_FACTOR_ENABLED`).
- Social login via Laravel Socialite for Google and Meta (`GOOGLE_OAUTH_ENABLED`, `META_OAUTH_ENABLED`). Google sign-up requires a verified Google email. A Google email only takes over an existing password account when Google reports it verified; otherwise the user signs in with their password and links Google from the profile page.
- Accounts created through Google have no password until the user sets one from `/profile`. Signed-in users can link or unlink a provider there; unlinking requires a password so nobody gets locked out.
- Google profile photos are stored on `users.avatar` and shown on the profile page.
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
| `SSO_ENABLED` | `true` | Game sign-on and stats API |
| `SSO_CODE_TTL_MINUTES` / `SSO_TOKEN_TTL_DAYS` | `5` / `30` | Authorization code and player token lifetimes |
| `ADMIN_EXTERNAL_LOGS_ENABLED` | `true` | External log receiver and viewer |
| `LOGIN_TWO_FACTOR_ENABLED` | `false` | Email 2FA |
| `PAYPAL_*` | | PayPal REST credentials, `sandbox` or `live` |
| `STRIPE_SECRET` / `STRIPE_PUBLIC_KEY` | | Stripe credentials |
| `GOOGLE_*` / `META_*` | | OAuth credentials |
| `WP_DB_*` | | WordPress database for posts |

## Integrating a game

1. **Register the game** at `/admin/game-clients`. Add the exact callback URL the game will use as a redirect URI. Tick *Public client* for a static JS game with no server. Copy the secret when it is shown.

2. **Send the player to Nikniq** when they press "Sign in":

   ```
   https://nikniq.com/sso/authorize
       ?client_id=CLIENT_ID
       &redirect_uri=https://chopper.nikniq.com/auth/callback
       &state=RANDOM
       &code_challenge=BASE64URL(SHA256(verifier))   # public clients, optional otherwise
       &code_challenge_method=S256
   ```

   Nikniq redirects back to `redirect_uri?code=...&state=...` within 5 minutes. On failure it sends `error` and `error_description` instead, or shows an error page if the redirect URI is not registered.

3. **Exchange the code** for a player token. Confidential clients do this server-side with the secret; public clients send the PKCE verifier.

   ```bash
   curl -X POST https://nikniq.com/api/sso/token \
     -H 'Content-Type: application/json' \
     -d '{"grant_type":"authorization_code","client_id":"CLIENT_ID","client_secret":"SECRET",
          "code":"CODE","redirect_uri":"https://chopper.nikniq.com/auth/callback"}'
   ```

   Response: `{ token_type, access_token, expires_at, player: {id,name,email,avatar}, stats }`. Tokens last 30 days. Store the token, not the code.

4. **Report events** as the player plays:

   ```bash
   curl -X POST https://nikniq.com/api/games/chopper/events \
     -H 'Authorization: Bearer TOKEN' -H 'Content-Type: application/json' \
     -d '{"event":"game_over","score":1240,"won":false,"duration_seconds":87,"level":"cave-3","metadata":{"deaths":2}}'
   ```

   Fields: `event` (required, `[a-z0-9_.-]`), `score`, `level`, `duration_seconds`, `won`, `metadata` (object, up to 50 keys), `occurred_at`. Send `{"events":[...]}` for a batch of up to 50. Events named `game_over`, `finish`, `complete`, `round_end`, or `session_end` count as a play. The response includes the updated stats and rank.

5. **Read back**: `GET /api/sso/me` with the token, or `GET /api/games/chopper/leaderboard?limit=25` without one. `POST /api/sso/logout` revokes the token.

Browser-only games can call these endpoints directly: Laravel's default CORS config allows any origin on `/api/*`. All SSO endpoints are rate limited.

Minimal browser flow for a public client:

```js
const verifier = base64url(crypto.getRandomValues(new Uint8Array(32)));
sessionStorage.setItem('pkce', verifier);
const challenge = base64url(await crypto.subtle.digest('SHA-256', new TextEncoder().encode(verifier)));
location.href = `https://nikniq.com/sso/authorize?client_id=${CLIENT_ID}&redirect_uri=${encodeURIComponent(CALLBACK)}&state=${state}&code_challenge=${challenge}&code_challenge_method=S256`;

// on the callback page
const code = new URLSearchParams(location.search).get('code');
const res = await fetch('https://nikniq.com/api/sso/token', {method:'POST', headers:{'Content-Type':'application/json'},
  body: JSON.stringify({grant_type:'authorization_code', client_id: CLIENT_ID, code, redirect_uri: CALLBACK, code_verifier: sessionStorage.getItem('pkce')})});
const { access_token, player } = await res.json();
```

## Artisan commands

- `php artisan games:migrate-assets [--move]` copies game thumbnails from `public_html/assets/games` into storage.
- `php artisan media:check` verifies that media rows point at files that exist.

## Tests

```bash
php artisan test
```

Tests run against an in-memory sqlite database configured in `phpunit.xml`, so no MySQL is needed.

## Project layout

```
app/Http/Controllers/          public controllers (Games, Shop, Whois, Cert, Subdomain, payments, licenses)
app/Http/Controllers/Admin/    admin CRUD and log viewers
app/Http/Controllers/Api/      license validation and external log receiver
app/Services/                  PayPalClient, StripeClient, EventLogger, ShopService, GameStatsService
app/Http/Controllers/Sso/      /sso/authorize for game sign-in
config/                        one file per feature flag group (games, shop, license, social, ...)
resources/views/themes/        one folder per frontpage theme
resources/views/admin/         admin views
database/seeders/              DatabaseSeeder (demo user, licenses) and GamesSeeder
public_html/                   web root
```

## License

Built on the [Laravel](https://laravel.com) framework and inherits its MIT License.
