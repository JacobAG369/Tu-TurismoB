<?php

// login, tokens, sesiones... si esto falla, algo más grave ya está mal.

declare(strict_types=1);

namespace App\Services;

use App\Models\Sesion;
use App\Models\PersonalAccessToken;
use App\Models\User;
use App\Repositories\UsuarioRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthService
{
    public function __construct(
        private readonly UsuarioRepositoryInterface $usuarios,
        private readonly VigenereService $vigenere,
    ) {}

    /**
     * Validate credentials, create a Sanctum token, and persist the MongoDB session.
     *
     * @return array<string, mixed>
     */
    public function login(Request $request, array $credentials): array
    {
        $email = mb_strtolower(trim((string) ($credentials['email'] ?? '')));
        $password = (string) ($credentials['password'] ?? '');

        $user = $this->usuarios->findByEmail($email);

        if (! $user instanceof User || ! Hash::check($password, $user->password)) {
            throw new \RuntimeException('Credenciales incorrectas.');
        }

        $user->tokens()->delete();
        Sesion::where('usuario_id', (string) $user->_id)->delete();

        $expiration = config('sanctum.expiration');
        $expiresAt = is_numeric($expiration)
            ? now()->addMinutes((int) $expiration)
            : now()->addMinutes(43200);

        $plainTextToken = sprintf(
            '%s%s%s',
            config('sanctum.token_prefix', ''),
            $tokenEntropy = Str::random(40),
            hash('crc32b', $tokenEntropy),
        );

        $accessToken = $user->tokens()->create([
            'name' => 'auth_token',
            'token' => hash('sha256', $plainTextToken),
            'abilities' => ['*'],
            'expires_at' => $expiresAt,
        ]);

        if (! $accessToken instanceof PersonalAccessToken) {
            throw new \RuntimeException('No se pudo crear el token de acceso.');
        }

        $plainToken = $accessToken->getKey().'|'.$plainTextToken;

        Sesion::create([
            'usuario_id' => (string) $user->_id,
            'token_id' => (string) $accessToken->getKey(),
            'token_vigenere' => $this->vigenere->encrypt($plainToken, (string) config('app.vigenere_key')),
            'ip' => $request->ip() ?? 'unknown',
            'dispositivo' => $request->userAgent() ?? 'unknown',
            'expira_en' => $expiresAt,
            'ultimo_acceso' => now(),
        ]);

         return [
             'token' => $plainToken,
             'token_type' => 'Bearer',
             'user' => [
                 'id' => (string) $user->_id,
                 'nombre' => $user->nombre,
                 'apellido' => $user->apellido,
                 'email' => $user->email,
                 'telefono' => $user->telefono,
                 'direccion' => $user->direccion,
                 'rol' => $user->rol,
             ],
         ];
    }
}
