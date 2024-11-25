<?php

namespace Database\Seeders;

use App\Models\Komunitas;
use Illuminate\Database\Seeder;

class KomunitasSeeder extends Seeder
{
    public function run()
    {
        Komunitas::factory()->count(5)->create()->each(function ($komunitas) {
            $komunitas->members()->attach(random_int(1, 5), ['status' => 'accepted']);
            $komunitas->increment('jumlah_anggota', 1);
        });
    }
}
