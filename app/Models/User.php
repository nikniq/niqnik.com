<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'admin_email',
        'provider_name',
        'provider_id',
        'avatar',
        'email_verified_at',
        'password',
        'is_admin',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    public function favourites()
    {
        return $this->hasMany(Favourite::class);
    }

    /**
     * OAuth-only accounts have no password until the user sets one.
     */
    public function hasPassword(): bool
    {
        return ! empty($this->password);
    }

    public function hasLinkedProvider(): bool
    {
        return ! empty($this->provider_name) && ! empty($this->provider_id);
    }

    public function providerLabel(): ?string
    {
        return match ($this->provider_name) {
            'google' => 'Google',
            'meta', 'facebook' => 'Meta',
            null, '' => null,
            default => ucfirst($this->provider_name),
        };
    }
}
