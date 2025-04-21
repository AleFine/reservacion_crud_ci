<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReservaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fecha' => 'required|date|after_or_equal:today',
            'hora' => 'required|date_format:H:i',
            'numero_de_personas' => 'required|integer|min:1',
            'id_comensal' => 'required|exists:comensales,id_comensal',
            'id_mesa' => 'required|exists:mesas,id_mesa',
        ];
    }
}