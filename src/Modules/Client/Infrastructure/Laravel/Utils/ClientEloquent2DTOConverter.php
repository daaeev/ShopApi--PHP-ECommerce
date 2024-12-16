<?php

namespace Project\Modules\Client\Infrastructure\Laravel\Utils;

use Project\Modules\Client\Api\DTO;
use Project\Modules\Client\Infrastructure\Laravel\Models as Eloquent;

class ClientEloquent2DTOConverter
{
    public static function convert(Eloquent\Client $record): DTO\Client
    {
        return new DTO\Client(
            $record->id,
            $record->firstname,
            $record->lastname,
            $record->phone,
            $record->email,
            $record->phone_confirmed,
            $record->email_confirmed,
            new \DateTimeImmutable($record->created_at),
            $record->updatedAt ? new \DateTimeImmutable($record->updated_at) : null
        );
    }
}