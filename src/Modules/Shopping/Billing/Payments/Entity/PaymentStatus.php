<?php

namespace Project\Modules\Shopping\Billing\Payments\Entity;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Paid = 'paid';
    case PartiallyPaid = 'partiallyPaid';
    case Refunded = 'refunded';
    case PartiallyRefunded = 'partiallyRefunded';
    case Cancelled = 'cancelled';
    case Failed = 'failed';
}
