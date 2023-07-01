<?php

namespace Project\Tests\Unit\Modules\Helpers;

use Project\Modules\Catalogue\Categories\Entity\Category;
use Project\Modules\Catalogue\Categories\Entity\CategoryId;

trait CategoryFactory
{
    private function makeCategory(
        CategoryId $id,
        string $name,
        string $slug,
    ): Category {
        return new Category(
            $id,
            $name,
            $slug,
        );
    }

    private function generateCategory(): Category
    {
        $category = new Category(
            CategoryId::random(),
            md5(rand()),
            md5(rand()),
        );
        $category->flushEvents();
        return $category;
    }
}