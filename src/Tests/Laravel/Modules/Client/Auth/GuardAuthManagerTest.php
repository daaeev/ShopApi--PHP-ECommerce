<?php

namespace Laravel\Modules\Client\Auth;

use Project\Tests\Laravel\TestCase;
use Project\Modules\Client\Entity\Client;
use Illuminate\Contracts\Session\Session;
use Project\Modules\Client\Entity\ClientId;
use Illuminate\Contracts\Auth\StatefulGuard;
use Project\Common\Repository\NotFoundException;
use Project\Modules\Client\Entity\Access\PhoneAccess;
use Project\Tests\Unit\Modules\Helpers\ContactsGenerator;
use Project\Modules\Client\Repository\ClientsRepositoryInterface;
use Project\Modules\Client\Infrastructure\Laravel\Auth\GuardAuthManager;

class GuardAuthManagerTest extends TestCase
{
    use ContactsGenerator;

    private readonly StatefulGuard $guard;
    private readonly Session $session;
    private readonly ClientsRepositoryInterface $clients;
    private readonly Client $client;
    private readonly ClientId $clientId;
    private readonly GuardAuthManager $authManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->guard = $this->getMockBuilder(StatefulGuard::class)->getMock();
        $this->session = $this->getMockBuilder(Session::class)->getMock();
        $this->clients = $this->getMockBuilder(ClientsRepositoryInterface::class)->getMock();
        $this->client = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->clientId = $this->getMockBuilder(ClientId::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->authManager = new GuardAuthManager($this->guard, $this->session, $this->clients);
    }

    public function testAuthorize()
    {
        $this->guard->expects($this->once())
            ->method('check')
            ->willReturn(false);

        $access = new PhoneAccess($this->generatePhone());
        $this->clients->expects($this->once())
            ->method('getByAccess')
            ->with($access)
            ->willReturn($this->client);

        $this->client->expects($this->once())
            ->method('getId')
            ->willReturn($this->clientId);

        $this->clientId->expects($this->once())
            ->method('getId')
            ->willReturn($clientId = random_int(1, 10));

        $this->guard->expects($this->once())
            ->method('loginUsingId')
            ->with($clientId);

        $this->authManager->authorize($access);
    }

    public function testAuthorizeIfClientAlreadyAuthorized()
    {
        $this->guard->expects($this->once())
            ->method('check')
            ->willReturn(true);

        $access = new PhoneAccess($this->generatePhone());
        $this->expectException(\DomainException::class);
        $this->authManager->authorize($access);
    }

    public function testAuthorizeIfClientByAccessDoesNotExists()
    {
        $this->guard->expects($this->once())
            ->method('check')
            ->willReturn(false);

        $access = new PhoneAccess($this->generatePhone());
        $this->clients->expects($this->once())
            ->method('getByAccess')
            ->with($access)
            ->willThrowException(new NotFoundException);

        $this->expectException(NotFoundException::class);
        $this->authManager->authorize($access);
    }

    public function testLogout()
    {
        $this->guard->expects($this->once())
            ->method('check')
            ->willReturn(true);

        $this->guard->expects($this->once())
            ->method('logout');

        $this->session->expects($this->once())
            ->method('invalidate');

        $this->session->expects($this->once())
            ->method('regenerateToken');

        $this->authManager->logout();
    }

    public function testLogoutIfClientDoesNotAuthorized()
    {
        $this->guard->expects($this->once())
            ->method('check')
            ->willReturn(false);

        $this->expectException(\DomainException::class);
        $this->authManager->logout();
    }

    public function testGetLoggedClient()
    {
        $this->guard->expects($this->once())
            ->method('check')
            ->willReturn(true);

        $this->guard->expects($this->once())
            ->method('id')
            ->willReturn($clientId = random_int(1, 10));

        $this->clients->expects($this->once())
            ->method('get')
            ->with(ClientId::make($clientId))
            ->willReturn($this->client);

        $logged = $this->authManager->logged();
        $this->assertSame($logged, $this->client);
    }

    public function testGetLoggedClientIfClientDoesNotAuthorized()
    {
        $this->guard->expects($this->once())
            ->method('check')
            ->willReturn(false);

        $logged = $this->authManager->logged();
        $this->assertNull($logged);
    }
}