<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Comensal;
use App\Models\Reserva;

class ComensalTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_comensal()
    {
        $data = [
            'nombre' => 'Juan Perez',
            'correo' => 'juan@example.com',
            'telefono' => '123456789',
            'direccion' => 'Calle Falsa 123',
        ];

        $comensal = Comensal::create($data);

        $this->assertDatabaseHas('comensales', [
            'id_comensal' => $comensal->id_comensal,
            'correo' => 'juan@example.com',
        ]);
    }

    public function test_comensal_has_many_reservas()
    {
        $comensal = Comensal::factory()->create();
        $reservas = Reserva::factory()->count(2)->create(['id_comensal' => $comensal->id_comensal]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $comensal->reservas);
        $this->assertCount(2, $comensal->reservas);
    }
}
