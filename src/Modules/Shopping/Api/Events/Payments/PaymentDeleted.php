<?php

namespace Project\Modules\Shopping\Api\Events\Payments;

class PaymentDeleted extends AbstractPaymentEvent
{
    public function getEventId(): string
    {
        return PaymentEvent::DELETED->value;
    }
}