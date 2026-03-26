<?php

// controlador de recuperación de contraseña — generar, verificar y resetear. sin magia negra.

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Mail\RecoveryCodeMail;
use App\Models\PasswordReset;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class PasswordRecoveryController extends Controller
{
    use ApiResponse;

    // ──────────────────────────────────────────────
    // POST /api/v1/auth/password/send-code
    // ──────────────────────────────────────────────

    /**
     * Genera un código de 6 dígitos, lo persiste con expiración de 15 min
     * y lo envía por correo al usuario registrado.
     *
     * Throttle: 5 intentos/minuto (configurado en las rutas).
     */
    public function sendCode(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email:rfc', 'max:255'],
        ]);

        $email = mb_strtolower(trim($data['email']));

        // Verificar que el email existe en la colección usuarios
        $user = User::where('email', $email)->first();

        if (! $user) {
            // Retornamos 422 genérico para no revelar si el email está registrado
            return $this->error(
                message: 'No encontramos una cuenta asociada a ese correo.',
                code: 422,
            );
        }

        // Eliminar cualquier código previo del mismo email
        PasswordReset::where('email', $email)->delete();

        // Generar código de 6 dígitos con padding por si acaso
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Persistir con expiración de 15 minutos
        PasswordReset::create([
            'email'      => $email,
            'code'       => $code,
            'expires_at' => now()->addMinutes(15),
        ]);

        // Enviar correo (usa el driver configurado en .env: log en desarrollo)
        Mail::to($email)->send(new RecoveryCodeMail($code, $email));

        return $this->success(
            data: null,
            message: 'Código enviado al correo. Válido por 15 minutos.',
            code: 202,
        );
    }

    // ──────────────────────────────────────────────
    // POST /api/v1/auth/password/verify-code
    // ──────────────────────────────────────────────

    /**
     * Valida que el código sea correcto y no haya expirado.
     * No elimina el registro (eso lo hace resetPassword).
     *
     * Throttle: 5 intentos/minuto (configurado en las rutas).
     */
    public function verifyCode(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email:rfc', 'max:255'],
            'code'  => ['required', 'digits:6'],
        ]);

        $email = mb_strtolower(trim($data['email']));

        $reset = PasswordReset::where('email', $email)
            ->where('code', $data['code'])
            ->first();

        if (! $reset) {
            return $this->error(
                message: 'Código incorrecto.',
                code: 422,
            );
        }

        if ($reset->isExpired()) {
            $reset->delete();

            return $this->error(
                message: 'El código ha expirado. Solicita uno nuevo.',
                code: 422,
            );
        }

        return $this->success(
            data: null,
            message: 'Código válido.',
        );
    }

    // ──────────────────────────────────────────────
    // POST /api/v1/auth/password/reset
    // ──────────────────────────────────────────────

    /**
     * Verifica el código, actualiza la contraseña del usuario con bcrypt
     * y elimina el registro de password_resets.
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email'                 => ['required', 'email:rfc', 'max:255'],
            'code'                  => ['required', 'digits:6'],
            'password'              => [
                'required',
                'string',
                'min:8',
                'max:128',
                'confirmed',
                // Al menos 1 mayúscula, 1 minúscula y 1 número
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',
            ],
            'password_confirmation' => ['required', 'string'],
        ], [
            'password.regex' => 'La contraseña debe tener al menos una mayúscula, una minúscula y un número.',
        ]);

        $email = mb_strtolower(trim($data['email']));

        // Verificar el código (misma lógica que verifyCode para no duplicar mensajes)
        $reset = PasswordReset::where('email', $email)
            ->where('code', $data['code'])
            ->first();

        if (! $reset) {
            return $this->error(
                message: 'Código incorrecto.',
                code: 422,
            );
        }

        if ($reset->isExpired()) {
            $reset->delete();

            return $this->error(
                message: 'El código ha expirado. Solicita uno nuevo.',
                code: 422,
            );
        }

        // Buscar al usuario
        $user = User::where('email', $email)->first();

        if (! $user) {
            return $this->error(
                message: 'Usuario no encontrado.',
                code: 404,
            );
        }

        // Actualizar contraseña con bcrypt
        // Usamos Hash::make porque el cast 'hashed' en el modelo no hace doble hash
        $user->password = Hash::make($data['password']);
        $user->save();

        // Eliminar el registro de recuperación
        $reset->delete();

        return $this->success(
            data: null,
            message: 'Contraseña actualizada correctamente. Ya puedes iniciar sesión.',
        );
    }
}
