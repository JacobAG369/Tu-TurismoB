<?php

// eventos: CRUD, GeoJSON, broadcasting y notificaciones. todo en un solo lugar.

declare(strict_types=1);

namespace App\Services;

use App\Events\EventoCreado;
use App\Events\NuevoEventoPublicado;
use App\Models\Evento;
use App\Models\Notificacion;
use App\Models\User;
use App\Repositories\EventoRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class EventoService
{
    public function __construct(
        private readonly EventoRepositoryInterface $eventos,
        private readonly ImageService $images,
    ) {}

    // ──────────────────────────────────────────────
    // Queries
    // ──────────────────────────────────────────────

    /**
     * @return Collection<int, Evento>
     */
    public function getAll(): Collection
    {
        return $this->eventos->all();
    }

    /**
     * @throws ModelNotFoundException
     */
    public function findById(string $id): Evento
    {
        $evento = $this->eventos->findById($id);

        if ($evento === null) {
            throw (new ModelNotFoundException())->setModel(Evento::class, $id);
        }

        return $evento;
    }

    /**
     * @return Collection<int, Evento>
     */
    public function searchByRadius(float $lat, float $lng, int $radiusInMeters): Collection
    {
        return $this->eventos->searchByRadius($lat, $lng, $radiusInMeters);
    }

    // ──────────────────────────────────────────────
    // Mutations
    // ──────────────────────────────────────────────

    /**
     * @param array<string, mixed> $data
     * @throws InvalidArgumentException
     */
    public function create(array $data, ?UploadedFile $image = null): Evento
    {
        $payload = $this->buildPayload($data, $image);

        /** @var Evento $evento */
        $evento = $this->eventos->create($payload);

        // Crear una notificación para cada usuario registrado
        $mensaje = sprintf('Ya puedes explorar el evento "%s" en el mapa.', $evento->nombre);
        User::all()->each(function (User $user) use ($mensaje): void {
            Notificacion::create([
                'usuario_id' => (string) $user->_id,
                'titulo'     => 'Nuevo evento turístico disponible',
                'mensaje'    => $mensaje,
                'leido'      => false,
            ]);
        });

        EventoCreado::dispatch($evento);
        NuevoEventoPublicado::dispatch($evento);

        return $evento;
    }

    /**
     * @param array<string, mixed> $data
     * @throws ModelNotFoundException
     * @throws InvalidArgumentException
     */
    public function update(string $id, array $data, ?UploadedFile $image = null): Evento
    {
        // verificar que existe antes de actualizar
        $this->findById($id);

        $payload = $this->buildPayload($data, $image);

        /** @var Evento $evento */
        $evento = $this->eventos->update($id, $payload);

        return $evento;
    }

    /**
     * @throws ModelNotFoundException
     */
    public function delete(string $id): void
    {
        $this->findById($id);  // lanza excepción si no existe

        $this->eventos->delete($id);
    }

    // ──────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────

    /**
     * @param  array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function buildPayload(array $data, ?UploadedFile $image = null): array
    {
        $payload = $data;

        if (array_key_exists('rating', $payload)) {
            $payload['rating'] = (float) $payload['rating'];
        }

        if ($image !== null) {
            $payload['imagenes'] = [$this->images->store($image, 'eventos')];
        }

        if (isset($data['latitud'], $data['longitud'])) {
            $payload['ubicacion'] = [
                'type'        => 'Point',
                'coordinates' => [
                    (float) $data['longitud'],  // GeoJSON: longitud va primero
                    (float) $data['latitud'],
                ],
            ];
        }

        // Remove raw coordinate keys — stored only inside `ubicacion`
        unset($payload['latitud'], $payload['longitud'], $payload['imagen']);

        return $payload;
    }
}
