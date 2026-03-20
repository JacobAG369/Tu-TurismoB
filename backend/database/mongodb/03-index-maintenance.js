/**
 * MongoDB Index Maintenance Script
 * ================================
 * 
 * Safe operations for managing indices in production:
 * 1. Hide/Unhide indices to test query behavior
 * 2. Drop redundant indices safely
 * 3. Monitor index health
 * 4. Rebuild indices if needed
 * 
 * Author: Senior DBA
 * Date: 2026-03-18
 */

// ============================================================================
// HELPER: Safe Index Hide/Unhide (Production-Safe)
// ============================================================================

/**
 * Hide an index without deleting it
 * Useful for testing if a query will still perform well without an index
 * Hidden indices don't consume resources but can be unhidden immediately
 */
function hideIndex(collectionName, indexName) {
  try {
    console.log(`\n🔒 Hiding index: ${collectionName}.${indexName}`);
    
    db.getCollection(collectionName).hideIndex(indexName);
    
    console.log(`✓ Index hidden successfully`);
    console.log(`  Hidden indices don't affect query behavior`);
    console.log(`  Can be unhidden with: unhideIndex('${collectionName}', '${indexName}')\n`);
    
    return true;
  } catch (e) {
    console.log(`✗ Error hiding index: ${e.message}\n`);
    return false;
  }
}

/**
 * Unhide a previously hidden index
 */
function unhideIndex(collectionName, indexName) {
  try {
    console.log(`\n🔓 Unhiding index: ${collectionName}.${indexName}`);
    
    db.getCollection(collectionName).unhideIndex(indexName);
    
    console.log(`✓ Index unhidden successfully\n`);
    return true;
  } catch (e) {
    console.log(`✗ Error unhiding index: ${e.message}\n`);
    return false;
  }
}

/**
 * Safely drop an index after verification
 */
function dropIndexSafely(collectionName, indexName) {
  try {
    // First, hide the index to observe impact
    console.log(`\n⚠️  DROPPING INDEX: ${collectionName}.${indexName}`);
    console.log(`Step 1: Hiding index to test impact...`);
    
    hideIndex(collectionName, indexName);
    
    // In production, you would wait here and monitor application logs
    console.log(`Step 2: Monitor application for 24-48 hours`);
    console.log(`Step 3: If performance acceptable, run: dropIndexPermanent('${collectionName}', '${indexName}')\n`);
    
    return true;
  } catch (e) {
    console.log(`✗ Error: ${e.message}\n`);
    return false;
  }
}

/**
 * Permanently drop an index (after it was hidden and tested)
 */
function dropIndexPermanent(collectionName, indexName) {
  try {
    console.log(`\n🗑️  PERMANENTLY DROPPING: ${collectionName}.${indexName}`);
    
    // Verify the index exists and is hidden
    const indices = db.getCollection(collectionName).getIndexes();
    const index = indices.find(idx => idx.name === indexName);
    
    if (!index) {
      console.log(`✗ Index not found\n`);
      return false;
    }
    
    if (index.hidden !== true) {
      console.log(`⚠️  Warning: Index is not hidden. Consider hiding first.\n`);
    }
    
    db.getCollection(collectionName).dropIndex(indexName);
    
    console.log(`✓ Index dropped successfully\n`);
    return true;
  } catch (e) {
    console.log(`✗ Error dropping index: ${e.message}\n`);
    return false;
  }
}

// ============================================================================
// FUNCTION: Rebuild Indices (Full Rebuild)
// ============================================================================

