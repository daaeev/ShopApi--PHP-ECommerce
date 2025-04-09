<?php

namespace Project\Modules\Shopping\Billing\Payments\Entity;

use Project\Common\Entity\Aggregate;
use Project\Common\Product\Currency;
use Project\Modules\Shopping\Api\Events\Payments\PaymentUpdated;
use Project\Modules\Shopping\Api\Events\Payments\PaymentStatusUpdated;
use Project\Modules\Shopping\Api\Events\Payments\PaymentTransactionAdded;

class Payment extends Aggregate
{
    private PaymentId $id;
    private PaymentUuid $uuid;
    private float $amount;
    private Currency $currency;
    private Gateway $gateway;
    private ?string $paymentUrl;
    private PaymentStatus $status;
    private Transactions $transactions;
    private \DateTimeImmutable $createdAt;
    private ?\DateTimeImmutable $updatedAt;

    public function __construct(
        PaymentId $id,
        PaymentUuid $uuid,
        float $amount,
        Currency $currency,
        Gateway $gateway,
        ?string $paymentUrl = null,
    ) {
        $this->id = $id;
        $this->uuid = $uuid;
        $this->amount = $amount;
        $this->currency = $currency;
        $this->gateway = $gateway;
        $this->paymentUrl = $paymentUrl;
        $this->status = PaymentStatus::Pending;
        $this->transactions = new Transactions;
        $this->createdAt = new \DateTimeImmutable;
        $this->updatedAt = null;
    }

    public function addTransaction(Transaction $transaction): void
    {
        if ($transaction->getGateway() !== $this->gateway) {
            throw new \DomainException('Cant add transaction from another gateway');
        }

        if ($this->transactions->contains($transaction)) {
            throw new \DomainException('Same transaction already exists');
        }

        $this->transactions->add($transaction);
        $this->addEvent(new PaymentTransactionAdded($this, $transaction));
        $this->updated();
        $this->refreshStatus();
    }

    private function updated(): void
    {
        $this->updatedAt = new \DateTimeImmutable;
        $this->addEvent(new PaymentUpdated($this));
    }

    private function refreshStatus(): void
    {
        if (empty($this->transactions->all())) {
            $this->status = PaymentStatus::Pending;
            return;
        }

        $oldStatus = $this->status;
        $newStatus = $this->transactions->last()->getRelatedPaymentStatus($this->amount);

        $paidAmount = $this->getPaidAmount();
        $refundedAmount = $this->getRefundedAmount();

        if ($refundedAmount === $this->getAmount()) {
            $newStatus = PaymentStatus::Refunded;
        } else if ($refundedAmount > 0) {
            $newStatus = PaymentStatus::PartiallyRefunded;
        } else if ($paidAmount === $this->getAmount()) {
            $newStatus = PaymentStatus::Paid;
        } else if ($paidAmount > 0) {
            $newStatus = PaymentStatus::PartiallyPaid;
        }

        $this->status = $newStatus;
        if ($oldStatus !== $newStatus) {
            $this->addEvent(new PaymentStatusUpdated($this));
            $this->updated();
        }
    }

    private function getPaidAmount(): float
    {
        $paidAmount = 0;
        foreach ($this->transactions->all() as $transaction) {
            if ($transaction->getStatus() === TransactionStatus::Paid) {
                $paidAmount += $transaction->getAmount();
            }
        }

        return $paidAmount;
    }

    private function getRefundedAmount(): float
    {
        $refundedAmount = 0;
        foreach ($this->transactions->all() as $transaction) {
            if ($transaction->getStatus() === TransactionStatus::Refunded) {
                $refundedAmount += $transaction->getAmount();
            }
        }

        return $refundedAmount;
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

    public function getTransactions(): array
    {
        return $this->transactions->all();
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