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
  echo json_encode($token->all());
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
    $jwt = \Jose\Easy\Load::jws($accessToken)
      ->algs(['RS256'])
      ->keyset($keys)
      ->exp()
      ->iat()
      ->iss($_ENV['OKTA_OAUTH2_ISSUER'])
      ->aud($_ENV['OKTA_AUDIENCE'])
      ->run();
      ;
  } catch(\Exception $e) {
    return false;
  }

  return $jwt->claims;
}

function getJWKS() {
  $cache = new \Kodus\Cache\FileCache(__DIR__.'/../cache/', 86400);

  $cacheKey = 'okta-jwks';

  $jwks = $cache->get($cacheKey);

  if(!$jwks) {
    $client = new \GuzzleHttp\Client();
    $response = $client->request('GET', $_ENV['OKTA_OAUTH2_ISSUER'].'/v1/keys');
    $jwks = (string)$response->getBody();
    $cache->set($cacheKey, $jwks);
  }

  return \Jose\Component\Core\JWKSet::createFromJson($jwks);
}

