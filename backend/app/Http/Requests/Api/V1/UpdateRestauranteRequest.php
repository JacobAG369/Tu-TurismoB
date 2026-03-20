<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRestauranteRequest extends FormRequest
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
            'direccion'    => ['sometimes', 'required', 'string', 'max:500'],
            'telefono'     => ['sometimes', 'required', 'string', 'max:20'],
            'horario'      => ['sometimes', 'required', 'string', 'max:100'],
            'latitud'      => ['sometimes', 'required_with:longitud', 'numeric', 'between:-90,90'],
            'longitud'     => ['sometimes', 'required_with:latitud', 'numeric', 'between:-180,180'],
            'web'          => ['sometimes', 'nullable', 'string', 'max:255', 'url'],
            'rating'       => ['sometimes', 'numeric', 'between:0,5'],
            'imagen'       => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'nombre.required'        => 'El nombre del restaurante es obligatorio.',
            'nombre.string'          => 'El nombre debe ser un texto válido.',
            'nombre.max'             => 'El nombre no puede superar los 200 caracteres.',
            'descripcion.required'   => 'La descripción es obligatoria.',
            'descripcion.string'     => 'La descripción debe ser un texto válido.',
            'descripcion.max'        => 'La descripción no puede superar los 5000 caracteres.',
            'direccion.required'     => 'La dirección es obligatoria.',
            'direccion.string'       => 'La dirección debe ser un texto válido.',
            'direccion.max'          => 'La dirección no puede superar los 500 caracteres.',
            'telefono.required'      => 'El teléfono es obligatorio.',
            'telefono.string'        => 'El teléfono debe ser un texto válido.',
            'telefono.max'           => 'El teléfono no puede superar los 20 caracteres.',
            'horario.required'       => 'El horario es obligatorio.',
            'horario.string'         => 'El horario debe ser un texto válido.',
            'horario.max'            => 'El horario no puede superar los 100 caracteres.',
            'latitud.required_with'  => 'La latitud es requerida si se envía la longitud.',
            'latitud.numeric'        => 'La latitud debe ser un valor numérico.',
            'latitud.between'        => 'La latitud debe estar entre -90 y 90.',
            'longitud.required_with' => 'La longitud es requerida si se envía la latitud.',
            'longitud.numeric'       => 'La longitud debe ser un valor numérico.',
            'longitud.between'       => 'La longitud debe estar entre -180 y 180.',
            'web.string'             => 'La URL debe ser un texto válido.',
            'web.max'                => 'La URL no puede superar los 255 caracteres.',
            'web.url'                => 'La URL debe ser un formato válido (ej: https://ejemplo.com).',
            'rating.numeric'         => 'El rating debe ser un valor numérico.',
            'rating.between'         => 'El rating debe estar entre 0 y 5.',
            'imagen.image'           => 'El archivo debe ser una imagen válida.',
            'imagen.mimes'           => 'La imagen debe estar en formato JPEG, PNG o WebP.',
            'imagen.max'             => 'La imagen no debe superar los 2MB.',
        ];
    }
}
