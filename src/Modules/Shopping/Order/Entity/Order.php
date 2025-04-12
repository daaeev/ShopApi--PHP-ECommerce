<?php

namespace Project\Modules\Shopping\Order\Entity;

use Webmozart\Assert\Assert;
use Project\Common\Entity\Aggregate;
use Project\Common\Product\Currency;
use Project\Modules\Shopping\Offers\Offer;
use Project\Modules\Shopping\Offers\OfferId;
use Project\Modules\Shopping\Offers\OfferUuId;
use Project\Modules\Shopping\Entity\Promocode;
use Project\Modules\Shopping\Offers\OffersCollection;
use Project\Modules\Shopping\Api\Events\Orders\OrderCreated;
use Project\Modules\Shopping\Api\Events\Orders\OrderUpdated;
use Project\Modules\Shopping\Api\Events\Orders\OrderDeleted;
use Project\Modules\Shopping\Order\Entity\Delivery\DeliveryInfo;
use Project\Modules\Shopping\Api\Events\Orders\OrderCompleted;
use Project\Modules\Shopping\Api\Events\Orders\PaymentStatusUpdated;

class Order extends Aggregate
{
    private OrderId $id;
    private ClientInfo $client;
    private ?Manager $manager = null;
    private OrderStatus $status = OrderStatus::NEW;
    private PaymentStatus $paymentStatus = PaymentStatus::NOT_PAID;
    private DeliveryInfo $delivery;
    private OffersCollection $offers;
    private Currency $currency;
    private ?Promocode $promocode = null;
    private Payments $payments;
    private int $totalPrice; // Price with discounts
    private int $regularPrice; // Price without discount
    private ?string $customerComment = null;
    private ?string $managerComment = null;
    private \DateTimeImmutable $createdAt;
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct(
        OrderId $id,
        ClientInfo $client,
        DeliveryInfo $delivery,
        array $offers,
        Currency $currency
    ) {
        $this->id = $id;
        $this->client = $client;
        $this->delivery = $delivery;
        $this->offers = new OffersCollection($offers);
        $this->currency = $currency;
        $this->payments = new Payments;
        $this->createdAt = new \DateTimeImmutable;
        $this->guardOffersCantBeEmpty();
        $this->guardOffersIdsIsUnique();
        $this->refreshPrice();
        $this->addEvent(new OrderCreated($this));
    }

    private function guardOffersCantBeEmpty(): void
    {
        Assert::notEmpty($this->offers->all(), 'Order must contain at least 1 offer');
    }

    private function guardOffersIdsIsUnique(): void
    {
        foreach ($this->offers as $offer) {
            foreach ($this->offers as $otherOffer) {
                if ($offer === $otherOffer) {
                    continue;
                }

                if ($offer->getId()->equalsTo($otherOffer->getId())) {
                    throw new \DomainException('Offers ids must be unique');
                }

                if ($offer->getUuid()->equalsTo($otherOffer->getUuid())) {
                    throw new \DomainException('Offers uuids must be unique');
                }
            }
        }
    }

    public function refreshPrice(): void
    {
        $this->refreshTotalPrice();
        $this->refreshRegularPrice();
    }

    private function refreshTotalPrice(): void
    {
        $totalPrice = array_reduce(
            array: $this->offers->all(),
            callback: fn ($totalPrice, Offer $offer) => $totalPrice + ($offer->getPrice() * $offer->getQuantity()),
            initial: 0
        );

        if (null !== $this->promocode) {
            $discountPrice = ($totalPrice / 100) * $this->promocode->getDiscountPercent();
            $totalPrice -= $discountPrice;
        }

        $this->totalPrice = (int) $totalPrice;
    }

    private function refreshRegularPrice(): void
    {
        $this->regularPrice = array_reduce(
            array: $this->offers->all(),
            callback: fn ($totalPrice, Offer $offer) => $totalPrice + ($offer->getRegularPrice() * $offer->getQuantity()),
            initial: 0
        );
    }

    public function __clone(): void
    {
        $this->id = clone $this->id;
        $this->client = clone $this->client;
        $this->delivery = clone $this->delivery;
        $this->offers = clone $this->offers;
        $this->promocode = clone $this->promocode;
        $this->createdAt = clone $this->createdAt;
        $this->updatedAt = $this->updatedAt ? clone $this->updatedAt : null;
    }

    public function addOffer(Offer $offer): void
    {
        $this->guardOrderNotCompleted();
        $this->offers->add($offer);
        $this->refreshPrice();
        $this->updated();
    }

    private function guardOrderNotCompleted(): void
    {
        if (OrderStatus::COMPLETED === $this->status) {
            throw new \DomainException('Cant update completed order');
        }
    }

