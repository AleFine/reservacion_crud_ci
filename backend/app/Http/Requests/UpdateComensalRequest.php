<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateComensalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('comensal');

        return [
            'nombre'    => 'sometimes|string|max:255',
            'correo'    => [
                'sometimes',
                'email',
                Rule::unique('comensales', 'correo')->ignore($id, 'id_comensal'),
            ],
            'telefono'  => 'nullable|string|max:20',
            'direccion' => 'nullable|string|max:255',
        ];
    }
}
