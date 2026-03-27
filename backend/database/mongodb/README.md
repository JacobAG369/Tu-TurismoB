# MongoDB Index Scripts - Quick Reference

This directory contains production-grade MongoDB index management scripts for Tu-Turismo.

## Files

- **`01-create-indexes.js`**: Create optimized compound and covering indices
- **`02-audit-indexes.js`**: Audit, validate, and detect redundant indices
- **`03-index-maintenance.js`**: Safe maintenance procedures (hide, drop, rebuild)
- **`README.md`**: This file

## Quick Start

### 1. Connect to MongoDB

```bash
# Local MongoDB
mongo

# MongoDB Atlas
mongo "mongodb+srv://username:password@cluster.mongodb.net/turismo"

# MongoDB on Docker
docker exec -it mongo mongosh
```

### 2. Create All Indices

```bash
# In MongoDB shell
load('/path/to/01-create-indexes.js')

# Output:
# === INDEX CREATION SUMMARY ===
# ✓ Lugares: 4 indices created
# ✓ Eventos: 3 indices created
# ... etc
```

### 3. Audit Current Indices

```bash
# In MongoDB shell
load('/path/to/02-audit-indexes.js')

# Runs comprehensive audit:
# - Lists all indices per collection
# - Detects redundant/duplicate indices
# - Shows index statistics
# - Provides recommendations
# - Exports configuration to JSON

# Output saved to: index-audit-report-TIMESTAMP.json
```

### 4. Maintain Indices Safely

```bash
# In MongoDB shell
load('/path/to/03-index-maintenance.js')

# Available functions:
# - hideIndex(collection, indexName)
# - unhideIndex(collection, indexName)
# - dropIndexSafely(collection, indexName)
# - dropIndexPermanent(collection, indexName)
# - rebuildAllIndices()
# - compactIndexes()
# - getIndexSizes()
# - validateIndexStatus(collection)
```

---

## Detailed Usage

### Creating Indices

```javascript
load('/path/to/01-create-indexes.js')

// Output example:
// === INDEX CREATION SUMMARY ===
// ✓ Lugares: 4 indices created
//   - Covering index (categoria_id, ubicacion, nombre, _id)
//   - Geospatial index (ubicacion)
//   - Name search (nombre)
//   - Category + rating (categoria_id, rating)
//
// ✓ Eventos: 3 indices created
//   - Date + geo (fecha, ubicacion)
//   - Covering index (fecha, ubicacion, nombre, _id)
//   - Name search (nombre)
//
// ✓ Restaurantes: 1 index created
//   - Covering geo index (ubicacion, nombre, _id)
//
// ✓ Reviews: 1 index created
//   - Lugar + rating (lugar_id, rating)
//
// ✓ Favoritos: 2 indices created
//   - User + type (user_id, referencia_tipo, referencia_id)
//   - User + reference (user_id, referencia_id)
//
// ✓ Users: 2 indices created
//   - Email (unique)
//   - Created date
//
// ✓ Categorias: 1 index created
//   - Slug (unique)
```

### Auditing Indices

```javascript
load('/path/to/02-audit-indexes.js')
auditAllIndices()

// Output includes:

// 1. INDEX SUMMARY
//    Total Indices: 13
//    Collections: 7

// 2. COLLECTION: lugares
//    Index: idx_lugares_categoria_geo_covering
//      Fields: categoria_id, ubicacion (2dsphere), nombre, _id
//      Covering: Yes
//      Size: ~15MB
//      Usage: High

// 3. REDUNDANCY ANALYSIS
//    WARNING: idx_lugares_ubicacion_geo overlaps with idx_lugares_categoria_geo_covering
//    Recommendation: Keep both (one for filtered queries, one for raw geo)

// 4. RECOMMENDATIONS
//    - All indices following ESR methodology ✓
//    - No critical redundancies detected ✓
//    - Consider: Add index on eventos.estado if filtering common
```

### Safe Index Maintenance Workflow

#### Step 1: Hide Index (Testing Phase)

```javascript
load('/path/to/03-index-maintenance.js')

// Hide index without deleting
hideIndex('lugares', 'idx_lugares_categoria_rating')

// Check status
validateIndexStatus('lugares')

// Output: Index hidden, monitor for 24-48 hours
```

#### Step 2: Monitor Impact

```
Metrics to check over 24-48 hours:
1. Query performance (should remain same if index wasn't used)
2. Error logs (look for index-related failures)
3. Slow query log (new slow queries = index was important)
4. Application metrics (latency, throughput)
```

#### Step 3: Decide & Act

```javascript
// If no issues found - drop permanently
dropIndexSafely('lugares', 'idx_lugares_categoria_rating')

// If issues found - restore index
unhideIndex('lugares', 'idx_lugares_categoria_rating')
```

### Index Rebuild

```javascript
load('/path/to/03-index-maintenance.js')

// Rebuild all indices (time-consuming, do off-peak)
rebuildAllIndices()

// This will:
// 1. Drop all custom indices
// 2. Rebuild from 01-create-indexes.js
// 3. Verify all 13 indices created
// 4. Show completion status
```

### Monitoring Index Sizes

```javascript
load('/path/to/03-index-maintenance.js')

getIndexSizes()

// Output example:
// COLLECTION: lugares
// ├── idx_lugares_categoria_geo_covering: 15.2 MB
// ├── idx_lugares_ubicacion_geo_alt: 12.8 MB
// ├── idx_lugares_categoria_rating: 8.5 MB
// └── Total: 36.5 MB
//
// COLLECTION: eventos
// └── idx_eventos_fecha_geo_covering: 8.3 MB
// ... etc
```

