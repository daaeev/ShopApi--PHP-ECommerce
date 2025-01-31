<?php

namespace Project\Modules\Client\Infrastructure\Laravel\Auth;

use Project\Modules\Client\Entity\Client;
use Illuminate\Contracts\Session\Session;
use Project\Modules\Client\Entity\ClientId;
use Illuminate\Contracts\Auth\StatefulGuard;
use Project\Modules\Client\Entity\Access\Access;
use Project\Modules\Client\Auth\AuthManagerInterface;
use Project\Modules\Client\Repository\ClientsRepositoryInterface;

class GuardAuthManager implements AuthManagerInterface
{
    public function __construct(
        private readonly StatefulGuard $guard,
        private readonly Session $session,
        private readonly ClientsRepositoryInterface $clients,
    ) {}

    public function authorize(Access $access): void
    {
        if ($this->guard->check()) {
            throw new \DomainException('Client already authenticated');
        }

        $client = $this->clients->getByAccess($access);
        $this->guard->loginUsingId($client->getId()->getId());
    }

    public function logout(): void
    {
        if (false === $this->guard->check()) {
            throw new \DomainException('Client does not authenticated');
        }

        $this->guard->logout();
        $this->session->invalidate();
        $this->session->regenerateToken();
    }

    public function logged(): ?Client
    {
        if (false === $this->guard->check()) {
            return null;
        }

        return $this->clients->get(ClientId::make($this->guard->id()));
    }
}