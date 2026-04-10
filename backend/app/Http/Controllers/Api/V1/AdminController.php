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
     * Genera el backup con mongodump, lo comprime en un ZIP temporal
     * y lo devuelve directamente como descarga (stream).
     * NO guarda archivos permanentemente en disco (compatible con Render Free).
     */
    public function backup(Request $request)
    {
        $request->validate([
            'type' => 'required|string',
        ]);

        $type = $request->input('type');

        $dsn = env('DB_DSN');
        if (empty($dsn)) {
            return $this->error('La variable DB_DSN no está configurada.', 500);
        }

        $dbName = config('database.connections.mongodb.database', 'tu_turismo');

        $timestamp  = now()->format('Y_m_d_H_i_s');
        $folderName = "backup_{$type}_{$timestamp}";
        $zipName    = "{$folderName}.zip";

        // Directorios temporales (serán eliminados tras la descarga)
        $tempPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $folderName;
        $zipPath  = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $zipName;

        try {
            if (!file_exists($tempPath)) {
                mkdir($tempPath, 0755, true);
            }

            // Ejecutar mongodump con ruta absoluta (Apache/PHP exec no hereda el PATH completo)
            $mongodump = '/usr/local/bin/mongodump';
            if (!file_exists($mongodump)) {
                // Fallback: buscar en PATH del sistema
                $mongodump = 'mongodump';
            }

            $cmd = "{$mongodump} --uri=\"{$dsn}\" --db=\"{$dbName}\" --out=\"{$tempPath}\"";
            if ($type !== 'full') {
                $cmd .= " --collection=\"{$type}\"";
            }

            $output     = [];
            $resultCode = null;
            exec($cmd . ' 2>&1', $output, $resultCode);

            \Illuminate\Support\Facades\Log::info('Backup mongodump', [
                'type'        => $type,
                'resultCode'  => $resultCode,
                'output'      => implode("\n", $output),
            ]);

            if ($resultCode !== 0) {
                $this->removeDir($tempPath);
                return $this->error(
                    message: 'mongodump falló (código ' . $resultCode . '): ' . implode(' | ', $output),
                    code: 500
                );
            }

            // Comprimir en ZIP
            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new \RuntimeException("No se pudo crear el archivo ZIP temporal.");
            }

            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($tempPath),
                RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($files as $file) {
                if (!$file->isDir()) {
                    $filePath     = $file->getRealPath();
                    $relativePath = substr($filePath, strlen($tempPath) + 1);
                    $zip->addFile($filePath, $relativePath);
                }
            }
            $zip->close();

            // Limpiar carpeta temporal del mongodump
            $this->removeDir($tempPath);

            // Devolver el ZIP como descarga directa (stream) y eliminar el archivo al terminar
            return response()->download($zipPath, $zipName, [
                'Content-Type'        => 'application/zip',
                'Content-Disposition' => 'attachment; filename="' . $zipName . '"',
            ])->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            // Cleanup en caso de error
            if (file_exists($tempPath)) $this->removeDir($tempPath);
            if (file_exists($zipPath))  unlink($zipPath);

            return $this->error(
                message: 'Error al generar el backup: ' . $e->getMessage(),
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

    /**
     * GET /api/v1/admin/backups
     *
     * En producción con filesystem efímero (Render Free), los backups
     * se generan on-demand y se descargan directamente (no persisten en disco).
     * Este endpoint devuelve lista vacía para mantener compatibilidad con el frontend.
     */
    public function backups(): JsonResponse
    {
        return $this->success(
            data: [],
            message: 'Los backups se generan y descargan directamente. Use el botón "Generar Backup".'
        );
    }

    /**
     * GET /api/v1/admin/backup/{filename}/download
     *
     * Endpoint mantenido para compatibilidad. En producción los backups
     * se descargan directamente desde POST /backup.
     */
    public function downloadBackup(string $filename)
    {
        return $this->error(
            'En producción los backups se generan y descargan directamente. Use POST /api/v1/admin/backup.',
            410
        );
    }
}
