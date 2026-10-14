<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SsoCode extends Model
{
    protected $fillable = [
        'game_client_id',
        'user_id',
        'code_hash',
        'redirect_uri',
        'code_challenge',
        'expires_at',
        'used_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
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

    public function isUsable(): bool
    {
        return $this->used_at === null && $this->expires_at->isFuture();
    }

    /**
     * Verify a PKCE code_verifier against the stored S256 challenge.
     */
    public function verifierMatches(?string $verifier): bool
    {
        if ($this->code_challenge === null) {
            return true;
        }
        if (! is_string($verifier) || $verifier === '') {
            return false;
        }
        $computed = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');

        return hash_equals($this->code_challenge, $computed);
    }
}
