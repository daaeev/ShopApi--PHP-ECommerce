<?php

namespace Project\Modules\Client\Queries\Handlers;

use Project\Modules\Client\Auth\AuthManagerInterface;
use Project\Modules\Client\Utils\ClientEntity2DTOConverter;
use Project\Modules\Client\Queries\GetAuthenticatedClientQuery;

class GetAuthenticatedClientHandler
{
    public function __construct(
        private AuthManagerInterface $authManager,
    ) {}

    public function __invoke(GetAuthenticatedClientQuery $query): array
    {
        $logged = $this->authManager->logged();
        if (null === $logged) {
            throw new \DomainException('You must be authenticated');
        }

        return ClientEntity2DTOConverter::convert($logged)->toArray();
    }
}