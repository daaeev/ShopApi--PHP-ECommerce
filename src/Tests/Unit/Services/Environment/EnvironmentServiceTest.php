<?php

namespace Project\Tests\Unit\Services\Environment;

use Illuminate\Support\Facades\App;
use Project\Common\Administrators\Role;
use Project\Modules\Client\Api\ClientsApi;
use Project\Common\Services\Environment\Language;
use Project\Modules\Administrators\Api\DTO\Admin;
use Project\Tests\Unit\Modules\Helpers\ClientFactory;
use Project\Modules\Client\Api\DTO\Client as ClientDTO;
use Project\Modules\Administrators\Api\AdministratorsApi;
use Project\Common\Services\Cookie\CookieManagerInterface;
use Project\Common\Services\Environment\EnvironmentService;
use Project\Modules\Client\Utils\ClientEntity2DTOConverter;
use Project\Common\Services\Environment\EnvironmentInterface;

class EnvironmentServiceTest extends \PHPUnit\Framework\TestCase
{
    use ClientFactory;

    private readonly CookieManagerInterface $cookie;
    private readonly AdministratorsApi $administrators;
    private readonly ClientsApi $clients;
    private readonly ClientDTO $clientDTO;
    private readonly EnvironmentInterface $environment;

    private readonly string $hashCookieName;
    private readonly string $hash;

    protected function setUp(): void
    {
        $this->cookie = $this->getMockBuilder(CookieManagerInterface::class)->getMock();
        $this->administrators = $this->getMockBuilder(AdministratorsApi::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->clients = $this->getMockBuilder(ClientsApi::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->clientDTO = ClientEntity2DTOConverter::convert($this->generateClient());

        $this->hashCookieName = uniqid();
        $this->hash = uniqid();
        $this->environment = new EnvironmentService(
            $this->cookie,
            $this->administrators,
            $this->clients,
            $this->hashCookieName
        );
    }

    public function testGetUnauthenticatedClient()
    {
        $this->cookie->expects($this->once())
            ->method('get')
            ->with($this->hashCookieName)
            ->willReturn($this->hash);

        $this->clients->expects($this->once())
            ->method('getAuthenticated')
            ->willReturn(null);

        $client = $this->environment->getClient();
        $this->assertNull($client->getId());
        $this->assertSame($this->hash, $client->getHash());
    }

    public function testGetAuthenticatedClient()
    {
        $this->cookie->expects($this->once())
            ->method('get')
            ->with($this->hashCookieName)
            ->willReturn($this->hash);

        $this->clients->expects($this->once())
            ->method('getAuthenticated')
            ->willReturn($this->clientDTO);

        $client = $this->environment->getClient();
        $this->assertSame($this->clientDTO->id, $client->getId());
        $this->assertSame($this->hash, $client->getHash());
    }

    public function testGetClientIfHashCookieDoesNotExists()
    {
        $this->cookie->expects($this->once())
            ->method('get')
            ->with($this->hashCookieName)
            ->willReturn(null);

        $this->clients->expects($this->never())
            ->method('getAuthenticated');

        $this->expectException(\DomainException::class);
        $this->environment->getClient();
    }

    public function testGetAdministrator()
    {
        $authenticated = new Admin(
            id: random_int(1, 9999),
            name: uniqid(),
            login: uniqid(),
            roles: [Role::ADMIN]
        );

        $this->administrators->expects($this->once())
            ->method('getAuthenticated')
            ->willReturn($authenticated);

        $administrator = $this->environment->getAdministrator();
        $this->assertNotNull($administrator);
        $this->assertSame($administrator->getId(), $authenticated->id);
        $this->assertSame($administrator->getName(), $authenticated->name);
        $this->assertSame($administrator->getRoles(), $authenticated->roles);
    }

    public function testGetAdministratorIfUnauthenticated()
    {
        $this->administrators->expects($this->once())
            ->method('getAuthenticated')
            ->willReturn(null);

        $administrator = $this->environment->getAdministrator();
        $this->assertNull($administrator);
    }

    public function testGetLanguage()
    {
        App::shouldReceive('currentLocale')
            ->once()
            ->andReturn(Language::default()->value);

        $language = $this->environment->getLanguage();
        $this->assertSame(Language::default(), $language);
    }
}