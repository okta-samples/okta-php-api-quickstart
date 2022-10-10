<?php
require_once(__DIR__.'/../vendor/autoload.php');

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__.'/..');
$dotenv->load();

header('Access-Control-Allow-Origin: *');

// Uncomment this to require a valid access token for every route
// if(!hasValidAccessToken()) {
//   header('HTTP/1.1 401 Unauthorized');
//   echo "Unauthorized";
//   die();
// }

$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
switch($path) {

  case '/api/whoami':
    whoami();
    break;

  case '/api/hello':
    hello();
    break;

}

function whoami() {
  $token = hasValidAccessToken();

  if(!$token) {
    header('HTTP/1.1 401 Unauthorized');
    echo "Unauthorized";
    die();
  }

  header('Content-type: application/json');
  echo json_encode($token);
}

function hello() {
  echo "Hello World";
}


function hasValidAccessToken() {
  // Require an access token is sent in the HTTP Authorization header
  if(!isset($_SERVER['HTTP_AUTHORIZATION'])) {
    return false;
  }

  $accessToken = explode(' ', $_SERVER['HTTP_AUTHORIZATION'])[1];

  $keys = getJWKS();

  try {
    $decoded = \Firebase\JWT\JWT::decode($accessToken, $keys);
  } catch(\Exception $e) {
    echo $e->getMessage()."\n";
    return false;
  }

  // Check the audience and issuer claims

  if($decoded->iss != $_ENV['OKTA_OAUTH2_ISSUER'])
    return false;

  if($decoded->aud != $_ENV['OKTA_AUDIENCE'])
    return false;

  return $decoded;
}

function getJWKS() {
  $httpClient = new \GuzzleHttp\Client();
  $httpFactory = new \GuzzleHttp\Psr7\HttpFactory();
  $cacheItemPool = \Phpfastcache\CacheManager::getInstance('files');

  $jwksUri = $_ENV['OKTA_OAUTH2_ISSUER'].'/v1/keys';

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

