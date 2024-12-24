<?php

namespace Project\Tests\Unit\Modules\Client\Commands;

use PHPUnit\Framework\TestCase;
use Project\Modules\Client\Entity\Client;
use Project\Modules\Client\Auth\AuthManagerInterface;
use Project\Modules\Client\Commands\LogoutClientCommand;
use Project\Modules\Client\Commands\Handlers\LogoutClientHandler;

class LogoutClientHandlerTest extends TestCase
{
    private readonly AuthManagerInterface $auth;
    private readonly Client $client;

    private readonly LogoutClientCommand $command;

    protected function setUp(): void
    {
        $this->auth = $this->getMockBuilder(AuthManagerInterface::class)->getMock();
        $this->client = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->command = new LogoutClientCommand;
    }

    public function testLogout()
    {
        $this->auth->expects($this->once())
            ->method('logged')
            ->willReturn($this->client);

        $this->auth->expects($this->once())->method('logout');
        $handler = new LogoutClientHandler($this->auth);
        call_user_func($handler, $this->command);
    }

    public function testLogoutIfClientDoesNotLoggedIn()
    {
        $this->auth->expects($this->once())
            ->method('logged')
            ->willReturn(null);

        $handler = new LogoutClientHandler($this->auth);
        $this->expectException(\DomainException::class);
        call_user_func($handler, $this->command);
    }
}