<?php

return [
    'dsn' => env('SENTRY_DSN', '__DSN__'),

    // Capture release as git sha
    // 'release' => trim(exec('git log --pretty="%h" -n1 HEAD')),

    // Capture bindings on SQL queries
    'breadcrumbs.sql_bindings' => false,

    // Capture default user context
    'user_context' => false
];