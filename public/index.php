<?php
require_once(__DIR__.'/../vendor/autoload.php');

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__.'/..');
$dotenv->load();
$dotenv->required(['OKTA_OAUTH2_ISSUER', 'OKTA_AUDIENCE'])->notEmpty();

// NOTE: "*" allows any origin. Restrict this to your own front-end origin in production.
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
header('Access-Control-Allow-Methods: GET, OPTIONS');

// Answer CORS preflight requests sent by browsers before a request with an Authorization header
if(($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
  http_response_code(204);
  exit;
}

// Uncomment this to require a valid access token for every route
// if(!hasValidAccessToken()) {
//   unauthorized();
// }

$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
switch($path) {

  case '/api/whoami':
    whoami();
    break;

  // Public route: no access token required
  case '/api/hello':
    hello();
    break;

  default:
    http_response_code(404);
    echo "Not Found";

}

function unauthorized() {
  http_response_code(401);
  header('WWW-Authenticate: Bearer');
  echo "Unauthorized";
  exit;
}

function whoami() {
  $token = hasValidAccessToken();

  if(!$token) {
    unauthorized();
  }

  header('Content-type: application/json');
  echo json_encode($token);
}

function hello() {
  echo "Hello World";
}


function hasValidAccessToken() {
  // Require an access token is sent in the HTTP Authorization header.
  // Some servers (e.g. Apache with CGI) only expose it as REDIRECT_HTTP_AUTHORIZATION.
  $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';

  if(!preg_match('/^Bearer\s+(\S+)$/i', $header, $matches)) {
    return false;
  }

  $accessToken = $matches[1];

  try {
    // Verifies the signature (against the issuer's JWKS) and the exp/nbf claims
    $decoded = \Firebase\JWT\JWT::decode($accessToken, getJWKS());
  } catch(\Throwable $e) {
    // Don't leak validation details to the client. Log them server-side instead.
    error_log('Access token validation failed: '.$e->getMessage());
    return false;
  }

  // Check the issuer and audience claims
  $issuer = rtrim($_ENV['OKTA_OAUTH2_ISSUER'], '/');

  if(($decoded->iss ?? null) !== $issuer)
    return false;

  // "aud" may be a string or an array of strings
  if(!in_array($_ENV['OKTA_AUDIENCE'], (array)($decoded->aud ?? []), true))
    return false;

  return $decoded;
}

function getJWKS() {
  // Reuse the key set (and its HTTP client and cache) across calls within a request
  static $keySet = null;

  if($keySet !== null) {
    return $keySet;
  }

  $httpClient = new \GuzzleHttp\Client();
  $httpFactory = new \GuzzleHttp\Psr7\HttpFactory();
  $cacheItemPool = \Phpfastcache\CacheManager::getInstance('files');

  $jwksUri = rtrim($_ENV['OKTA_OAUTH2_ISSUER'], '/').'/v1/keys';

  $keySet = new \Firebase\JWT\CachedKeySet(
      $jwksUri,
      $httpClient,
      $httpFactory,
      $cacheItemPool,
      300,  // $expiresAfter int seconds to set the JWKS to expire
      true  // $rateLimit    true to enable rate limit of 10 RPS on lookup of invalid keys
  );

  return $keySet;
}