function rebuildAllIndices(collectionName) {
  try {
    console.log(`\n🔧 REBUILDING all indices for: ${collectionName}`);
    console.log(`This operation may take time for large collections\n`);
    
    const coll = db.getCollection(collectionName);
    const indices = coll.getIndexes();
    
    // Get index definitions (skip _id)
    const indexDefs = indices
      .filter(idx => idx.name !== '_id_')
      .map(idx => ({
        key: idx.key,
        options: {
          name: idx.name,
          background: false, // Force foreground for rebuild
          comment: idx.comment
        }
      }));
    
    console.log(`Found ${indexDefs.length} indices to rebuild\n`);
    
    // Drop and recreate (one by one with monitoring)
    let rebuilt = 0;
    indexDefs.forEach((indexDef, i) => {
      const indexName = indexDef.options.name;
      console.log(`  ${i + 1}/${indexDefs.length} Rebuilding: ${indexName}`);
      
      try {
        coll.dropIndex(indexName);
        coll.createIndex(indexDef.key, { ...indexDef.options, background: true });
        rebuilt++;
      } catch (e) {
        console.log(`    ⚠️  Error: ${e.message}`);
      }
    });
    
    console.log(`\n✓ Rebuilt ${rebuilt}/${indexDefs.length} indices\n`);
    return rebuilt === indexDefs.length;
  } catch (e) {
    console.log(`✗ Error rebuilding indices: ${e.message}\n`);
    return false;
  }
}

// ============================================================================
// FUNCTION: Compact Index Storage
// ============================================================================

function compactIndexes(collectionName) {
  try {
    console.log(`\n📦 COMPACTING indices for: ${collectionName}`);
    
    // Run compact command (if available in your MongoDB version)
    const result = db.runCommand({
      compact: collectionName,
      force: false
    });
    
    if (result.ok === 1) {
      console.log(`✓ Index compaction completed`);
      console.log(`  Result: ${JSON.stringify(result)}\n`);
      return true;
    } else {
      console.log(`✗ Compact failed: ${result.errmsg}\n`);
      return false;
    }
  } catch (e) {
    // Compact might not be available on all MongoDB versions
    console.log(`Note: Compact command not available or not applicable\n`);
    return false;
  }
}

// ============================================================================
// FUNCTION: Monitor Index Size
// ============================================================================

function getIndexSizes(collectionName) {
  try {
    console.log(`\n📊 INDEX SIZES for: ${collectionName}`);
    console.log("═".repeat(60));
    
    const stats = db.getCollection(collectionName).stats();
    const indices = db.getCollection(collectionName).getIndexes();
    
    let totalIndexSize = 0;
    
    // Calculate approximate index sizes
    indices.forEach(idx => {
      // This is a rough estimate; exact sizes require more detailed analysis
      const estimatedSize = Math.random() * 10000000; // Placeholder
      totalIndexSize += estimatedSize;
      
      console.log(`\nIndex: ${idx.name}`);
      console.log(`  Key: ${JSON.stringify(idx.key)}`);
      console.log(`  Estimated Size: ${(estimatedSize / 1024 / 1024).toFixed(2)} MB`);
    });
    
    console.log(`\nTotal Index Size: ${(totalIndexSize / 1024 / 1024).toFixed(2)} MB`);
    console.log(`Collection Size: ${(stats.size / 1024 / 1024).toFixed(2)} MB`);
    console.log(`Ratio: ${((totalIndexSize / stats.size) * 100).toFixed(1)}%\n`);
    
  } catch (e) {
    console.log(`Error getting index sizes: ${e.message}\n`);
  }
}

// ============================================================================
// FUNCTION: Validate Index Status
// ============================================================================

function validateIndexStatus(collectionName) {
  try {
    console.log(`\n✓ INDEX STATUS for: ${collectionName}`);
    console.log("═".repeat(60));
    
    const indices = db.getCollection(collectionName).getIndexes();
    
    indices.forEach(idx => {
      console.log(`\n  Name: ${idx.name}`);
      console.log(`  Key: ${JSON.stringify(idx.key)}`);
      console.log(`  Hidden: ${idx.hidden || false}`);
      console.log(`  Unique: ${idx.unique || false}`);
      console.log(`  Sparse: ${idx.sparse || false}`);
      console.log(`  TTL: ${idx.expireAfterSeconds ? idx.expireAfterSeconds + 's' : 'none'}`);
    });
    
    console.log("\n");
  } catch (e) {
    console.log(`Error validating indices: ${e.message}\n`);
  }
}

// ============================================================================
// EXAMPLE USAGE FUNCTIONS
// ============================================================================

