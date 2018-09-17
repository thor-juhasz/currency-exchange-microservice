<?php

namespace App\Exceptions;

use Exception;
use App\Exceptions\Currency as CurrencyException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Laravel\Lumen\Exceptions\Handler as ExceptionHandler;


class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        AuthorizationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        ValidationException::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param   \Exception  $e
     *
     * @return  void
     * @throws \Exception
     *
     * @author  Þói Juhasz
     */
    public function report(Exception $e)
    {
        $debug = (bool) env('APP_DEBUG', true);
        $sentry_dsn = (string) env('SENTRY_DSN', '');
        if (!$debug && !empty($sentry_dsn) && app()->bound('sentry') && $this->shouldReport($e)) {
            /** @var  \Raven_Client $sentry */
            $sentry = app('sentry');

            if ($e instanceof CurrencyException) {
                $sentry->extra_context($e->get_extra_data());
            }

            $sentry->captureException($e);
        }

        parent::report($e);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param   \Illuminate\Http\Request  $request
     * @param   \Exception  $e
     * @return  \Illuminate\Http\JsonResponse
     *
     * @author  Þói Juhasz
     */
    public function render($request, Exception $e): JsonResponse
    {
        if ($e instanceof ValidationException) {
            return response()->json([
                'status' => 'error',
                'validation_errors' => $e->validator->errors()->all(),
            ], 422);
        }

        $debug = (bool) env('APP_DEBUG');

        $res = [
            'status' => 'error',
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ];

        if ($debug) {
            if ($e instanceof CurrencyException) {
                $res['extra_data'] = $e->get_extra_data();
            }

            $res['exception'] = get_class($e);
            $res['stacktrace'] = [];
            $trace = explode("\n", $e->getTraceAsString());
            foreach ($trace as $line) {
                $res['stacktrace'][] = $line;
            }
            $res['request_url'] = $request->fullUrl();
            $res['request_body'] = $request->getContent();
            $res['request_headers'] = $request->header();
        }

        $status = 500;
        if (method_exists($e, 'getStatusCode')) {
            $status = $e->getStatusCode();
        }

        return response()->json($res, $status);
    }
}