<?php

namespace Project\Tests\Unit\Services\Environment;

use Illuminate\Support\Facades\App;
use Project\Modules\Client\Api\ClientsApi;
use Project\Common\Services\Environment\Client;
use Project\Common\Services\Environment\Language;
use Project\Modules\Administrators\Api\DTO\Admin;
use Project\Common\Services\Environment\Environment;
use Project\Tests\Unit\Modules\Helpers\AdminFactory;
use Project\Tests\Unit\Modules\Helpers\ClientFactory;
use Project\Modules\Client\Api\DTO\Client as ClientDTO;
use Project\Common\Services\Environment\Administrator;
use Project\Modules\Administrators\Api\AdministratorsApi;
use Project\Common\Services\Cookie\CookieManagerInterface;
use Project\Common\Services\Environment\EnvironmentService;
use Project\Modules\Client\Utils\ClientEntity2DTOConverter;
use Project\Common\Services\Environment\EnvironmentInterface;
use Project\Common\Services\Configuration\ApplicationConfiguration;
use Project\Modules\Administrators\Utils\AdministratorEntity2DTOConverter;

class EnvironmentServiceTest extends \PHPUnit\Framework\TestCase
{
    use ClientFactory, AdminFactory;

    private readonly CookieManagerInterface $cookie;

    private readonly AdministratorsApi $administrators;
    private readonly Admin $adminDTO;

    private readonly ClientsApi $clients;
    private readonly ClientDTO $clientDTO;

    private readonly ApplicationConfiguration $configuration;
    private readonly string $hash;

    private readonly Environment $customEnvironment;
    private readonly EnvironmentInterface $environment;


