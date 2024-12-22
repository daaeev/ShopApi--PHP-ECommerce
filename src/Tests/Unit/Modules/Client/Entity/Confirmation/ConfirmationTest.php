<?php

namespace Project\Tests\Unit\Modules\Client\Entity\Confirmation;

use PHPUnit\Framework\TestCase;
use Project\Modules\Client\Entity\Confirmation\Confirmation;
use Project\Modules\Client\Entity\Confirmation\ConfirmationUuid;

class ConfirmationTest extends TestCase
{
    public function testCreateConfirmation()
    {
        $confirmation = new Confirmation(
            $uuid = ConfirmationUuid::random(),
            $code = random_int(1, 10),
            $lifeTimeInMinutes = 10,
        );

        $expectedExpiredAt = new \DateTimeImmutable("+$lifeTimeInMinutes minutes");
        $this->assertSame($uuid, $confirmation->getUuid());
        $this->assertSame($code, $confirmation->getCode());
        $this->assertSame(0, $expectedExpiredAt->diff($confirmation->getExpiredAt())->i);
        $this->assertNotNull($confirmation->getCreatedAt());
        $this->assertNull($confirmation->getUpdatedAt());
    }

    public function testClone()
    {
        $confirmation = new Confirmation(uuid: ConfirmationUuid::random(), code: random_int(1, 10));
        $cloned = clone $confirmation;
        $this->assertTrue($cloned->getUuid()->equalsTo($confirmation->getUuid()));
        $this->assertNotSame($cloned->getUuid(), $confirmation->getUuid());
    }

    public function testRefreshExpiredAt()
    {
        $confirmation = new Confirmation(
            uuid: ConfirmationUuid::random(),
            code: random_int(1, 10),
            lifeTimeInMinutes: 5
        );

        $oldExpiredAt = $confirmation->getExpiredAt();
        $lifeTimeInMinutes = 15;
        $expectedExpiredAt = new \DateTimeImmutable("+$lifeTimeInMinutes minutes");
        $this->assertNotSame(0, $expectedExpiredAt->diff($confirmation->getExpiredAt())->i);

        $refreshed = $confirmation->refreshExpiredAt($lifeTimeInMinutes);
        $this->assertSame($oldExpiredAt, $confirmation->getExpiredAt());
        $this->assertNotSame($oldExpiredAt, $refreshed->getExpiredAt());
        $this->assertSame(0, $expectedExpiredAt->diff($refreshed->getExpiredAt())->i);
        $this->assertNull($confirmation->getUpdatedAt());
        $this->assertNotNull($refreshed->getUpdatedAt());
    }

    public function testValidateCode()
    {
        $confirmation = new Confirmation(ConfirmationUuid::random(), $code = random_int(1, 10));
        $this->expectNotToPerformAssertions();
        $confirmation->validateCode($code);
    }

    public function testValidateCodeWithWrongCode()
    {
        $confirmation = new Confirmation(ConfirmationUuid::random(), $code = random_int(1, 10));
        $this->expectException(\DomainException::class);
        $confirmation->validateCode($code + 1);
    }

    public function testValidateCodeIfConfirmationExpired()
    {
        $confirmation = new Confirmation(ConfirmationUuid::random(), $code = random_int(1, 10), lifeTimeInMinutes: -5);
        $this->expectException(\DomainException::class);
        $confirmation->validateCode($code);
    }
}