<?php

// For health checks
$app->get('/', function () use ($app) {
    $code = 200;
    $response = [
        'status'     => 'OK',
        'time'       => date('Y-m-d H:i:s'),
        'agent'      => false,
        'agent_on'   => (bool)env('CONSUL_ENABLED'),
        'agent_addr' => CONSUL_ENDPOINT
    ];

    if (\App\Lib\Consul::getAgentStatus()) {
        $response['agent'] = true;
    }

    if (
        !defined('API_SECRET') ||
        !API_SECRET ||
        !env('API_NAME') ||
        ($response['agent_on'] && !$response['agent'])
    ) {
        $code = 503;
        $response['status'] = 'Unavailable';
    }
    return response()->json($response, $code);
});
