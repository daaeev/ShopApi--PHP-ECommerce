<?php

namespace Project\Modules\Client\Commands;

use Project\Common\ApplicationMessages\ApplicationMessageInterface;

class RefreshPhoneConfirmationCommand implements ApplicationMessageInterface
{
    public function __construct(
        public readonly string $confirmationUuid,
    ) {}
}