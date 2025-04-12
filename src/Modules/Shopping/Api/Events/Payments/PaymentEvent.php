<?php

namespace Project\Modules\Shopping\Api\Events\Payments;

enum PaymentEvent: string
{
    case CREATED = 'payments.created';
    case TRANSACTION_ADDED = 'payments.transactionAdded';
    case STATUS_UPDATED = 'payments.statusUpdated';
}
