<?php

namespace Project\Modules\Product\Utils;

use Project\Modules\Product\Entity;
use Project\Modules\Product\Api\DTO;

class Entity2DTOConverter
{
    public static function convert(Entity\Product $entity): DTO\Product
    {
        return new DTO\Product(
            $entity->getId()->getId(),
            $entity->getName(),
            $entity->getCode(),
            $entity->isActive(),
            $entity->getAvailability()->value,
            array_map(function (Entity\Color\Color $color) {
                return $color->getColor();
            }, $entity->getColors()),
            array_map(function (Entity\Size\Size $size) {
                return $size->value;
            }, $entity->getSizes()),
            array_map(function (Entity\Price\Price $price) {
                return [
                    'currency' => $price->getCurrency()->value,
                    'value' => $price->getPrice()
                ];
            }, $entity->getPrices()),
        );
    }
}