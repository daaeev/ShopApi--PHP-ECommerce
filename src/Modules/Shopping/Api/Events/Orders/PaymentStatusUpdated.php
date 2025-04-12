<?php

namespace Project\Modules\Shopping\Api\Events\Orders;

class PaymentStatusUpdated extends AbstractOrderEvent
{
    public function getEventId(): string
    {
        return OrderEvent::COMPLETED->value;
    }
}