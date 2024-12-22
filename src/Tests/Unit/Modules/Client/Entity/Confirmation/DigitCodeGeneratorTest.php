<?php

namespace Project\Tests\Unit\Modules\Client\Entity\Confirmation;

use PHPUnit\Framework\TestCase;
use Project\Modules\Client\Entity\Confirmation\DigitCodeGenerator;
use Project\Modules\Client\Entity\Confirmation\CodeGeneratorInterface;

class DigitCodeGeneratorTest extends TestCase
{
    private readonly CodeGeneratorInterface $generator;

    protected function setUp(): void
    {
        $this->generator = new DigitCodeGenerator;
    }

    public function testGenerateCode()
    {
        $code = $this->generator->generate();
        $this->assertGreaterThanOrEqual(1000, $code);
        $this->assertLessThanOrEqual(9999, $code);
    }

    public function testDoesNotGenerateSameCode()
    {
        $this->assertNotSame($this->generator->generate(), $this->generator->generate());
    }
}