<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Evento;
use App\Models\Lugar;
use App\Models\Restaurante;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MapaController extends Controller
{
    use ApiResponse;

    /**
     * Get all markers (Lugares, Eventos, Restaurantes) for the map.
     */
    public function getMarkers(): JsonResponse
    {
        // 1. Fetch Lugares
        $lugares = Lugar::select('_id', 'nombre', 'categoria_id', 'ubicacion')
            ->get()
            ->map(function ($item) {
                return [
                    'id'           => $item->_id,
                    'nombre'       => $item->nombre,
                    'categoria_id' => $item->categoria_id,
                    'ubicacion'    => $item->ubicacion,
                    'tipo'         => 'lugar',
                ];
            });

        // 2. Fetch Eventos
        $eventos = Evento::select('_id', 'nombre', 'ubicacion')
            ->get()
            ->map(function ($item) {
                return [
                    'id'           => $item->_id,
                    'nombre'       => $item->nombre,
                    'categoria_id' => null,
                    'ubicacion'    => $item->ubicacion,
                    'tipo'         => 'evento',
                ];
            });

        // 3. Fetch Restaurantes
        $restaurantes = Restaurante::select('_id', 'nombre', 'ubicacion')
            ->get()
            ->map(function ($item) {
                return [
                    'id'           => $item->_id,
                    'nombre'       => $item->nombre,
                    'categoria_id' => null,
                    'ubicacion'    => $item->ubicacion,
                    'tipo'         => 'restaurante',
                ];
            });

        // Merge all collections
        $marcadores = $lugares->concat($eventos)->concat($restaurantes);

        return $this->success(
            data: $marcadores,
            message: 'Marcadores obtenidos correctamente.',
        );
    }

    /**
     * Get nearby markers within a given distance using $near.
     */
    public function getNearby(Request $request): JsonResponse
    {
        $request->validate([
            'lat'   => 'required|numeric',
            'lng'   => 'required|numeric',
            'radio' => 'nullable|numeric|min:0', // distance in meters
        ]);

        $lat   = (float) $request->input('lat');
        $lng   = (float) $request->input('lng');
        $radio = (float) $request->input('radio', 5000); // 5km default

        $geoQuery = [
            '$geometry' => [
                'type'        => 'Point',
                'coordinates' => [$lng, $lat],
            ],
            '$maxDistance' => $radio,
        ];

        // 1. Fetch nearby Lugares
        $lugares = Lugar::where('ubicacion', 'near', $geoQuery)
            ->select('_id', 'nombre', 'categoria_id', 'ubicacion')
            ->get()
            ->map(function ($item) {
                return [
                    'id'           => $item->_id,
                    'nombre'       => $item->nombre,
                    'categoria_id' => $item->categoria_id,
                    'ubicacion'    => $item->ubicacion,
                    'tipo'         => 'lugar',
                ];
            });

        // 2. Fetch nearby Eventos
        $eventos = Evento::where('ubicacion', 'near', $geoQuery)
            ->select('_id', 'nombre', 'ubicacion')
            ->get()
            ->map(function ($item) {
                return [
                    'id'           => $item->_id,
                    'nombre'       => $item->nombre,
                    'categoria_id' => null,
                    'ubicacion'    => $item->ubicacion,
                    'tipo'         => 'evento',
                ];
            });

        // 3. Fetch nearby Restaurantes
        $restaurantes = Restaurante::where('ubicacion', 'near', $geoQuery)
            ->select('_id', 'nombre', 'ubicacion')
            ->get()
            ->map(function ($item) {
                return [
                    'id'           => $item->_id,
                    'nombre'       => $item->nombre,
                    'categoria_id' => null,
                    'ubicacion'    => $item->ubicacion,
                    'tipo'         => 'restaurante',
                ];
            });

        // Merge all collections
        $marcadores = $lugares->concat($eventos)->concat($restaurantes);

        return $this->success(
            data: $marcadores,
            message: 'Marcadores cercanos obtenidos correctamente.',
        );
    }
}
