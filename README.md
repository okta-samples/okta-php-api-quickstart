PHP API Quickstart
==================

Install dependencies:

```bash
composer install
```

Copy the file `.env.example` to `.env` and fill in your Okta app configuration.

Run the app with the built-in PHP server:

```bash
php -S 127.0.0.1:8080 -t public
```

Get an access token by using an OAuth client. Make a request with that access token to this API, such as:

```bash
curl -H "Authorization: Bearer TOKEN" http://127.0.0.1:8080/api/whoami
```
