<?php

namespace Project\Common\Services\Configuration;

use Webmozart\Assert\Assert;

class ApplicationConfiguration
{
    public function __construct(
        private readonly array $config
    ) {
        Assert::keyExists($this->config, 'client-hash-cookie-name');
        Assert::keyExists($this->config, 'client-hash-cookie-length');
        Assert::keyExists($this->config, 'client-hash-cookie-lifetime-in-minutes');
    }

    public function getClientHashCookieName(string $default = 'clientHash'): string
    {
        if (empty($this->config['client-hash-cookie-name'])) {
            return $default;
        }

        return $this->config['client-hash-cookie-name'];
    }

    public function getClientHashCookieLength(int $default = 40): int
    {
        if (empty($this->config['client-hash-cookie-length'])) {
            return $default;
        }

        return $this->config['client-hash-cookie-length'];
    }

    public function getClientHashCookieLifeTimeInMinutes(int $default = 1440): int
    {
        if (empty($this->config['client-hash-cookie-lifetime-in-minutes'])) {
            return $default;
        }

        return $this->config['client-hash-cookie-lifetime-in-minutes'];
    }
}