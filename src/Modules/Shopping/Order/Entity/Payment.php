<?php

namespace Project\Modules\Shopping\Order\Entity;

use Project\Common\Product\Currency;
use Project\Modules\Shopping\Billing\Payments\Entity\Gateway;
use Project\Modules\Shopping\Billing\Payments\Entity\PaymentStatus;

class Payment
{
    public function __construct(
        private PaymentId $id,
        private PaymentUuid $uuid,
        private float $amount,
        private Currency $currency,
        private Gateway $gateway,
        private ?string $paymentUrl,
        private PaymentStatus $status,
        private \DateTimeImmutable $createdAt,
        private ?\DateTimeImmutable $updatedAt,
    ) {}

    public function __clone(): void
    {
        $this->id = clone $this->id;
        $this->uuid = clone $this->uuid;
        $this->createdAt = clone $this->createdAt;
        $this->updatedAt = $this->createdAt ? clone $this->createdAt : null;
    }

    public function getId(): PaymentId
    {
        return $this->id;
    }

    public function getUuid(): PaymentUuid
    {
        return $this->uuid;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    public function getGateway(): Gateway
    {
        return $this->gateway;
    }

    public function getPaymentUrl(): ?string
    {
        return $this->paymentUrl;
    }

    public function getStatus(): PaymentStatus
    {
        return $this->status;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }
}