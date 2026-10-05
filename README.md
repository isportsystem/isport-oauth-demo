# iSport sign-in demo

One-file web client of the iSport customer login (OAuth 2.0 Authorization Code + PKCE). After sign-in it shows
`/me`, `/my-bookings` and `/my-credits`, can open the website signed in (`web-login`) and signs out (`oauth-revoke`).

## Setup

1. In the iSport admin, open **OAuth apps** and add an app of type **Web server**. Register the folder of `index.php`
   as the redirect URI, e.g. `http://localhost:8787/`, and copy the client ID and client secret.
2. Create `config.local.php` next to `index.php` (it is in `.gitignore` - never commit it):

   ```php
   <?php
   return array(
   	'isport_base_url' => 'https://your-isport-site.example',
   	'client_id' => '...',
   	'client_secret' => '...',
   );
   ```

3. Run `php -S localhost:8787 index.php` (PHP 7.4+ with curl) and open http://localhost:8787/.

The full guide is in the iSport admin under **OAuth apps** - customer API documentation.
