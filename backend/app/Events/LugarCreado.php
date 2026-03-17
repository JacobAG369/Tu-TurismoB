<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Lugar;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LugarCreado implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var array<string, mixed>
     */
    public array $payload;

    /**
     * Create a new event instance.
     */
    public function __construct(Lugar $lugar)
    {
        $this->payload = [
            'id' => $lugar->id,
            'nombre' => $lugar->nombre,
            'ubicacion' => $lugar->ubicacion,
            'tipo' => 'lugar',
        ];
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('turismo-updates'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'lugar.creado';
    }
}
