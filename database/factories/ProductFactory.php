<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->company.' Platform',
            'product_code' => strtoupper($this->faker->unique()->lexify('PRD-????')),
            'vendor' => $this->faker->company,
            'category' => $this->faker->randomElement(['Analytics', 'Security', 'Data', 'Productivity']),
            'description' => $this->faker->sentence(10),
            'price' => $this->faker->numberBetween(20, 120),
            'duration_months' => $this->faker->randomElement([6, 12, 18, 24, 36]),
        ];
    }
}
