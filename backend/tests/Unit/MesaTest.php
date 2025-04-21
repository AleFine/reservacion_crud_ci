<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Mesa;
use App\Models\Reserva;

class MesaTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_mesa()
    {
        $data = [
            'numero_mesa' => 'M01',
            'capacidad' => 4,
            'ubicacion' => 'Interior',
        ];

        $mesa = Mesa::create($data);

        $this->assertDatabaseHas('mesas', [
            'id_mesa' => $mesa->id_mesa,
            'numero_mesa' => 'M01',
        ]);
    }

    public function test_mesa_has_many_reservas()
    {
        $mesa = Mesa::factory()->create();
        $reservas = Reserva::factory()->count(2)->create(['id_mesa' => $mesa->id_mesa]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $mesa->reservas);
        $this->assertCount(2, $mesa->reservas);
    }
}
