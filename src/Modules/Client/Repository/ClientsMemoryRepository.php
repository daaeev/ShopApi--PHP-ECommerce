<?php

namespace Project\Modules\Client\Repository;

use Project\Modules\Client\Entity;
use Project\Common\Repository\IdentityMap;
use Project\Common\Entity\Hydrator\Hydrator;
use Project\Common\Repository\NotFoundException;
use Project\Common\Repository\DuplicateKeyException;

class ClientsMemoryRepository implements ClientsRepositoryInterface
{
    private int $increment = 0;

    public function __construct(
        private Hydrator $hydrator,
        private IdentityMap $identityMap
    ) {}

    public function add(Entity\Client $client): void
    {
        $this->guardContactsUnique($client);

        if (null === $client->getId()->getId()) {
            $this->hydrator->hydrate($client->getId(), ['id' => ++$this->increment]);
        }

        if ($this->identityMap->has($client->getId()->getId())) {
            throw new DuplicateKeyException('Client with same id already exists');
        }

        $this->identityMap->add($client->getId()->getId(), $client);
    }

    private function guardContactsUnique(Entity\Client $client): void
    {
        if (empty($client->getContacts()->getPhone())) {
            return;
        }

        foreach ($this->identityMap->all() as $item) {
            if ($client->getId()->equalsTo($item->getId())) {
                continue;
            }

            if ($client->getContacts()->getPhone() === $item->getContacts()->getPhone()) {
                throw new DuplicateKeyException('Client with same phone already exists');
            }
        }
    }

    public function update(Entity\Client $client): void
    {
        $this->guardContactsUnique($client);
        if (!$this->identityMap->has($client->getId()->getId())) {
            throw new NotFoundException('Client does not exists');
        }
    }

    public function delete(Entity\Client $client): void
    {
        if (!$this->identityMap->has($client->getId()->getId())) {
            throw new NotFoundException('Client does not exists');
        }

        $this->identityMap->remove($client->getId()->getId());
    }

    public function get(Entity\ClientId $id): Entity\Client
    {
        if (empty($id->getId())) {
            throw new NotFoundException('Client does not exists');
        }

        if ($this->identityMap->has($id->getId())) {
            return $this->identityMap->get($id->getId());
        }

        throw new NotFoundException('Client does not exists');
    }

    public function getByPhone(string $phone): Entity\Client
    {
        $identityMapClients = $this->identityMap->all();
        foreach ($identityMapClients as $client) {
            if ($phone === $client->getContacts()->getPhone()) {
                return $client;
            }
        }

        throw new NotFoundException('Client does not exists');
    }

    public function getByConfirmation(Entity\Confirmation\ConfirmationUuid $confirmationUuid): Entity\Client
    {
        $identityMapClients = $this->identityMap->all();
        foreach ($identityMapClients as $client) {
            if ($client->hasConfirmation($confirmationUuid)) {
                return $client;
            }
        }

        throw new NotFoundException('Client does not exists');
    }
}