    private function updated(): void
    {
        $this->addEvent(new OrderUpdated($this));
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function replaceOffer(Offer $offer, Offer $newOffer): void
    {
        $this->guardOrderNotCompleted();
        $this->offers->replace($offer->getUuid(), $newOffer);
        $this->refreshPrice();
        $this->updated();
    }

    public function getOffer(OfferId|OfferUuId $offerId): Offer
    {
        return $this->offers->get($offerId);
    }

    public function removeOffer(OfferId|OfferUuId $offerId): void
    {
        $this->guardOrderNotCompleted();
        $this->offers->remove($offerId);
        $this->guardOffersCantBeEmpty();
        $this->refreshPrice();
        $this->updated();
    }

    public function usePromocode(Promocode $promocode): void
    {
        $this->guardOrderNotCompleted();
        if (null !== $this->promocode) {
            throw new \DomainException('Order already have other promocode');
        }

        if (empty($promocode->getId()->getId())) {
            throw new \DomainException('Promocode id cant be empty');
        }

        $this->promocode = $promocode;
        $this->refreshPrice();
        $this->updated();
    }

    public function removePromocode(): void
    {
        $this->guardOrderNotCompleted();
        if (null === $this->promocode) {
            throw new \DomainException('Order does not have promocode');
        }

        $this->promocode = null;
        $this->refreshPrice();
        $this->updated();
    }

    public function updateClientInfo(ClientInfo $client): void
    {
        $this->guardOrderNotCompleted();
        if (!$client->getClient()->same($this->client->getClient())) {
            throw new \DomainException('Cant attach order to another client');
        }

        $this->client = $client;
        $this->updated();
    }

    public function updateStatus(OrderStatus $status): void
    {
        $this->guardOrderNotCompleted();
        if ($this->status === $status) {
            throw new \DomainException('Order already have same status');
        }

        $this->status = $status;
        if (OrderStatus::COMPLETED === $status) {
            $this->addEvent(new OrderCompleted($this));
        }

        $this->updated();
    }

    public function addPayment(Payment $payment): void
    {
        if ($this->payments->contains($payment)) {
            throw new \DomainException('Same payment already exists');
        }

        $this->payments->add($payment);
        $this->refreshPaymentStatus();
        $this->updated();
    }

    private function refreshPaymentStatus(): void
    {
        $newPaymentStatus = $this->payments->getOrderPaymentStatus($this->totalPrice);
        if ($newPaymentStatus === $this->paymentStatus) {
            return;
        }

        $this->paymentStatus = $newPaymentStatus;
        $this->addEvent(new PaymentStatusUpdated($this));
        $this->updated();
    }

    public function updatePayment(Payment $payment): void
    {
        if (false === $this->payments->contains($payment)) {
            throw new \DomainException('Same payment already exists');
        }

        $this->payments->update($payment);
        $this->refreshPaymentStatus();
        $this->updated();
    }

    public function getPayment(PaymentUuid $uuid): Payment
    {
        return $this->payments->getByUuid($uuid);
    }

    public function updateDelivery(DeliveryInfo $delivery): void
    {
        $this->guardOrderNotCompleted();
        $this->delivery = $delivery;
        $this->updated();
    }

    public function addCustomerComment(string $comment): void
    {
        $this->guardOrderNotCompleted();
        if (!empty($this->customerComment)) {
            throw new \DomainException('Cant update existed customer comment');
        }

        $this->customerComment = $comment;
        $this->updated();
    }

    public function updateManagerComment(string $comment): void
    {
        $this->guardOrderNotCompleted();
        $this->managerComment = $comment;
        $this->updated();
    }

    public function attachManager(Manager $manager): void
    {
        if (null !== $this->manager) {
            throw new \DomainException('Manager already attached to order');
        }

        $this->manager = $manager;
        $this->updated();
    }

    public function detachManager(): void
    {
        if (null === $this->manager) {
            throw new \DomainException('Manager does not attached to order');
        }

        $this->manager = null;
        $this->updated();
    }

    public function delete(): void
    {
        if (OrderStatus::CANCELED !== $this->status) {
            throw new \DomainException('Only cancelled order can be deleted');
        }

        $this->addEvent(new OrderDeleted($this));
    }

    public function getId(): OrderId
    {
        return $this->id;
    }

    public function getClient(): ClientInfo
    {
        return $this->client;
    }

    public function getStatus(): OrderStatus
    {
        return $this->status;
    }

    public function getPaymentStatus(): PaymentStatus
    {
        return $this->paymentStatus;
    }

    public function getDelivery(): DeliveryInfo
    {
        return $this->delivery;
    }

    /**
     * @return Offer[]
     */
    public function getOffers(): array
    {
        return $this->offers->all();
    }

    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    public function getTotalPrice(): int
    {
        return $this->totalPrice;
    }

    public function getRegularPrice(): int
    {
        return $this->regularPrice;
    }

    public function getPromocode(): ?Promocode
    {
        return $this->promocode;
    }

    public function getCustomerComment(): ?string
    {
        return $this->customerComment;
    }

    public function getManagerComment(): ?string
    {
        return $this->managerComment;
    }

    public function getManager(): ?Manager
    {
        return $this->manager;
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