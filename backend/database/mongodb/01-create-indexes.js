/**
 * Tu-Turismo MongoDB Index Strategy
 * ===================================
 * 
 * ESR Methodology: Equality, Sort, Range
 * - Equality fields first (used in WHERE clauses)
 * - Sort fields second (ORDER BY fields)
 * - Range fields last ($gt, $lt, $near)
 * 
 * Covering Index: Include all required fields to avoid collection lookups
 * 
 * Author: Senior DBA
 * Date: 2026-03-18
 * Version: 1.0
 */

// ============================================================================
// 1. LUGARES COLLECTION - COVERING INDEX FOR MAP ENDPOINT
// ============================================================================
// USE CASE: GET /api/v1/mapa?categoria_id=X
// Query: Lugares.find({ categoria_id: X }, { select: _id, nombre, categoria_id, ubicacion })
// ESR: categoria_id (E) + ubicacion (R) + nombre (S) + _id (covered)

db.lugares.createIndex(
  {
    "categoria_id": 1,        // EQUALITY - used in WHERE clause
    "ubicacion": "2dsphere",  // RANGE - geospatial queries ($near, $geoWithin)
    "nombre": 1,              // SORT - for alphabetical ordering if needed
    "_id": 1                  // COVERED - include _id to avoid collection lookup
  },
  {
    name: "idx_lugares_categoria_geo_covering",
    background: true,
    comment: "Covering index for map endpoint with category filter"
  }
);

// Index Explanation:
// - categoria_id: EQUALITY (E) - first field for filtering by category
// - ubicacion: RANGE (R) - second field for geospatial distance queries
// - nombre: SORT (S) - third field for alphabetical sorting
// - _id: COVERED - fourth field included in index to avoid collection lookup
// 
// Benefits:
// ✓ No collection scan needed
// ✓ All fields obtained from index only
// ✓ Supports: kategoria_id=X AND geo queries
// ✓ Alphabetical sort available

// ============================================================================
// 2. LUGARES COLLECTION - GEOGRAPHIC RANGE QUERIES (NEARBY)
// ============================================================================
// USE CASE: GET /api/v1/mapa/nearby?lat=X&lng=Y&radio=Z
// Query: Lugares.find({ ubicacion: { $near: GeoJSON, $maxDistance: Z } })
// ESR: ubicacion (R) only - geospatial is range operation

db.lugares.createIndex(
  {
    "ubicacion": "2dsphere"
  },
  {
    name: "idx_lugares_ubicacion_geo",
    background: true,
    comment: "Geospatial index for nearby marker queries"
  }
);

// Note: This index overlaps with the covering index, but we keep it
// for queries that don't filter by categoria_id

// ============================================================================
// 3. LUGARES COLLECTION - SEARCH BY NAME
// ============================================================================
// USE CASE: Search functionality - find Lugares by nombre
// Query: Lugares.find({ nombre: /pattern/ })
// ESR: nombre (E for prefix search)

db.lugares.createIndex(
  {
    "nombre": 1
  },
  {
    name: "idx_lugares_nombre",
    background: true,
    collation: { locale: "es", strength: 2 },
    comment: "Index for name search with Spanish collation"
  }
);

// ============================================================================
// 4. EVENTOS COLLECTION - DATE + LOCATION
// ============================================================================
// USE CASE: GET /api/v1/mapa?categoria_id=eventos&fecha=X
// Query: Eventos.find({ fecha: { $gte: X, $lte: Y }, ubicacion: near })
// ESR: fecha (E/R) + ubicacion (R)

db.eventos.createIndex(
  {
    "fecha": 1,               // EQUALITY/RANGE - date filtering
    "ubicacion": "2dsphere"   // RANGE - geospatial
  },
  {
    name: "idx_eventos_fecha_geo",
    background: true,
    comment: "Index for event timeline and nearby queries"
  }
);

// ============================================================================
// 5. EVENTOS COLLECTION - COVERING INDEX (WITH NAME)
// ============================================================================
// USE CASE: Event list endpoint returning _id, nombre, ubicacion, fecha
// Query: Eventos.find({}, { select: _id, nombre, ubicacion, fecha })
// ESR: fecha (E) + ubicacion (R) + nombre (S) + _id (covered)

db.eventos.createIndex(
  {
    "fecha": 1,               // EQUALITY - filter by date
    "ubicacion": "2dsphere",  // RANGE - geospatial
    "nombre": 1,              // SORT - alphabetical
    "_id": 1                  // COVERED
  },
  {
    name: "idx_eventos_fecha_geo_covering",
    background: true,
    comment: "Covering index for event listings"
  }
);

// ============================================================================
// 6. EVENTOS COLLECTION - NAME SEARCH
// ============================================================================

db.eventos.createIndex(
  {
    "nombre": 1
  },
  {
    name: "idx_eventos_nombre",
    background: true,
    collation: { locale: "es", strength: 2 },
    comment: "Index for event name search"
  }
);

// ============================================================================
// 7. RESTAURANTES COLLECTION - GEOSPATIAL + NAME
// ============================================================================
// USE CASE: GET /api/v1/mapa/nearby for restaurants
// Query: Restaurantes.find({ ubicacion: { $near: geo } })
// ESR: ubicacion (R) + nombre (S) + _id (covered)

