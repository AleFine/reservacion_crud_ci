<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReservaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_reserva' => $this->id_reserva,
            'fecha' => $this->fecha,
            'hora' => $this->hora,
            'numero_de_personas' => $this->numero_de_personas,
            'id_comensal' => $this->id_comensal,
            'id_mesa' => $this->id_mesa,
            'comensal' => new ComensalResource($this->whenLoaded('comensal')),
            'mesa' => new MesaResource($this->whenLoaded('mesa')),
        ];
    }
}
