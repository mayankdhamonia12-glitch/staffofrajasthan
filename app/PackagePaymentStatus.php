<?php

namespace App;

enum PackagePaymentStatus: string
{
    case Created = 'created';
    case Failed = 'failed';
    case Captured = 'captured';
    case Refunded = 'refunded';
}
