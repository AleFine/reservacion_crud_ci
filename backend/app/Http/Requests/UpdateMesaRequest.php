<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMesaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $mesaId = $this->route('id');

        return [
            'numero_mesa' => 'sometimes|string|unique:mesas,numero_mesa,' . $mesaId . ',id_mesa',
            'capacidad' => 'sometimes|integer|min:1',
            'ubicacion' => 'nullable|string|max:255',
        ];
    }
}
