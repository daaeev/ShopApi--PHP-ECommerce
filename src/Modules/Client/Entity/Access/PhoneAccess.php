<?php

namespace Project\Modules\Client\Entity\Access;

use Project\Common\Utils\ContactsValidator;

class PhoneAccess extends Access
{
    public function __construct(
        private readonly string $phone
    ) {
        ContactsValidator::validatePhone($this->phone);
        parent::__construct();
    }

    public function getType(): AccessType
    {
        return AccessType::PHONE;
    }

    public function getCredentials(): array
    {
        return ['phone' => $this->phone];
    }
}