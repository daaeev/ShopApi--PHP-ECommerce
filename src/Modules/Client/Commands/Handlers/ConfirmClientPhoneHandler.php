<?php

namespace Project\Modules\Client\Commands\Handlers;

use Project\Modules\Client\Auth\AuthManagerInterface;
use Project\Modules\Client\Entity\Access\PhoneAccess;
use Project\Modules\Client\Commands\ConfirmClientPhoneCommand;
use Project\Modules\Client\Entity\Confirmation\ConfirmationUuid;
use Project\Modules\Client\Repository\ClientsRepositoryInterface;
use Project\Common\ApplicationMessages\Events\DispatchEventsTrait;
use Project\Common\ApplicationMessages\Events\DispatchEventsInterface;

class ConfirmClientPhoneHandler implements DispatchEventsInterface
{
    use DispatchEventsTrait;

    public function __construct(
        private readonly ClientsRepositoryInterface $clients,
        private readonly AuthManagerInterface $auth,
    ) {}

    public function __invoke(ConfirmClientPhoneCommand $command): void
    {
        if (null !== $this->auth->logged()) {
            throw new \DomainException('You are already logged in');
        }

        $confirmationUuid = ConfirmationUuid::make($command->confirmationUuid);
        $client = $this->clients->getByConfirmation($confirmationUuid);
        $client->applyConfirmation($confirmationUuid, $command->inputCode);

        $access = new PhoneAccess($client->getContacts()->getPhone());
        if (!$client->hasAccess($access)) {
            $client->addAccess($access);
        }

        if (!$client->getContacts()->isPhoneConfirmed()) {
            $client->confirmPhone();
        }

        $this->clients->update($client);
        $this->auth->authorize($access);
        $this->dispatchEvents($client->flushEvents());
    }
}