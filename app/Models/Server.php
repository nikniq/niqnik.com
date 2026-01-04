<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Server extends Model
{
    use HasFactory;

    public const STATUS_ONLINE = 'online';
    public const STATUS_MAINTENANCE = 'maintenance';
    public const STATUS_OFFLINE = 'offline';

    public const STATUSES = [
        self::STATUS_ONLINE,
        self::STATUS_MAINTENANCE,
        self::STATUS_OFFLINE,
    ];

    protected $fillable = [
        'name',
        'hostname',
        'status',
        'environment',
        'last_seen_at',
        'notes',
        'meta',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
        'meta' => 'array',
    ];
}
