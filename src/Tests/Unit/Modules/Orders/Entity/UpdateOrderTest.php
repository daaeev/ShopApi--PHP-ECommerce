<?php

namespace Project\Tests\Unit\Modules\Orders\Entity;

use Project\Common\Client\Client;
use Project\Modules\Shopping\Entity\Promocode;
use Project\Tests\Unit\Modules\Helpers\OrderFactory;
use Project\Tests\Unit\Modules\Helpers\AssertEvents;
use Project\Tests\Unit\Modules\Helpers\OffersFactory;
use Project\Modules\Shopping\Order\Entity\ClientInfo;
use Project\Modules\Shopping\Order\Entity\OrderStatus;
use Project\Tests\Unit\Modules\Helpers\PromocodeFactory;
use Project\Modules\Shopping\Order\Entity\PaymentStatus;
use Project\Modules\Shopping\Api\Events\Orders\OrderUpdated;
use Project\Modules\Shopping\Order\Entity\Delivery\DeliveryInfo;
use Project\Modules\Shopping\Order\Entity\Delivery\DeliveryService;
use Project\Modules\Shopping\Discounts\Promocodes\Entity\PromocodeId;

class UpdateOrderTest extends \PHPUnit\Framework\TestCase
{
    use OrderFactory, OffersFactory, PromocodeFactory, AssertEvents;

    public function testUsePromocode()
    {
        $order = $this->generateOrder([$this->generateOffer()]);
        $oldUpdatedAt = $order->getUpdatedAt();
        $promocode = Promocode::fromBaseEntity($this->generatePromocode());
        $order->usePromocode($promocode);

        $this->assertSame($promocode, $order->getPromocode());
        $this->assertNotSame($oldUpdatedAt, $order->getUpdatedAt());
        $this->assertEvents($order, [new OrderUpdated($order)]);
    }

    public function testUsePromocodeIfOrderCompleted()
    {
        $order = $this->generateOrder([$this->generateOffer()]);
        $order->updateStatus(OrderStatus::COMPLETED);
        $promocode = Promocode::fromBaseEntity($this->generatePromocode());

        $this->expectException(\DomainException::class);
        $order->usePromocode($promocode);
    }

    public function testUsePromocodeIfOrderAlreadyHavePromocode()
    {
        $order = $this->generateOrder([$this->generateOffer()]);
        $promocode = Promocode::fromBaseEntity($this->generatePromocode());
        $order->usePromocode($promocode);

        $otherPromocode = Promocode::fromBaseEntity($this->generatePromocode());
        $this->expectException(\DomainException::class);
        $order->usePromocode($otherPromocode);
    }

    public function testUsePromocodeWithEmptyId()
    {
        $order = $this->generateOrder([$this->generateOffer()]);
        $promocode = new Promocode(id: PromocodeId::next(), code: 'test', discountPercent: 50);

        $this->expectException(\DomainException::class);
        $order->usePromocode($promocode);
    }

    public function testRemovePromocode()
    {
        $order = $this->generateOrder([$this->generateOffer()]);
        $order->usePromocode(Promocode::fromBaseEntity($this->generatePromocode()));
        $oldUpdatedAt = $order->getUpdatedAt();
        $order->flushEvents();

        $order->removePromocode();
        $this->assertNull($order->getPromocode());
        $this->assertNotSame($oldUpdatedAt, $order->getUpdatedAt());
        $this->assertEvents($order, [new OrderUpdated($order)]);
    }

    public function testRemovePromocodeIfOrderCompleted()
    {
        $order = $this->generateOrder([$this->generateOffer()]);
        $order->usePromocode(Promocode::fromBaseEntity($this->generatePromocode()));
        $order->updateStatus(OrderStatus::COMPLETED);

        $this->expectException(\DomainException::class);
        $order->removePromocode();
    }

    public function testRemovePromocodeIfOrderDoesNotHavePromo()
    {
        $order = $this->generateOrder([$this->generateOffer()]);
        $this->expectException(\DomainException::class);
        $order->removePromocode();
    }

    public function testUpdateClientInfo()
    {
        $order = $this->generateOrder([$this->generateOffer()]);
        $oldUpdatedAt = $order->getUpdatedAt();
        $client = new ClientInfo(
            client: $order->getClient()->getClient(),
            firstName: uniqid(),
            lastName: uniqid(),
            phone: md5(rand()),
            email: md5(rand()),
        );

        $order->updateClientInfo($client);
        $this->assertTrue($client->equalsTo($order->getClient()));
        $this->assertSame($client, $order->getClient());
        $this->assertNotSame($oldUpdatedAt, $order->getUpdatedAt());
        $this->assertEvents($order, [new OrderUpdated($order)]);
    }

    public function testUpdateClientInfoIfOrderCompleted()
    {
        $order = $this->generateOrder([$this->generateOffer()]);
        $order->updateStatus(OrderStatus::COMPLETED);
        $client = new ClientInfo(
            client: $order->getClient()->getClient(),
            firstName: md5(rand()),
            lastName: md5(rand()),
            phone: md5(rand()),
            email: md5(rand()),
        );

        $this->expectException(\DomainException::class);
        $order->updateClientInfo($client);
    }

