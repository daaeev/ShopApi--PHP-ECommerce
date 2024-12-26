<?php

namespace Project\Tests\Unit\Modules\Administrators\Repository;

use Project\Common\Administrators\Role;
use Project\Modules\Administrators\Entity\AdminId;
use Project\Tests\Unit\Modules\Helpers\AdminFactory;
use Project\Common\Repository\DuplicateKeyException;
use Project\Common\Repository\NotFoundException;
use Project\Modules\Administrators\Repository\AdminsRepositoryInterface;

trait AdminsRepositoryTestTrait
{
    use AdminFactory;

    protected AdminsRepositoryInterface $admins;

    public function testAdd()
    {
        $initial = $this->generateAdmin();
        $initialSerialized = serialize($initial);
        $this->admins->add($initial);

        $found = $this->admins->get($initial->getId());
        $this->assertSame($initial, $found);
        $this->assertSame($initialSerialized, serialize($found));
    }

    public function testAddIncrementIds()
    {
        $admin = $this->makeAdmin(
            id: AdminId::next(),
            name: uniqid(),
            login: $this->correctAdminLogin,
            password: $this->correctAdminPassword,
            roles: [Role::ADMIN]
        );

        $this->admins->add($admin);
        $this->assertNotNull($admin->getId()->getId());
    }

    public function testAddWithDuplicatedId()
    {
        $admin = $this->generateAdmin();
        $adminWithSameId = $this->makeAdmin(
            id: $admin->getId(),
            name: $admin->getName(),
            login: $this->correctAdminLogin,
            password: $admin->getPassword(),
            roles: $admin->getRoles()
        );

        $this->admins->add($admin);
        $this->expectException(DuplicateKeyException::class);
        $this->admins->add($adminWithSameId);
    }

    public function testAddWithNotUniqueLogin()
    {
        $admin = $this->generateAdmin();
        $adminWithNotUniqueLogin = $this->generateAdmin();
        $adminWithNotUniqueLogin->setLogin($admin->getLogin());
        $this->admins->add($admin);
        $this->expectException(DuplicateKeyException::class);
        $this->admins->add($adminWithNotUniqueLogin);
    }

    public function testUpdate()
    {
        $initial = $this->generateAdmin();
        $initialSerialized = serialize($initial);

        $this->admins->add($initial);
        $added = $this->admins->get($initial->getId());

        $added->setName('Updated admin name for test update');
        $added->setLogin($this->correctAdminLogin);
        $added->setPassword($this->correctAdminPassword);
        $added->setRoles([Role::MANAGER]);
        $addedSerialized = serialize($added);
        $this->admins->update($added);

        $updated = $this->admins->get($initial->getId());
        $this->assertSame($initial, $added);
        $this->assertSame($added, $updated);
        $this->assertNotSame($initialSerialized, $addedSerialized);
        $this->assertSame($addedSerialized, serialize($updated));
    }

    public function testUpdateIfDoesNotExists()
    {
        $this->expectException(NotFoundException::class);
        $admin = $this->generateAdmin();
        $this->admins->update($admin);
    }

    public function testUpdateWithNotUniqueLogin()
    {
        $admin = $this->generateAdmin();
        $adminWithNotUniqueLogin = $this->generateAdmin();
        $this->admins->add($admin);
        $this->admins->add($adminWithNotUniqueLogin);
        $adminWithNotUniqueLogin->setLogin($admin->getLogin());
        $this->expectException(DuplicateKeyException::class);
        $this->admins->update($adminWithNotUniqueLogin);
    }

    public function testDelete()
    {
        $admin = $this->generateAdmin();
        $this->admins->add($admin);
        $this->admins->delete($admin);
        $this->expectException(NotFoundException::class);
        $this->admins->get($admin->getId());
    }

    public function testDeleteIfDoesNotExists()
    {
        $this->expectException(NotFoundException::class);
        $admin = $this->generateAdmin();
        $this->admins->delete($admin);
    }

    public function testGetIfDoesNotExists()
    {
        $this->expectException(NotFoundException::class);
        $this->admins->get(AdminId::random());
    }

    public function testGetByCredentials()
    {
        $admin = $this->generateAdmin();
        $this->admins->add($admin);
        $found = $this->admins->getByCredentials($admin->getLogin(), $admin->getPassword());
        $this->assertSame($admin, $found);
    }

    public function testGetByCredentialsIfDoesNotExists()
    {
        $this->expectException(NotFoundException::class);
        $this->admins->getByCredentials($this->correctAdminLogin, $this->correctAdminPassword);
    }

    public function testGetByCredentialsIfLoginExistsButPasswordMismatch()
    {
        $admin = $this->generateAdmin();
        $this->admins->add($admin);
        $this->expectException(NotFoundException::class);
        $this->admins->getByCredentials($admin->getLogin(), uniqid());
    }
}
