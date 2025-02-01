<?php

namespace Project\Tests\Unit\Modules\Client\Commands;

use PHPUnit\Framework\TestCase;
use Project\Modules\Client\Entity\Client;
use Project\Modules\Client\Entity\Contacts;
use Project\Modules\Client\Auth\AuthManagerInterface;
use Project\Modules\Client\Entity\Access\PhoneAccess;
use Project\Tests\Unit\Modules\Helpers\ContactsGenerator;
use Project\Modules\Client\Api\Events\AbstractClientEvent;
use Project\Modules\Client\Commands\ConfirmClientPhoneCommand;
use Project\Modules\Client\Entity\Confirmation\ConfirmationUuid;
use Project\Modules\Client\Repository\ClientsRepositoryInterface;
use Project\Common\ApplicationMessages\Buses\MessageBusInterface;
use Project\Modules\Client\Commands\Handlers\ConfirmClientPhoneHandler;

class ConfirmClientPhoneHandlerTest extends TestCase
{
    use ContactsGenerator;

    private readonly ClientsRepositoryInterface $clients;
    private readonly AuthManagerInterface $auth;
    private readonly Client $client;
    private readonly Contacts $contacts;
    private readonly AbstractClientEvent $event;
    private readonly MessageBusInterface $eventBus;

    private readonly ConfirmationUuid $confirmationUuid;
    private readonly ConfirmClientPhoneCommand $command;

