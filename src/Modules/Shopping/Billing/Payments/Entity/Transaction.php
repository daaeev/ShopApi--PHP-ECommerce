<?php

namespace Project\Modules\Shopping\Billing\Payments\Entity;

class Transaction
{
    public function __construct(
        private TransactionUuid $uuid,
        private Gateway $gateway,
        private float $amount,
        private TransactionStatus $status,
        private ?string $data,
        private \DateTimeImmutable $createdAt,
    ) {}

    public function __clone(): void
    {
        $this->uuid = clone $this->uuid;
        $this->createdAt = clone $this->createdAt;
    }

    public function getRelatedPaymentStatus(float $paymentAmount): PaymentStatus
    {
        if ($this->status === TransactionStatus::Pending) {
            return PaymentStatus::Pending;
        }

        if ($this->status === TransactionStatus::Processing) {
            return PaymentStatus::Processing;
        }

        if ($this->status === TransactionStatus::Refunded) {
            if ($paymentAmount === $this->amount) {
                return PaymentStatus::Refunded;
            } else {
                return PaymentStatus::PartiallyRefunded;
            }
        }

        if ($this->status === TransactionStatus::Paid) {
            if ($paymentAmount === $this->amount) {
                return PaymentStatus::Paid;
            } else {
                return PaymentStatus::PartiallyPaid;
            }
        }

        if ($this->status === TransactionStatus::Failed) {
            return PaymentStatus::Failed;
        }

        throw new \DomainException("Related payment status does not provided for transaction status '{$this->status->value}'");
    }

    public function equalsTo(self $other): bool
    {
        $otherCreatedAt = $other->createdAt->format(\DateTimeInterface::RFC3339);
        return $this->uuid->equalsTo($other->uuid)
            && $this->gateway === $other->gateway
            && $this->amount === $other->amount
            && $this->status === $other->status
            && $this->createdAt->format(\DateTimeInterface::RFC3339) === $otherCreatedAt;
    }

    public function getUuid(): TransactionUuid
    {
        return $this->uuid;
    }

    public function getGateway(): Gateway
    {
        return $this->gateway;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getStatus(): TransactionStatus
    {
        return $this->status;
    }

    public function getData(): string
    {
        return $this->data;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}