<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use MongoDB\Laravel\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Tu-Turismo MongoDB Optimized Indices Migration
 * 
 * This migration creates compound and covering indices following ESR methodology:
 * - Equality fields first (WHERE clauses)
 * - Sort fields middle (ORDER BY clauses)
 * - Range fields last ($gt, $lt, $near, geospatial)
 * 
 * Covering indices include all required fields to avoid collection lookups.
 * 
 * Created: 2026-03-18
 * Version: 1.0
 */
return new class extends Migration
{
    protected $connection = 'mongodb';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ============================================================================
        // 1. LUGARES COLLECTION - COVERING INDEX FOR MAP ENDPOINT
        // ============================================================================
        // USE CASE: GET /api/v1/mapa?categoria_id=X
        // ESR: categoria_id (E) + ubicacion (R) + nombre (S) + _id (covered)
        Schema::connection('mongodb')->collection('lugares', function (Blueprint $collection) {
            $collection->index(
                ['categoria_id' => 1, 'ubicacion' => '2dsphere', 'nombre' => 1, '_id' => 1],
                ['name' => 'idx_lugares_categoria_geo_covering']
            );
        });

        // ============================================================================
        // 2. LUGARES COLLECTION - GEOGRAPHIC RANGE QUERIES (NEARBY)
        // ============================================================================
        // USE CASE: GET /api/v1/mapa/nearby?lat=X&lng=Y&radio=Z
        Schema::connection('mongodb')->collection('lugares', function (Blueprint $collection) {
            $collection->geospatial('ubicacion', '2dsphere')
                ->name('idx_lugares_ubicacion_geo_alt');
        });

        // ============================================================================
        // 3. LUGARES COLLECTION - CATEGORY + RATING FILTER
        // ============================================================================
        // USE CASE: Find places by category, sorted by rating
        // ESR: categoria_id (E) + rating (S)
        Schema::connection('mongodb')->collection('lugares', function (Blueprint $collection) {
            $collection->index(
                ['categoria_id' => 1, 'rating' => -1],
                ['name' => 'idx_lugares_categoria_rating']
            );
        });

        // ============================================================================
        // 4. EVENTOS COLLECTION - DATE + LOCATION
        // ============================================================================
        // USE CASE: GET /api/v1/mapa?categoria_id=eventos&fecha=X
        // ESR: fecha (E/R) + ubicacion (R)
        Schema::connection('mongodb')->collection('eventos', function (Blueprint $collection) {
            $collection->index(
                ['fecha' => 1, 'ubicacion' => '2dsphere'],
                ['name' => 'idx_eventos_fecha_geo']
            );
        });

        // ============================================================================
        // 5. EVENTOS COLLECTION - COVERING INDEX (WITH NAME)
        // ============================================================================
        // USE CASE: Event list endpoint returning _id, nombre, ubicacion, fecha
        // ESR: fecha (E) + ubicacion (R) + nombre (S) + _id (covered)
        Schema::connection('mongodb')->collection('eventos', function (Blueprint $collection) {
            $collection->index(
                ['fecha' => 1, 'ubicacion' => '2dsphere', 'nombre' => 1, '_id' => 1],
                ['name' => 'idx_eventos_fecha_geo_covering']
            );
        });

        // ============================================================================
        // 6. EVENTOS COLLECTION - NAME SEARCH
        // ============================================================================
        Schema::connection('mongodb')->collection('eventos', function (Blueprint $collection) {
            $collection->index(
                ['nombre' => 1],
                ['name' => 'idx_eventos_nombre']
            );
        });

        // ============================================================================
        // 7. RESTAURANTES COLLECTION - GEOSPATIAL + NAME COVERING INDEX
        // ============================================================================
        // USE CASE: GET /api/v1/mapa/nearby for restaurants
        // ESR: ubicacion (R) + nombre (S) + _id (covered)
        Schema::connection('mongodb')->collection('restaurantes', function (Blueprint $collection) {
            $collection->index(
                ['ubicacion' => '2dsphere', 'nombre' => 1, '_id' => 1],
                ['name' => 'idx_restaurantes_geo_covering']
            );
        });

        // ============================================================================
        // 8. REVIEWS COLLECTION - LOOKUP BY LUGAR_ID + RATING
        // ============================================================================
        // USE CASE: Get reviews for a place, sorted by rating
        // Query: Reviews.find({ lugar_id: X }).sort({ rating: -1 })
        // ESR: lugar_id (E) + rating (S)
        Schema::connection('mongodb')->collection('reviews', function (Blueprint $collection) {
            $collection->index(
                ['lugar_id' => 1, 'rating' => -1, '_id' => 1],
                ['name' => 'idx_reviews_lugar_rating']
            );
        });

        // ============================================================================
        // 9. FAVORITOS COLLECTION - USER + TYPE COVERING INDEX
        // ============================================================================
        // USE CASE: Get all favorites for a user filtered by type
        // ESR: user_id (E) + referencia_tipo (E) + referencia_id (S)
        Schema::connection('mongodb')->collection('favoritos', function (Blueprint $collection) {
            $collection->index(
                ['user_id' => 1, 'referencia_tipo' => 1, 'referencia_id' => 1, '_id' => 1],
                ['name' => 'idx_favoritos_user_tipo']
            );
        });

        // ============================================================================
        // 10. FAVORITOS COLLECTION - USER + REFERENCE INDEX
        // ============================================================================
        // USE CASE: Check if user has favorited specific item
        Schema::connection('mongodb')->collection('favoritos', function (Blueprint $collection) {
            $collection->index(
                ['user_id' => 1, 'referencia_id' => 1],
                ['name' => 'idx_favoritos_user_referencia']
            );
        });

        // ============================================================================
        // 11. USERS COLLECTION - EMAIL LOOKUP (UNIQUE)
        // ============================================================================
        // USE CASE: Authentication - find user by email
        // ESR: email (E) with unique constraint
        Schema::connection('mongodb')->collection('users', function (Blueprint $collection) {
            $collection->index(
                ['email' => 1],
                ['name' => 'idx_users_email', 'unique' => true]
            );
        });

        // ============================================================================
        // 12. USERS COLLECTION - CREATED_AT FOR INACTIVE USER DETECTION
        // ============================================================================
        // USE CASE: Find and cleanup inactive accounts
        Schema::connection('mongodb')->collection('users', function (Blueprint $collection) {
            $collection->index(
                ['created_at' => 1],
                ['name' => 'idx_users_created_at']
            );
        });

        // ============================================================================
        // 13. CATEGORIAS COLLECTION - SLUG LOOKUP (UNIQUE)
        // ============================================================================
        // USE CASE: Get category by slug for URL routing
        Schema::connection('mongodb')->collection('categorias', function (Blueprint $collection) {
            $collection->index(
                ['slug' => 1],
                ['name' => 'idx_categorias_slug', 'unique' => true]
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indices in reverse order
        Schema::connection('mongodb')->collection('lugares', function (Blueprint $collection) {
            $collection->dropIndex('idx_lugares_categoria_geo_covering');
            $collection->dropIndex('idx_lugares_ubicacion_geo_alt');
            $collection->dropIndex('idx_lugares_categoria_rating');
        });

        Schema::connection('mongodb')->collection('eventos', function (Blueprint $collection) {
            $collection->dropIndex('idx_eventos_fecha_geo');
            $collection->dropIndex('idx_eventos_fecha_geo_covering');
            $collection->dropIndex('idx_eventos_nombre');
        });

        Schema::connection('mongodb')->collection('restaurantes', function (Blueprint $collection) {
            $collection->dropIndex('idx_restaurantes_geo_covering');
        });

        Schema::connection('mongodb')->collection('reviews', function (Blueprint $collection) {
            $collection->dropIndex('idx_reviews_lugar_rating');
        });

        Schema::connection('mongodb')->collection('favoritos', function (Blueprint $collection) {
            $collection->dropIndex('idx_favoritos_user_tipo');
            $collection->dropIndex('idx_favoritos_user_referencia');
        });

        Schema::connection('mongodb')->collection('users', function (Blueprint $collection) {
            $collection->dropIndex('idx_users_email');
            $collection->dropIndex('idx_users_created_at');
        });

        Schema::connection('mongodb')->collection('categorias', function (Blueprint $collection) {
            $collection->dropIndex('idx_categorias_slug');
        });
    }
};
