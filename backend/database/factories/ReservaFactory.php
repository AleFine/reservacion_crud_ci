<?php

namespace Database\Factories;

use App\Models\Reserva;
use App\Models\Comensal;
use App\Models\Mesa;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReservaFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Reserva::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'fecha' => $this->faker->dateTimeBetween('now', '+30 days')->format('Y-m-d'),
            'hora' => $this->faker->time('H:i:00'),
            'numero_de_personas' => $this->faker->numberBetween(1, 4),
            'id_comensal' => function () {
                return Comensal::factory()->create()->id_comensal;
            },
            'id_mesa' => function () {
                return Mesa::factory()->create()->id_mesa;
            },
        ];
    }
}