<?php

namespace Database\Factories;

use App\Models\Mesa;
use Illuminate\Database\Eloquent\Factories\Factory;

class MesaFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Mesa::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'numero_mesa' => 'M' . $this->faker->unique()->numberBetween(1, 100),
            'capacidad' => $this->faker->numberBetween(4, 10),
            'ubicacion' => $this->faker->randomElement(['Interior', 'Terraza', 'Ventana', 'Salón principal', null]),
        ];
    }
}