    public function testUpdateClientInfoUsingOtherClientId()
    {
        $order = $this->generateOrder([$this->generateOffer()]);
        $client = new ClientInfo(
            client: new Client(hash: md5(rand()), id: rand(1, 99999)),
            firstName: md5(rand()),
            lastName: md5(rand()),
            phone: md5(rand()),
            email: md5(rand()),
        );

        $this->expectException(\DomainException::class);
        $order->updateClientInfo($client);
    }

    public function testUpdateOrderStatus()
    {
        $order = $this->generateOrder([$this->generateOffer()]);
        $oldUpdatedAt = $order->getUpdatedAt();
        $order->updateStatus(OrderStatus::IN_PROGRESS);

        $this->assertSame(OrderStatus::IN_PROGRESS, $order->getStatus());
        $this->assertNotSame($oldUpdatedAt, $order->getUpdatedAt());
        $this->assertEvents($order, [new OrderUpdated($order)]);
    }

    public function testUpdateOrderStatusIfOrderCompleted()
    {
        $order = $this->generateOrder([$this->generateOffer()]);
        $order->updateStatus(OrderStatus::COMPLETED);

        $this->expectException(\DomainException::class);
        $order->updateStatus(OrderStatus::IN_PROGRESS);
    }

    public function testUpdateOrderStatusToCurrentStatus()
    {
        $order = $this->generateOrder([$this->generateOffer()]);
        $this->expectException(\DomainException::class);
        $order->updateStatus(OrderStatus::NEW);
    }

    public function testUpdatePaymentStatus()
    {
        $order = $this->generateOrder([$this->generateOffer()]);
        $oldUpdatedAt = $order->getUpdatedAt();
        $order->updatePaymentStatus(PaymentStatus::PAID);

        $this->assertSame(PaymentStatus::PAID, $order->getPaymentStatus());
        $this->assertNotSame($oldUpdatedAt, $order->getUpdatedAt());
        $this->assertEvents($order, [new OrderUpdated($order)]);
    }

    public function testUpdatePaymentStatusIfOrderCompleted()
    {
        $order = $this->generateOrder([$this->generateOffer()]);
        $order->updateStatus(OrderStatus::COMPLETED);

        $this->expectException(\DomainException::class);
        $order->updatePaymentStatus(PaymentStatus::PAID);
    }

    public function testUpdatePaymentStatusToCurrentPaymentStatus()
    {
        $order = $this->generateOrder([$this->generateOffer()]);
        $this->expectException(\DomainException::class);
        $order->updatePaymentStatus(PaymentStatus::NOT_PAID);
    }

    public function testUpdateDeliveryInfo()
    {
        $order = $this->generateOrder([$this->generateOffer()]);
        $oldUpdatedAt = $order->getUpdatedAt();
        $delivery = new DeliveryInfo(
            service: DeliveryService::NOVA_POST,
            country: md5(rand()),
            city: md5(rand()),
            street: md5(rand()),
            houseNumber: md5(rand()),
        );

        $order->updateDelivery($delivery);
        $this->assertTrue($order->getDelivery()->equalsTo($delivery));
        $this->assertSame($delivery, $order->getDelivery());
        $this->assertNotSame($oldUpdatedAt, $order->getUpdatedAt());
        $this->assertEvents($order, [new OrderUpdated($order)]);
    }

    public function testUpdateDeliveryInfoIfOrderCompleted()
    {
        $order = $this->generateOrder([$this->generateOffer()]);
        $order->updateStatus(OrderStatus::COMPLETED);
        $delivery = new DeliveryInfo(
            service: DeliveryService::NOVA_POST,
            country: md5(rand()),
            city: md5(rand()),
            street: md5(rand()),
            houseNumber: md5(rand()),
        );

        $this->expectException(\DomainException::class);
        $order->updateDelivery($delivery);
    }

    public function testAddCustomerComment()
    {
        $order = $this->generateOrder([$this->generateOffer()]);
        $oldUpdatedAt = $order->getUpdatedAt();
        $comment = md5(rand());

        $order->addCustomerComment($comment);
        $this->assertSame($comment, $order->getCustomerComment());
        $this->assertNotSame($oldUpdatedAt, $order->getUpdatedAt());
        $this->assertEvents($order, [new OrderUpdated($order)]);
    }

    public function testAddCustomerCommentIfOrderCompleted()
    {
        $order = $this->generateOrder([$this->generateOffer()]);
        $order->updateStatus(OrderStatus::COMPLETED);

        $this->expectException(\DomainException::class);
        $order->addCustomerComment(md5(rand()));
    }

    public function testAddCustomerCommentIfOrderAlreadyHaveCustomerComment()
    {
        $order = $this->generateOrder([$this->generateOffer()]);
        $order->addCustomerComment(md5(rand()));

        $this->expectException(\DomainException::class);
        $order->addCustomerComment(md5(rand()));
    }

    public function testUpdateManagerComment()
    {
        $order = $this->generateOrder([$this->generateOffer()]);
        $oldUpdatedAt = $order->getUpdatedAt();
        $comment = md5(rand());

        $order->updateManagerComment($comment);
        $this->assertSame($comment, $order->getManagerComment());
        $this->assertNotSame($oldUpdatedAt, $order->getUpdatedAt());
        $this->assertEvents($order, [new OrderUpdated($order)]);
    }

    public function testUpdateManagerCommentIfOrderCompleted()
    {
        $order = $this->generateOrder([$this->generateOffer()]);
        $order->updateStatus(OrderStatus::COMPLETED);

        $this->expectException(\DomainException::class);
        $order->updateManagerComment(md5(rand()));
    }
}