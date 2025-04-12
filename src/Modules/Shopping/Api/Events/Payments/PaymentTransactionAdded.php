<?php

namespace Project\Modules\Shopping\Api\Events\Payments;

use Project\Modules\Shopping\Billing\Payments\Entity;

class PaymentTransactionAdded extends AbstractPaymentEvent
{
    private readonly Entity\Transaction $transaction;

    public function __construct(Entity\Payment $payment, Entity\Transaction $transaction)
    {
        parent::__construct($payment);
        $this->transaction = $transaction;
    }

    public function getEventId(): string
    {
        return PaymentEvent::TRANSACTION_ADDED->value;
    }

    public function getData(): array
    {
        return [
            ...parent::getData(),
            'transaction' => [] // TODO
        ];
    }
}