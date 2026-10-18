<?php

namespace App;

enum PaymentWebhookStatus: string
{
    case Processing = 'processing';
    case Processed = 'processed';
    case Ignored = 'ignored';
    case Failed = 'failed';
}
