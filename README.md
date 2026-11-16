PHP API Quickstart
==================

This is the completed project corresponding to the guide "[Protect your API endpoints](https://developer.okta.com/docs/guides/protect-your-api/php/main/)".


Prerequisites
-------------

- PHP 8.1 or later with the `curl` and `json` extensions
- [Composer](https://getcomposer.org/)
- An Okta org with a **custom authorization server** (the `default` one works). The org authorization server cannot issue access tokens that third-party APIs can validate.

> **Note**: Okta Integrator Free Plan orgs no longer support machine-to-machine (M2M) apps, so the client credentials flow is not available there. To follow these steps, use a sandbox or preview org. The code is still valid for any Okta org.

Getting Started
---------------

Install dependencies:

```bash
composer install
```

Copy the file `.env.example` to `.env` and set:

- `OKTA_OAUTH2_ISSUER`: the issuer URI of your authorization server, for example `https://{yourOktaDomain}/oauth2/default` (no trailing slash needed).
- `OKTA_AUDIENCE`: the audience configured on that authorization server, for example `api://default`.

Run the API with the built-in PHP server. It uses port 8081 so it can run alongside the [okta-php-app-quickstart](https://github.com/okta-samples/okta-php-app-quickstart) front-end, which uses 8080:

```bash
php -S 127.0.0.1:8081 -t public
```

Test the API
------------

The public route needs no token:

```bash
curl http://127.0.0.1:8081/api/hello
```

`/api/whoami` is protected and returns the token claims, or 401 without a valid token. This API has no login page of its own, so you need a front-end application that signs the user in with the authorization code flow and sends the resulting access token to the API as a `Bearer` token. You can't test the protected route with cURL or Postman alone, because that would need a machine-to-machine (client credentials) app. Use the same issuer and an audience that matches your authorization server for both the front-end and this API.

If you run behind Apache with CGI/FastCGI and the `Authorization` header is missing, add this to your rewrite rules: `RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]`.
