<?php

namespace Project\Modules\Administrators\Infrastructure\Laravel\AuthManager;

use Illuminate\Contracts\Session\Session;
use Project\Modules\Administrators\Entity;
use Illuminate\Contracts\Auth\StatefulGuard;
use Project\Common\Repository\NotFoundException;
use Project\Modules\Administrators\AuthManager\AuthManagerInterface;
use Project\Modules\Administrators\Repository\AdminsRepositoryInterface;

class GuardAuthManager implements AuthManagerInterface
{
    public function __construct(
        private readonly StatefulGuard $guard,
        private readonly Session $session,
        private readonly AdminsRepositoryInterface $admins,
    ) {}

    public function login(string $login, string $password): void
    {
        if ($this->guard->check()) {
            throw new \DomainException('You already authorized');
        }

        try {
            $admin = $this->admins->getByCredentials($login, $password);
        } catch (NotFoundException) {
            throw new \DomainException('Credentials does not match');
        }

        if (false === $this->guard->loginUsingId($admin->getId()->getId())) {
            throw new \DomainException('Credentials does not match');
        }
    }

    public function logout(): void
    {
        if (!$this->guard->check()) {
            throw new \DomainException('You does not authorized');
        }

        $this->guard->logout();
        $this->session->invalidate();
        $this->session->regenerateToken();
    }

    public function logged(): ?Entity\Admin
    {
        if (!$this->guard->check()) {
            return null;
        }

        $adminId = $this->guard->id();
        return $this->admins->get(Entity\AdminId::make($adminId));
    }
}