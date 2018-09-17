<?php

namespace App\Http\Controllers\v1;

use App\Exceptions\Currency;
use App\Lib\OER;
use App\Exceptions\Currency as CurrencyException;

use Laravel\Lumen\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;


class ExchangeController extends Controller
{
    /**
     * Return list of currencies this service can exchange
     *
     * @param   \Illuminate\Http\Request  $request  The request
     *
     * @return  \Illuminate\Http\JsonResponse
     * @throws  \App\Exceptions\Currency
     *
     * @author  Þói Juhasz
     */
    public function list(Request $request): JsonResponse
    {
        // Unset $request since we don't use it here
        unset($request);

        // Get list of currencies
        $currencies = OER::getCurrencies();

        // Currency list can not be empty
        if (!is_array($currencies) || empty($currencies))
            throw new CurrencyException(
                "Querying for currency list. Response is invalid.");

        // Return currency list
        return response()->json([
            'status' => "success",
            'currencies' => $currencies
        ], 200);
    }

    /**
     * Return list of exchange rates with $currency as a base
     *
     * @param   \Illuminate\Http\Request  $request   The request
     * @param   string                    $currency  Base currency to get exchange rates for
     *
     * @return  \Illuminate\Http\JsonResponse
     * @throws  \App\Exceptions\Currency
     *
     * @author  Þói Juhasz
     */
    public function rates(Request $request, string $currency = ''): JsonResponse
    {
        // Get currency restrictions from query param
        $symbols = $request->input('symbols');
        $symbols = (is_array($symbols) && !empty($symbols)) ? array_values($symbols) : [];

        // Get exchange rates
        $exchangeRates = OER::getRates($currency, $symbols);

        // If result is null, then $currency is invalid
        if ($exchangeRates === null)
            throw new CurrencyException(
                sprintf("Getting exchange rates. Currency %s is invalid.", $currency),
                [
                    'baseCurrency' => $currency,
                    'symbols' => $symbols
                ]);

        // If list is empty, throw exception
        if (!count($exchangeRates)) {
            $message = "Response is invalid";

            // If symbols is not empty, then the requested currencies are invalid
            if (!empty($symbols)) {
                $message = "Symbols are invalid";
            }

            throw new CurrencyException(
                sprintf("Getting exchange rates. %s.", $message),
                [
                    'baseCurrency' => $currency,
                    'symbols' => $symbols
                ]);
        }

        return response()->json([
            'status' => "success",
            'rates' => $exchangeRates
        ], 200);
    }

    /**
     * Return list of amounts converted from base currency to new currency
     *
     * @param   \Illuminate\Http\Request  $request   The request
     * @param   string                    $base      Base currency the amounts are in
     * @param   string                    $currency  Currency to convert the amounts into
     *
     * @return  \Illuminate\Http\JsonResponse
     * @throws  \App\Exceptions\Currency
     *
     * @author  Þói Juhasz
     */
    public function convert(Request $request, string $base = '', string $currency = ''): JsonResponse
    {
        // Both $base and $currency must be provided
        if (!$base || !$currency)
            throw new CurrencyException(
                "Converting amounts. Both base currency and exchange currency must be provided.");

        // Get list of amounts to convert
        $amounts = $request->input('amounts');

        // Amounts must be provided
        if (!is_array($amounts) || empty($amounts))
            throw new CurrencyException(
                "Converting amounts. Must provide a list of amounts to exchange (eg. ?amounts[]=123&amounts[]=456).",
                [
                    'baseCurrency' => $base,
                    'currency' => $currency
                ]);

        // Amounts must be numeric
        foreach ($amounts as $amount) {
            if (!is_numeric($amount)) {
                throw new CurrencyException(
                    "Converting amounts. Amounts must be numeric (eg. ?amounts[]=123&amounts[]=456).",
                    [
                        'baseCurrency' => $base,
                        'currency' => $currency
                    ]);
            }
        }

        // Convert the amounts
        $converted = OER::convert($base, $currency, $amounts);

        // If result is null, either $base or $currency are invalid
        if ($converted === null)
            throw new CurrencyException(
                sprintf("Converting amounts. Either %s or %s currencies are invalid.", $base, $currency),
                [
                    'baseCurrency' => $base,
                    'currency' => $currency
                ]);

        if (!is_array($converted) || empty($converted))
            throw new CurrencyException(
                "Converting amounts. Response is invalid.",
                [
                    'baseCurrency' => $base,
                    'currency' => $currency
                ]);

        return response()->json([
            'status' => "success",
            'base' => $base,
            'currency' => $currency,
            'amounts' => $converted
        ], 200);
    }
}
