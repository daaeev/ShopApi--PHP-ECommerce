<?php

namespace Project\Tests\Unit\Modules\Client\Entity\Access;

use PHPUnit\Framework\TestCase;
use Project\Modules\Client\Entity\Access\AccessType;
use Project\Modules\Client\Entity\Access\PhoneAccess;
use Project\Modules\Client\Entity\Access\SocialAccess;
use Project\Tests\Unit\Modules\Helpers\ContactsGenerator;

class PhoneAccessTest extends TestCase
{
    use ContactsGenerator;

    public function testCreatePhoneAccess()
    {
        $access = new PhoneAccess($phone = $this->generatePhone());
        $this->assertSame(AccessType::PHONE, $access->getType());
        $this->assertNotNull($access->getCreatedAt());
        $this->assertSame(['phone' => $phone], $access->getCredentials());
    }

    public function testCreateAccessWithInvalidPhone()
    {
        $this->expectException(\DomainException::class);
        new PhoneAccess('invalid phone');
    }

    public function testEqualsTo()
    {
        $phoneAccess = new PhoneAccess($phone = $this->generatePhone());
        $samePhoneAccess = new PhoneAccess($phone);
        $anotherPhoneAccess = new PhoneAccess($this->generatePhone());
        $socialAccess = new SocialAccess(email: $this->generateEmail(), socialId: random_bytes(5));

        $this->assertTrue($phoneAccess->equalsTo($samePhoneAccess));
        $this->assertFalse($phoneAccess->equalsTo($anotherPhoneAccess));
        $this->assertFalse($phoneAccess->equalsTo($socialAccess));
    }
}