<?php

namespace Project\Tests\Laravel\Modules\Client\Repository;

use Project\Common\Repository\IdentityMap;
use Project\Common\Entity\Hydrator\Hydrator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Project\Modules\Client\Entity\Confirmation\DigitCodeGenerator;
use Project\Tests\Unit\Modules\Client\Repository\ClientsRepositoryTestTrait;
use Project\Modules\Client\Infrastructure\Laravel\Repository\ClientsEloquentRepository;

class ClientsEloquentRepositoryTest extends \Project\Tests\Laravel\TestCase
{
    use ClientsRepositoryTestTrait, RefreshDatabase;

    protected function setUp(): void
    {
        $this->clients = new ClientsEloquentRepository(new Hydrator, new IdentityMap);
        $this->codeGenerator = new DigitCodeGenerator;
        parent::setUp();
    }
}