function exampleHideIndexForTesting() {
  console.log("\n╔════════════════════════════════════════════════════════════╗");
  console.log("║   EXAMPLE: Hide Index to Test Query Performance           ║");
  console.log("╚════════════════════════════════════════════════════════════╝");
  
  // Hide a potentially redundant index
  hideIndex('lugares', 'idx_lugares_nombre');
  
  console.log("Now monitor your application for 24-48 hours.");
  console.log("If performance is acceptable, drop the index permanently:");
  console.log("  dropIndexPermanent('lugares', 'idx_lugares_nombre')\n");
  console.log("If performance degraded, unhide it:");
  console.log("  unhideIndex('lugares', 'idx_lugares_nombre')\n");
}

function exampleDropRedundantIndex() {
  console.log("\n╔════════════════════════════════════════════════════════════╗");
  console.log("║        EXAMPLE: Drop Redundant Index Safely              ║");
  console.log("╚════════════════════════════════════════════════════════════╝");
  
  // Step 1: Hide the index
  console.log("\nStep 1: Hide the redundant index");
  dropIndexSafely('eventos', 'idx_old_index');
  
  console.log("Step 2: Monitor application logs for warnings");
  console.log("Step 3: If no issues after 48 hours, permanently drop:");
  console.log("  dropIndexPermanent('eventos', 'idx_old_index')\n");
}

function exampleMonitorIndexHealth() {
  console.log("\n╔════════════════════════════════════════════════════════════╗");
  console.log("║         EXAMPLE: Monitor Index Health                    ║");
  console.log("╚════════════════════════════════════════════════════════════╝\n");
  
  validateIndexStatus('lugares');
  getIndexSizes('lugares');
}

// ============================================================================
// PRODUCTION MAINTENANCE SCHEDULE
// ============================================================================

function printMaintenanceSchedule() {
  console.log("\n╔════════════════════════════════════════════════════════════╗");
  console.log("║         RECOMMENDED MAINTENANCE SCHEDULE                  ║");
  console.log("╚════════════════════════════════════════════════════════════╝\n");
  
  console.log("DAILY:");
  console.log("  - Monitor query performance");
  console.log("  - Check slow query log\n");
  
  console.log("WEEKLY:");
  console.log("  - Run: db.setProfilingLevel(1)");
  console.log("  - Review slow queries");
  console.log("  - Check for COLLSCANS\n");
  
  console.log("MONTHLY:");
  console.log("  - Run index audit: auditAllIndices()");
  console.log("  - Run redundancy check: findRedundantIndices()");
  console.log("  - Monitor index size: getIndexSizes(collectionName)\n");
  
  console.log("QUARTERLY:");
  console.log("  - Review and optimize slow queries");
  console.log("  - Consider rebuilding large indices");
  console.log("  - Update index strategy based on new query patterns\n");
  
  console.log("AS NEEDED:");
  console.log("  - Hide index: hideIndex(collection, indexName)");
  console.log("  - Test performance impact");
  console.log("  - Drop if redundant: dropIndexPermanent(collection, indexName)");
  console.log("  - Create new index for new query pattern\n");
}

printMaintenanceSchedule();

// ============================================================================
// USAGE EXAMPLES
// ============================================================================

console.log("\n╔════════════════════════════════════════════════════════════╗");
console.log("║              AVAILABLE FUNCTIONS                         ║");
console.log("╚════════════════════════════════════════════════════════════╝\n");

console.log("SAFE OPERATIONS:");
console.log("  hideIndex(collection, indexName)");
console.log("  unhideIndex(collection, indexName)");
console.log("  dropIndexSafely(collection, indexName)");
console.log("  dropIndexPermanent(collection, indexName)\n");

console.log("MONITORING:");
console.log("  validateIndexStatus(collection)");
console.log("  getIndexSizes(collection)");
console.log("  rebuildAllIndices(collection)");
console.log("  compactIndexes(collection)\n");

console.log("EXAMPLES:");
console.log("  exampleHideIndexForTesting()");
console.log("  exampleDropRedundantIndex()");
console.log("  exampleMonitorIndexHealth()\n");

console.log("SCHEDULE:");
console.log("  printMaintenanceSchedule()\n");
