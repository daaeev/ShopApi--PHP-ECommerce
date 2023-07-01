<?php

namespace Project\Tests\Unit\Modules\Categories\Entity;

use Webmozart\Assert\InvalidArgumentException;
use Project\Tests\Unit\Modules\Helpers\AssertEvents;
use Project\Tests\Unit\Modules\Helpers\CategoryFactory;
use Project\Modules\Catalogue\Api\Events\Category\CategoryUpdated;

class NameTest extends \PHPUnit\Framework\TestCase
{
    use CategoryFactory, AssertEvents;

    public function testUpdateName()
    {
        $category = $this->generateCategory();
        $this->assertNull($category->getUpdatedAt());
        $updatedName = md5(rand());
        $this->assertNotSame($updatedName, $category->getName());
        $category->updateName($updatedName);
        $this->assertNotNull($category->getUpdatedAt());
        $this->assertSame($updatedName, $category->getName());
        $this->assertEvents($category, [new CategoryUpdated($category)]);
    }

    public function testUpdateNameToSame()
    {
        $category = $this->generateCategory();
        $this->assertNull($category->getUpdatedAt());
        $updatedName = $category->getName();
        $this->assertSame($updatedName, $category->getName());
        $category->updateName($updatedName);
        $this->assertNull($category->getUpdatedAt());
        $this->assertSame($updatedName, $category->getName());
        $this->assertEmpty($category->flushEvents());
    }

    public function testUpdateNameToEmpty()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->generateCategory()->updateName('');
    }
}