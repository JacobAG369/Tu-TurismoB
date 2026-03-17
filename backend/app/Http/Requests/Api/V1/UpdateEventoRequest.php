<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEventoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // guarded by sanctum
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'nombre'       => ['sometimes', 'required', 'string', 'max:200'],
            'descripcion'  => ['sometimes', 'required', 'string'],
            'fecha'        => ['sometimes', 'required', 'date'],
            'latitud'      => ['sometimes', 'required_with:longitud', 'numeric', 'between:-90,90'],
            'longitud'     => ['sometimes', 'required_with:latitud', 'numeric', 'between:-180,180'],
            'imagen'       => ['nullable', 'string'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'nombre.required'        => 'El nombre del evento es obligatorio.',
            'nombre.max'             => 'El nombre no puede superar los 200 caracteres.',
            'descripcion.required'   => 'La descripción es obligatoria.',
            'fecha.required'         => 'La fecha es obligatoria.',
            'fecha.date'             => 'La fecha debe ser una fecha válida.',
            'latitud.required_with'  => 'La latitud es requerida si se envía la longitud.',
            'latitud.numeric'        => 'La latitud debe ser un valor numérico.',
            'latitud.between'        => 'La latitud debe estar entre -90 y 90.',
            'longitud.required_with' => 'La longitud es requerida si se envía la latitud.',
            'longitud.numeric'       => 'La longitud debe ser un valor numérico.',
            'longitud.between'       => 'La longitud debe estar entre -180 y 180.',
            'imagen.string'          => 'El campo imagen debe ser una cadena de texto.',
        ];
    }
}
