# iSport sign-in demo

One-file web client of the iSport customer login (OAuth 2.0 Authorization Code + PKCE). After sign-in it shows
`/me`, `/my-bookings` and `/my-credits`, can open the website signed in (`web-login`) and signs out (`oauth-revoke`).

- Local: `php -S localhost:8787 index.php`, open http://localhost:8787/
- Hosted: https://demo-vyvoj.isporttest.cz/oauth-demo/

`config.local.php` (never commit) holds `isport_base_url`, `client_id` and `client_secret`. The redirect URI is the
folder of `index.php` (e.g. `http://localhost:8787/`) and must be registered on the app in the iSport admin.
