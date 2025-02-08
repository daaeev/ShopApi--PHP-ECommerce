<?php

namespace Project\Infrastructure\Laravel\ApplicationMessages\Buses;

use Project\Infrastructure\Laravel\Jobs\ProcessCommand;
use Project\Common\Services\Environment\EnvironmentInterface;
use Project\Common\ApplicationMessages\ApplicationMessageInterface;
use Project\Common\ApplicationMessages\Buses\MessageBusInterface;

class QueueCommandBus implements MessageBusInterface
{
    public function __construct(
        private readonly EnvironmentInterface $environment,
    ) {}

    public function dispatch(ApplicationMessageInterface $message)
    {
        ProcessCommand::dispatch($message, $this->environment->getEnvironment())->onQueue('commands');
    }

    public function canDispatch(ApplicationMessageInterface $message): bool
    {
        return true;
    }
}