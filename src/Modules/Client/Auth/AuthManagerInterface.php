<?php

namespace Project\Modules\Client\Auth;

use Project\Modules\Client\Entity\Client;
use Project\Modules\Client\Entity\Access\Access;

interface AuthManagerInterface
{
    public function authorize(Access $access): void;

    public function logout(): void;

    public function logged(): ?Client;
}