<?php

namespace Project\Tests\Unit\Modules\Client\Commands;

use PHPUnit\Framework\TestCase;
use Project\Modules\Client\Entity\Client;
use Project\Modules\Client\Auth\AuthManagerInterface;
use Project\Modules\Client\Api\Events\AbstractClientEvent;
use Project\Modules\Client\Commands\RefreshConfirmationCommand;
use Project\Modules\Client\Entity\Confirmation\ConfirmationUuid;
use Project\Modules\Client\Repository\ClientsRepositoryInterface;
use Project\Common\ApplicationMessages\Buses\MessageBusInterface;
use Project\Modules\Client\Commands\Handlers\RefreshConfirmationHandler;

class RefreshConfirmationHandlerTest extends TestCase
{
    private readonly ClientsRepositoryInterface $clients;
    private readonly AuthManagerInterface $auth;
    private readonly Client $client;
    private readonly ConfirmationUuid $confirmationUuid;
    private readonly AbstractClientEvent $event;
    private readonly MessageBusInterface $eventBus;

    private readonly RefreshConfirmationCommand $command;

    protected function setUp(): void
    {
        $this->clients = $this->getMockBuilder(ClientsRepositoryInterface::class)->getMock();
        $this->auth = $this->getMockBuilder(AuthManagerInterface::class)->getMock();
        $this->client = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->confirmationUuid = ConfirmationUuid::random();
        $this->event = $this->getMockBuilder(AbstractClientEvent::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->eventBus = $this->getMockBuilder(MessageBusInterface::class)->getMock();
        $this->command = new RefreshConfirmationCommand($this->confirmationUuid->getId());
    }

    public function testRefreshClientConfirmation()
    {
        $this->auth->expects($this->once())
            ->method('logged')
            ->willReturn(null);

        $this->clients->expects($this->once())
            ->method('getByConfirmation')
            ->with($this->confirmationUuid)
            ->willReturn($this->client);

        $this->client->expects($this->once())
            ->method('refreshConfirmationExpiredAt')
            ->with($this->confirmationUuid);

        $this->clients->expects($this->once())
            ->method('update')
            ->with($this->client);

        $this->client->expects($this->once())
            ->method('flushEvents')
            ->willReturn([$this->event]);

        $this->eventBus->expects($this->once())
            ->method('dispatch')
            ->with($this->event);

        $handler = new RefreshConfirmationHandler($this->auth, $this->clients);
        $handler->setDispatcher($this->eventBus);
        call_user_func($handler, $this->command);
    }

    public function testRefreshClientConfirmationIfClientAlreadyAuthorized()
    {
        $this->auth->expects($this->once())
            ->method('logged')
            ->willReturn($this->client);

        $handler = new RefreshConfirmationHandler($this->auth, $this->clients);
        $handler->setDispatcher($this->eventBus);

        $this->expectException(\DomainException::class);
        call_user_func($handler, $this->command);
    }
}