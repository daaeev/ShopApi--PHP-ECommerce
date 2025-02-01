<?php

namespace Project\Common\Services\SMS;

use Psr\Log\LoggerInterface;

class LogSmsSender implements SmsSenderInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    public function send(string $phone, string $message): void
    {
        $this->logger->info("Sending SMS to $phone: \"$message\"");
    }
}