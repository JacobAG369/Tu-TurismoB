<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLugarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && (auth()->user()?->rol === 'admin' || true);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'nombre'       => ['sometimes', 'string', 'max:200'],
            'descripcion'  => ['sometimes', 'string', 'max:5000'],
            'categoria_id' => ['sometimes', 'string', 'max:50'],
            'latitud'      => ['sometimes', 'numeric', 'between:-90,90'],
            'longitud'     => ['sometimes', 'numeric', 'between:-180,180'],
            'direccion'    => ['sometimes', 'nullable', 'string', 'max:500'],
            'rating'       => ['sometimes', 'numeric', 'between:0,5'],
            'imagen'       => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'nombre.string'          => 'El nombre debe ser un texto válido.',
            'nombre.max'             => 'El nombre no puede superar los 200 caracteres.',
            'descripcion.string'     => 'La descripción debe ser un texto válido.',
            'descripcion.max'        => 'La descripción no puede superar los 5000 caracteres.',
            'categoria_id.string'    => 'La categoría debe ser un texto válido.',
            'categoria_id.max'       => 'El ID de categoría excede la longitud máxima.',
            'latitud.numeric'        => 'La latitud debe ser un valor numérico.',
            'latitud.between'        => 'La latitud debe estar entre -90 y 90.',
            'longitud.numeric'       => 'La longitud debe ser un valor numérico.',
            'longitud.between'       => 'La longitud debe estar entre -180 y 180.',
            'direccion.string'       => 'La dirección debe ser un texto válido.',
            'direccion.max'          => 'La dirección no puede superar los 500 caracteres.',
            'rating.numeric'         => 'El rating debe ser un valor numérico.',
            'rating.between'         => 'El rating debe estar entre 0 y 5.',
            'imagen.image'           => 'El archivo debe ser una imagen válida.',
            'imagen.mimes'           => 'La imagen debe estar en formato JPEG, PNG o WebP.',
            'imagen.max'             => 'La imagen no debe superar los 2MB.',
        ];
    }
}
