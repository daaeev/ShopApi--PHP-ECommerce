<?php

namespace Project\Tests\Unit\Modules\Client\Services;

use PHPUnit\Framework\TestCase;
use Project\Modules\Client\Entity;
use Project\Modules\Client\Api\DTO;
use Project\Modules\Client\Api\ClientsApi;
use Project\Common\Entity\Hydrator\Hydrator;
use Project\Common\Repository\NotFoundException;
use Project\Modules\Client\Auth\AuthManagerInterface;
use Project\Tests\Unit\Modules\Helpers\ClientFactory;
use Project\Modules\Client\Repository\ClientsRepositoryInterface;
use Project\Common\ApplicationMessages\Buses\MessageBusInterface;
use Project\Modules\Client\Repository\QueryClientsRepositoryInterface;

class ClientsApiTest extends TestCase
{
    use ClientFactory;

    private readonly ClientsRepositoryInterface $clients;
    private readonly QueryClientsRepositoryInterface $queryClients;
    private readonly AuthManagerInterface $authManager;
    private readonly MessageBusInterface $eventBus;
    private readonly ClientsApi $api;

    private readonly Hydrator $hydrator;
    private readonly int $clientId;
    private readonly DTO\Client $clientDTO;
    private readonly Entity\Client $clientEntity;

    protected function setUp(): void
    {
        $this->clients = $this->getMockBuilder(ClientsRepositoryInterface::class)->getMock();
        $this->queryClients = $this->getMockBuilder(QueryClientsRepositoryInterface::class)->getMock();
        $this->authManager = $this->getMockBuilder(AuthManagerInterface::class)->getMock();
        $this->eventBus = $this->getMockBuilder(MessageBusInterface::class)->getMock();
        $this->api = new ClientsApi($this->clients, $this->queryClients, $this->authManager);
        $this->api->setDispatcher($this->eventBus);

        $this->hydrator = new Hydrator;
        $this->clientId = rand();
        $this->clientDTO = $this->getMockBuilder(DTO\Client::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->clientEntity = $this->generateClient();
    }

    public function testGet()
    {
        $this->queryClients->expects($this->once())
            ->method('get')
            ->with($id = random_int(1, 10))
            ->willReturn($this->clientDTO);

        $client = $this->api->get($id);
        $this->assertSame($client, $this->clientDTO);
    }

    public function testGetIfDoesNotExists()
    {
        $this->queryClients->expects($this->once())
            ->method('get')
            ->with($id = random_int(1, 10))
            ->willThrowException(new NotFoundException);

        $this->expectException(NotFoundException::class);
        $this->api->get($id);
    }

    public function testGetByPhone()
    {
        $this->queryClients->expects($this->once())
            ->method('getByPhone')
            ->with($phone = $this->generatePhone())
            ->willReturn($this->clientDTO);

        $client = $this->api->getByPhone($phone);
        $this->assertSame($client, $this->clientDTO);
    }

    public function testGetByPhoneIfDoesNotExists()
    {
        $this->queryClients->expects($this->once())
            ->method('getByPhone')
            ->willThrowException(new NotFoundException);

        $client = $this->api->getByPhone($this->generatePhone());
        $this->assertNull($client);
    }

    public function testGetAuthenticated()
    {
        $this->authManager->expects($this->once())
            ->method('logged')
            ->willReturn($this->clientEntity);

        $client = $this->api->getAuthenticated();
        $this->assertSame($client->id, $this->clientEntity->getId()->getId());
        $this->assertSame($client->firstName, $this->clientEntity->getName()->getFirstName());
        $this->assertSame($client->lastName, $this->clientEntity->getName()->getLastName());
        $this->assertSame($client->phone, $this->clientEntity->getContacts()->getPhone());
        $this->assertSame($client->email, $this->clientEntity->getContacts()->getEmail());
        $this->assertSame($client->phoneConfirmed, $this->clientEntity->getContacts()->isPhoneConfirmed());
        $this->assertSame($client->emailConfirmed, $this->clientEntity->getContacts()->isEmailConfirmed());
        $this->assertSame($client->createdAt, $this->clientEntity->getCreatedAt());
        $this->assertSame($client->updatedAt, $this->clientEntity->getUpdatedAt());
    }

    public function testGetAuthenticatedIfUnauthenticated()
    {
        $this->authManager->expects($this->once())
            ->method('logged')
            ->willReturn(null);

        $client = $this->api->getAuthenticated();
        $this->assertNull($client);
    }

    public function testCreate()
    {
        $firstName = uniqid();
        $lastName = uniqid();
        $phone = $this->generatePhone();
        $email = $this->generateEmail();

        $this->clients->expects($this->once())
            ->method('add')
            ->with($this->callback(function (Entity\Client $client) use ($firstName, $lastName, $phone, $email) {
                $this->assertSame($firstName, $client->getName()->getFirstName());
                $this->assertSame($lastName, $client->getName()->getLastName());
                $this->assertSame($phone, $client->getContacts()->getPhone());
                $this->assertSame($email, $client->getContacts()->getEmail());
                $this->hydrator->hydrate($client->getId(), ['id' => $this->clientId]);
                return true;
            }));

        $this->eventBus->expects($this->exactly(2))->method('dispatch');

        $client = $this->api->create($firstName, $lastName, $phone, $email);
        $this->assertSame($this->clientId, $client->id);
        $this->assertSame($firstName, $client->firstName);
        $this->assertSame($lastName, $client->lastName);
        $this->assertSame($phone, $client->phone);
        $this->assertSame($email, $client->email);
    }
}