---

## Common Tasks

### Find Slow Queries

```javascript
// Enable query profiling
db.setProfilingLevel(1, { slowms: 100 })

// Run audit to see which queries use index scan
load('/path/to/02-audit-indexes.js')

// Check slow logs
db.system.profile.find({ "millis": { $gt: 100 } }).limit(10).pretty()
```

### Verify Index Usage in Queries

```javascript
// Check if query uses index
db.lugares.find({ categoria_id: "restaurante" }).explain("executionStats")

// Good output (uses index):
{
  "executionStats": {
    "executionStages": {
      "stage": "IXSCAN",           // Index scan (good)
      "totalKeysExamined": 1000,
      "totalDocsExamined": 100,
      "nReturned": 100
    }
  }
}

// Bad output (collection scan):
{
  "executionStats": {
    "executionStages": {
      "stage": "COLLSCAN",         // Collection scan (bad)
      "totalKeysExamined": 0,
      "totalDocsExamined": 50000,
      "nReturned": 100
    }
  }
}
```

### Check for Duplicate Values (before unique index)

```javascript
// Find duplicate emails
db.users.aggregate([
  { $group: { _id: "$email", count: { $sum: 1 } } },
  { $match: { count: { $gt: 1 } } }
])

// If duplicates exist, keep most recent:
db.users.aggregate([
  { $sort: { created_at: -1 } },
  { $group: { 
      _id: "$email",
      doc: { $first: "$$ROOT" }
    }
  }
])
```

### List All Indices

```javascript
// By collection
db.lugares.getIndexes()

// Formatted output
db.lugares.getIndexes().forEach(idx => {
  print(`${idx.name}: ${JSON.stringify(idx.key)}`)
})

// Example:
// _id_: {"_id":1}
// idx_lugares_categoria_geo_covering: {"categoria_id":1,"ubicacion":"2dsphere","nombre":1,"_id":1}
// idx_lugares_ubicacion_geo_alt: {"ubicacion":"2dsphere"}
// idx_lugares_categoria_rating: {"categoria_id":1,"rating":-1}
```

### Drop Single Index

```javascript
// Safe method (hide first, then drop)
load('/path/to/03-index-maintenance.js')
dropIndexSafely('lugares', 'idx_lugares_categoria_rating')

// Quick method (immediate drop, be careful!)
db.lugares.dropIndex('idx_lugares_categoria_rating')
```

### Rebuild Single Collection

```javascript
// Drop all custom indices for a collection
db.lugares.getIndexes().forEach(idx => {
  if (idx.name !== '_id_') {
    db.lugares.dropIndex(idx.name)
  }
})

// Recreate just lugares indices
db.lugares.createIndex(
  { categoria_id: 1, ubicacion: "2dsphere", nombre: 1, _id: 1 },
  { name: "idx_lugares_categoria_geo_covering" }
)
// ... repeat for other lugares indices
```

---

## Troubleshooting

### Issue: "Cannot load script"

```
Error: Cannot load script: /path/to/01-create-indexes.js

Solution 1: Use absolute path
load('/absolute/path/to/01-create-indexes.js')

Solution 2: Use full URL if on remote server
load('file:///path/to/01-create-indexes.js')

Solution 3: Load from script directly
source '/path/to/01-create-indexes.js'  // In MongoDB CLI
```

### Issue: "Index already exists"

```
Error: index [name] already exists

Solution 1: Check if index truly exists
db.collection.getIndexes() | grep name

Solution 2: If you want to recreate, drop first
db.collection.dropIndex('name')

Solution 3: Rename in script
{ name: "idx_collection_field_v2" }
```

### Issue: "Unique index prevents insert"

```
Error: E11000 duplicate key error

Solution: Find and remove duplicate
db.collection.aggregate([
  { $group: { _id: "$field", count: { $sum: 1 } } },
  { $match: { count: { $gt: 1 } } }
])

Then delete duplicates and retry
```

### Issue: "Index creation timeout"

```
Error: Timeout waiting for index creation

Solution 1: Use background index
{ background: true }

Solution 2: Create during off-peak
# Schedule during low traffic hours

Solution 3: Increase timeout
// In connection string, add:
serverSelectionTimeoutMS=60000
```

---

## Performance Tips

1. **Create indices during maintenance window** (off-peak hours)
2. **Use background: true** for large collections
3. **Monitor CPU/disk during creation** with `db.currentOp()`
4. **Create on secondary first** in replica sets
5. **Verify index usage** with `explain("executionStats")`
6. **Remove unused indices** periodically (run audit monthly)

---

## Monitoring Checklist

- [ ] Run audit weekly: `load('02-audit-indexes.js')`
- [ ] Check index sizes monthly: `getIndexSizes()`
- [ ] Validate index status monthly: `validateIndexStatus(collection)`
- [ ] Review slow query log weekly
- [ ] Monitor explain() output for COLLSCAN
- [ ] Remove unused indices quarterly

---

## References

- **Comprehensive Strategy**: `docs/MONGODB_INDEXING_STRATEGY.md`
- **MongoDB Indices**: https://docs.mongodb.com/manual/indexes/
- **ESR Rule**: https://docs.mongodb.com/manual/tutorial/design-efficient-indexes/
- **Covering Queries**: https://docs.mongodb.com/manual/tutorial/covered-queries/

---

**Version**: 1.0  
**Last Updated**: 2026-03-18  
**Status**: Production Ready
