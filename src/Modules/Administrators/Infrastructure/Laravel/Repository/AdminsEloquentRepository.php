<?php

namespace Project\Modules\Administrators\Infrastructure\Laravel\Repository;

use Project\Common\Administrators\Role;
use Illuminate\Contracts\Hashing\Hasher;
use Project\Common\Repository\IdentityMap;
use Project\Modules\Administrators\Entity;
use Project\Common\Entity\Hydrator\Hydrator;
use Project\Common\Repository\NotFoundException;
use Project\Common\Repository\DuplicateKeyException;
use Project\Modules\Administrators\Repository\AdminsRepositoryInterface;
use Project\Modules\Administrators\Infrastructure\Laravel\Models as Eloquent;

class AdminsEloquentRepository implements AdminsRepositoryInterface
{
    public function __construct(
        private readonly Hydrator $hydrator,
        private readonly IdentityMap $identityMap,
        private readonly Hasher $hasher,
    ) {}

    public function add(Entity\Admin $entity): void
    {
        $id = $entity->getId()->getId();
        if (!empty($id) && $this->identityMap->has($id)) {
            throw new DuplicateKeyException('Admin with same id already exists');
        }

        if (Eloquent\Administrator::find($id)) {
            throw new DuplicateKeyException('Admin with same id already exists');
        }

        $this->persist($entity, new Eloquent\Administrator);
        $this->identityMap->add($entity->getId()->getId(), $entity);
    }

    private function persist(Entity\Admin $entity, Eloquent\Administrator $record): void
    {
        $this->guardLoginUnique($entity);

        if (!empty($entity->getPassword())) {
            $record->hashPassword($this->hasher, $entity->getPassword());
        }

        $record->id = $entity->getId()->getId();
        $record->name = $entity->getName();
        $record->login = $entity->getLogin();
        $record->roles = array_column($entity->getRoles(), 'value');
        $record->save();
        $this->hydrator->hydrate($entity->getId(), ['id' => $record->id]);
    }

    private function guardLoginUnique(Entity\Admin $entity): void
    {
        $notUnique = Eloquent\Administrator::query()
            ->where('id', '!=', $entity->getId()->getId())
            ->where('login', $entity->getLogin())
            ->exists();

        if ($notUnique) {
            throw new DuplicateKeyException('Admin with same login already exists');
        }
    }

    public function update(Entity\Admin $entity): void
    {
        $id = $entity->getId()->getId();
        if (empty($id) || !$this->identityMap->has($id)) {
            throw new NotFoundException('Admin does not exists');
        }

        if (!$record = Eloquent\Administrator::find($id)) {
            throw new NotFoundException('Admin does not exists');
        }

        $this->persist($entity, $record);
    }

    public function delete(Entity\Admin $entity): void
    {
        $id = $entity->getId()->getId();
        if (empty($id) || !$this->identityMap->has($id)) {
            throw new NotFoundException('Admin does not exists');
        }

        if (!$record = Eloquent\Administrator::find($id)) {
            throw new NotFoundException('Admin does not exists');
        }

        $this->identityMap->remove($id);
        $record->delete();
    }

    public function get(Entity\AdminId $id): Entity\Admin
    {
        if (empty($id->getId())) {
            throw new NotFoundException('Admin does not exists');
        }

        if ($this->identityMap->has($id->getId())) {
            return $this->identityMap->get($id->getId());
        }

        if (!$record = Eloquent\Administrator::find($id->getId())) {
            throw new NotFoundException('Admin does not exists');
        }

        $entity = $this->hydrate($record);
        $this->identityMap->add($id->getId(), $entity);
        return $entity;
    }

    private function hydrate(Eloquent\Administrator $record): Entity\Admin
    {
        return $this->hydrator->hydrate(Entity\Admin::class, [
            'id' => new Entity\AdminId($record->id),
            'name' => $record->name,
            'login' => $record->login,
            'roles' => array_map(function (string $role) {
                return Role::from($role);
            }, $record->roles),
        ]);
    }

    public function getByCredentials(string $login, string $password): Entity\Admin
    {
        foreach ($this->identityMap->all() as $admin) {
            if (($admin->getLogin() === $login) && ($admin->getPassword() === $password)) {
                return $admin;
            }
        }

        $record = Eloquent\Administrator::query()
            ->where('login', $login)
            ->first();

        if (empty($record)) {
            throw new NotFoundException('Admin does not exists');
        }

        if (false === $this->hasher->check($password, $record->password)) {
            throw new NotFoundException('Admin does not exists');
        }

        $entity = $this->hydrate($record);
        $this->identityMap->add($entity->getId()->getId(), $entity);
        return $entity;
    }
}
