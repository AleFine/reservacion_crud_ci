<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Reserva;
use App\Models\Comensal;
use App\Models\Mesa;

class ReservaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $comensales = Comensal::all();
        $mesas = Mesa::all();
        $numeroDeReservas = 30;
        
        if ($comensales->count() > 0 && $mesas->count() > 0) {
            for ($i = 0; $i < $numeroDeReservas; $i++) {
                Reserva::create([
                    'fecha' => fake()->dateTimeBetween('now', '+30 days')->format('Y-m-d'),
                    'hora' => fake()->time('H:i:00'),
                    'numero_de_personas' => fake()->numberBetween(1, 4),
                    'id_comensal' => $comensales->random()->id_comensal,
                    'id_mesa' => $mesas->random()->id_mesa,
                ]);
            }
        }
    }
}
