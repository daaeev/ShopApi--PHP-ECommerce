<?php

namespace Project\Tests\Laravel\Modules\Administrator\Auth;

use Project\Tests\Laravel\TestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Request;
use Illuminate\Contracts\Session\Session;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Contracts\Auth\Authenticatable;
use Project\Modules\Administrators\Entity\Admin;
use Project\Common\Repository\NotFoundException;
use Project\Modules\Administrators\Entity\AdminId;
use Project\Tests\Unit\Modules\Helpers\AdminFactory;
use Project\Modules\Administrators\AuthManager\AuthManagerInterface;
use Project\Modules\Administrators\Repository\AdminsRepositoryInterface;
use Project\Modules\Administrators\Infrastructure\Laravel\AuthManager\GuardAuthManager;

class GuardAuthManagerTest extends TestCase
{
    use AdminFactory;

    private readonly StatefulGuard $guard;
    private readonly Authenticatable $authenticatable;
    private readonly Session $session;
    private readonly AdminsRepositoryInterface $admins;
    private readonly Admin $admin;
    private readonly AdminId $adminId;

    private readonly AuthManagerInterface $authManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->guard = $this->getMockBuilder(StatefulGuard::class)->getMock();
        $this->authenticatable = $this->getMockBuilder(Authenticatable::class)->getMock();
        $this->session = $this->getMockBuilder(Session::class)->getMock();
        $this->admins = $this->getMockBuilder(AdminsRepositoryInterface::class)->getMock();
        $this->admin = $this->getMockBuilder(Admin::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->adminId = $this->getMockBuilder(AdminId::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->authManager = new GuardAuthManager($this->guard, $this->session, $this->admins);
    }

    public function testLogin()
    {
        $this->guard->expects($this->once())
            ->method('check')
            ->willReturn(false);

        $this->admins->expects($this->once())
            ->method('getByCredentials')
            ->with($this->correctAdminLogin, $this->correctAdminPassword)
            ->willReturn($this->admin);

        $this->admin->expects($this->once())
            ->method('getId')
            ->willReturn($this->adminId);

        $this->adminId->expects($this->once())
            ->method('getId')
            ->willReturn($adminId = random_int(1, 10));

        $this->guard->expects($this->once())
            ->method('loginUsingId')
            ->with($adminId)
            ->willReturn($this->authenticatable);

        $this->authManager->login($this->correctAdminLogin, $this->correctAdminPassword);
    }

    public function testLoginIfAdminAlreadyLogged()
    {
        $this->guard->expects($this->once())
            ->method('check')
            ->willReturn(true);

        $this->expectException(\DomainException::class);
        $this->authManager->login($this->correctAdminLogin, $this->correctAdminPassword);
    }

    public function testLoginIfCredentialsMismatch()
    {
        $this->guard->expects($this->once())
            ->method('check')
            ->willReturn(false);

        $this->admins->expects($this->once())
            ->method('getByCredentials')
            ->with($this->correctAdminLogin, $this->correctAdminPassword)
            ->willThrowException(new NotFoundException);

        $this->expectException(\DomainException::class);
        $this->authManager->login($this->correctAdminLogin, $this->correctAdminPassword);
    }

    public function testLoginIfLoginFailed()
    {
        $this->guard->expects($this->once())
            ->method('check')
            ->willReturn(false);

        $this->admins->expects($this->once())
            ->method('getByCredentials')
            ->with($this->correctAdminLogin, $this->correctAdminPassword)
            ->willReturn($this->admin);

        $this->admin->expects($this->once())
            ->method('getId')
            ->willReturn($this->adminId);

        $this->adminId->expects($this->once())
            ->method('getId')
            ->willReturn($adminId = random_int(1, 10));

        $this->guard->expects($this->once())
            ->method('loginUsingId')
            ->with($adminId)
            ->willReturn(false);

        $this->expectException(\DomainException::class);
        $this->authManager->login($this->correctAdminLogin, $this->correctAdminPassword);
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

    public function testLogoutIfAdminUnauthenticated()
    {
        $this->guard->expects($this->once())
            ->method('check')
            ->willReturn(false);

        $this->expectException(\DomainException::class);
        $this->authManager->logout();
    }

    public function testGetLoggedAdmin()
    {
        $this->guard->expects($this->once())
            ->method('check')
            ->willReturn(true);

        $this->guard->expects($this->once())
            ->method('id')
            ->willReturn($adminId = random_int(1, 10));

        $this->admins->expects($this->once())
            ->method('get')
            ->with(AdminId::make($adminId))
            ->willReturn($this->admin);

        $logged = $this->authManager->logged();
        $this->assertSame($logged, $this->admin);
    }

    public function testGetLoggedAdminIfUnauthenticated()
    {
        $this->guard->expects($this->once())
            ->method('check')
            ->willReturn(false);

        $this->assertNull($this->authManager->logged());
    }
}