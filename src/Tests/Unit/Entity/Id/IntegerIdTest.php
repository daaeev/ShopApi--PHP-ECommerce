<?php

namespace Project\Tests\Unit\Entity\Id;

use Project\Common\Entity\Id\IntegerId;
use TypeError;

class IntegerIdTest extends \PHPUnit\Framework\TestCase
{
    public function testCreate()
    {
        $id = new IntegerId(23);

        $this->assertEquals(23, $id->id);
        $this->assertTrue($id->equalsTo(new IntegerId(23)));
        $this->assertfalse($id->equalsTo(new IntegerId(1)));

        $nullableId = new IntegerId(null);

        $this->assertNull($nullableId->id);
        $this->assertFalse($nullableId->equalsTo(new IntegerId(null)));
        $this->assertFalse($nullableId->equalsTo(new IntegerId(1)));

        $nextId = IntegerId::next();

        $this->assertNull($nextId->id);

        $randomId = IntegerId::random();

        $this->assertIsInt($randomId->id);
    }

    public function testCreateWithNotIntegerIdValue()
    {
        $this->expectException(TypeError::class);
        new IntegerId('Invalid argument');
    }
}