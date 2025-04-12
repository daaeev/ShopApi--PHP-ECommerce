<?php

namespace Project\Modules\Shopping\Api\Events\Orders;

enum OrderEvent: string
{
    case CREATED = 'orders.created';
    case UPDATED = 'orders.updated';
    case DELETED = 'orders.deleted';
    case COMPLETED = 'orders.completed';
    case PAYMENT_STATUS_UPDATED = 'orders.paymentStatusUpdated';
}
