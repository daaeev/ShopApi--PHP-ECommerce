<?php

namespace Project\Tests\Unit\Modules\Client\Entity\Confirmation;

use PHPUnit\Framework\TestCase;
use Project\Modules\Client\Entity\Confirmation\StaticCodeGenerator;
use Project\Modules\Client\Entity\Confirmation\CodeGeneratorInterface;

class StaticCodeGeneratorTest extends TestCase
{
    private readonly CodeGeneratorInterface $generator;

    protected function setUp(): void
    {
        $this->generator = new StaticCodeGenerator;
    }

    public function testGenerateCode()
    {
        $this->assertSame('0000', $this->generator->generate());
    }
}