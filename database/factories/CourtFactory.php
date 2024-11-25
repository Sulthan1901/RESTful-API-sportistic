<?php

namespace Database\Factories;

use App\Models\Court;
use Illuminate\Database\Eloquent\Factories\Factory;

class CourtFactory extends Factory
{
  protected $model = Court::class;

  public function definition()
  {
    return [
      'name' => $this->faker->company,
      'type' => $this->faker->randomElement(['futsal', 'badminton', 'basketball']),
      'location' => $this->faker->address,
      'facilities' => $this->faker->words(3),
      'price_per_hour' => $this->faker->randomFloat(2, 50, 200),
      'available' => $this->faker->boolean(80),
    ];
  }
}
