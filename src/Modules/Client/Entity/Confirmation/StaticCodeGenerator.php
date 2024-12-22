<?php

namespace Project\Modules\Client\Entity\Confirmation;

class StaticCodeGenerator implements CodeGeneratorInterface
{
    public function generate(): int|string
    {
        return '0000';
    }
}