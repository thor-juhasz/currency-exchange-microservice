# Currency Exchange micro-service

This package runs on Lumen PHP framework and is hosted with nginx powered by PHP 7.1.

This package uses the [Open Exchange Rates](https://openexchangerates.org/) API to get currencies and their exchange rates.
It uses Consul key-value store to retrieve the key.

### [Releases](https://github.com/thor-juhasz/currency-exchange-microservice/releases)

See Releases page for latest package

### Requirements

PHP 7.1+

The following PHP extensions are required:

```
php-curl
php-json
php-mbstring
```

Make sure these extensions are installed and enabled.

### Configuration

#### Cache

This service relies heavily on caching.

Make sure to configure it correctly in the `.env` file.

#### Sentry

This service will report all exceptions to Sentry if being run in production.

Make sure to configure the `SENTRY_DSN` string in the `.env` file.

#### Consul service discovery

This package is built to be run as a docker container, using Consul for service discovery.

Make sure to configure consul in the `.env` file.

1. If `CONSUL_AWS` config key is set to `true`, and `APP_DEBUG` is set to `false`,
the system will automatically try and get the AWS instance IP,
and defines that as the consul endpoint with port 8500.

2. If the system environment variable `CONSUL_IP` is set,
that will be defined as the consul endpoint with port 8500.

3. Uses the `CONSUL_ENDPOINT` configuration key from the `.env` file.

#### Open Exchange Rates

You will need to store your Open Exchange Rates authentication key
in the Consul key-value store, with the key name `open_exchange_rates_key`.

### Notes

When running on local nginx installation, site configuration will need to be updated to include this:

```
    location / {
        # Modify try_files to include /index.php?$query_string
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        try_files $uri /index.php =404;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;

        # Only use one of these! Comment out or remove the one that does not apply
        fastcgi_pass unix:/var/run/php7.1-fpm.sock;
        fastcgi_pass 127.0.0.1:9000

        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
```

## Docker with PHP 7.1

This docker uses the juhasz84/ubuntu-nginx-php71-docker:latest image.

See [this page](https://github.com/thor-juhasz/ubuntu-nginx-php71-docker) to have a look at the github repo.

See [this page](https://hub.docker.com/r/juhasz84/ubuntu-nginx-php71-docker/) to have a look at it in hub.docker.com.

## Usage

All requests to this service should be prefixed with `/v1`.
For example,
```
http://currency_exchange:80/v1/list
```

### Authentication

All requests to the API must be authenticated with request headers.

`X-API-Key` - An authentication token.
`X-API-Date` - A datetime stamp (YYYY-MM-DD HH:MM:SS)

To create an authentication key,
create a keyed hash value using the HMAC method
(`hash_hmac` php function with `sha256` algorithm),
of the `API_NAME` env key, datetime stamp, and `SHARED_SECRET` env key.

Example:
```php
$apiName = 'CurrencyExchangeService';
$date = date('Y-m-d H:i:s');
$apiSecret = GetAPISecretHere();
$key = hash_hmac('sha256', $apiName . $date, $apiSecret);
```

The datetime stamp can not be older than 3 minutes
(or whatever the `API_SIGNATURE_TIMEOUT` value is in your `.env` file).

### List of routes

All currencies should be a 3 digit currency code.

1. GET `/list` - List available currencies.
2. GET `/rates/[currency]` - Get the exchange rates for `[currency]`.
    - Can also be provided with a query string to limit results to certain currencies.
    - Query string should be formatted like so: `?symbols[]=EUR&symbols[]=USD`.
3. GET `/convert/[base]/[convert]` - Convert amounts from `[base]` currency to `[convert]` currency.
    - Must be provided with a query string containing a list of amounts to convert.
    - Query string should be formatted like so: `?amounts[]=123&amounts[]=456`.
4. POST `/convert/[base]/[convert]` - Convert amounts from `[base]` currency to `[convert]` currency.
    - Must be provided with a list of amounts to convert in the POST data.
    - POST data should be formatted like so: `amounts[]=123&amounts[]=456`.