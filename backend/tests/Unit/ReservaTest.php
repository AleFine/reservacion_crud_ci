<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Reserva;
use App\Models\Comensal;
use App\Models\Mesa;

class ReservaTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_reserva()
    {
        $comensal = Comensal::factory()->create();
        $mesa = Mesa::factory()->create(['capacidad' => 5]);
        $data = [
            'fecha' => now()->format('Y-m-d'),
            'hora' => '12:00:00',
            'numero_de_personas' => 3,
            'id_comensal' => $comensal->id_comensal,
            'id_mesa' => $mesa->id_mesa,
        ];

        $reserva = Reserva::create($data);

        $this->assertDatabaseHas('reservas', [
            'id_reserva' => $reserva->id_reserva,
            'id_comensal' => $comensal->id_comensal,
            'id_mesa' => $mesa->id_mesa,
        ]);
    }

    public function test_reserva_relations()
    {
        $reserva = Reserva::factory()->create();

        $this->assertInstanceOf(Comensal::class, $reserva->comensal);
        $this->assertInstanceOf(Mesa::class, $reserva->mesa);
    }
}
