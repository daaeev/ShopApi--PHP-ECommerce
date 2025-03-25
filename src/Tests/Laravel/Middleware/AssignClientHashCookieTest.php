<?php

namespace Project\Tests\Laravel\Middleware;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Project\Common\Services\Cookie\CookieManagerInterface;
use Project\Common\Services\Configuration\ApplicationConfiguration;
use Project\Infrastructure\Laravel\Middleware\AssignClientHashCookie;

class AssignClientHashCookieTest extends \PHPUnit\Framework\TestCase
{
    private readonly CookieManagerInterface $cookie;
    private readonly ApplicationConfiguration $configuration;
    private readonly AssignClientHashCookie $middleware;

    private readonly Request $request;
    private readonly Response $response;
    private readonly \Closure $next;

    protected function setUp(): void
    {
        $this->cookie = $this->getMockBuilder(CookieManagerInterface::class)->getMock();
        $this->configuration = $this->getMockBuilder(ApplicationConfiguration::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->middleware = new AssignClientHashCookie($this->cookie, $this->configuration);
        $this->request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->response = $this->getMockBuilder(Response::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->next = fn (Request $request) => $this->response;
    }

    public function testAssignClientHash()
    {
        $this->configuration->expects($this->exactly(2))
            ->method('getClientHashCookieName')
            ->willReturn($cookieName = 'clientHash');

        $this->cookie->expects($this->once())
            ->method('get')
            ->with($cookieName)
            ->willReturn(null);

        $this->configuration->expects($this->once())
            ->method('getClientHashCookieLength')
            ->willReturn($hashLength = random_int(10, 20));

        $this->configuration->expects($this->once())
            ->method('getClientHashCookieLifeTimeInMinutes')
            ->willReturn($cookieLifeTimeInMinutes = random_int(10, 20));

        $this->cookie->expects($this->once())
            ->method('add')
            ->with(
                $cookieName,
                $this->callback(fn (string $hash) => $hashLength === mb_strlen($hash)),
                $cookieLifeTimeInMinutes,
            );

        $response = $this->middleware->handle($this->request, $this->next);
        $this->assertSame($response, $this->response);
    }

    public function testAssignClientHashIfCookieAlreadyExists()
    {
        $this->configuration->expects($this->exactly(2))
            ->method('getClientHashCookieName')
            ->willReturn($cookieName = 'clientHash');

        $this->configuration->expects($this->once())
            ->method('getClientHashCookieLength')
            ->willReturn($hashLength = random_int(10, 20));

        $this->cookie->expects($this->exactly(2))
            ->method('get')
            ->with($cookieName)
            ->willReturn(Str::random($hashLength));

        $response = $this->middleware->handle($this->request, $this->next);
        $this->assertSame($response, $this->response);
    }

    public function testAssignClientHashIfCurrentHashNotValid()
    {
        $this->configuration->expects($this->exactly(3))
            ->method('getClientHashCookieName')
            ->willReturn($cookieName = 'clientHash');

        $this->configuration->expects($this->exactly(2))
            ->method('getClientHashCookieLength')
            ->willReturn($hashLength = random_int(10, 20));

        $this->cookie->expects($this->exactly(2))
            ->method('get')
            ->with($cookieName)
            ->willReturn(Str::random($hashLength - 1));

        $this->configuration->expects($this->once())
            ->method('getClientHashCookieLifeTimeInMinutes')
            ->willReturn($cookieLifeTimeInMinutes = random_int(10, 20));

        $this->cookie->expects($this->once())
            ->method('add')
            ->with(
                $cookieName,
                $this->callback(fn (string $generatedHash) => mb_strlen($generatedHash) === $hashLength),
                $cookieLifeTimeInMinutes,
            );

        $response = $this->middleware->handle($this->request, $this->next);
        $this->assertSame($response, $this->response);
    }
}