    protected function setUp(): void
    {
        $this->clients = $this->getMockBuilder(ClientsRepositoryInterface::class)->getMock();
        $this->auth = $this->getMockBuilder(AuthManagerInterface::class)->getMock();
        $this->client = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->contacts = $this->getMockBuilder(Contacts::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->event = $this->getMockBuilder(AbstractClientEvent::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->eventBus = $this->getMockBuilder(MessageBusInterface::class)->getMock();

        $this->confirmationUuid = ConfirmationUuid::random();
        $this->command = new ConfirmClientPhoneCommand(
            confirmationUuid: $this->confirmationUuid->getId(),
            inputCode: random_int(1, 10)
        );
    }

    public function testConfirmClientPhone()
    {
        $this->auth->expects($this->once())
            ->method('logged')
            ->willReturn(null);

        $this->clients->expects($this->once())
            ->method('getByConfirmation')
            ->with($this->confirmationUuid)
            ->willReturn($this->client);

        $this->client->expects($this->once())
            ->method('applyConfirmation')
            ->with($this->confirmationUuid, $this->command->inputCode);

        $phone = $this->generatePhone();
        $this->client->expects($this->exactly(2))
            ->method('getContacts')
            ->willReturn($this->contacts);

        $this->contacts->expects($this->once())
            ->method('getPhone')
            ->willReturn($phone);

        $access = new PhoneAccess($phone);
        $this->client->expects($this->once())
            ->method('hasAccess')
            ->with($this->callback(function (PhoneAccess $commandAccess) use ($access) {
                return $commandAccess->equalsTo($access);
            }))
            ->willReturn(false);

        $this->client->expects($this->once())
            ->method('addAccess')
            ->with($this->callback(function (PhoneAccess $commandAccess) use ($access) {
                return $commandAccess->equalsTo($access);
            }));

        $this->contacts->expects($this->once())
            ->method('isPhoneConfirmed')
            ->willReturn(false);

        $this->client->expects($this->once())
            ->method('confirmPhone');

        $this->clients->expects($this->once())
            ->method('update')
            ->with($this->client);

        $this->auth->expects($this->once())
            ->method('authorize')
            ->with($this->callback(function (PhoneAccess $commandAccess) use ($access) {
                return $commandAccess->equalsTo($access);
            }));

        $this->client->expects($this->once())
            ->method('flushEvents')
            ->willReturn([$this->event]);

        $this->eventBus->expects($this->once())
            ->method('dispatch')
            ->with($this->event);

        $handler = new ConfirmClientPhoneHandler($this->clients, $this->auth);
        $handler->setDispatcher($this->eventBus);
        call_user_func($handler, $this->command);
    }

    public function testConfirmClientPhoneIfClientAlreadyAuthorized()
    {
        $this->auth->expects($this->once())
            ->method('logged')
            ->willReturn($this->client);

        $handler = new ConfirmClientPhoneHandler($this->clients, $this->auth);
        $handler->setDispatcher($this->eventBus);

        $this->expectException(\DomainException::class);
        call_user_func($handler, $this->command);
    }

    public function testConfirmClientPhoneIfClientAlreadyHasSameAccess()
    {
        $this->auth->expects($this->once())
            ->method('logged')
            ->willReturn(null);

        $this->clients->expects($this->once())
            ->method('getByConfirmation')
            ->with($this->confirmationUuid)
            ->willReturn($this->client);

        $this->client->expects($this->once())
            ->method('applyConfirmation')
            ->with($this->confirmationUuid, $this->command->inputCode);

        $phone = $this->generatePhone();
        $this->client->expects($this->exactly(2))
            ->method('getContacts')
            ->willReturn($this->contacts);

        $this->contacts->expects($this->once())
            ->method('getPhone')
            ->willReturn($phone);

        $access = new PhoneAccess($phone);
        $this->client->expects($this->once())
            ->method('hasAccess')
            ->with($this->callback(function (PhoneAccess $commandAccess) use ($access) {
                return $commandAccess->equalsTo($access);
            }))
            ->willReturn(true);

        $this->contacts->expects($this->once())
            ->method('isPhoneConfirmed')
            ->willReturn(false);

        $this->client->expects($this->once())
            ->method('confirmPhone');

        $this->client->expects($this->never())->method('addAccess');

        $this->clients->expects($this->once())
            ->method('update')
            ->with($this->client);

        $this->auth->expects($this->once())
            ->method('authorize')
            ->with($this->callback(function (PhoneAccess $commandAccess) use ($access) {
                return $commandAccess->equalsTo($access);
            }));

        $this->client->expects($this->once())
            ->method('flushEvents')
            ->willReturn([$this->event]);

        $this->eventBus->expects($this->once())
            ->method('dispatch')
            ->with($this->event);

        $handler = new ConfirmClientPhoneHandler($this->clients, $this->auth);
        $handler->setDispatcher($this->eventBus);
        call_user_func($handler, $this->command);
    }

    public function testConfirmClientPhoneIfPhoneAlreadyConfirmed()
    {
        $this->auth->expects($this->once())
            ->method('logged')
            ->willReturn(null);

        $this->clients->expects($this->once())
            ->method('getByConfirmation')
            ->with($this->confirmationUuid)
            ->willReturn($this->client);

        $this->client->expects($this->once())
            ->method('applyConfirmation')
            ->with($this->confirmationUuid, $this->command->inputCode);

        $phone = $this->generatePhone();
        $this->client->expects($this->exactly(2))
            ->method('getContacts')
            ->willReturn($this->contacts);

        $this->contacts->expects($this->once())
            ->method('getPhone')
            ->willReturn($phone);

        $access = new PhoneAccess($phone);
        $this->client->expects($this->once())
            ->method('hasAccess')
            ->with($this->callback(function (PhoneAccess $commandAccess) use ($access) {
                return $commandAccess->equalsTo($access);
            }))
            ->willReturn(false);

        $this->client->expects($this->once())
            ->method('addAccess')
            ->with($this->callback(function (PhoneAccess $commandAccess) use ($access) {
                return $commandAccess->equalsTo($access);
            }));

        $this->contacts->expects($this->once())
            ->method('isPhoneConfirmed')
            ->willReturn(true);

        $this->client->expects($this->never())
            ->method('confirmPhone');

        $this->clients->expects($this->once())
            ->method('update')
            ->with($this->client);

        $this->auth->expects($this->once())
            ->method('authorize')
            ->with($this->callback(function (PhoneAccess $commandAccess) use ($access) {
                return $commandAccess->equalsTo($access);
            }));

        $this->client->expects($this->once())
            ->method('flushEvents')
            ->willReturn([$this->event]);

        $this->eventBus->expects($this->once())
            ->method('dispatch')
            ->with($this->event);

        $handler = new ConfirmClientPhoneHandler($this->clients, $this->auth);
        $handler->setDispatcher($this->eventBus);
        call_user_func($handler, $this->command);
    }
}