<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'game_client_id',
        'user_id',
        'event',
        'score',
        'level',
        'duration_seconds',
        'won',
        'metadata',
        'occurred_at',
    ];

    protected $casts = [
        'score' => 'integer',
        'duration_seconds' => 'integer',
        'won' => 'boolean',
        'metadata' => 'array',
        'occurred_at' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(GameClient::class, 'game_client_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
