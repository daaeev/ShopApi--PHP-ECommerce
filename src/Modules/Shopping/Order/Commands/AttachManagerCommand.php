<?php

namespace Project\Modules\Shopping\Order\Commands;

use Project\Common\ApplicationMessages\ApplicationMessageInterface;

class AttachManagerCommand implements ApplicationMessageInterface
{
    public function __construct(
        public readonly int|string $orderId,
    ) {}
}