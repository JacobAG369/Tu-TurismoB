<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Evento;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NuevoEventoPublicado implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $connection = 'database';

    public string $queue = 'broadcasts';

    public bool $afterCommit = true;

    public function __construct(
        public readonly Evento $evento,
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel('mapa-actualizaciones')];
    }

    public function broadcastAs(): string
    {
        return 'nuevo-evento-publicado';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => (string) $this->evento->_id,
            'nombre' => $this->evento->nombre,
            'ubicacion' => [
                'type' => 'Point',
                'coordinates' => [
                    (float) ($this->evento->ubicacion['coordinates'][0] ?? 0),
                    (float) ($this->evento->ubicacion['coordinates'][1] ?? 0),
                ],
            ],
            'tipo_recurso' => 'evento',
        ];
    }
}
