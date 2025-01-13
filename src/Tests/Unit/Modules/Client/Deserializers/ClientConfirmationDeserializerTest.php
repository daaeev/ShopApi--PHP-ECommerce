<?php

namespace Project\Tests\Unit\Modules\Client\Deserializers;

use PHPUnit\Framework\TestCase;
use Project\Tests\Unit\Modules\Helpers\ClientFactory;
use Project\Modules\Client\Api\Events\Client\ClientCreated;
use Project\Common\ApplicationMessages\Events\SerializedEvent;
use Project\Modules\Client\Entity\Confirmation\StaticCodeGenerator;
use Project\Modules\Client\Entity\Confirmation\CodeGeneratorInterface;
use Project\Modules\Client\Api\Events\Confirmation\ClientConfirmationCreated;
use Project\Modules\Client\Api\Events\Confirmation\ClientConfirmationRefreshed;
use Project\Modules\Client\Adapters\Events\ClientConfirmationEventsDeserializer;

class ClientConfirmationDeserializerTest extends TestCase
{
    use ClientFactory;

    private CodeGeneratorInterface $codeGenerator;

    public function setUp(): void
    {
        $this->codeGenerator = new StaticCodeGenerator;
    }

    public function testConfirmationCreatedDeserializer()
    {
        $client = $this->generateClient();
        $confirmationUuid = $client->generateConfirmation($this->codeGenerator);
        $confirmation = $client->getConfirmation($confirmationUuid);

        $event = new ClientConfirmationCreated($client, $confirmation);
        $deserializer = new ClientConfirmationEventsDeserializer(new SerializedEvent($event));
        $this->assertSame($deserializer->getClientPhone(), $client->getContacts()->getPhone());
        $this->assertSame($deserializer->getConfirmationCode(), $confirmation->getCode());
        $this->assertSame(
            $deserializer->getConfirmationExpiredAt()->getTimestamp(),
            $confirmation->getExpiredAt()->getTimestamp()
        );
    }

    public function testConfirmationRefreshedDeserializer()
    {
        $client = $this->generateClient();
        $confirmationUuid = $client->generateConfirmation($this->codeGenerator);
        $confirmation = $client->getConfirmation($confirmationUuid);

        $event = new ClientConfirmationRefreshed($client, $confirmation);
        $deserializer = new ClientConfirmationEventsDeserializer(new SerializedEvent($event));
        $this->assertSame($deserializer->getClientPhone(), $client->getContacts()->getPhone());
        $this->assertSame($deserializer->getConfirmationCode(), $confirmation->getCode());
        $this->assertSame(
            $deserializer->getConfirmationExpiredAt()->getTimestamp(),
            $confirmation->getExpiredAt()->getTimestamp()
        );
    }

    public function testCreateDeserializerWithAnotherEvent()
    {
        $client = $this->generateClient();
        $event = new ClientCreated($client);
        $serializedEvent = new SerializedEvent($event);
        $this->expectException(\InvalidArgumentException::class);
        new ClientConfirmationEventsDeserializer($serializedEvent);
    }
}