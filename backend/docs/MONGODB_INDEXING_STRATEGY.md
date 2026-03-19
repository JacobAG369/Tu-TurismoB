# MongoDB Indexing Strategy - Tu-Turismo

## Table of Contents

1. [Overview](#overview)
2. [ESR Methodology](#esr-methodology)
3. [Covering Index Technique](#covering-index-technique)
4. [Collection-by-Collection Strategy](#collection-by-collection-strategy)
5. [Index Reference](#index-reference)
6. [Monitoring and Maintenance](#monitoring-and-maintenance)
7. [Troubleshooting](#troubleshooting)
8. [Performance Benchmarking](#performance-benchmarking)

---

## Overview

This document describes the MongoDB indexing strategy for Tu-Turismo, a tourism platform that manages places, events, restaurants, and user interactions. The strategy focuses on optimizing query performance while minimizing index overhead.

### Goals

- **Performance**: Reduce query execution time and memory usage
- **Coverage**: Index all frequently executed queries
- **Redundancy Prevention**: Avoid duplicate or overlapping indices
- **Production Safety**: Implement safe maintenance procedures

### Key Metrics

- **Total Indices**: 13 across 7 collections
- **Covering Indices**: 5 (zero collection lookups)
- **Unique Indices**: 2 (email, slug)
- **Geospatial Indices**: 3 (critical for map functionality)

---

## ESR Methodology

ESR stands for **Equality, Sort, Range** - the optimal field ordering for compound indices in MongoDB.

### ESR Order Explained

```
db.collection.createIndex({
  field1: 1,     // E - Equality (WHERE clauses)
  field2: 1,     // S - Sort (ORDER BY)
  field3: 1      // R - Range ($gt, $lt, $near, geospatial)
})
```

### Why ESR Matters

1. **Equality (E)**: Fields used in equality filters (field = X)
   - MongoDB uses equality fields to narrow the index scan range
   - Place first to filter down the key space quickly

2. **Sort (S)**: Fields used in sorting (ORDER BY)
   - MongoDB uses these to return results in order
   - Must come AFTER equality fields
   - Avoids in-memory sort phase (very expensive)

3. **Range (R)**: Fields used in range queries
   - Includes $gt, $lt, $gte, $lte, $near, geospatial
   - Place last (after sort fields)

### Example: Map Query

```javascript
// Query: Find places by category, return sorted by name
db.lugares.find(
  { categoria_id: X },           // EQUALITY
  { sort: { nombre: 1 } }        // SORT
)

// Optimal Index (ESR):
db.lugares.createIndex({
  "categoria_id": 1,    // E
  "nombre": 1,          // S
  "_id": 1              // COVERED
})
```

### Example: Geospatial Query

```javascript
// Query: Find places near location, filter by category
db.lugares.find({
  categoria_id: X,                              // EQUALITY
  ubicacion: { $near: { $geometry: GeoJSON } } // RANGE
})

// Optimal Index (ESR):
// Note: Geospatial fields are RANGE, placed last
db.lugares.createIndex({
  "categoria_id": 1,        // E
  "ubicacion": "2dsphere"   // R (geospatial is range)
})
```

### Why Not Reverse?

```javascript
// BAD - Wrong ESR order
db.lugares.createIndex({
  "nombre": 1,              // Wrong: Sort field first
  "categoria_id": 1,        // Wrong: Equality field second
  "ubicacion": "2dsphere"   // Range field last (correct)
})

// Problem: MongoDB must:
// 1. Walk through entire index (can't stop early)
// 2. Sort results in memory (expensive)
// 3. Defeat index benefits for equality filter
```

---

## Covering Index Technique

A **covering index** contains all fields required by a query, allowing MongoDB to return results directly from the index without accessing the collection.

### Why Covering Indices Matter

```
Without Covering Index:
Query → Index Lookup → Collection Lookup → Return Results
Performance: ~50ms per 10k docs

With Covering Index:
Query → Index Lookup → Return Results
Performance: ~5ms per 10k docs
Improvement: 10x faster
```

### How to Create a Covering Index

Include all required fields in the index, ending with `_id`:

```javascript
// Example: Map endpoint query
// Fields needed: _id, nombre, categoria_id, ubicacion

db.lugares.createIndex({
  "categoria_id": 1,        // E - equality filter
  "ubicacion": "2dsphere",  // R - geospatial
  "nombre": 1,              // S - sorting
  "_id": 1                  // COVERED - include to avoid collection lookup
})
```

### Covering Index Rules

1. **Include ALL query fields**: `select()`, `projection()`
2. **Follow ESR order**: E, S, R
3. **Include `_id` last**: Completes the covering
4. **Avoid non-indexed fields**: Query must match projection

```javascript
// Query with projection
db.lugares.find(
  { categoria_id: X },
  { _id: 1, nombre: 1, categoria_id: 1, ubicacion: 1 }
).sort({ nombre: 1 })

// Covering index covers this query
db.lugares.createIndex({
  "categoria_id": 1,
  "ubicacion": "2dsphere",
  "nombre": 1,
  "_id": 1
})
```

---

## Collection-by-Collection Strategy

### 1. LUGARES (Places)

**Primary Use Case**: Map display with category filtering

#### Indices

| Name | Fields | ESR | Use Case |
|------|--------|-----|----------|
| `idx_lugares_categoria_geo_covering` | categoria_id, ubicacion, nombre, _id | E,R,S | Map endpoint with category filter |
| `idx_lugares_ubicacion_geo_alt` | ubicacion | R | Standalone geospatial queries |
| `idx_lugares_categoria_rating` | categoria_id, rating | E,S | Category listing with rating sort |

#### Query Patterns

```javascript
// Pattern 1: Map with category
db.lugares.find({ categoria_id: "restaurante" })
  .select(_id, nombre, categoria_id, ubicacion)
// Uses: idx_lugares_categoria_geo_covering

// Pattern 2: Nearby search
db.lugares.find({
  ubicacion: { $near: { $geometry: GeoJSON, $maxDistance: 5000 } }
})
// Uses: idx_lugares_ubicacion_geo_alt

// Pattern 3: Category + Rating
db.lugares.find({ categoria_id: "hotel" })
  .sort({ rating: -1 })
// Uses: idx_lugares_categoria_rating
```

#### ESR Breakdown

```
Index: idx_lugares_categoria_geo_covering
categoria_id: 1      → E (WHERE categoria_id = X)
ubicacion: 2dsphere  → R (WHERE ubicacion near X)
nombre: 1            → S (ORDER BY nombre)
_id: 1               → COVERED (avoid collection lookup)
```

---

### 2. EVENTOS (Events)

**Primary Use Case**: Event timeline with date filtering and geospatial search

#### Indices

| Name | Fields | ESR | Use Case |
|------|--------|-----|----------|
| `idx_eventos_fecha_geo` | fecha, ubicacion | E,R | Date + location filter |
| `idx_eventos_fecha_geo_covering` | fecha, ubicacion, nombre, _id | E,R,S | Event listings |
| `idx_eventos_nombre` | nombre | E | Name search |

#### Query Patterns

```javascript
// Pattern 1: Events on date
db.eventos.find({
  fecha: { $gte: ISODate("2026-03-19"), $lte: ISODate("2026-03-20") }
}).sort({ fecha: 1 })
// Uses: idx_eventos_fecha_geo

// Pattern 2: Nearby events
db.eventos.find({
  ubicacion: { $near: { $geometry: GeoJSON } }
})
// Uses: idx_eventos_fecha_geo

// Pattern 3: List events
db.eventos.find({})
  .select(_id, nombre, ubicacion, fecha)
  .sort({ fecha: 1 })
// Uses: idx_eventos_fecha_geo_covering
```

#### ESR Breakdown

```
Index: idx_eventos_fecha_geo_covering
fecha: 1             → E (WHERE fecha between X and Y)
ubicacion: 2dsphere  → R (WHERE ubicacion near Z)
nombre: 1            → S (ORDER BY nombre)
_id: 1               → COVERED
```

---

### 3. RESTAURANTES (Restaurants)

**Primary Use Case**: Geographic search for nearby restaurants

#### Indices

| Name | Fields | ESR | Use Case |
|------|--------|-----|----------|
| `idx_restaurantes_geo_covering` | ubicacion, nombre, _id | R,S | Nearby search |

#### Query Patterns

```javascript
// Pattern 1: Nearby restaurants
db.restaurantes.find({
  ubicacion: { $near: { $geometry: GeoJSON, $maxDistance: 2000 } }
}).select(_id, nombre, ubicacion)
// Uses: idx_restaurantes_geo_covering
```

#### ESR Breakdown

```
Index: idx_restaurantes_geo_covering
ubicacion: 2dsphere  → R (WHERE ubicacion near X)
nombre: 1            → S (ORDER BY nombre)
_id: 1               → COVERED
```

---

### 4. REVIEWS (User Reviews)

**Primary Use Case**: Get reviews for a place, sorted by rating

#### Indices

| Name | Fields | ESR | Use Case |
|------|--------|-----|----------|
| `idx_reviews_lugar_rating` | lugar_id, rating, _id | E,S | Get place reviews |

#### Query Patterns

```javascript
// Pattern 1: Get reviews for a place, highest rated first
db.reviews.find({ lugar_id: ObjectId("...") })
  .sort({ rating: -1 })
  .select(_id, rating, contenido, usuario_id)
// Uses: idx_reviews_lugar_rating
```

#### ESR Breakdown

```
Index: idx_reviews_lugar_rating
lugar_id: 1  → E (WHERE lugar_id = X)
rating: -1   → S (ORDER BY rating DESC)
_id: 1       → COVERED
```

---

### 5. FAVORITOS (User Favorites)

**Primary Use Case**: Get user's favorites, filtered by type

#### Indices

| Name | Fields | ESR | Use Case |
|------|--------|-----|----------|
| `idx_favoritos_user_tipo` | user_id, referencia_tipo, referencia_id, _id | E,E,S | Get user's favorites by type |
| `idx_favoritos_user_referencia` | user_id, referencia_id | E,E | Check if item is favorited |

#### Query Patterns

```javascript
// Pattern 1: Get user's restaurant favorites
db.favoritos.find({
  user_id: ObjectId("..."),
  referencia_tipo: "restaurante"
}).select(_id, referencia_id)
// Uses: idx_favoritos_user_tipo

// Pattern 2: Check if user has favorited
db.favoritos.findOne({
  user_id: ObjectId("..."),
  referencia_id: ObjectId("...")
})
// Uses: idx_favoritos_user_referencia
```

#### ESR Breakdown

```
Index: idx_favoritos_user_tipo
user_id: 1           → E (WHERE user_id = X)
referencia_tipo: 1   → E (WHERE referencia_tipo = Y)
referencia_id: 1     → S (sorting/equality on ID)
_id: 1               → COVERED
```

---

### 6. USERS (Authentication)

**Primary Use Case**: User authentication and account management

#### Indices

| Name | Fields | ESR | Use Case |
|------|--------|-----|----------|
| `idx_users_email` | email | E,U | Login/authentication (UNIQUE) |
| `idx_users_created_at` | created_at | S | Inactive account detection |

#### Query Patterns

```javascript
// Pattern 1: Find user by email
db.users.findOne({ email: "user@example.com" })
// Uses: idx_users_email (UNIQUE)

// Pattern 2: Find inactive users (age > 6 months)
db.users.find({
  created_at: { $lt: ISODate("2025-09-18") }
})
// Uses: idx_users_created_at
```

#### ESR Breakdown

```
Index: idx_users_email
email: 1  → E (WHERE email = X) with UNIQUE constraint

Index: idx_users_created_at
created_at: 1  → S (ORDER BY created_at)
```

---

### 7. CATEGORIAS (Categories)

**Primary Use Case**: URL routing and category lookups

#### Indices

| Name | Fields | ESR | Use Case |
|------|--------|-----|----------|
| `idx_categorias_slug` | slug | E,U | URL routing (UNIQUE) |

#### Query Patterns

```javascript
// Pattern 1: Get category by slug
db.categorias.findOne({ slug: "restaurantes" })
// Uses: idx_categorias_slug (UNIQUE)
```

#### ESR Breakdown

```
Index: idx_categorias_slug
slug: 1  → E (WHERE slug = X) with UNIQUE constraint
```

---

## Index Reference

### Complete Index List

```
LUGARES (4 indices)
├── idx_lugares_categoria_geo_covering: {categoria_id:1, ubicacion:2d, nombre:1, _id:1}
├── idx_lugares_ubicacion_geo_alt: {ubicacion:2d}
└── idx_lugares_categoria_rating: {categoria_id:1, rating:-1}

EVENTOS (3 indices)
├── idx_eventos_fecha_geo: {fecha:1, ubicacion:2d}
├── idx_eventos_fecha_geo_covering: {fecha:1, ubicacion:2d, nombre:1, _id:1}
└── idx_eventos_nombre: {nombre:1}

RESTAURANTES (1 index)
└── idx_restaurantes_geo_covering: {ubicacion:2d, nombre:1, _id:1}

REVIEWS (1 index)
└── idx_reviews_lugar_rating: {lugar_id:1, rating:-1, _id:1}

FAVORITOS (2 indices)
├── idx_favoritos_user_tipo: {user_id:1, referencia_tipo:1, referencia_id:1, _id:1}
└── idx_favoritos_user_referencia: {user_id:1, referencia_id:1}

USERS (2 indices)
├── idx_users_email: {email:1} [UNIQUE]
└── idx_users_created_at: {created_at:1}

CATEGORIAS (1 index)
└── idx_categorias_slug: {slug:1} [UNIQUE]
```

### Index Storage Estimation

| Collection | Indices | Est. Size | Ratio to Data |
|---|---|---|---|
| lugares | 4 | ~50MB | 0.3x |
| eventos | 3 | ~30MB | 0.3x |
| restaurantes | 1 | ~15MB | 0.3x |
| reviews | 1 | ~20MB | 0.2x |
| favoritos | 2 | ~25MB | 0.3x |
| users | 2 | ~10MB | 0.5x |
| categorias | 1 | ~1MB | 0.5x |
| **TOTAL** | **13** | **~150MB** | **~0.3x** |

---

## Monitoring and Maintenance

### Safe Index Maintenance Workflow

```
1. DISCOVER REDUNDANCY
   └─ Run: 02-audit-indexes.js
   
2. HIDE INDEX (TEST PHASE)
   └─ Run: 03-index-maintenance.js → hideIndex()
   └─ Monitor: 24-48 hours
   
3. EVALUATE IMPACT
   └─ Check: Logs, query times, error rates
   └─ Good? → Step 4
   └─ Bad? → unhideIndex() and keep using
   
4. DROP PERMANENTLY
   └─ Run: 03-index-maintenance.js → dropIndexSafely()
   └─ Monitor: 24 hours post-drop
```

### Using the Audit Script

```bash
# Connect to MongoDB
mongo "mongodb+srv://user:password@cluster/turismo"

# Load and run audit
load('/path/to/02-audit-indexes.js')

# Output includes:
# - All indices by collection
# - Redundant/duplicate indices
# - Index statistics and sizes
# - Cardinality analysis
# - DBA recommendations
```

### Using the Maintenance Script

```bash
# Connect to MongoDB
mongo "mongodb+srv://user:password@cluster/turismo"

# Load maintenance functions
load('/path/to/03-index-maintenance.js')

# Hide an index for testing
hideIndex('lugares', 'idx_lugares_categoria_rating')

# Check status after 48 hours
validateIndexStatus('lugares')

# If no issues, drop permanently
dropIndexSafely('lugares', 'idx_lugares_categoria_rating')

# Rebuild all indices
rebuildAllIndices()
```

### Key Metrics to Monitor

1. **Query Performance**
   - Average execution time
   - Documents scanned vs. returned
   - Index usage in explain() output

2. **Index Health**
   - Index sizes and growth
   - Index validity
   - Duplicate/redundant indices

3. **System Health**
   - CPU during index creation
   - Disk I/O patterns
   - Memory usage

### Recommended Maintenance Schedule

```
DAILY
└─ Monitor index sizes and health
└─ Check for redundant indices

WEEKLY
└─ Run audit script
└─ Review slow query logs
└─ Validate index usage

MONTHLY
└─ Full index rebuild (off-peak)
└─ Compact index storage
└─ Review and remove unused indices

QUARTERLY
└─ Performance benchmarking
└─ Index strategy review
└─ Cardinality analysis
└─ ESR optimization check
```

---

## Troubleshooting

### Issue: Query Still Slow After Index Creation

**Symptoms**:
- Index exists but query is slow
- explain() shows index is used but COLLSCAN stage present

**Solutions**:

1. Check ESR order:
```javascript
// BAD: Wrong ESR
db.lugares.createIndex({ nombre: 1, categoria_id: 1, ubicacion: "2dsphere" })

// GOOD: Correct ESR
db.lugares.createIndex({ categoria_id: 1, ubicacion: "2dsphere", nombre: 1 })
```

2. Verify projection matches index:
```javascript
// Query with projection missing a field
db.lugares.find({ categoria_id: X }, { _id: 1, nombre: 1 })
  // Uses index but not covering (ubicacion missing from projection)

// Fix: Include all fields in projection
db.lugares.find({ categoria_id: X }, { _id: 1, nombre: 1, ubicacion: 1 })
  // Now uses covering index
```

3. Check explain output:
```javascript
db.lugares.find({ categoria_id: X }).explain("executionStats")
// Look for: "stage": "IXSCAN" (good) vs "stage": "COLLSCAN" (bad)
// Check: executionStats.executionStages.totalDocsExamined vs returned
```

### Issue: Index Creation Takes Too Long

**Symptoms**:
- Creating compound indices blocks other operations
- High CPU/disk I/O during index creation

**Solutions**:

1. Use background index creation:
```javascript
db.lugares.createIndex(
  { categoria_id: 1, ubicacion: "2dsphere" },
  { background: true }
)
```

2. Build indices during maintenance window:
```
- Schedule during off-peak hours
- Monitor: db.currentOp() to see progress
- Allow 30-60 mins per collection
```

3. Create on secondary first:
```
- Disable secondary
- Build index on secondary
- Re-add to replica set
- Indices sync automatically
- Repeat for other secondaries
- Failover to secondary with index
```

### Issue: Unique Index Prevents Inserts

**Symptoms**:
- Write failures with "duplicate key error"
- Unique index on email/slug

**Solutions**:

1. Check for duplicate values:
```javascript
db.users.aggregate([
  { $group: { _id: "$email", count: { $sum: 1 } } },
  { $match: { count: { $gt: 1 } } }
])
```

2. Remove duplicates before creating unique index:
```javascript
db.users.deleteOne({ email: "duplicate@example.com" })
// Keep the most recent entry
```

3. Use sparse unique index (allows multiple null):
```javascript
db.users.createIndex(
  { email: 1 },
  { unique: true, sparse: true }
)
```

### Issue: Index Consuming Too Much Disk Space

**Symptoms**:
- Indices larger than expected
- Storage growing rapidly

**Solutions**:

1. Compact indices:
```bash
load('/path/to/03-index-maintenance.js')
compactIndexes()
```

2. Remove redundant indices:
```javascript
// Run audit to find redundant indices
load('/path/to/02-audit-indexes.js')
auditAllIndices()

// Drop redundant ones
db.lugares.dropIndex('idx_lugares_categoria_old')
```

3. Check index statistics:
```bash
load('/path/to/03-index-maintenance.js')
getIndexSizes()
```

---

## Performance Benchmarking

### Before Index Creation

```bash
# Record baseline metrics
mongo "mongodb://localhost/turismo"

// 1. Query time (map endpoint)
start = new Date()
db.lugares.find({ categoria_id: "restaurante" }).limit(100).toArray()
print("Time: " + (new Date() - start) + "ms")

// 2. Documents scanned
db.lugares.find({ categoria_id: "restaurante" }).explain("executionStats")
// Check: executionStats.executionStages.totalDocsExamined
```

### After Index Creation

```bash
# Compare metrics
// 1. Run same query
start = new Date()
db.lugares.find({ categoria_id: "restaurante" }).limit(100).toArray()
print("Time: " + (new Date() - start) + "ms")

// 2. Documents scanned (should be much less)
db.lugares.find({ categoria_id: "restaurante" }).explain("executionStats")
```

### Expected Improvements

| Query Type | Before | After | Improvement |
|---|---|---|---|
| Map with category | 50-100ms | 5-10ms | **10x** |
| Nearby search | 100-200ms | 10-20ms | **10x** |
| Reviews by place | 30-50ms | 3-5ms | **10x** |
| User favorites | 20-30ms | 2-3ms | **10x** |

### Measurement Template

```javascript
// Run this for each critical query

db.collection.find(query).explain("executionStats")

// Record:
{
  "executionStats": {
    "executionTime_ms": 50,           // Query time
    "totalKeysExamined": 1200,        // Index entries scanned
    "totalDocsExamined": 100,         // Docs examined
    "nReturned": 100,                 // Results returned
    "executionStages": {
      "stage": "IXSCAN"              // IXSCAN = using index (good)
                                     // COLLSCAN = full scan (bad)
    }
  }
}

// Ideal metrics:
// - totalDocsExamined ≈ nReturned (no waste)
// - stage == IXSCAN or IXSCAN + SORT
// - executionTime_ms < 10ms
```

---

## Implementation Checklist

- [ ] Review ESR methodology
- [ ] Review covering index technique
- [ ] Run Laravel migration: `php artisan migrate`
- [ ] Verify all 13 indices created: `load('02-audit-indexes.js')`
- [ ] Benchmark critical queries (before/after)
- [ ] Monitor for 48 hours in production
- [ ] Check error logs for index-related issues
- [ ] Validate no query regressions
- [ ] Document any deviations from strategy

---

## References

- [MongoDB Index Documentation](https://docs.mongodb.com/manual/indexes/)
- [ESR Rule for Index Design](https://docs.mongodb.com/manual/tutorial/design-efficient-indexes/#indexes-that-support-sort-operations)
- [Covering Queries](https://docs.mongodb.com/manual/tutorial/covered-queries/)
- [Geospatial Indexes](https://docs.mongodb.com/manual/geospatial-queries/indexes/)

---

**Document Version**: 1.0  
**Last Updated**: 2026-03-18  
**Author**: Senior DBA  
**Status**: Ready for Production Implementation
