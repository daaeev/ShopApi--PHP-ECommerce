<?php

namespace Project\Modules\Shopping\Api\Events\Payments;

class PaymentStatusUpdated extends AbstractPaymentEvent
{
    public function getEventId(): string
    {
        return PaymentEvent::STATUS_UPDATED->value;
    }
}