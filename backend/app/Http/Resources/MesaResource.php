<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MesaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_mesa' => $this->id_mesa,
            'numero_mesa' => $this->numero_mesa,
            'capacidad' => $this->capacidad,
            'ubicacion' => $this->ubicacion,
        ];
    }
}
