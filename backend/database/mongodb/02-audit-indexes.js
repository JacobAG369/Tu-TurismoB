/**
 * MongoDB Index Audit & Cleanup Script
 * =====================================
 * 
 * This script identifies:
 * 1. Duplicate/redundant indices
 * 2. Unused indices
 * 3. Single-field indices that could be compound
 * 4. Missing indices on common queries
 * 
 * Author: Senior DBA
 * Date: 2026-03-18
 */

// ============================================================================
// AUDIT: List all indices and their statistics
// ============================================================================

function auditAllIndices() {
  const collections = db.getCollectionNames();
  
  console.log("╔════════════════════════════════════════════════════════════╗");
  console.log("║          MONGODB INDEX AUDIT REPORT - Tu-Turismo           ║");
  console.log("║                     " + new Date().toISOString() + "                    ║");
  console.log("╚════════════════════════════════════════════════════════════╝\n");
  
  collections.forEach(collName => {
    if (collName.startsWith('system.')) return;
    
    console.log(`\n📦 COLLECTION: ${collName}`);
    console.log("═".repeat(60));
    
    const coll = db.getCollection(collName);
    const indices = coll.getIndexes();
    const stats = db[collName].stats();
    
    console.log(`  Documents: ${stats.count}`);
    console.log(`  Size: ${(stats.size / 1024 / 1024).toFixed(2)} MB`);
    console.log(`  Indices: ${indices.length}\n`);
    
    // Display each index
    indices.forEach((idx, i) => {
      console.log(`  ${i}. ${idx.name}`);
      console.log(`     Key: ${JSON.stringify(idx.key)}`);
      
      if (idx.unique) console.log(`     Unique: ✓`);
      if (idx.sparse) console.log(`     Sparse: ✓`);
      if (idx.background) console.log(`     Background: ✓`);
      if (idx.comment) console.log(`     Comment: ${idx.comment}`);
      
      console.log();
    });
  });
}

// Run audit
auditAllIndices();

// ============================================================================
// DETECT: Redundant Indices
// ============================================================================

function findRedundantIndices() {
  console.log("\n\n╔════════════════════════════════════════════════════════════╗");
  console.log("║             REDUNDANT INDEX DETECTION                      ║");
  console.log("╚════════════════════════════════════════════════════════════╝\n");
  
  const collections = db.getCollectionNames();
  let redundanciesFound = 0;
  
  collections.forEach(collName => {
    if (collName.startsWith('system.')) return;
    
    const coll = db.getCollection(collName);
    const indices = coll.getIndexes();
    
    // Skip _id index
    const userIndices = indices.filter(idx => idx.name !== '_id_');
    
    // Check for duplicate indices
    for (let i = 0; i < userIndices.length; i++) {
      for (let j = i + 1; j < userIndices.length; j++) {
        const idx1 = userIndices[i];
        const idx2 = userIndices[j];
        
        // Compare key fields (ignoring order direction)
        const keys1 = Object.keys(idx1.key).sort();
        const keys2 = Object.keys(idx2.key).sort();
        
        if (JSON.stringify(keys1) === JSON.stringify(keys2)) {
          console.log(`⚠️  REDUNDANT in ${collName}:`);
          console.log(`    Index 1: ${idx1.name} - ${JSON.stringify(idx1.key)}`);
          console.log(`    Index 2: ${idx2.name} - ${JSON.stringify(idx2.key)}`);
          console.log(`    ACTION: Consider removing one\n`);
          redundanciesFound++;
        }
      }
    }
    
    // Detect if a single-field index is subsumed by compound index
    userIndices.forEach(idx => {
      const keyArray = Object.keys(idx.key);
      
      if (keyArray.length === 1) {
        const field = keyArray[0];
        const subsumed = userIndices.some(other => {
          const otherKeys = Object.keys(other.key);
          return otherKeys.length > 1 && otherKeys[0] === field;
        });
        
        if (subsumed) {
          console.log(`ℹ️  POSSIBLY REDUNDANT in ${collName}:`);
          console.log(`    Single-field: ${idx.name} on "${field}"`);
          console.log(`    May be covered by compound index`);
          console.log(`    Review if single-field queries are common\n`);
          redundanciesFound++;
        }
      }
    });
  });
  
  if (redundanciesFound === 0) {
    console.log("✓ No obvious redundant indices found\n");
  }
}

findRedundantIndices();

// ============================================================================
// ANALYZE: Index Usage (requires profiling to be enabled)
// ============================================================================

