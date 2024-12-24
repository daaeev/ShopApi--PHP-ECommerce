<?php

namespace Project\Tests\Unit\Modules\Client\Repository;

use Project\Modules\Client\Entity\Name;
use Project\Modules\Client\Entity\ClientId;
use Project\Common\Repository\NotFoundException;
use Project\Common\Repository\DuplicateKeyException;
use Project\Tests\Unit\Modules\Helpers\ClientFactory;
use Project\Modules\Client\Entity\Access\PhoneAccess;
use Project\Tests\Unit\Modules\Helpers\ContactsGenerator;
use Project\Modules\Client\Entity\Confirmation\ConfirmationUuid;
use Project\Modules\Client\Repository\ClientsRepositoryInterface;
use Project\Modules\Client\Entity\Confirmation\CodeGeneratorInterface;

trait ClientsRepositoryTestTrait
{
    use ClientFactory, ContactsGenerator;

    protected ClientsRepositoryInterface $clients;
    protected CodeGeneratorInterface $codeGenerator;

    public function testAdd()
    {
        $initial = $this->generateClient();
        $initial->confirmPhone();
        $initial->updateEmail($this->generateEmail());
        $initial->confirmEmail();
        $initial->updateName(new Name('FirstName', 'LastName'));
        $initial->addAccess(new PhoneAccess($this->generatePhone()));
        $initial->generateConfirmation($this->codeGenerator);
        $initialSerialized = serialize($initial);
        $this->clients->add($initial);

        $found = $this->clients->get($initial->getId());
        $this->assertSame($initial, $found);
        $this->assertSame($initialSerialized, serialize($found));
    }

    public function testAddIncrementIds()
    {
        $client = $this->makeClient(ClientId::next(), $this->generatePhone());
        $this->clients->add($client);
        $this->assertNotNull($client->getId()->getId());
    }

    public function testAddWithDuplicatedId()
    {
        $client = $this->generateClient();
        $clientWithSameId = $this->makeClient($client->getId(), $this->generatePhone());
        $this->clients->add($client);
        $this->expectException(DuplicateKeyException::class);
        $this->clients->add($clientWithSameId);
    }

    public function testAddWithNotUniquePhone()
    {
        $client = $this->generateClient();
        $this->clients->add($client);

        $clientWithNotUniquePhone = $this->makeClient(ClientId::next(), $client->getContacts()->getPhone());
        $this->expectException(DuplicateKeyException::class);
        $this->clients->add($clientWithNotUniquePhone);
    }

    public function testAddWithNotUniqueEmail()
    {
        $client = $this->generateClient();
        $client->updateEmail($this->generateEmail());
        $this->clients->add($client);

        $clientWithNotUniqueEmail = $this->generateClient();
        $clientWithNotUniqueEmail->updateEmail($client->getContacts()->getEmail());

        $this->expectNotToPerformAssertions();
        $this->clients->add($clientWithNotUniqueEmail);
    }

    public function testUpdate()
    {
        $initial = $this->generateClient();
        $initialSerialized = serialize($initial);
        $this->clients->add($initial);

        $added = $this->clients->get($initial->getId());
        $added->confirmPhone();
        $added->updateEmail($this->generateEmail());
        $added->confirmEmail();
        $added->updateName(new Name('FirstNameUpdated', 'LastNameUpdated'));
        $added->addAccess(new PhoneAccess($this->generatePhone()));
        $initial->generateConfirmation($this->codeGenerator);
        $addedSerialized = serialize($added);
        $this->clients->update($added);

        $updated = $this->clients->get($initial->getId());
        $this->assertSame($initial, $added);
        $this->assertSame($added, $updated);
        $this->assertNotSame($initialSerialized, $addedSerialized);
        $this->assertSame($addedSerialized, serialize($updated));
    }

    public function testUpdateIfDoesNotExists()
    {
        $this->expectException(NotFoundException::class);
        $client = $this->generateClient();
        $this->clients->update($client);
    }

    public function testDelete()
    {
        $client = $this->generateClient();
        $this->clients->add($client);
        $this->clients->delete($client);
        $this->expectException(NotFoundException::class);
        $this->clients->get($client->getId());
    }

    public function testDeleteIfDoesNotExists()
    {
        $this->expectException(NotFoundException::class);
        $client = $this->generateClient();
        $this->clients->delete($client);
    }

    public function testGet()
    {
        $initial = $this->generateClient();
        $this->clients->add($initial);

        $founded = $this->clients->get($initial->getId());
        $this->assertSame($initial, $founded);
    }

    public function testGetIfDoesNotExists()
    {
        $this->expectException(NotFoundException::class);
        $this->clients->get(ClientId::random());
    }

    public function testGetByPhone()
    {
        $initial = $this->generateClient();
        $this->clients->add($initial);
        $found = $this->clients->getByPhone($initial->getContacts()->getPhone());
        $this->assertSame($initial, $found);
    }

    public function testGetByPhoneIfDoesNotExists()
    {
        $this->expectException(NotFoundException::class);
        $this->clients->getByPhone($this->generatePhone());
    }

    public function testGetByConfirmationUuid()
    {
        $initial = $this->generateClient();
        $confirmationUuid = $initial->generateConfirmation($this->codeGenerator);
        $this->clients->add($initial);
        $found = $this->clients->getByConfirmation($confirmationUuid);
        $this->assertSame($initial, $found);
    }

    public function testGetByConfirmationUuidIfDoesNotExists()
    {
        $this->expectException(NotFoundException::class);
        $this->clients->getByConfirmation(ConfirmationUuid::random());
    }
}
