<?php

namespace Project\Tests\Unit\Modules\Client\Entity;

use Project\Modules\Client\Entity;
use Project\Tests\Unit\Modules\Helpers\AssertEvents;
use Project\Tests\Unit\Modules\Helpers\ClientFactory;

class ClientAccessesTest extends \PHPUnit\Framework\TestCase
{
    use ClientFactory, AssertEvents;

    public function testAddPhoneAccess()
    {
        $access = new Entity\Access\PhoneAccess($this->generatePhone());
        $client = $this->generateClient();
        $client->addAccess($access);
        $this->assertTrue($client->hasAccess($access));
        $this->assertCount(1, $client->getAccesses());
        $this->assertEvents($client, []);
    }

    public function testAddSocialAccess()
    {
        $access = new Entity\Access\SocialAccess($this->generateEmail(), random_bytes(10));
        $client = $this->generateClient();
        $client->addAccess($access);
        $this->assertTrue($client->hasAccess($access));
        $this->assertCount(1, $client->getAccesses());
        $this->assertEvents($client, []);
    }

    public function testAddPhoneAccessIfSameAlreadyExists()
    {
        $access = new Entity\Access\PhoneAccess($this->generatePhone());
        $client = $this->generateClient();
        $client->addAccess($access);
        $this->expectException(\DomainException::class);
        $client->addAccess($access);
    }

    public function testAddSocialAccessIfSameAlreadyExists()
    {
        $access = new Entity\Access\SocialAccess($this->generateEmail(), random_bytes(10));
        $client = $this->generateClient();
        $client->addAccess($access);
        $this->expectException(\DomainException::class);
        $client->addAccess($access);
    }

    public function testRemoveAccess()
    {
        $access = new Entity\Access\PhoneAccess($this->generatePhone());
        $client = $this->generateClient();
        $client->addAccess($access);
        $client->removeAccess($access);
        $this->assertFalse($client->hasAccess($access));
        $this->assertEmpty($client->getAccesses());
        $this->assertEvents($client, []);
    }

    public function testRemoveAccessIfAccessDoesNotExist()
    {
        $access = new Entity\Access\PhoneAccess($this->generatePhone());
        $client = $this->generateClient();
        $this->expectException(\DomainException::class);
        $client->removeAccess($access);
    }
}