<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreRestauranteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // guarded by sanctum
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'nombre'       => ['required', 'string', 'max:200'],
            'descripcion'  => ['required', 'string'],
            'direccion'    => ['required', 'string'],
            'telefono'     => ['required', 'string'],
            'horario'      => ['required', 'string'],
            'latitud'      => ['required', 'numeric', 'between:-90,90'],
            'longitud'     => ['required', 'numeric', 'between:-180,180'],
            'rating'       => ['nullable', 'numeric', 'between:0,5'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'nombre.required'        => 'El nombre del restaurante es obligatorio.',
            'nombre.max'             => 'El nombre no puede superar los 200 caracteres.',
            'descripcion.required'   => 'La descripción es obligatoria.',
            'direccion.required'     => 'La dirección es obligatoria.',
            'telefono.required'      => 'El teléfono es obligatorio.',
            'horario.required'       => 'El horario es obligatorio.',
            'latitud.required'       => 'La latitud es obligatoria.',
            'latitud.numeric'        => 'La latitud debe ser un valor numérico.',
            'latitud.between'        => 'La latitud debe estar entre -90 y 90.',
            'longitud.required'      => 'La longitud es obligatoria.',
            'longitud.numeric'       => 'La longitud debe ser un valor numérico.',
            'longitud.between'       => 'La longitud debe estar entre -180 y 180.',
            'rating.numeric'         => 'El rating debe ser numérico.',
            'rating.between'         => 'El rating debe estar entre 0 y 5.',
        ];
    }
}
