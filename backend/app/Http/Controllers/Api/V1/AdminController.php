<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Lugar;
use App\Models\Evento;
use App\Models\Restaurante;
use App\Models\Favorito;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

class AdminController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/v1/admin/stats
     *
     * Returns application totals, user role distribution, and top 5 favorite places.
     */
     public function stats(): JsonResponse
     {
         // 1. Totals by collection
         $totales = [
             'usuarios'     => User::count(),
             'lugares'      => Lugar::count(),
             'eventos'      => Evento::count(),
             'restaurantes' => Restaurante::count(),
         ];

         // 2. User distribution by rol
         $distribucionRoles = User::raw(function ($collection) {
             return $collection->aggregate([
                 ['$group' => ['_id' => '$rol', 'count' => ['$sum' => 1]]]
             ]);
         });

         $roles = [];
         foreach ($distribucionRoles as $roleStat) {
             $rolName = $roleStat->_id ?? 'desconocido';
             $roles[$rolName] = $roleStat->count;
         }

         // 3. Growth rate calculation (users created in last 30 days vs total)
         $treintaDiasAtras = now()->subDays(30)->toDateTime();
         $usuariosUltimo30Dias = User::where('created_at', '>=', $treintaDiasAtras)->count();
         $totalUsuarios = $totales['usuarios'] ?? 1;
         $tasaCrecimiento = $totalUsuarios > 0 ? round(($usuariosUltimo30Dias / $totalUsuarios) * 100, 2) : 0;

         // 4. Count pending alerts (resources without rating or incomplete)
         $recursosSinRating = Lugar::whereNull('rating')->count() +
                             Evento::whereNull('rating')->count() +
                             Restaurante::whereNull('rating')->count();
         $alertasPendientes = max($recursosSinRating, 0);

         // 5. Top 5 places by favorites
         $topFavoritosCursor = Favorito::raw(function ($collection) {
             return $collection->aggregate([
                 ['$match' => ['tipo' => 'lugar']],
                 ['$group' => ['_id' => '$referencia_id', 'total_favoritos' => ['$sum' => 1]]],
                 ['$sort' => ['total_favoritos' => -1]],
                 ['$limit' => 5]
             ]);
         });

         $topLugaresIds = [];
         $favoritosCountMap = [];
         foreach ($topFavoritosCursor as $fav) {
             if (isset($fav->_id)) {
                 $id = (string) $fav->_id;
                 $topLugaresIds[] = $id;
                 $favoritosCountMap[$id] = $fav->total_favoritos;
             }
         }

         // Get places data
         $lugaresInfo = Lugar::whereIn('_id', $topLugaresIds)->get(['nombre', 'ubicacion', 'categorias_ids']);
         
         $top5 = [];
         foreach ($lugaresInfo as $lugar) {
             $lugarId = (string) $lugar->_id;
             $top5[] = [
                 'id'              => $lugarId,
                 'nombre'          => $lugar->nombre,
                 'total_favoritos' => $favoritosCountMap[$lugarId] ?? 0,
             ];
         }

         // Sort descending locally to ensure correct order
         usort($top5, fn($a, $b) => $b['total_favoritos'] <=> $a['total_favoritos']);

         return $this->success(
             data: [
                 'totales'             => $totales,
                 'roles'               => $roles,
                 'top_lugares'         => $top5,
                 'tasa_crecimiento'    => $tasaCrecimiento,
                 'alertas_pendientes'  => $alertasPendientes,
             ],
             message: 'Estadísticas obtenidas exitosamente.'
         );
     }

    /**
     * POST /api/v1/admin/backup
     *
     * Accepts a 'type' parameter ('full' or collection name).
     * Uses exec() to run mongodump, zips the result, and returns the download URL.
     */
    public function backup(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|string',
        ]);

        $type = $request->input('type');

        try {
            $dsn = env('DB_DSN');
            if (empty($dsn)) {
                throw new \RuntimeException("La variable DB_DSN no está configurada.");
            }

            $dbName = config('database.connections.mongodb.database', 'tu_turismo');
            
            // Setup folder Names
            $timestamp = now()->format('Y_m_d_H_i_s');
            $folderName = "backup_{$type}_{$timestamp}";
            $zipName = "{$folderName}.zip";

            // Storage paths
            $backupDir = storage_path('app/public/backups');
            if (!file_exists($backupDir)) {
                mkdir($backupDir, 0755, true);
            }

            $tempPath = storage_path("app/temp/{$folderName}");
            if (!file_exists($tempPath)) {
                mkdir($tempPath, 0755, true);
            }

            // Command for mongodump
            $cmd = "mongodump --uri=\"{$dsn}\" --db=\"{$dbName}\" --out=\"{$tempPath}\"";
            if ($type !== 'full') {
                $cmd .= " --collection=\"{$type}\"";
            }

            // Run command
            $output = [];
            $resultCode = null;
            exec($cmd, $output, $resultCode);

            if ($resultCode !== 0) {
                // Return 500 without crashing the whole application
                return $this->error(
                    message: "mongodump falló con el código {$resultCode}.",
                    code: 500
                );
            }

            // Path to resulting zip
            $zipPath = "{$backupDir}/{$zipName}";
            
            // Zip the folder
            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
                // Add files recursively
                $files = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($tempPath),
                    RecursiveIteratorIterator::LEAVES_ONLY
                );

                foreach ($files as $name => $file) {
                    if (!$file->isDir()) {
                        $filePath = $file->getRealPath();
                        // Get relative path for zip internal structure
                        $relativePath = substr($filePath, strlen($tempPath) + 1);
                        $zip->addFile($filePath, $relativePath);
                    }
                }
                $zip->close();
            } else {
                throw new \RuntimeException("No se pudo crear el archivo zip: {$zipPath}");
            }

            // Clean up temporary mongodump folder
            $this->removeDir($tempPath);

            // Using asset() pointing to storage to return proper download URL
            $url = asset("storage/backups/{$zipName}");

            return $this->success(
                data: ['url' => $url, 'filename' => $zipName],
                message: 'Backup generado exitosamente.'
            );

        } catch (\Exception $e) {
            return $this->error(
                message: 'Error de sistema al generar el backup: ' . $e->getMessage(),
                code: 500
            );
        }
    }

    /**
     * Recursively remove a directory and its contents.
     */
    private function removeDir(string $dir): void
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    $itemPath = $dir . DIRECTORY_SEPARATOR . $object;
                    if (is_dir($itemPath) && !is_link($itemPath)) {
                        $this->removeDir($itemPath);
                    } else {
                        unlink($itemPath);
                    }
                }
            }
            rmdir($dir);
        }
    }
}
