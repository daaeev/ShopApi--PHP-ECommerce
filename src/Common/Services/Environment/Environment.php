<?php

namespace Project\Common\Services\Environment;

class Environment
{
    public function __construct(
        private readonly Client $client,
        private readonly ?Administrator $admin,
        private readonly Language $language,
    ) {}

    public function getClient(): Client
    {
        return $this->client;
    }

    public function getAdministrator(): ?Administrator
    {
        return $this->admin;
    }

    public function getLanguage(): Language
    {
        return $this->language;
    }
}