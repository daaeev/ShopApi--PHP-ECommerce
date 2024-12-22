<?php

namespace Project\Tests\Unit\Modules\Client\Entity\Access;

use PHPUnit\Framework\TestCase;
use Project\Modules\Client\Entity\Access\AccessType;
use Project\Modules\Client\Entity\Access\PhoneAccess;
use Project\Modules\Client\Entity\Access\SocialAccess;
use Project\Tests\Unit\Modules\Helpers\ContactsGenerator;

class SocialAccessTest extends TestCase
{
    use ContactsGenerator;

    public function testCreateSocialAccess()
    {
        $access = new SocialAccess($email = $this->generateEmail(), $socialId = random_bytes(5));
        $this->assertSame(AccessType::SOCIAL, $access->getType());
        $this->assertNotNull($access->getCreatedAt());
        $this->assertSame(['email' => $email, 'socialId' => $socialId], $access->getCredentials());
    }

    public function testCreateAccessWithInvalidEmail()
    {
        $this->expectException(\DomainException::class);
        new SocialAccess(email: 'invalid email', socialId: random_bytes(5));
    }

    public function testCreateAccessWithEmptySocialId()
    {
        $this->expectException(\InvalidArgumentException::class);
        new SocialAccess(email: $this->generateEmail(), socialId: '');
    }

    public function testEqualsTo()
    {
        $socialAccess = new SocialAccess($email = $this->generateEmail(), $socialId = random_bytes(5));
        $sameSocialAccess = new SocialAccess($email, $socialId);
        $anotherSocialAccess = new SocialAccess(email: $this->generateEmail(), socialId: random_bytes(5));
        $phoneAccess = new PhoneAccess($this->generatePhone());

        $this->assertTrue($socialAccess->equalsTo($sameSocialAccess));
        $this->assertFalse($socialAccess->equalsTo($anotherSocialAccess));
        $this->assertFalse($socialAccess->equalsTo($phoneAccess));
    }
}