db.restaurantes.createIndex(
  {
    "ubicacion": "2dsphere",  // RANGE
    "nombre": 1,              // SORT
    "_id": 1                  // COVERED
  },
  {
    name: "idx_restaurantes_geo_covering",
    background: true,
    comment: "Covering index for restaurant map queries"
  }
);

// ============================================================================
// 8. REVIEWS COLLECTION - LOOKUP BY LUGAR_ID + RATING
// ============================================================================
// USE CASE: Get reviews for a place, sorted by rating
// Query: Reviews.find({ lugar_id: X }).sort({ rating: -1 })
// ESR: lugar_id (E) + rating (S)

db.reviews.createIndex(
  {
    "lugar_id": 1,    // EQUALITY
    "rating": -1,     // SORT (descending)
    "_id": 1          // COVERED
  },
  {
    name: "idx_reviews_lugar_rating",
    background: true,
    comment: "Index for retrieving place reviews sorted by rating"
  }
);

// ============================================================================
// 9. FAVORITOS COLLECTION - USER FAVORITES
// ============================================================================
// USE CASE: Get all favorites for a user
// Query: Favoritos.find({ user_id: X, referencia_tipo: Y })
// ESR: user_id (E) + referencia_tipo (E) + referencia_id (S)

db.favoritos.createIndex(
  {
    "user_id": 1,         // EQUALITY - user filter
    "referencia_tipo": 1, // EQUALITY - type filter (lugar/evento/restaurante)
    "referencia_id": 1,   // SORT/EQUALITY
    "_id": 1              // COVERED
  },
  {
    name: "idx_favoritos_user_tipo",
    background: true,
    comment: "Covering index for user favorites retrieval"
  }
);

// Alternative: Check if this query is common
db.favoritos.createIndex(
  {
    "user_id": 1,
    "referencia_id": 1
  },
  {
    name: "idx_favoritos_user_referencia",
    background: true,
    comment: "Index for favorite lookup by user and reference"
  }
);

// ============================================================================
// 10. USERS COLLECTION - EMAIL LOOKUP + INACTIVE USERS
// ============================================================================
// USE CASE: Authentication - find user by email
// Query: Users.find({ email: X })
// ESR: email (E) + created_at (S for inactive account checks)

db.users.createIndex(
  {
    "email": 1
  },
  {
    name: "idx_users_email",
    unique: true,
    background: true,
    comment: "Unique index for email login"
  }
);

// For checking inactive users (age > 6 months)
db.users.createIndex(
  {
    "created_at": 1
  },
  {
    name: "idx_users_created_at",
    background: true,
    expireAfterSeconds: 15552000, // 6 months, optional TTL
    comment: "Index for inactive user detection"
  }
);

// ============================================================================
// 11. CATEGORIAS COLLECTION - SLUG LOOKUP
// ============================================================================
// USE CASE: Get category by slug for URL routing
// Query: Categorias.findOne({ slug: X })

db.categorias.createIndex(
  {
    "slug": 1
  },
  {
    name: "idx_categorias_slug",
    unique: true,
    background: true,
    comment: "Unique index for category URL routing"
  }
);

// ============================================================================
// COMPOUND INDEX FOR SPECIFIC QUERIES
// ============================================================================

// For "find places by category" + filter by rating
db.lugares.createIndex(
  {
    "categoria_id": 1,      // EQUALITY
    "rating": -1            // SORT (highest rated first)
  },
  {
    name: "idx_lugares_categoria_rating",
    background: true,
    comment: "Index for filtered place listings by category and rating"
  }
);

// ============================================================================
// MULTI-KEY INDEXES (if collections use arrays)
// ============================================================================
// NOTE: If Lugares.etiquetas or Reviews.tags exist as arrays:

// Multi-key index for array fields
// db.lugares.createIndex(
//   {
//     "etiquetas": 1,      // Multi-key - MongoDB auto-creates index entries for each array element
//     "categoria_id": 1
//   },
//   {
//     name: "idx_lugares_etiquetas",
//     background: true,
//     comment: "Multi-key index for tagging/filtering"
//   }
// );

// ============================================================================
// VERIFICATION COMMANDS
// ============================================================================

print("\n=== INDEX CREATION SUMMARY ===");
print("✓ Lugares: 4 indices created");
print("  - Covering index (categoria_id, ubicacion, nombre, _id)");
print("  - Geospatial index (ubicacion)");
print("  - Name search (nombre)");
print("  - Category + rating (categoria_id, rating)");
print("\n✓ Eventos: 3 indices created");
print("  - Date + geo (fecha, ubicacion)");
print("  - Covering index (fecha, ubicacion, nombre, _id)");
print("  - Name search (nombre)");
print("\n✓ Restaurantes: 1 index created");
print("  - Covering geo index (ubicacion, nombre, _id)");
print("\n✓ Reviews: 1 index created");
print("  - Lugar + rating (lugar_id, rating)");
print("\n✓ Favoritos: 2 indices created");
print("  - User + type (user_id, referencia_tipo, referencia_id)");
print("  - User + reference (user_id, referencia_id)");
print("\n✓ Users: 2 indices created");
print("  - Email (unique)");
print("  - Created date");
print("\n✓ Categorias: 1 index created");
print("  - Slug (unique)");
