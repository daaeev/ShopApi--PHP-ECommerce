<?php

namespace Project\Modules\Client\Commands\Handlers;

use Project\Modules\Client\Entity\Client;
use Project\Modules\Client\Entity\ClientId;
use Project\Common\Repository\NotFoundException;
use Project\Modules\Client\Auth\AuthManagerInterface;
use Project\Modules\Client\Commands\GeneratePhoneConfirmationCommand;
use Project\Modules\Client\Repository\ClientsRepositoryInterface;
use Project\Common\ApplicationMessages\Events\DispatchEventsTrait;
use Project\Common\ApplicationMessages\Events\DispatchEventsInterface;
use Project\Modules\Client\Entity\Confirmation\CodeGeneratorInterface;

class GeneratePhoneConfirmationHandler implements DispatchEventsInterface
{
    use DispatchEventsTrait;

    public function __construct(
        private readonly AuthManagerInterface $auth,
        private readonly ClientsRepositoryInterface $clients,
        private readonly CodeGeneratorInterface $codeGenerator,
    ) {}

    public function __invoke(GeneratePhoneConfirmationCommand $command): string
    {
        if (null !== $this->auth->logged()) {
            throw new \DomainException('You are already logged in');
        }

        $client = $this->getClient($command->phone);
        $confirmationUuid = $client->generateConfirmation($this->codeGenerator);
        $this->clients->update($client);
        $this->dispatchEvents($client->flushEvents());
        return $confirmationUuid->getId();
    }

    private function getClient(string $phone): Client
    {
        try {
            return $this->clients->getByPhone($phone);
        } catch (NotFoundException) {
            $client = new Client(ClientId::next(), phone: $phone);
            $this->clients->add($client);
            return $client;
        }
    }
}