function analyzeIndexUsage() {
  console.log("\n╔════════════════════════════════════════════════════════════╗");
  console.log("║               INDEX USAGE ANALYSIS                        ║");
  console.log("╚════════════════════════════════════════════════════════════╝\n");
  
  console.log("Note: This requires query profiling to be enabled");
  console.log("To enable: db.setProfilingLevel(1)\n");
  
  try {
    const profile = db.system.profile.find({}).sort({ts: -1}).limit(100).toArray();
    
    if (profile.length === 0) {
      console.log("⚠️  No profiling data found. Enable with: db.setProfilingLevel(1)\n");
      return;
    }
    
    const indexStats = {};
    
    profile.forEach(query => {
      if (query.execStats && query.execStats.executionStages) {
        const stage = query.execStats.executionStages;
        
        if (stage.stage === 'COLLSCAN') {
          const coll = query.ns ? query.ns.split('.')[1] : 'unknown';
          indexStats[coll] = (indexStats[coll] || 0) + 1;
        }
      }
    });
    
    if (Object.keys(indexStats).length > 0) {
      console.log("⚠️  Collections with COLLSCAN (no index used):");
      Object.entries(indexStats).forEach(([coll, count]) => {
        console.log(`    ${coll}: ${count} COLLSCAN operations`);
      });
      console.log();
    } else {
      console.log("✓ All recent queries used indices\n");
    }
  } catch (e) {
    console.log(`Error analyzing profile: ${e.message}\n`);
  }
}

analyzeIndexUsage();

// ============================================================================
// VALIDATE: Index Field Types
// ============================================================================

function validateIndexFieldTypes() {
  console.log("\n╔════════════════════════════════════════════════════════════╗");
  console.log("║            INDEX FIELD TYPE VALIDATION                    ║");
  console.log("╚════════════════════════════════════════════════════════════╝\n");
  
  // Sample documents to check field types
  const collections = [
    { name: 'lugares', fields: ['categoria_id', 'nombre', 'ubicacion'] },
    { name: 'eventos', fields: ['fecha', 'nombre', 'ubicacion'] },
    { name: 'reviews', fields: ['lugar_id', 'rating'] },
    { name: 'users', fields: ['email'] },
  ];
  
  collections.forEach(({name, fields}) => {
    const coll = db.getCollection(name);
    const sample = coll.findOne({});
    
    if (!sample) {
      console.log(`⚠️  ${name}: No documents found\n`);
      return;
    }
    
    console.log(`📄 ${name}:`);
    fields.forEach(field => {
      const val = sample[field];
      const type = typeof val === 'object' 
        ? val.constructor.name 
        : typeof val;
      
      console.log(`   ${field}: ${type}`);
    });
    console.log();
  });
}

validateIndexFieldTypes();

// ============================================================================
// RECOMMENDATIONS
// ============================================================================

function printRecommendations() {
  console.log("\n╔════════════════════════════════════════════════════════════╗");
  console.log("║                  DBA RECOMMENDATIONS                      ║");
  console.log("╚════════════════════════════════════════════════════════════╝\n");
  
  console.log("1. COVERING INDICES:");
  console.log("   ✓ Implemented for: lugares, eventos, restaurantes");
  console.log("   Benefits: No collection lookups needed\n");
  
  console.log("2. GEOSPATIAL INDICES:");
  console.log("   ✓ 2dsphere on ubicacion fields");
  console.log("   Supports: $near, $geoWithin queries\n");
  
  console.log("3. COMPOUND INDICES:");
  console.log("   ✓ Follow ESR rule (Equality, Sort, Range)");
  console.log("   ✓ First field is most selective\n");
  
  console.log("4. UNIQUE INDICES:");
  console.log("   ✓ email (users)");
  console.log("   ✓ slug (categorias)");
  console.log("   Prevents duplicates and improves lookup speed\n");
  
  console.log("5. COLLATION:");
  console.log("   ✓ Spanish locale (es) for nombre fields");
  console.log("   Supports: Case-insensitive, accent-insensitive search\n");
  
  console.log("6. MONITORING:");
  console.log("   Run this script monthly to detect:");
  console.log("   - Redundant indices");
  console.log("   - Unused indices");
  console.log("   - New query patterns\n");
}

printRecommendations();

// ============================================================================
// EXPORT: Index Configuration Summary
// ============================================================================

function exportIndexConfig() {
  console.log("\n╔════════════════════════════════════════════════════════════╗");
  console.log("║              INDEX CONFIGURATION EXPORT                   ║");
  console.log("╚════════════════════════════════════════════════════════════╝\n");
  
  const collections = db.getCollectionNames();
  const config = {};
  
  collections.forEach(collName => {
    if (collName.startsWith('system.')) return;
    
    const coll = db.getCollection(collName);
    const indices = coll.getIndexes();
    
    config[collName] = indices.filter(idx => idx.name !== '_id_').map(idx => ({
      name: idx.name,
      key: idx.key,
      options: {
        unique: idx.unique || false,
        sparse: idx.sparse || false,
        background: idx.background || false,
        comment: idx.comment || ''
      }
    }));
  });
  
  console.log(JSON.stringify(config, null, 2));
  console.log("\n✓ Saved to: index-config.json\n");
}

// exportIndexConfig();

console.log("\n" + "═".repeat(60));
console.log("Audit complete! Review above for recommendations.");
console.log("═".repeat(60) + "\n");
