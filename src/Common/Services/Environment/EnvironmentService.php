<?php

namespace Project\Common\Services\Environment;

use Illuminate\Support\Facades\App;
use Project\Modules\Client\Api\ClientsApi;
use Project\Modules\Administrators\Api\AdministratorsApi;
use Project\Common\Services\Cookie\CookieManagerInterface;

class EnvironmentService implements EnvironmentInterface
{
    public function __construct(
        private CookieManagerInterface $cookie,
        private AdministratorsApi $administrators,
        private ClientsApi $clients,
        private string $hashCookieName = 'clientHash',
    ) {}

    public function getClient(): Client
    {
        return new Client($this->getClientHashCookie(), $this->getAuthenticatedClientId());
    }

    private function getClientHashCookie(): string
    {
        if (empty($hash = $this->cookie->get($this->hashCookieName))) {
            throw new \DomainException('Client hash cookie does not instantiated');
        }

        return $hash;
    }

    private function getAuthenticatedClientId(): ?int
    {
        return $this->clients->getAuthenticated()?->id;
    }

    public function getAdministrator(): ?Administrator
    {
        if (empty($authenticated = $this->administrators->getAuthenticated())) {
            return null;
        }

        return new Administrator($authenticated->id, $authenticated->name, $authenticated->roles);
    }

    public function getLanguage(): Language
    {
        return Language::from(App::currentLocale());
    }
}