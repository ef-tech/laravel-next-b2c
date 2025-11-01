<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Error Messages (English)
    |--------------------------------------------------------------------------
    |
    | RFC 7807-compliant error messages (English)
    | Corresponds to error code definitions (shared/error-codes.json)
    |
    */

    'auth' => [
        'invalid_credentials' => 'Invalid email or password',
        'token_expired' => 'Authentication token has expired',
        'token_invalid' => 'Invalid authentication token',
        'insufficient_permissions' => 'Insufficient permissions',
    ],

    'validation' => [
        'invalid_input' => 'Validation failed',
        'invalid_email' => 'Invalid email format',
    ],

    'business' => [
        'resource_not_found' => 'Resource not found',
        'resource_conflict' => 'Resource already exists',
    ],

    'infrastructure' => [
        'database_unavailable' => 'Database connection failed',
        'external_api_error' => 'External API request failed',
        'request_timeout' => 'Request timeout',
    ],
];
