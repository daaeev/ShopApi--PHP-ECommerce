<?php

namespace Project\Modules\Shopping\Api\Events\Payments;

class PaymentCreated extends AbstractPaymentEvent
{
    public function getEventId(): string
    {
        return PaymentEvent::CREATED->value;
    }
}