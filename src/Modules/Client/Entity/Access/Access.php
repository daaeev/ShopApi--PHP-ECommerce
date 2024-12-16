<?php

namespace Project\Modules\Client\Entity\Access;

abstract class Access
{
    private readonly \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable;
    }

    public function equalsTo(self $other): bool
    {
        return ($other->getType() === $this->getType())
            && ($other->getCredentials() === $this->getCredentials());
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    abstract public function getType(): AccessType;

    abstract public function getCredentials(): array;
}