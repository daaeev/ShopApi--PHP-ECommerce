<?php

namespace Project\Modules\Administrators\Repository;

use Project\Modules\Administrators\Entity;

interface AdminsRepositoryInterface
{
    public function add(Entity\Admin $entity): void;

    public function update(Entity\Admin $entity): void;

    public function delete(Entity\Admin $entity): void;

    public function get(Entity\AdminId $id): Entity\Admin;

    public function getByCredentials(string $login, string $password): Entity\Admin;
}