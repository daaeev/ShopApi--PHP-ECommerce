<?php

namespace Project\Modules\Shopping\Adapters\Events;

use Webmozart\Assert\Assert;
use Project\Common\Product\Currency;
use Project\Modules\Shopping\Billing\Payments\Entity\Gateway;
use Project\Common\ApplicationMessages\Events\SerializedEvent;
use Project\Modules\Shopping\Api\Events\Payments\PaymentEvent;
use Project\Modules\Shopping\Billing\Payments\Entity\PaymentStatus;

class PaymentEventsDeserializer
{
    public function __construct(
        private readonly SerializedEvent $event
    ) {
        $eventsId = [
            PaymentEvent::CREATED->value,
            PaymentEvent::STATUS_UPDATED->value,
            PaymentEvent::TRANSACTION_ADDED->value,
        ];

        Assert::inArray($event->getEventId(), $eventsId);
    }

    public function paymentCreated(): bool
    {
        return $this->event->getEventId() === PaymentEvent::CREATED->value;
    }

    public function getOrderId(): int
    {
        return $this->event->orderId;
    }

    public function getPaymentUuid(): string
    {
        return $this->event->uuid;
    }

    public function getPaymentAmount(): float
    {
        return $this->event->amount;
    }

    public function getPaymentCurrency(): Currency
    {
        return Currency::from($this->event->currency);
    }

    public function getPaymentGateway(): Gateway
    {
        return Gateway::from($this->event->gateway);
    }

    public function getPaymentUrl(): ?string
    {
        return $this->event->paymentUrl;
    }

    public function getPaymentStatus(): PaymentStatus
    {
        return PaymentStatus::from($this->event->status);
    }

    public function getPaymentCreatedAt(): \DateTimeImmutable
    {
        return new \DateTimeImmutable($this->event->createdAt);
    }

    public function getPaymentUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->event->updatedAt ? new \DateTimeImmutable($this->event->updatedAt) : null;
    }
}