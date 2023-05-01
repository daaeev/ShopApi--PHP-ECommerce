<?php

namespace Project\Modules\Product\Entity\Price;

class Price
{
    public function __construct(
        private Currency $currency,
        private float $price
    ) {}

    public function equalsTo(self $other): bool
    {
        return (
            ($other->getCurrency() === $this->getCurrency())
            && ($other->getPrice() === $this->getPrice())
        );
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getCurrency(): Currency
    {
        return $this->currency;
    }
}