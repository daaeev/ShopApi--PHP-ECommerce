<?php

namespace Project\Modules\Administrators\Repository;

use Project\Common\Repository\IdentityMap;
use Project\Modules\Administrators\Entity;
use Project\Common\Entity\Hydrator\Hydrator;
use Project\Common\Repository\NotFoundException;
use Project\Common\Repository\DuplicateKeyException;

class AdminsMemoryRepository implements AdminsRepositoryInterface
{
    private int $increment = 0;

    public function __construct(
        private Hydrator $hydrator,
        private IdentityMap $identityMap
    ) {}

    public function add(Entity\Admin $entity): void
    {
        $this->guardLoginUnique($entity);

        if (null === $entity->getId()->getId()) {
            $this->hydrator->hydrate($entity->getId(), ['id' => ++$this->increment]);
        }

        if ($this->identityMap->has($entity->getId()->getId())) {
            throw new DuplicateKeyException('Admin with same id already exists');
        }

        $this->identityMap->add($entity->getId()->getId(), $entity);
    }

    private function guardLoginUnique(Entity\Admin $entity): void
    {
        foreach ($this->identityMap->all() as $item) {
            if ($entity->getId()->equalsTo($item->getId())) {
                continue;
            }

            if ($entity->getLogin() === $item->getLogin()) {
                throw new DuplicateKeyException('Admin with same login already exists');
            }
        }
    }

    public function update(Entity\Admin $entity): void
    {
        $this->guardLoginUnique($entity);
        if (!$this->identityMap->has($entity->getId()->getId())) {
            throw new NotFoundException('Admin does not exists');
        }
    }

    public function delete(Entity\Admin $entity): void
    {
        if (!$this->identityMap->has($entity->getId()->getId())) {
            throw new NotFoundException('Admin does not exists');
        }

        $this->identityMap->remove($entity->getId()->getId());
    }

    public function get(Entity\AdminId $id): Entity\Admin
    {
        if (empty($id->getId())) {
            throw new NotFoundException('Admin does not exists');
        }

        if (!$this->identityMap->has($id->getId())) {
            throw new NotFoundException('Admin does not exists');
        }

        return $this->identityMap->get($id->getId());
    }

    public function getByCredentials(string $login, string $password): Entity\Admin
    {
        foreach ($this->identityMap->all() as $admin) {
            if (($admin->getLogin() === $login) && ($admin->getPassword() === $password)) {
                return $admin;
            }
        }

        throw new NotFoundException('Admin does not exists');
    }
}
