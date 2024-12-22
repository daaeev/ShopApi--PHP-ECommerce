<?php

namespace Project\Tests\Unit\Modules\Client\Entity;

use Project\Tests\Unit\Modules\Helpers\AssertEvents;
use Project\Tests\Unit\Modules\Helpers\ClientFactory;
use Project\Modules\Client\Entity\Confirmation\ConfirmationUuid;
use Project\Modules\Client\Entity\Confirmation\CodeGeneratorInterface;
use Project\Modules\Client\Api\Events\Confirmation\ClientConfirmationCreated;
use Project\Modules\Client\Api\Events\Confirmation\ClientConfirmationRefreshed;

class ClientConfirmationsTest extends \PHPUnit\Framework\TestCase
{
    use ClientFactory, AssertEvents;

    private readonly CodeGeneratorInterface $codeGenerator;
    private readonly int $confirmationLifeTimeInMinutes;

    protected function setUp(): void
    {
        $this->codeGenerator = $this->getMockBuilder(CodeGeneratorInterface::class)->getMock();
        $this->confirmationLifeTimeInMinutes = 10;
    }

    public function testGenerateConfirmation()
    {
        $this->codeGenerator->expects($this->once())
            ->method('generate')
            ->willReturn($code = random_int(1000, 9999));

        $client = $this->generateClient();
        $expectedExpiredAt = new \DateTimeImmutable("+$this->confirmationLifeTimeInMinutes minutes");
        $uuid = $client->generateConfirmation($this->codeGenerator, $this->confirmationLifeTimeInMinutes);
        $confirmation = $client->getConfirmation($uuid);

        $this->assertTrue($client->hasConfirmation($uuid));
        $this->assertCount(1, $client->getConfirmations());
        $this->assertEvents($client, [new ClientConfirmationCreated($client, $confirmation)]);

        $this->assertSame($code, $confirmation->getCode());
        $this->assertSame(0, $expectedExpiredAt->diff($confirmation->getExpiredAt())->i);
        $this->assertNotNull($confirmation->getCreatedAt());
        $this->assertNull($confirmation->getUpdatedAt());
    }

    public function testRefreshConfirmationExpiredAt()
    {
        $client = $this->generateClient();
        $uuid = $client->generateConfirmation($this->codeGenerator, $this->confirmationLifeTimeInMinutes);
        $confirmation = $client->getConfirmation($uuid);
        $client->flushEvents();

        $lifeTime = $this->confirmationLifeTimeInMinutes + random_int(5, 10);
        $expectedExpiredAt = new \DateTimeImmutable("+$lifeTime minutes");
        $client->refreshConfirmationExpiredAt($uuid, $lifeTime);

        $refreshed = $client->getConfirmation($uuid);
        $this->assertNotSame($confirmation, $refreshed);
        $this->assertSame(0, $expectedExpiredAt->diff($refreshed->getExpiredAt())->i);
        $this->assertCount(1, $client->getConfirmations());
        $this->assertSame($confirmation->getCode(), $refreshed->getCode());
        $this->assertNotNull($refreshed->getUpdatedAt());
        $this->assertEvents($client, [new ClientConfirmationRefreshed($client, $refreshed)]);
    }

    public function testRefreshConfirmationExpiredAtThatDoesNotExists()
    {
        $client = $this->generateClient();
        $this->expectException(\DomainException::class);
        $client->refreshConfirmationExpiredAt(ConfirmationUuid::random());
    }

    public function testGetConfirmationThatDoesNotExists()
    {
        $client = $this->generateClient();
        $this->expectException(\DomainException::class);
        $client->getConfirmation(ConfirmationUuid::random());
    }

    public function testApplyConfirmation()
    {
        $client = $this->generateClient();
        $uuid = $client->generateConfirmation($this->codeGenerator, $this->confirmationLifeTimeInMinutes);
        $confirmation = $client->getConfirmation($uuid);
        $client->flushEvents();

        $client->applyConfirmation($uuid, $confirmation->getCode());
        $this->assertEmpty($client->getConfirmations());
        $this->assertFalse($client->hasConfirmation($uuid));
        $this->assertEvents($client, []);
    }

    public function testApplyConfirmationWithWrongCode()
    {
        $client = $this->generateClient();
        $uuid = $client->generateConfirmation($this->codeGenerator, $this->confirmationLifeTimeInMinutes);
        $this->expectException(\DomainException::class);
        $client->applyConfirmation($uuid, 1234);
    }

    public function testApplyExpiredConfirmation()
    {
        $client = $this->generateClient();
        $uuid = $client->generateConfirmation($this->codeGenerator, lifeTimeInMinutes: -5);
        $confirmation = $client->getConfirmation($uuid);
        $this->expectException(\DomainException::class);
        $client->applyConfirmation($uuid, $confirmation->getCode());
    }

    public function testApplyConfirmationThatDoesNotExists()
    {
        $client = $this->generateClient();
        $this->expectException(\DomainException::class);
        $client->applyConfirmation(ConfirmationUuid::random(), 1234);
    }
}