<?php

namespace Project\Modules\Shopping\Order\Consumers;

use Project\Modules\Shopping\Order\Entity as Order;
use Project\Common\ApplicationMessages\Events\DispatchEventsTrait;
use Project\Common\ApplicationMessages\Events\DispatchEventsInterface;
use Project\Modules\Shopping\Adapters\Events\PaymentEventsDeserializer;
use Project\Modules\Shopping\Order\Repository\OrdersRepositoryInterface;

class PaymentEventsConsumer implements DispatchEventsInterface
{
    use DispatchEventsTrait;

    public function __construct(
        private readonly OrdersRepositoryInterface $orders,
    ) {}

    public function __invoke(PaymentEventsDeserializer $event): void
    {
        $order = $this->orders->get(Order\OrderId::make($event->getOrderId()));
        $payment = new Order\Payment(
            id: Order\PaymentId::next(),
            uuid: Order\PaymentUuid::make($event->getPaymentUuid()),
            amount: $event->getPaymentAmount(),
            currency: $event->getPaymentCurrency(),
            gateway: $event->getPaymentGateway(),
            paymentUrl: $event->getPaymentUrl(),
            status: $event->getPaymentStatus(),
            createdAt: $event->getPaymentCreatedAt(),
            updatedAt: $event->getPaymentUpdatedAt(),
        );

        if ($event->paymentCreated()) {
            $order->addPayment($payment);
        } else {
            $order->updatePayment($payment);
        }

        $this->orders->update($order);
        $this->dispatchEvents($order->flushEvents());
    }
}