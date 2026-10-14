<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlayerToken extends Model
{
    protected $fillable = [
        'game_client_id',
        'user_id',
        'token_hash',
        'last_used_at',
        'expires_at',
        'revoked_at',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(GameClient::class, 'game_client_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function hashFor(string $plain): string
    {
        return hash('sha256', $plain);
    }

    public function isValid(): bool
    {
        return $this->revoked_at === null && $this->expires_at->isFuture();
    }
}
