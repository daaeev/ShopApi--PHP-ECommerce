<?php

namespace Project\Tests\Unit\Configuration;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Project\Common\Services\Configuration\ApplicationConfiguration;

class ApplicationConfigurationTest extends TestCase
{
    public function testGetConfigurationValues()
    {
        $initConfiguration = [
            'client-hash-cookie-name' => 'test',
            'client-hash-cookie-length' => random_int(1, 10),
            'client-hash-cookie-lifetime-in-minutes' => random_int(1, 10),
        ];

        $configuration = new ApplicationConfiguration($initConfiguration);
        $this->assertSame(
            $initConfiguration['client-hash-cookie-name'],
            $configuration->getClientHashCookieName()
        );

        $this->assertSame(
            $initConfiguration['client-hash-cookie-length'],
            $configuration->getClientHashCookieLength()
        );

        $this->assertSame(
            $initConfiguration['client-hash-cookie-lifetime-in-minutes'],
            $configuration->getClientHashCookieLifeTimeInMinutes()
        );
    }

    public function testGetConfigurationDefaultValues()
    {
        $initConfiguration = [
            'client-hash-cookie-name' => null,
            'client-hash-cookie-length' => null,
            'client-hash-cookie-lifetime-in-minutes' => null,
        ];

        $configuration = new ApplicationConfiguration($initConfiguration);
        $this->assertSame(
            'defaultName',
            $configuration->getClientHashCookieName('defaultName')
        );

        $this->assertSame(
            $defaultLength = random_int(1, 10),
            $configuration->getClientHashCookieLength($defaultLength)
        );

        $this->assertSame(
            $defaultLifeTime = random_int(1, 10),
            $configuration->getClientHashCookieLifeTimeInMinutes($defaultLifeTime)
        );
    }

    #[DataProvider('configurationsWithoutRequiredVariables')]
    public function testCreateConfigurationWithoutRequiredVariables(array $config)
    {
        $this->expectException(\InvalidArgumentException::class);
        new ApplicationConfiguration($config);
    }

    public static function configurationsWithoutRequiredVariables()
    {
        return [
            [
                [
                    'client-hash-cookie-length' => random_int(1, 10),
                    'client-hash-cookie-lifetime-in-minutes' => random_int(1, 10),
                ],
            ],
            [
                [
                    'client-hash-cookie-name' => 'test',
                    'client-hash-cookie-lifetime-in-minutes' => random_int(1, 10),
                ],
            ],
            [
                [
                    'client-hash-cookie-name' => 'test',
                    'client-hash-cookie-length' => random_int(1, 10),
                ],
            ],
        ];
    }
}