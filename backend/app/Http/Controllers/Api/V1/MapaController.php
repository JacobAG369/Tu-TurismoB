<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Evento;
use App\Models\Lugar;
use App\Models\Restaurante;
use App\Services\MapaService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MapaController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly MapaService $service,
    ) {}

    /**
     * Get all markers (Lugares, Eventos, Restaurantes) for the map.
     */
    public function getMarkers(Request $request): JsonResponse
    {
        $marcadores = $this->service->getMarkers($request->string('categoria_id')->value());

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
            ->select(['_id', 'nombre', 'descripcion', 'ubicacion', 'direccion', 'imagenes', 'rating', 'rating_promedio', 'categoria_id'])
            ->get()
            ->map(function ($lugar) {
                return [
                    'id'           => (string) $lugar->_id,
                    'nombre'       => $lugar->nombre,
                    'descripcion'  => $lugar->descripcion,
                    'ubicacion'    => $lugar->ubicacion,
                    'direccion'    => $lugar->direccion,
                    'categoria_id' => $lugar->categoria_id,
                    'imagen_url'   => is_array($lugar->imagenes) && count($lugar->imagenes) > 0 ? $lugar->imagenes[0] : null,
                    'imagenes'     => $lugar->imagenes,
                    'rating'       => $lugar->rating ?? $lugar->rating_promedio ?? 0,
                    'reviews_count'=> 0,
                    'tipo_recurso' => 'lugar',
                    'tipo'         => 'lugar',
                ];
            });

        // 2. Fetch nearby Eventos
        $eventos = Evento::where('ubicacion', 'near', $geoQuery)
            ->select(['_id', 'nombre', 'descripcion', 'ubicacion', 'fecha', 'imagenes', 'rating', 'rating_promedio'])
            ->get()
            ->map(function ($evento) {
                return [
                    'id'           => (string) $evento->_id,
                    'nombre'       => $evento->nombre,
                    'descripcion'  => $evento->descripcion,
                    'ubicacion'    => $evento->ubicacion,
                    'fecha'        => $evento->fecha,
                    'categoria_id' => null,
                    'imagen_url'   => is_array($evento->imagenes) && count($evento->imagenes) > 0 ? $evento->imagenes[0] : null,
                    'imagenes'     => $evento->imagenes,
                    'rating'       => $evento->rating ?? $evento->rating_promedio ?? 0,
                    'reviews_count'=> 0,
                    'tipo_recurso' => 'evento',
                    'tipo'         => 'evento',
                ];
            });

        // 3. Fetch nearby Restaurantes
        $restaurantes = Restaurante::where('ubicacion', 'near', $geoQuery)
            ->select(['_id', 'nombre', 'descripcion', 'ubicacion', 'direccion', 'telefono', 'horario', 'web', 'imagenes', 'rating', 'rating_promedio'])
            ->get()
            ->map(function ($restaurante) {
                return [
                    'id'           => (string) $restaurante->_id,
                    'nombre'       => $restaurante->nombre,
                    'descripcion'  => $restaurante->descripcion,
                    'ubicacion'    => $restaurante->ubicacion,
                    'direccion'    => $restaurante->direccion,
                    'telefono'     => $restaurante->telefono,
                    'horario'      => $restaurante->horario,
                    'sitio_web'    => $restaurante->web,
                    'categoria_id' => null,
                    'imagen_url'   => is_array($restaurante->imagenes) && count($restaurante->imagenes) > 0 ? $restaurante->imagenes[0] : null,
                    'imagenes'     => $restaurante->imagenes,
                    'rating'       => $restaurante->rating ?? $restaurante->rating_promedio ?? 0,
                    'reviews_count'=> 0,
                    'tipo_recurso' => 'restaurante',
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
