<?php

namespace Project\Infrastructure\Laravel\Middleware;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Project\Common\Services\Cookie\CookieManagerInterface;
use Project\Common\Services\Configuration\ApplicationConfiguration;

class AssignClientHashCookie
{
    public function __construct(
        private CookieManagerInterface $cookie,
        private ApplicationConfiguration $configuration,
    ) {}

    public function handle(Request $request, \Closure $next): Response
    {
        if (!$this->hashAssigned() || !$this->hashIsValid()) {
            $this->cookie->add(
                $this->configuration->getClientHashCookieName(),
                $this->generateHash(),
                $this->configuration->getClientHashCookieLifeTimeInMinutes(),
            );
        }

        return $next($request);
    }

    private function hashAssigned(): bool
    {
        return !empty($this->cookie->get($this->configuration->getClientHashCookieName()));
    }

    private function hashIsValid(): bool
    {
        $hash = $this->cookie->get($this->configuration->getClientHashCookieName());
        return is_string($hash) && (mb_strlen($hash) === $this->configuration->getClientHashCookieLength());
    }

    private function generateHash(): string
    {
        return Str::random($this->configuration->getClientHashCookieLength());
    }
}
