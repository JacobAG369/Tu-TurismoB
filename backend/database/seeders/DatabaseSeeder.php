<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Categoria;
use App\Models\Lugar;
use App\Models\Evento;
use App\Models\Restaurante;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Limpiar colecciones antes de seedear
        User::truncate();
        Categoria::truncate();
        Lugar::truncate();
        Restaurante::truncate();
        Evento::truncate();

        // --- 1. USUARIOS REALES (Admin y Turistas) ---
        $admin = User::create([
            'nombre' => 'Jacob',
            'apellido' => 'Gomez',
            'email' => 'admin@tuturismo.com',
            'password' => Hash::make('password123'),
            'rol' => 'admin',
            'telefono' => '3312345678',
            'idioma' => 'Español'
        ]);

        $turista1 = User::create([
            'nombre' => 'Carlos',
            'apellido' => 'Vela',
            'email' => 'carlos.turista@gmail.com',
            'password' => Hash::make('password123'),
            'rol' => 'turista',
            'telefono' => '3399887766',
            'idioma' => 'Español'
        ]);

        // --- 2. CATEGORÍAS ---
        $catMonumento = Categoria::create(['nombre' => 'Monumentos', 'icono' => 'landmark', 'slug' => 'monumentos']);
        $catRestaurante = Categoria::create(['nombre' => 'Restaurantes', 'icono' => 'utensils', 'slug' => 'restaurantes']);
        $catEvento = Categoria::create(['nombre' => 'Eventos', 'icono' => 'calendar', 'slug' => 'eventos']);
        $catTour = Categoria::create(['nombre' => 'Tours', 'icono' => 'camera', 'slug' => 'tours']);
        $catCompra = Categoria::create(['nombre' => 'Compras', 'icono' => 'shopping-bag', 'slug' => 'compras']);
        $catCultura = Categoria::create(['nombre' => 'Cultura', 'icono' => 'music', 'slug' => 'cultura']);

        // --- 3. LUGARES (Monumentos y Cultura) ---
        $lugares = [
            [
                'nombre' => 'Teatro Degollado',
                'descripcion' => 'Edificio neoclásico del siglo XIX, sede de la Orquesta Filarmónica de Jalisco.',
                'categoria_id' => $catMonumento->id,
                'ubicacion' => $this->geoPoint(-103.3444, 20.6772),
                'rating_promedio' => 4.9,
                'direccion' => 'Calle Belén s/n, Zona Centro, Guadalajara',
                'imagenes' => ['https://sc.jalisco.gob.mx/sites/sc.jalisco.gob.mx/files/teatro_degollado_0.jpg']
            ],
            [
                'nombre' => 'Hospicio Cabañas',
                'descripcion' => 'Patrimonio de la Humanidad por la UNESCO. Alberga los murales de José Clemente Orozco.',
                'categoria_id' => $catCultura->id,
                'ubicacion' => $this->geoPoint(-103.3375, 20.6769),
                'rating_promedio' => 5.0,
                'direccion' => 'C. Cabañas 8, Las Fresas, Guadalajara',
                'imagenes' => ['https://museocabanas.jalisco.gob.mx/static/img/murales.jpg']
            ],
            [
                'nombre' => 'Guachimontones',
                'descripcion' => 'Principal zona arqueológica de Jalisco, famosa por sus pirámides circulares.',
                'categoria_id' => $catCultura->id,
                'ubicacion' => $this->geoPoint(-103.8419, 20.6931),
                'rating_promedio' => 4.7,
                'direccion' => 'Teuchitlán, Jalisco',
                'imagenes' => ['https://inah.gob.mx/images/zonas/guachimontones.jpg']
            ],
            [
                'nombre' => 'Basílica de Nuestra Señora de Zapopan',
                'descripcion' => 'Santuario franciscano del siglo XVII, hogar de la "Generala".',
                'categoria_id' => $catMonumento->id,
                'ubicacion' => $this->geoPoint(-103.3917, 20.7214),
                'rating_promedio' => 4.8,
                'direccion' => 'Eva Briseño 152, Zapopan Centro',
                'imagenes' => ['https://mexicodesconocido.com.mx/wp-content/uploads/2018/09/zapopan.jpg']
            ]
        ];
        foreach ($lugares as $l) {
            Lugar::create($l);
        }

        // --- 4. RESTAURANTES ---
        $restaurantes = [
            [
                'nombre' => 'Alcalde',
                'descripcion' => 'Cocina de autor mexicana, listado entre los 50 Best de Latinoamérica.',
                'ubicacion' => $this->geoPoint(-103.3765, 20.6749),
                'rating_promedio' => 4.9,
                'direccion' => 'Av. México 2903, Vallarta Norte, Guadalajara',
                'telefono' => '3336157400',
                'horario' => '13:30 - 23:00',
                'web' => 'https://alcalde.com.mx',
                'imagenes' => ['https://alcalde.com.mx/wp-content/uploads/2021/03/alcalde_logo.png']
            ],
            [
                'nombre' => 'Santo Coyote',
                'descripcion' => 'Experiencia gastronómica tradicional con un ambiente místico y buffet espectacular.',
                'ubicacion' => $this->geoPoint(-103.3642, 20.6728),
                'rating_promedio' => 4.7,
                'direccion' => 'C. Lerdo de Tejada 2379, Americana, Guadalajara',
                'telefono' => '3336166978',
                'horario' => '08:00 - 00:00',
                'web' => 'http://santocoyote.com.mx',
                'imagenes' => ['https://santocoyote.com.mx/images/logo.png']
            ],
            [
                'nombre' => 'Casa Luna',
                'descripcion' => 'Gastronomía y arte en un entorno mágico en el corazón de Tlaquepaque.',
                'ubicacion' => $this->geoPoint(-103.3108, 20.6402),
                'rating_promedio' => 4.8,
                'direccion' => 'Calle Independencia 211, Tlaquepaque Centro',
                'telefono' => '3336398980',
                'web' => 'http://casaluna.com.mx',
                'imagenes' => ['https://casaluna.com.mx/logo.jpg']
            ]
        ];
        foreach ($restaurantes as $r) {
            Restaurante::create($r);
        }

        // --- 5. EVENTOS ---
        $eventos = [
            [
                'nombre' => 'Feria Internacional del Libro (FIL)',
                'descripcion' => 'La reunión editorial más importante de Iberoamérica.',
                'ubicacion' => $this->geoPoint(-103.3916, 20.6534),
                'fecha_inicio' => '2025-11-29T10:00:00Z',
                'lugar_nombre' => 'Expo Guadalajara',
                'estado' => 'Disponible',
                'imagenes' => ['https://www.fil.com.mx/img/logo_fil.png']
            ],
            [
                'nombre' => 'Fiestas de Octubre',
                'descripcion' => 'La feria más grande de Jalisco con conciertos y juegos mecánicos.',
                'ubicacion' => $this->geoPoint(-103.3444, 20.7303),
                'fecha_inicio' => '2025-10-01T12:00:00Z',
                'lugar_nombre' => 'Auditorio Benito Juárez',
                'estado' => 'Disponible',
                'imagenes' => ['https://fiestasdeoctubre.com.mx/logo.png']
            ],
            [
                'nombre' => 'Romería de Zapopan',
                'descripcion' => 'Procesión religiosa multitudinaria Patrimonio de la Humanidad.',
                'ubicacion' => $this->geoPoint(-103.3917, 20.7214),
                'fecha_inicio' => '2025-10-12T05:00:00Z',
                'lugar_nombre' => 'De Catedral de GDL a Basílica de Zapopan',
                'estado' => 'Finalizado',
                'imagenes' => ['https://zapopan.gob.mx/romeria.jpg']
            ]
        ];
        foreach ($eventos as $e) {
            Evento::create($e);
        }

        // --- 6. COMPRAS Y TOURS ---
        $compras = [
            [
                'nombre' => 'Centro Comercial Andares',
                'descripcion' => 'El mall más exclusivo de occidente con marcas internacionales.',
                'categoria_id' => $catCompra->id,
                'ubicacion' => $this->geoPoint(-103.4116, 20.7101),
                'rating_promedio' => 4.9,
                'direccion' => 'Blvd. Puerta de Hierro 4965, Zapopan',
                'imagenes' => ['https://andares.com/logo.png']
            ],
            [
                'nombre' => 'Jose Cuervo Express (Tour)',
                'descripcion' => 'Viaje en tren antiguo hacia el pueblo mágico de Tequila con cata de agave.',
                'categoria_id' => $catTour->id,
                'ubicacion' => $this->geoPoint(-103.3514, 20.6622),
                'rating_promedio' => 5.0,
                'direccion' => 'Estación de Tren Guadalajara',
                'imagenes' => ['https://www.mundocuervo.com/express.jpg']
            ]
        ];
        foreach ($compras as $c) {
            Lugar::create($c);
        }
    }

    /**
     * Build a MongoDB GeoJSON point.
     *
     * @return array{type: string, coordinates: array{0: float, 1: float}}
     */
    private function geoPoint(float $longitude, float $latitude): array
    {
        return [
            'type' => 'Point',
            'coordinates' => [$longitude, $latitude],
        ];
    }
}
