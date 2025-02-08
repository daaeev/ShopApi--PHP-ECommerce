<?php

namespace Project\Common\Services\Environment;

use Webmozart\Assert\Assert;
use Project\Common\Utils\Arrayable;

class Client implements Arrayable
{
    public function __construct(
        private string $hash,
        private ?int $id = null,
    ) {
        Assert::notEmpty($hash, 'Client hash required');
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function same(self $other): bool
    {
        $sameHash = $this->hash === $other->hash;
        $sameId = !empty($this->id) && ($this->id === $other->id);
        return $sameId || $sameHash;
    }

    public function toArray(): array
    {
        return [
            'hash' => $this->hash,
            'id' => $this->id,
        ];
    }
}