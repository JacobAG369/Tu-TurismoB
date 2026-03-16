<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Access controlled by auth:sanctum middleware
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'nombre'    => ['sometimes', 'string', 'max:100'],
            'apellido'  => ['sometimes', 'string', 'max:100'],
            'telefono'  => ['sometimes', 'nullable', 'string', 'max:20'],
            'direccion' => ['sometimes', 'nullable', 'string', 'max:255'],
            'idioma'    => ['sometimes', 'nullable', 'string', 'max:10'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'nombre.string'    => 'El nombre debe ser texto.',
            'nombre.max'       => 'El nombre no puede superar 100 caracteres.',
            'apellido.string'  => 'El apellido debe ser texto.',
            'apellido.max'     => 'El apellido no puede superar 100 caracteres.',
            'telefono.max'     => 'El teléfono no puede superar 20 caracteres.',
            'direccion.max'    => 'La dirección no puede superar 255 caracteres.',
            'idioma.max'       => 'El idioma no puede superar 10 caracteres.',
        ];
    }
}
