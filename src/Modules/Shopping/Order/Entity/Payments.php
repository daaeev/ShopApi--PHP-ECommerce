<?php

namespace Project\Modules\Shopping\Order\Entity;

use Project\Modules\Shopping\Billing\Payments\Entity as Billing;
use Project\Modules\Shopping\Order\Entity as Order;

class Payments
{
    public function __construct(
        private array $payments = [],
    ) {}

    public function __clone(): void
    {
        $payments = [];
        foreach ($this->payments as $payment) {
            $payments[] = clone $payment;
        }

        $this->payments = $payments;
    }

    public function add(Order\Payment $payment): void
    {
        if ($this->contains($payment)) {
            throw new \DomainException('Same payment already exists');
        }

        $this->payments[] = $payment;
    }

    public function contains(Order\Payment $payment): bool
    {
        foreach ($this->all() as $currentPayment) {
            if ($payment->getUuid()->equalsTo($currentPayment->getUuid())) {
                return true;
            }
        }

        return false;
    }

    public function update(Order\Payment $payment): void
    {
        if (false === $this->contains($payment)) {
            throw new \DomainException('Same payment already exists');
        }

        foreach ($this->all() as $key => $payment) {
            if ($payment->getUuid()->equalsTo($payment->getUuid())) {
                $this->payments[$key] = $payment;
                break;
            }
        }
    }

    public function getOrderPaymentStatus(float $orderTotalPrice): PaymentStatus
    {
        $paidAmount = 0;
        foreach ($this->payments as $payment) {
            if ($payment->getStatus() === Billing\PaymentStatus::Paid) {
                return Order\PaymentStatus::PAID;
            }

            if ($payment->getStatus() === Billing\PaymentStatus::PartiallyPaid) {
                $paidAmount += $payment->getPaidAmount();
            }
        }

        if ($paidAmount === 0) {
            return Order\PaymentStatus::NOT_PAID;
        }

        if ($paidAmount >= $orderTotalPrice) {
            return Order\PaymentStatus::PAID;
        }

        return Order\PaymentStatus::PARTIALLY_PAID;
    }

    public function getByUuid(Order\PaymentUuid $uuid): Order\Payment
    {
        foreach ($this->all() as $payment) {
            if ($payment->getUuid()->equalsTo($uuid)) {
                return $payment;
            }
        }

        throw new \DomainException('Payment does not exists');
    }

    /**
     * @return Order\Payment[]
     */
    public function all(): array
    {
        return $this->payments;
    }
}