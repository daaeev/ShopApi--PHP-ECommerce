<?php

namespace Project\Modules\Shopping\Api\Events\Payments;

use Project\Common\ApplicationMessages\Events\Event;
use Project\Modules\Shopping\Billing\Payments\Entity;

abstract class AbstractPaymentEvent extends Event
{
    public function __construct(
        private readonly Entity\Payment $payment,
    ) {}

    public function getData(): array
    {
        // TODO
    }
}