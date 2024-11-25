<?php

namespace Database\Seeders;

use App\Models\Court;
use Illuminate\Database\Seeder;

class CourtSeeder extends Seeder
{
    public function run()
    {
        Court::factory()->count(10)->create([
            'available' => true,
        ]);
    }
}
