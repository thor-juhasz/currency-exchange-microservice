<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Closure;



class ApiAuth
{
    /**
     * Authentication checker
     *
     * NOTE: The type checker will complain if the type of next is set to `Closure`...
     * "This call is invalid, this is not a function, it is an object of type Closure (Typing[4009])"
     *
     * @param   Request  $request
     * @param   Closure  $next
     *
     * @return  JsonResponse
     *
     * @author  Þói Juhasz
     */
     public function handle(Request $request, $next): JsonResponse
     {
         // Collect headers when in debug mode
        $debug = (bool) env('APP_DEBUG');
        $debug_headers = [];

        // Check headers
        $signature = $request->header('X-API-Key');
        $date = $request->header('X-API-Date');
        if (! $date) {
            $date = date('Y-m-d H:i:s');
        }

        // Passed date must be of "Y-m-d H:i:s" format
        if (! preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/D', $date)) {
            $message = 'Received an invalid header while trying to authenticate user';

            if (! $debug) {
                return response()->json([
                    'status' => 'error',
                    'message' => $message,
                ], 400);
            }

            $debug_headers['X-Date-Format'] = $message;
        }

        // Try generating datetime object from passed date
        try {
            $datetime = new \DateTime($date);
            $now = new \DateTime();

            $diff = abs($datetime->getTimestamp() - $now->getTimestamp());

            // Verify that signature has not timed out yet
            $signature_timeout = env('API_SIGNATURE_TIMEOUT')
                ? (int) env('API_SIGNATURE_TIMEOUT')
                : 180;

            if ($diff > $signature_timeout) {
                $message = 'API signature has timed out';

                if (! $debug) {
                    return response()->json([
                        'status' => 'error',
                        'message' => $message,
                    ], 403);
                }

                $debug_headers['X-Date-Timeout'] = $message;
            }
        } catch (\Exception $e) {
            // Unauthorize upon error

            $message = $e->getMessage();
            $stack = $e->getTraceAsString();

            if (! $debug) {
                unset($e);
                return response()->json([
                    'status' => 'error',
                    'message' => $message,
                    'stacktrace' => $stack,
                ], 500);
            }

            $debug_headers['X-Date-unexpected'] = $message;
            $debug_headers['X-Date-unexpected-stack'] = $stack;
        }

        // APP must have shared secret configured
        if (! defined('API_SECRET') || ! API_SECRET || ! env('API_NAME')) {
            if (! $debug) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Internal server error',
                ], 500);
            }

            $debug_headers['X-Api-Secret'] = 'API_SECRET or API_NAME not set';
        }

        // Newly generated signature must match passed one
        $check = hash_hmac('sha256', env('API_NAME') . $date, API_SECRET);

        if ($check !== $signature) {
            $message = 'Signature mismatch';

            if (! $debug) {
                return response()->json([
                    'status' => 'error',
                    'message' => $message,
                ], 403);
            }

            $debug_headers['X-Api-Signature'] = $message;
        }

        $response = $next($request);

        if ($debug) {
            foreach ($debug_headers as $header => $message) {
                $response->header($header, $message);
            }
        }

        return $response;
     }
}
