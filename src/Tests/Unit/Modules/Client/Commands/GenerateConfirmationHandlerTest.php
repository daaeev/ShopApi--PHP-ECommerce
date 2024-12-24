<?php

namespace Project\Tests\Unit\Modules\Client\Commands;

use PHPUnit\Framework\TestCase;
use Project\Modules\Client\Entity\Client;
use Project\Modules\Client\Entity\Contacts;
use Project\Modules\Client\Auth\AuthManagerInterface;
use Project\Tests\Unit\Modules\Helpers\ContactsGenerator;
use Project\Modules\Client\Api\Events\AbstractClientEvent;
use Project\Modules\Client\Entity\Confirmation\ConfirmationUuid;
use Project\Modules\Client\Commands\GenerateConfirmationCommand;
use Project\Modules\Client\Repository\ClientsRepositoryInterface;
use Project\Common\ApplicationMessages\Buses\MessageBusInterface;
use Project\Modules\Client\Entity\Confirmation\CodeGeneratorInterface;
use Project\Modules\Client\Commands\Handlers\GenerateConfirmationHandler;

class GenerateConfirmationHandlerTest extends TestCase
{
    use ContactsGenerator;

    private readonly ClientsRepositoryInterface $clients;
    private readonly AuthManagerInterface $auth;
    private readonly CodeGeneratorInterface $codeGenerator;
    private readonly Client $client;
    private readonly ConfirmationUuid $confirmationUuid;
    private readonly AbstractClientEvent $event;
    private readonly MessageBusInterface $eventBus;

    private readonly GenerateConfirmationCommand $command;

    protected function setUp(): void
    {
        $this->clients = $this->getMockBuilder(ClientsRepositoryInterface::class)->getMock();
        $this->auth = $this->getMockBuilder(AuthManagerInterface::class)->getMock();
        $this->codeGenerator = $this->getMockBuilder(CodeGeneratorInterface::class)->getMock();
        $this->client = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->confirmationUuid = ConfirmationUuid::random();
        $this->contacts = $this->getMockBuilder(Contacts::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->event = $this->getMockBuilder(AbstractClientEvent::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->eventBus = $this->getMockBuilder(MessageBusInterface::class)->getMock();
        $this->command = new GenerateConfirmationCommand($this->generatePhone());
    }

    public function testGenerateClientConfirmation()
    {
        $this->auth->expects($this->once())
            ->method('logged')
            ->willReturn(null);

        $this->clients->expects($this->once())
            ->method('getByPhone')
            ->with($this->command->phone)
            ->willReturn($this->client);

        $this->client->expects($this->once())
            ->method('generateConfirmation')
            ->with($this->codeGenerator)
            ->willReturn($this->confirmationUuid);

        $this->clients->expects($this->once())
            ->method('update')
            ->with($this->client);

        $this->client->expects($this->once())
            ->method('flushEvents')
            ->willReturn([$this->event]);

        $this->eventBus->expects($this->once())
            ->method('dispatch')
            ->with($this->event);

        $handler = new GenerateConfirmationHandler($this->auth, $this->clients, $this->codeGenerator);
        $handler->setDispatcher($this->eventBus);
        $uuid = call_user_func($handler, $this->command);
        $this->assertSame($this->confirmationUuid->getId(), $uuid);
    }

    public function testGenerateClientConfirmationIfClientAlreadyAuthorized()
    {
        $this->auth->expects($this->once())
            ->method('logged')
            ->willReturn($this->client);

        $handler = new GenerateConfirmationHandler($this->auth, $this->clients, $this->codeGenerator);
        $handler->setDispatcher($this->eventBus);

        $this->expectException(\DomainException::class);
        call_user_func($handler, $this->command);
    }
}