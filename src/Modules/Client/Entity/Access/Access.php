<?php

namespace Project\Modules\Client\Entity\Access;

abstract class Access
{
    protected AccessType $type;
    protected \DateTimeImmutable $createdAt;

    public function __construct(AccessType $type)
    {
        $this->type = $type;
        $this->createdAt = new \DateTimeImmutable;
    }

    public function equalsTo(self $other): bool
    {
        return ($other->getType() === $this->getType())
            && ($other->getCredentials() === $this->getCredentials());
    }

    public function getType(): AccessType
    {
        return $this->type;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    abstract public function getCredentials(): array;
}