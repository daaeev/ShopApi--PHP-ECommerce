<?php

namespace Project\Modules\Client\Adapters\Events;

use Webmozart\Assert\Assert;
use Project\Modules\Client\Api\Events\ClientEvent;
use Project\Common\ApplicationMessages\Events\SerializedEvent;

class ClientConfirmationEventsDeserializer
{
    public function __construct(
        private readonly SerializedEvent $event
    ) {
        Assert::inArray(
            $this->event->getEventId(),
            [ClientEvent::CONFIRMATION_CREATED->value, ClientEvent::CONFIRMATION_REFRESHED->value]
        );
    }

    public function getClientPhone(): string
    {
        return $this->event->client['phone'];
    }

    public function getConfirmationCode(): string
    {
        return $this->event->confirmation['code'];
    }

    public function getConfirmationExpiredAt(): \DateTimeImmutable
    {
        return new \DateTimeImmutable($this->event->confirmation['expiredAt']);
    }
}