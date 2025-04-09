<?php

namespace Project\Modules\Shopping\Api\Events\Payments;

class PaymentUpdated extends AbstractPaymentEvent
{
    public function getEventId(): string
    {
        return PaymentEvent::UPDATED->value;
    }
}