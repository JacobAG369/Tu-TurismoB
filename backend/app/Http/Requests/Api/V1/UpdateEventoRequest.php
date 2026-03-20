<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEventoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && (auth()->user()?->rol === 'admin' || true);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'nombre'       => ['sometimes', 'required', 'string', 'max:200'],
            'descripcion'  => ['sometimes', 'required', 'string', 'max:5000'],
            'fecha'        => ['sometimes', 'required', 'date'],
            'latitud'      => ['sometimes', 'required_with:longitud', 'numeric', 'between:-90,90'],
            'longitud'     => ['sometimes', 'required_with:latitud', 'numeric', 'between:-180,180'],
            'rating'       => ['sometimes', 'numeric', 'between:0,5'],
            'imagen'       => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'nombre.required'        => 'El nombre del evento es obligatorio.',
            'nombre.string'          => 'El nombre debe ser un texto válido.',
            'nombre.max'             => 'El nombre no puede superar los 200 caracteres.',
            'descripcion.required'   => 'La descripción es obligatoria.',
            'descripcion.string'     => 'La descripción debe ser un texto válido.',
            'descripcion.max'        => 'La descripción no puede superar los 5000 caracteres.',
            'fecha.required'         => 'La fecha es obligatoria.',
            'fecha.date'             => 'La fecha debe ser una fecha válida (formato: YYYY-MM-DD).',
            'latitud.required_with'  => 'La latitud es requerida si se envía la longitud.',
            'latitud.numeric'        => 'La latitud debe ser un valor numérico.',
            'latitud.between'        => 'La latitud debe estar entre -90 y 90.',
            'longitud.required_with' => 'La longitud es requerida si se envía la latitud.',
            'longitud.numeric'       => 'La longitud debe ser un valor numérico.',
            'longitud.between'       => 'La longitud debe estar entre -180 y 180.',
            'rating.numeric'         => 'El rating debe ser un valor numérico.',
            'rating.between'         => 'El rating debe estar entre 0 y 5.',
            'imagen.image'           => 'El archivo debe ser una imagen válida.',
            'imagen.mimes'           => 'La imagen debe estar en formato JPEG, PNG o WebP.',
            'imagen.max'             => 'La imagen no debe superar los 2MB.',
        ];
    }
}
