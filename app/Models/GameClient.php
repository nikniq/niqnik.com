<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class GameClient extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'product_id',
        'homepage_url',
        'client_id',
        'client_secret',
        'redirect_uris',
        'is_public',
        'is_enabled',
        'notes',
    ];

    protected $hidden = ['client_secret'];

    protected $casts = [
        'redirect_uris' => 'array',
        'is_public' => 'boolean',
        'is_enabled' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function tokens()
    {
        return $this->hasMany(PlayerToken::class);
    }

    public function events()
    {
        return $this->hasMany(GameEvent::class);
    }

    public function stats()
    {
        return $this->hasMany(PlayerGameStat::class);
    }

    public static function generateClientId(): string
    {
        return Str::lower(Str::random(32));
    }

    public static function generateSecret(): string
    {
        return Str::random(48);
    }

    public function setPlainSecret(string $plain): void
    {
        $this->client_secret = Hash::make($plain);
    }

    public function verifySecret(?string $plain): bool
    {
        return is_string($plain) && $plain !== '' && Hash::check($plain, $this->client_secret);
    }

    public function redirectAllowed(string $uri): bool
    {
        return in_array($uri, $this->redirect_uris ?? [], true);
    }

    public function playUrl(): ?string
    {
        return $this->homepage_url ?: $this->product?->url;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
