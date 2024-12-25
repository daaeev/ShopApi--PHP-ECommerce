<?php

namespace Project\Modules\Client\Repository;

use Project\Modules\Client\Entity;

interface ClientsRepositoryInterface
{
    public function add(Entity\Client $client): void;

    public function update(Entity\Client $client): void;

    public function delete(Entity\Client $client): void;

    public function get(Entity\ClientId $id): Entity\Client;

    public function getByPhone(string $phone): Entity\Client;

    public function getByConfirmation(Entity\Confirmation\ConfirmationUuid $confirmationUuid): Entity\Client;

    public function getByAccess(Entity\Access\Access $access): Entity\Client;
}