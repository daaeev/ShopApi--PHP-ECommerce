<?php

namespace Project\Common\Services\Environment;

use Illuminate\Support\Facades\App;
use Project\Modules\Client\Api\ClientsApi;
use Project\Modules\Administrators\Api\AdministratorsApi;
use Project\Common\Services\Cookie\CookieManagerInterface;
use Project\Common\Services\Configuration\ApplicationConfiguration;

class EnvironmentService implements EnvironmentInterface
{
    private ?Environment $customEnvironment = null;

    public function __construct(
        private CookieManagerInterface $cookie,
        private AdministratorsApi $administrators,
        private ClientsApi $clients,
        private ApplicationConfiguration $configuration,
    ) {}

    public function getClient(): Client
    {
        if (isset($this->customEnvironment)) {
            return $this->customEnvironment->getClient();
        }

        return new Client($this->getClientHashCookie(), $this->getAuthenticatedClientId());
    }

    private function getClientHashCookie(): string
    {
        if (empty($hash = $this->cookie->get($this->configuration->getClientHashCookieName()))) {
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
        if (null !== $this->customEnvironment) {
            return $this->customEnvironment->getAdministrator();
        }

        $authenticated = $this->administrators->getAuthenticated();
        if (null === $authenticated) {
            return null;
        }

        return new Administrator($authenticated->id, $authenticated->name, $authenticated->roles);
    }

    public function getLanguage(): Language
    {
        if (isset($this->customEnvironment)) {
            return $this->customEnvironment->getLanguage();
        }

        return Language::from(App::currentLocale());
    }

    public function getEnvironment(): Environment
    {
        if (isset($this->customEnvironment)) {
            return $this->customEnvironment;
        }

        return new Environment(
            client: $this->getClient(),
            admin: $this->getAdministrator(),
            language: $this->getLanguage()
        );
    }

    public function useEnvironment(Environment $environment): void
    {
        $this->customEnvironment = $environment;
    }
}