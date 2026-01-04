<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Media;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'product_code',
        'vendor',
        'url',
        'media_id',
        'category',
        'description',
        'price',
        'duration_months',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'duration_months' => 'integer',
    ];

    public function licenses()
    {
        return $this->hasMany(License::class);
    }

    public function media()
    {
        return $this->belongsTo(Media::class);
    }

    public function favourites()
    {
        return $this->morphMany(Favourite::class, 'favoritable');
    }

    public function isFavoritedBy(?\App\Models\User $user): bool
    {
        if (! $user) return false;
        return $this->favourites()->where('user_id', $user->id)->exists();
    }
}
