<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReservaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fecha' => 'sometimes|date|after_or_equal:today',
            'hora' => 'sometimes|date_format:H:i:s',
            'numero_de_personas' => 'sometimes|integer|min:1',
            'id_comensal' => 'sometimes|exists:comensales,id_comensal',
            'id_mesa' => 'sometimes|exists:mesas,id_mesa',
        ];
    }
}