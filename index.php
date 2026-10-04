<?php
// Minimal iSport sign-in demo: OAuth 2.0 Authorization Code + PKCE, then the customer API.
// Local run: php -S localhost:8787 index.php

$config = require __DIR__ . '/config.local.php';
session_name('isport_oauth_demo');
session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Lax']);

$https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
$redirectUri = ($https ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/';
$base = $config['isport_base_url'];

function b64url($bytes) { return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '='); }
function h($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }

function call($method, $url, $body = null, $headers = array())
{
	$curl = curl_init($url);
	curl_setopt_array($curl, array(CURLOPT_CUSTOMREQUEST => $method, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20, CURLOPT_HTTPHEADER => $headers));
	if ($body !== null)
		curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
	$raw = (string) curl_exec($curl);
	return array('status' => (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE), 'json' => json_decode($raw, true), 'raw' => $raw);
}

function back($message = null)
{
	if ($message)
		$_SESSION['message'] = $message;
	header('Location: ./');
	exit;
}

$client = array('client_id' => $config['client_id'], 'client_secret' => $config['client_secret']);
$form = array('Content-Type: application/x-www-form-urlencoded');
$auth = isset($_SESSION['token']) ? array('Authorization: Bearer ' . $_SESSION['token']) : array();
$action = $_GET['action'] ?? null;

if ($action === 'login')
{
	$_SESSION['verifier'] = b64url(random_bytes(48));
	$_SESSION['state'] = b64url(random_bytes(16));
	header('Location: ' . $base . '/oauth-authorize.php?' . http_build_query(array('response_type' => 'code', 'client_id' => $config['client_id'],
		'redirect_uri' => $redirectUri, 'state' => $_SESSION['state'], 'code_challenge' => b64url(hash('sha256', $_SESSION['verifier'], true)), 'code_challenge_method' => 'S256')));
	exit;
}

if (isset($_GET['state']))
{
	if (!hash_equals($_SESSION['state'] ?? '', (string) $_GET['state']))
		back('State mismatch - start the sign-in again.');
	if (isset($_GET['error']))
		back('Sign-in not completed: ' . $_GET['error']);

	$token = call('POST', $base . '/oauth-token.php', http_build_query($client + array('grant_type' => 'authorization_code', 'code' => (string) $_GET['code'],
		'redirect_uri' => $redirectUri, 'code_verifier' => $_SESSION['verifier'])), $form);
	if (empty($token['json']['access_token']))
		back('Token exchange failed: ' . $token['raw']);
	$_SESSION['token'] = $token['json']['access_token'];
	back();
}

if ($action === 'website' && $auth)
{
	$link = call('POST', $base . '/api/v1/web-login.php', '{"destination":"bookings"}', array_merge($auth, array('Content-Type: application/json')));
	empty($link['json']['url']) ? back('Web login failed: ' . $link['raw']) : header('Location: ' . $link['json']['url']);
	exit;
}

if ($action === 'logout' && $auth)
{
	call('POST', $base . '/oauth-revoke.php', http_build_query($client + array('token' => $_SESSION['token'])), $form);
	unset($_SESSION['token']);
	back();
}

$message = $_SESSION['message'] ?? null;
unset($_SESSION['message']);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>iSport sign-in demo</title>
<style>
body {font: 15px/1.5 system-ui, sans-serif; max-width: 860px; margin: 0 auto; padding: 16px; color: #1f2933;}
a.button {display: inline-block; padding: 8px 14px; background: #0093dd; color: #fff; border-radius: 4px; text-decoration: none; margin-right: 8px;}
pre {background: #f4f6f8; padding: 12px; overflow: auto; max-height: 320px; font-size: 13px;}
.muted {color: #6b7280;}
.error {color: #b42318;}
</style>
</head>
<body>
<h1>iSport sign-in demo</h1>
<?php if ($message): ?><p class="error"><?php echo h($message); ?></p><?php endif; ?>
<?php if (!$auth): ?>
<p><a class="button" href="?action=login">Sign in with iSport</a></p>
<p class="muted">Test account: test-app-customer@example.invalid / TestPass123!</p>
<?php else: ?>
<p><a class="button" href="?action=website">Open bookings on the website</a><a href="?action=logout">Sign out</a></p>
<?php foreach (array('me.php', 'my-bookings.php', 'my-credits.php') as $endpoint): $response = call('GET', $base . '/api/v1/' . $endpoint, null, $auth); ?>
<h2>GET /api/v1/<?php echo $endpoint; ?> <span class="muted"><?php echo $response['status']; ?></span></h2>
<pre><?php echo h($response['json'] !== null ? json_encode($response['json'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : $response['raw']); ?></pre>
<?php endforeach; ?>
<?php endif; ?>
</body>
</html>
