<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route is already gated by auth:sanctum middleware
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'nombre'      => ['sometimes', 'string', 'max:100'],
            'descripcion' => ['nullable', 'string'],
            'icono'       => ['nullable', 'string', 'max:100'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'nombre.max' => 'El nombre no puede superar los 100 caracteres.',
        ];
    }
}
