<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'nombre' => ['sometimes', 'string', 'max:100'],
            'apellido' => ['sometimes', 'string', 'max:100'],
            'email' => ['sometimes', 'email', 'max:150'],
            'telefono' => ['sometimes', 'nullable', 'string', 'max:30'],
            'direccion' => ['sometimes', 'nullable', 'string', 'max:255'],
            'idioma' => ['sometimes', 'nullable', 'string', 'max:10'],
            'rol' => ['sometimes', 'string', 'in:admin,turista'],
            'password' => ['sometimes', 'nullable', 'string', 'min:8'],
        ];
    }
}
