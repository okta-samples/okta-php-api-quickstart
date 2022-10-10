PHP API Quickstart
==================

This is the completed project corresponding to the guide "[Protect your API endpoints](https://developer.okta.com/docs/guides/protect-your-api/php/main/)".


Getting Started
---------------

Install dependencies:

```bash
composer install
```

Copy the file `.env.example` to `.env` and fill in your Okta issuer and audience configuration.

Run the app with the built-in PHP server:

```bash
php -S 127.0.0.1:8080 -t public
```

Get an access token by using an OAuth client such as https://example-app.com/client. Make a request with that access token to this API, such as:

```bash
curl -H "Authorization: Bearer TOKEN" http://127.0.0.1:8080/api/whoami
```
