<?php

namespace Database\Factories;

use App\Models\Server;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Server>
 */
class ServerFactory extends Factory
{
    protected $model = Server::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company . ' Server',
            'hostname' => $this->faker->unique()->domainName,
            'status' => $this->faker->randomElement(Server::STATUSES),
            'environment' => $this->faker->randomElement(['production', 'staging', 'development']),
            'last_seen_at' => $this->faker->optional()->dateTimeBetween('-2 days'),
            'notes' => $this->faker->optional()->sentence(),
            'meta' => ['os' => $this->faker->randomElement(['linux', 'windows'])],
        ];
    }
}
