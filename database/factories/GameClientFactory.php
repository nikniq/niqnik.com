<?php

namespace Database\Factories;

use App\Models\GameClient;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class GameClientFactory extends Factory
{
    protected $model = GameClient::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => ucwords($name),
            'slug' => str($name)->slug()->toString(),
            'homepage_url' => 'https://'.str($name)->slug().'.nikniq.test',
            'client_id' => GameClient::generateClientId(),
            'client_secret' => Hash::make('secret-'.$name),
            'redirect_uris' => ['https://'.str($name)->slug().'.nikniq.test/auth/callback'],
            'is_public' => false,
            'is_enabled' => true,
        ];
    }

    public function public(): static
    {
        return $this->state(fn () => ['is_public' => true]);
    }

    public function disabled(): static
    {
        return $this->state(fn () => ['is_enabled' => false]);
    }
}