    protected function setUp(): void
    {
        $this->cookie = $this->getMockBuilder(CookieManagerInterface::class)->getMock();
        $this->administrators = $this->getMockBuilder(AdministratorsApi::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->adminDTO = AdministratorEntity2DTOConverter::convert($this->generateAdmin());

        $this->clients = $this->getMockBuilder(ClientsApi::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->clientDTO = ClientEntity2DTOConverter::convert($this->generateClient());

        $this->customEnvironment = $this->getMockBuilder(Environment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configuration = $this->getMockBuilder(ApplicationConfiguration::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->hash = uniqid();
        $this->environment = new EnvironmentService(
            $this->cookie,
            $this->administrators,
            $this->clients,
            $this->configuration,
        );
    }

    public function testGetUnauthenticatedClient()
    {
        $this->configuration->expects($this->once())
            ->method('getClientHashCookieName')
            ->willReturn($hashCookieName = 'clientHash');

        $this->cookie->expects($this->once())
            ->method('get')
            ->with($hashCookieName)
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
        $this->configuration->expects($this->once())
            ->method('getClientHashCookieName')
            ->willReturn($hashCookieName = 'clientHash');

        $this->cookie->expects($this->once())
            ->method('get')
            ->with($hashCookieName)
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
        $this->configuration->expects($this->once())
            ->method('getClientHashCookieName')
            ->willReturn($hashCookieName = 'clientHash');

        $this->cookie->expects($this->once())
            ->method('get')
            ->with($hashCookieName)
            ->willReturn(null);

        $this->clients->expects($this->never())
            ->method('getAuthenticated');

        $this->expectException(\DomainException::class);
        $this->environment->getClient();
    }

    public function testGetClientWithCustomEnvironment()
    {
        $this->configuration->expects($this->never())
            ->method('getClientHashCookieName');

        $this->cookie->expects($this->never())
            ->method('get');

        $this->clients->expects($this->never())
            ->method('getAuthenticated');

        $expectedClient = new Client(hash: uniqid(), id: random_int(1, 10));
        $this->customEnvironment->expects($this->once())
            ->method('getClient')
            ->willReturn($expectedClient);

        $this->environment->useEnvironment($this->customEnvironment);
        $client = $this->environment->getClient();
        $this->assertSame($expectedClient, $client);
    }

    public function testGetAdministrator()
    {
        $this->administrators->expects($this->once())
            ->method('getAuthenticated')
            ->willReturn($this->adminDTO);

        $administrator = $this->environment->getAdministrator();
        $this->assertNotNull($administrator);
        $this->assertSame($administrator->getId(), $this->adminDTO->id);
        $this->assertSame($administrator->getName(), $this->adminDTO->name);
        $this->assertSame($administrator->getRoles(), $this->adminDTO->roles);
    }

    public function testGetAdministratorIfUnauthenticated()
    {
        $this->administrators->expects($this->once())
            ->method('getAuthenticated')
            ->willReturn(null);

        $administrator = $this->environment->getAdministrator();
        $this->assertNull($administrator);
    }

    public function testGetAdministratorWithCustomEnvironment()
    {
        $this->administrators->expects($this->never())
            ->method('getAuthenticated');

        $expectedAdmin = new Administrator($this->adminDTO->id, $this->adminDTO->name, $this->adminDTO->roles);
        $this->customEnvironment->expects($this->once())
            ->method('getAdministrator')
            ->willReturn($expectedAdmin);

        $this->environment->useEnvironment($this->customEnvironment);
        $administrator = $this->environment->getAdministrator();
        $this->assertSame($expectedAdmin, $administrator);
    }

    public function testGetLanguage()
    {
        App::shouldReceive('currentLocale')
            ->once()
            ->andReturn(Language::default()->value);

        $language = $this->environment->getLanguage();
        $this->assertSame(Language::default(), $language);
    }

    public function testGetLanguageWithCustomEnvironment()
    {
        App::shouldReceive('currentLocale')->never();

        $this->customEnvironment->expects($this->once())
            ->method('getLanguage')
            ->willReturn(Language::default());

        $this->environment->useEnvironment($this->customEnvironment);
        $language = $this->environment->getLanguage();
        $this->assertSame(Language::default(), $language);
    }

    public function testGetEnvironment()
    {
        // Client
        $this->configuration->expects($this->once())
            ->method('getClientHashCookieName')
            ->willReturn($hashCookieName = 'clientHash');

        $this->cookie->expects($this->once())
            ->method('get')
            ->with($hashCookieName)
            ->willReturn($this->hash);

        $this->clients->expects($this->once())
            ->method('getAuthenticated')
            ->willReturn($this->clientDTO);

        // Administrator
        $this->administrators->expects($this->once())
            ->method('getAuthenticated')
            ->willReturn($this->adminDTO);

        // Language
        App::shouldReceive('currentLocale')
            ->once()
            ->andReturn(Language::default()->value);

        $environment = $this->environment->getEnvironment();

        $this->assertSame($this->clientDTO->id, $environment->getClient()->getId());
        $this->assertSame($this->hash, $environment->getClient()->getHash());

        $this->assertNotNull($environment->getAdministrator());
        $this->assertSame($environment->getAdministrator()->getId(), $this->adminDTO->id);
        $this->assertSame($environment->getAdministrator()->getName(), $this->adminDTO->name);
        $this->assertSame($environment->getAdministrator()->getRoles(), $this->adminDTO->roles);

        $this->assertSame(Language::default(), $environment->getLanguage());
    }

    public function testGetCustomEnvironment()
    {
        // Client
        $this->configuration->expects($this->never())
            ->method('getClientHashCookieName');

        $this->cookie->expects($this->never())
            ->method('get');

        $this->clients->expects($this->never())
            ->method('getAuthenticated');

        // Administrator
        $this->administrators->expects($this->never())
            ->method('getAuthenticated');

        // Language
        App::shouldReceive('currentLocale')->never();

        $this->environment->useEnvironment($this->customEnvironment);
        $this->assertSame($this->customEnvironment, $this->environment->getEnvironment());
    }
}