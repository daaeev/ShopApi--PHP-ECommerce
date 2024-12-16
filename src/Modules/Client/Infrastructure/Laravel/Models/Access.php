<?php

namespace Project\Modules\Client\Infrastructure\Laravel\Models;

use Illuminate\Database\Eloquent\Model;
use Project\Modules\Client\Entity\Access\AccessType;

class Access extends Model
{
    protected $table = 'clients_accesses';

    protected $fillable = [
        'client_id',
        'type',
        'credentials',
        'created_at',
    ];

    protected $casts = [
        'type' => AccessType::class,
        'credentials' => 'array',
        'created_at' => 'datetime',
    ];
}