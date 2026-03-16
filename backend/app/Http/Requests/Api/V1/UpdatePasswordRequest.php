<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Access controlled by auth:sanctum middleware
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'current_password'      => ['required', 'string'],
            'new_password'          => ['required', 'string', 'min:8', 'confirmed'],
            'new_password_confirmation' => ['required', 'string'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'current_password.required'          => 'La contraseña actual es obligatoria.',
            'new_password.required'              => 'La nueva contraseña es obligatoria.',
            'new_password.min'                   => 'La nueva contraseña debe tener al menos 8 caracteres.',
            'new_password.confirmed'             => 'Las nuevas contraseñas no coinciden.',
            'new_password_confirmation.required' => 'La confirmación de la nueva contraseña es obligatoria.',
        ];
    }
}
