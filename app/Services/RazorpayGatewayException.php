<?php

namespace App\Services;

use RuntimeException;

class RazorpayGatewayException extends RuntimeException
{
    public function __construct(public readonly string $failureCode)
    {
        parent::__construct('The payment provider could not complete the request.');
    }
}
