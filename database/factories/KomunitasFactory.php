<?php

namespace Database\Factories;

use App\Models\Komunitas;
use Illuminate\Database\Eloquent\Factories\Factory;

class KomunitasFactory extends Factory
{
  protected $model = Komunitas::class;

  public function definition()
  {
    return [
      'nama_komunitas' => $this->faker->unique()->company,
      'deskripsi_komunitas' => $this->faker->sentence,
      'jumlah_anggota' => 0,
      'creator_id' => 1, // Replace with actual user ID
    ];
  }
}
