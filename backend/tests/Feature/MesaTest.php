<?php

namespace Tests\Feature;

use App\Models\Mesa;
use PHPUnit\Framework\Attributes\Test;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MesaTest extends TestCase
{
    use RefreshDatabase;

    
    #[Test]
    public function puede_obtener_lista_paginada_de_mesas()
    {
        Mesa::factory()->count(20)->create();

        $response = $this->getJson('/api/mesas');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id_mesa', 'numero_mesa', 'capacidad', 'ubicacion']
                ],
                'links',
                'meta'
            ]);
    }

    #[Test]
    public function puede_filtrar_mesas_por_term_busqueda()
    {
        $mesa1 = Mesa::factory()->create(['numero_mesa' => 'A1', 'ubicacion' => 'terraza']);
        $mesa2 = Mesa::factory()->create(['numero_mesa' => 'B2', 'ubicacion' => 'interior']);

        $response = $this->getJson('/api/mesas?searchTerm=terraza');
        
        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['numero_mesa' => 'A1']);
    }

    #[Test]
    public function puede_crear_una_mesa()
    {
        $data = [
            'numero_mesa' => 'M1',
            'capacidad' => 4,
            'ubicacion' => 'Terraza'
        ];

        $response = $this->postJson('/api/mesas', $data);
        
        $response->assertCreated()
            ->assertJsonFragment($data);
    }

    #[Test]
    public function validacion_numero_mesa_duplicado()
    {
        Mesa::factory()->create(['numero_mesa' => 'M1']);

        $response = $this->postJson('/api/mesas', [
            'numero_mesa' => 'M1',
            'capacidad' => 4
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['numero_mesa']);
    }

    #[Test]
    public function puede_actualizar_una_mesa()
    {
        $mesa = Mesa::factory()->create();

        $updateData = [
            'numero_mesa' => 'M2',
            'capacidad' => 6
        ];

        $response = $this->putJson("/api/mesas/{$mesa->id_mesa}", $updateData);

        $response->assertOk()
            ->assertJsonFragment($updateData);
    }

    #[Test]
    public function no_puede_eliminar_mesas_con_reservas()
    {
        $mesa = Mesa::factory()->hasReservas(1)->create();

        $response = $this->deleteJson("/api/mesas/{$mesa->id_mesa}");

        $response->assertConflict()
            ->assertJson([
                'message' => 'No se puede eliminar la mesa porque tiene reservas asociadas'
            ]);
            
        $this->assertDatabaseHas('mesas', ['id_mesa' => $mesa->id_mesa]);
    }

    #[Test]
    public function puede_eliminar_mesa_sin_reservas()
    {
        $mesa = Mesa::factory()->create();

        $response = $this->deleteJson("/api/mesas/{$mesa->id_mesa}");

        $response->assertOk()
            ->assertJson(['message' => 'Mesa eliminada correctamente']);
    }
}