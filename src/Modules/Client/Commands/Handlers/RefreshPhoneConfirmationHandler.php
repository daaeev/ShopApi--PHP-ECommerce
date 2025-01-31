<?php

namespace Project\Modules\Client\Commands\Handlers;

use Project\Modules\Client\Auth\AuthManagerInterface;
use Project\Modules\Client\Commands\RefreshPhoneConfirmationCommand;
use Project\Modules\Client\Entity\Confirmation\ConfirmationUuid;
use Project\Modules\Client\Repository\ClientsRepositoryInterface;
use Project\Common\ApplicationMessages\Events\DispatchEventsTrait;
use Project\Common\ApplicationMessages\Events\DispatchEventsInterface;

class RefreshPhoneConfirmationHandler implements DispatchEventsInterface
{
    use DispatchEventsTrait;

    public function __construct(
        private readonly AuthManagerInterface $auth,
        private readonly ClientsRepositoryInterface $clients,
    ) {}

    public function __invoke(RefreshPhoneConfirmationCommand $command): void
    {
        if (null !== $this->auth->logged()) {
            throw new \DomainException('You are already logged in');
        }

        $confirmationUuid = ConfirmationUuid::make($command->confirmationUuid);
        $client = $this->clients->getByConfirmation($confirmationUuid);
        $client->refreshConfirmationExpiredAt($confirmationUuid);
        $this->clients->update($client);
        $this->dispatchEvents($client->flushEvents());
    }
}