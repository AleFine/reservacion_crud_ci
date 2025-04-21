<?php

namespace Tests\Feature;

use App\Models\Comensal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ComensalTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function puede_obtener_lista_paginada_de_comensales()
    {
        Comensal::factory()->count(20)->create();

        $response = $this->getJson('/api/comensales');
        
        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id_comensal', 'nombre', 'correo']
                ],
                'links',
                'meta'
            ]);
    }

    #[Test]
    public function puede_crear_un_comensal()
    {
        $data = [
            'nombre' => 'Juan Pérez',
            'correo' => 'juan@example.com',
            'telefono' => '+5491122334455'
        ];

        $response = $this->postJson('/api/comensales', $data);

        $response->assertCreated()
            ->assertJsonFragment($data);
    }

    #[Test]
    public function validacion_para_correo_duplicado()
    {
        Comensal::factory()->create(['correo' => 'existente@example.com']);

        $response = $this->postJson('/api/comensales', [
            'nombre' => 'Test',
            'correo' => 'existente@example.com'
        ]);

        $response->assertUnprocessable() 
            ->assertJsonValidationErrors(['correo'])
            ->assertJsonValidationErrorFor('correo');
    }

    #[Test]
    public function puede_actualizar_un_comensal()
    {
        $comensal = Comensal::factory()->create();

        $updateData = [
            'nombre' => 'Nombre Actualizado',
            'correo' => 'nuevo@example.com'
        ];

        $response = $this->putJson("/api/comensales/{$comensal->id_comensal}", $updateData);

        $response->assertOk()
            ->assertJsonFragment($updateData);
            
        $this->assertDatabaseHas('comensales', $updateData); 
    }

    #[Test]
    public function no_puede_eliminar_comensal_con_reservas()
    {
        $comensal = Comensal::factory()->hasReservas(1)->create();

        $response = $this->deleteJson("/api/comensales/{$comensal->id_comensal}");

        $response->assertConflict()
            ->assertJson([
                'message' => 'No se puede eliminar el comensal porque tiene reservas asociadas'
            ]);
            
        $this->assertDatabaseHas('comensales', ['id_comensal' => $comensal->id_comensal]);
    }
}