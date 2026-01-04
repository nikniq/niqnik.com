<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class GamesSeeder extends Seeder
{
    public function run(): void
    {
        $category = config('games.category', env('GAMES_CATEGORY', 'Game'));

        Product::insertOrIgnore([
            [
                'name' => 'Space Runner',
                'product_code' => 'GAME-SPR-01',
                'vendor' => 'External',
                'category' => $category,
                'description' => 'A fast-paced endless runner set among asteroids and drifting satellites.',
                'price' => 0.00,
                'duration_months' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Puzzle Grove',
                'product_code' => 'GAME-PZG-01',
                'vendor' => 'External',
                'category' => $category,
                'description' => 'Relaxing puzzle challenges with handcrafted levels and soothing visuals.',
                'price' => 0.00,
                'duration_months' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Neon Racers',
                'product_code' => 'GAME-NRC-01',
                'vendor' => 'External',
                'category' => $category,
                'description' => 'Retro-style top-down racing with tight controls and online leaderboards.',
                'price' => 0.00,
                'duration_months' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
