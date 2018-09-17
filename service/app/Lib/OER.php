<?php

namespace App\Lib;

use Aveiv\OpenExchangeRatesApi\Client as OERClient;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\Facades\Cache;

/**
 * Class OER
 *
 * @provides  getCurrencies()  Get list of supported currencies
 * @provides  getRates()       Get currency exchange rates
 * @provides  convert()        Convert numbers from one currency to another
 *
 * @package App\Lib
 *
 * @author  Þói Juhasz
 */
class OER
{
    /**
     * Instance of Open Exchange Rates client, set after class definition
     *
     * @var \Aveiv\OpenExchangeRatesApi\Client
     */
    public static $client = '';

    /**
     * Create client
     *
     * @param   bool  $force  Default false. If true, will create new GuzzleClient
     *
     * @return  void
     *
     * @author  Þói Juhasz
     */
    public static function init($force = false): void
    {
        if (!static::$client || $force) {
            static::$client = new OERClient(
                static::getOpenExchangeRatesKey(),
                (new GuzzleClient())
            );
        }
    }

    /**
     * Get a list of all supported currencies
     *
     * Results are renewed if cache age exceeds 1 hour
     * Results are stored in cache for 3 hours though to give a 2 hour grace period for service downtime
     *
     * @return  array<string, string>
     *
     * @author  Þói Juhasz
     */
    public static function getCurrencies(): array
    {
        OER::init();

        // Get list supported currencies from cache
        $currencies = Cache::get('currency_list', []);

        // Also get time when last updated
        $currenciesUpdated = (int) Cache::get('currency_list_updated', 3600);

        // Calculate how long ago in seconds since list was fetched
        $lastUpdated = ((int) round(time() / 60)) - $currenciesUpdated;

        // If list is empty, or was updated 1 hour or more ago, refetch
        if (!count($currencies) || $lastUpdated >= 3600) {
            try {
                $currencies = static::$client->getCurrencies();
                // Store in cache (for 3 hours)
                Cache::put('currency_list', $currencies, 180);
                Cache::put('currency_list_updated', round(time() / 60), 180);
            } catch (\Exception $e) {
                // Let's not do anything here for now
            }
        }

        return $currencies;
    }

    /**
     * Get exchange rates for a currency
     *
     * Results are renewed if cache age exceeds 1 hour
     * Results are stored in cache for 3 hours though to give a 2 hour grace period for service downtime
     *
     * @param   string         $currency  The currency to use as a base for the exchange rates
     * @param   array<string>  $symbols   List of currencies to get exchange rates for, if empty, fetches all
     *
     * @return  null|array<string, float>
     *
     * @author  Þói Juhasz
     */
    public static function getRates(string $currency, array $symbols = []): ?array
    {
        OER::init();

        // Make sure $currency is supported
        if (!array_key_exists($currency, static::getCurrencies()))
            return null;

        // Construct cache key
        $cacheKey = sprintf('rates_%s_%s', $currency, sha1(serialize($symbols)));

        // Get exchanges rates from cache
        $exchangeRates = Cache::get($cacheKey, []);
        // Also get time when last updated
        $exchangeRatesUpdated = Cache::get(sprintf("%s_updated", $cacheKey), 3600);

        // Calculate how long ago in seconds since exchange rates were fetched
        $lastUpdated = round(time() / 60) - $exchangeRatesUpdated;

        // If exchange rates list is empty, or were updated 1 hour or more ago, refetch
        if (!count($exchangeRates) || $lastUpdated >= 3600) {
            $response = static::$client->getLatest($currency, $symbols);
            $exchangeRates = $response['rates'];
            // Store in cache (for 3 hours)
            Cache::put($cacheKey, $exchangeRates, 180);
            Cache::put(sprintf("%s_updated", $cacheKey), round(time() / 60), 180);
        }

        // Return results
        return $exchangeRates;
    }

    /**
     * Convert a list of amounts from $base currency to new currency
     *
     * @param   string        $base      Base currency that $amounts are in
     * @param   string        $currency  Currency to exchange $amounts into
     * @param   array<float>  $amounts   List of amounts in $base currency to exchange to new $currency
     *
     * @return  null|array<float>
     *
     * @author  Þói Juhasz
     */
    public static function convert(string $base, string $currency, array $amounts): ?array
    {
        // Get the exchange rates
        $exchangeRates = static::getRates($base);

        // Make sure $base and $currency are both supported
        if (!array_key_exists($currency, static::getCurrencies()) || $exchangeRates === null)
            return null;

        // Get exchange rate for the currency
        $rate = (float) $exchangeRates[$currency];

        // Convert all amounts to new currency
        $convertedAmounts = array_map(
            function ($amount) use ($rate) {
                return (float) round($amount * $rate, 2);
            }, $amounts);

        // Return the exchanged amounts
        return $convertedAmounts;
     }

    /**
     * Get the Open Exchange Rates API key from consul KeyValue store
     *
     * @return  null|string
     *
     * @author  Þói Juhasz
     */
    public static function getOpenExchangeRatesKey(): ?string
    {
        return Consul::getKeyValue('open_exchange_rates_key');
    }
}