<?php

// API endpoints
$app->group(['middleware' => 'ApiAuth', 'prefix' => 'v1'], function () use ($app) {
    // Get a list of all supported currencies
    $app->get('list', 'ExchangeController@list');

    // Get the exchange rates for a currency
    $app->get('rates/{currency}', 'ExchangeController@rates');

    // Convert from base currency to new currency
    $app->post('convert/{base}/{currency}', 'ExchangeController@convert');
    $app->get('convert/{base}/{currency}', 'ExchangeController@convert');
});
