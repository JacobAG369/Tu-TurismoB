<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLugarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route is already gated by auth:sanctum middleware
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'nombre'       => ['sometimes', 'string', 'max:200'],
            'descripcion'  => ['sometimes', 'string'],
            'categoria_id' => ['sometimes', 'string'],
            'latitud'      => ['sometimes', 'numeric', 'between:-90,90'],
            'longitud'     => ['sometimes', 'numeric', 'between:-180,180'],
            'imagenes'     => ['nullable', 'array'],
            'imagenes.*'   => ['string'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'nombre.max'       => 'El nombre no puede superar los 200 caracteres.',
            'latitud.numeric'  => 'La latitud debe ser un valor numérico.',
            'latitud.between'  => 'La latitud debe estar entre -90 y 90.',
            'longitud.numeric' => 'La longitud debe ser un valor numérico.',
            'longitud.between' => 'La longitud debe estar entre -180 y 180.',
            'imagenes.array'   => 'El campo imagenes debe ser un arreglo.',
        ];
    }
}
