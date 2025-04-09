<?php

namespace Project\Modules\Shopping\Billing\Payments\Entity;

enum TransactionStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Paid = 'paid';
    case Refunded = 'refunded';
    case Failed = 'failed';
}
