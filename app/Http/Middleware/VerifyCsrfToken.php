<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        //
        'charges',
        'withdraw-api',
        'api-transaction-detail',
        'merchant-withdrawal',
        'withdraw-payment',
        'createConnection',
        'makeBulkPayment'
    ];
}
