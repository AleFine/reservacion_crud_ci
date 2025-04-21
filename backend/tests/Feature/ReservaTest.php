<?php

namespace Tests\Feature;

use App\Models\Comensal;
use App\Models\Mesa;
use App\Models\Reserva;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ReservaTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function puede_crear_una_reserva_valida()
    {
        $comensal = Comensal::factory()->create();
        $mesa = Mesa::factory()->create(['capacidad' => 4]);

        $data = [
            'fecha' => Carbon::tomorrow()->format('Y-m-d'),
            'hora' => '20:00',
            'numero_de_personas' => 2,
            'id_comensal' => $comensal->id_comensal,
            'id_mesa' => $mesa->id_mesa
        ];

        $response = $this->postJson('/api/reservas', $data);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id_reserva', 
                    'fecha', 
                    'hora'
                ]
            ]);
    }

    #[Test]
    public function validacion_capacidad_mesa_insuficiente()
    {
        $comensal = Comensal::factory()->create();
        $mesa = Mesa::factory()->create(['capacidad' => 2]);

        $response = $this->postJson('/api/reservas', [
            'fecha' => Carbon::tomorrow()->format('Y-m-d'),
            'hora' => '20:00',
            'numero_de_personas' => 4,
            'id_comensal' => $comensal->id_comensal,
            'id_mesa' => $mesa->id_mesa
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'La capacidad de la mesa es insuficiente'
            ]);
    }

    #[Test]
    public function validacion_conflicto_horario()
    {
        $comensal = Comensal::factory()->create();
        $mesa = Mesa::factory()->create(['capacidad' => 4]);
        
        $fechaValida = now()->format('Y-m-d');
        $hora = '14:00';
        
        // crea una primera reserva y verifica si existe
        $reservaExistente = Reserva::create([
            'fecha' => $fechaValida,
            'hora' => $hora,
            'numero_de_personas' => 2,
            'id_comensal' => $comensal->id_comensal,
            'id_mesa' => $mesa->id_mesa
        ]);
        
        $this->assertDatabaseHas('reservas', [
            'id_mesa' => $mesa->id_mesa,
            'fecha' => $fechaValida,
            'hora' => $hora
        ]);
        
        // reserva duplicada
        $response = $this->postJson('/api/reservas', [
            'fecha' => $fechaValida,
            'hora' => $hora,
            'id_mesa' => $mesa->id_mesa,
            'numero_de_personas' => 2,
            'id_comensal' => $comensal->id_comensal
        ]);
        
        $response->assertStatus(409)
            ->assertJson([
                'message' => 'La mesa ya está reservada en esta fecha y hora'
            ]);
    }

    #[Test]
    public function puede_filtrar_reservas_por_fecha()
    {
        Reserva::factory()->create(['fecha' => '2024-01-01']);
        Reserva::factory()->create(['fecha' => '2024-01-02']);

        $response = $this->getJson('/api/reservas?date=2024-01-01');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    #[Test]
    public function puede_actualizar_reserva()
    {
        $reserva = Reserva::factory()->create();
        $nuevaMesa = Mesa::factory()->create(['capacidad' => 6]);

        $updateData = [
            'id_mesa' => $nuevaMesa->id_mesa,
            'numero_de_personas' => 5
        ];

        $response = $this->putJson("/api/reservas/{$reserva->id_reserva}", $updateData);

        $response->assertOk()
            ->assertJsonFragment($updateData);
    }

    #[Test]
    public function no_puede_actualizar_reserva_con_capacidad_insuficiente()
    {
        $reserva = Reserva::factory()->create();
        $nuevaMesa = Mesa::factory()->create(['capacidad' => 2]);

        $response = $this->putJson("/api/reservas/{$reserva->id_reserva}", [
            'id_mesa' => $nuevaMesa->id_mesa,
            'numero_de_personas' => 3
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'La capacidad de la mesa es insuficiente'
            ]);
    }

    #[Test]
    public function puede_eliminar_reserva()
    {
        $reserva = Reserva::factory()->create();

        $response = $this->deleteJson("/api/reservas/{$reserva->id_reserva}");

        $response->assertOk()
            ->assertJson(['message' => 'Reserva eliminada correctamente']);
    }
}