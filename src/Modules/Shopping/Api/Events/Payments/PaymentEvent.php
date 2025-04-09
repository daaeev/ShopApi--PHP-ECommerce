<?php

namespace Project\Modules\Shopping\Api\Events\Payments;

enum PaymentEvent: string
{
    case CREATED = 'payments.created';
    case UPDATED = 'payments.updated';
    case DELETED = 'payments.deleted';

    case TRANSACTION_ADDED = 'payments.transactionAdded';
    case STATUS_UPDATED = 'payments.statusUpdated';
}
