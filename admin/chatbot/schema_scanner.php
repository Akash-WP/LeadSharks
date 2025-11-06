<?php
/**
 * PHP Dynamic Database Schema Scanner
 * Automatically scans connected database for tables, columns, relationships
 * Updates JSON cache with incremental changes only
 */

require_once 'logger.php';
require_once 'db_config.php';

class SchemaScanner {
    private $cacheFile;
    private $sampleSize;
    private $config;
    
    public function __construct($cacheFile = 'cache/schema_cache.json') {
        $this->cacheFile = __DIR__ . '/' . $cacheFile;
        $this->sampleSize = 10;
        $this->config = DatabaseConfig::getInstance()->getConfig();
        
        // Ensure cache directory exists
        $cacheDir = dirname($this->cacheFile);
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
    }
    
    public function getTableSchema($silent = false) {
        try {
            $conn = getDbConnection();
            
            $schemaData = [
                'scan_timestamp' => date('c'),
                'database_info' => [],
                'tables' => [],
                'relationships' => [],
                'table_stats' => []
            ];
            
            // Get database info
            $result = $conn->query("SELECT DATABASE() as db_name, VERSION() as version");
            $dbInfo = $result->fetch_assoc();
            $schemaData['database_info'] = $dbInfo;
            
            // Get all tables in current database
            $tablesQuery = "
                SELECT table_name, table_rows, table_comment, create_time, update_time
                FROM information_schema.tables 
                WHERE table_schema = DATABASE() 
                ORDER BY table_name
            ";
            
            $result = $conn->query($tablesQuery);
            $tables = [];
            while ($row = $result->fetch_assoc()) {
                $tables[] = $row;
            }
            
            if (!$silent) {
                echo "Scanning " . count($tables) . " tables...\n";
            }
            
            foreach ($tables as $table) {
                $tableName = $table['table_name'];
                if (!$silent) {
                    echo "  Scanning: $tableName\n";
                }
                
                // Get column information
                $columnsQuery = "
                    SELECT 
                        column_name,
                        data_type,
                        is_nullable,
                        column_default,
                        extra,
                        column_comment,
                        character_maximum_length,
                        numeric_precision,
                        numeric_scale
                    FROM information_schema.columns 
                    WHERE table_schema = DATABASE() AND table_name = ?
                    ORDER BY ordinal_position
                ";
                
                $stmt = $conn->prepare($columnsQuery);
                $stmt->bind_param('s', $tableName);
                $stmt->execute();
                $columnResult = $stmt->get_result();
                
                $columns = [];
                while ($row = $columnResult->fetch_assoc()) {
                    $columns[] = $row;
                }
                
                // Get foreign key relationships
                $fkQuery = "
                    SELECT 
                        column_name,
                        referenced_table_name,
                        referenced_column_name,
                        constraint_name
                    FROM information_schema.key_column_usage
                    WHERE table_schema = DATABASE() 
                    AND table_name = ? 
                    AND referenced_table_name IS NOT NULL
                ";
                
                $stmt = $conn->prepare($fkQuery);
                $stmt->bind_param('s', $tableName);
                $stmt->execute();
                $fkResult = $stmt->get_result();
                
                $foreignKeys = [];
                while ($row = $fkResult->fetch_assoc()) {
                    $foreignKeys[] = $row;
                }
                
                // Get indexes
                $indexResult = $conn->query("SHOW INDEX FROM `$tableName`");
                $indexes = [];
                while ($row = $indexResult->fetch_assoc()) {
                    $indexes[] = $row;
                }
                
                // Store table schema
                $schemaData['tables'][$tableName] = [
                    'columns' => $columns,
                    'foreign_keys' => $foreignKeys,
                    'indexes' => $indexes,
                    'table_info' => $table,
                    'row_count' => $table['table_rows'] ?: 0,
                    'last_update' => $table['update_time'] ? date('c', strtotime($table['update_time'])) : null
                ];
                
                // Add to relationships list
                foreach ($foreignKeys as $fk) {
                    $schemaData['relationships'][] = [
                        'from_table' => $tableName,
                        'from_column' => $fk['column_name'],
                        'to_table' => $fk['referenced_table_name'],
                        'to_column' => $fk['referenced_column_name'],
                        'constraint_name' => $fk['constraint_name']
                    ];
                }
                
                // Get table statistics and sample data
                try {
                    $countResult = $conn->query("SELECT COUNT(*) as count FROM `$tableName`");
                    $countRow = $countResult->fetch_assoc();
                    $actualCount = $countRow['count'];
                    
                    // Get sample data for context
                    $sampleData = [];
                    if ($actualCount > 0) {
                        $sampleResult = $conn->query("SELECT * FROM `$tableName` LIMIT " . $this->sampleSize);
                        while ($row = $sampleResult->fetch_assoc()) {
                            // Convert datetime objects to strings for JSON serialization
                            $cleanRow = [];
                            foreach ($row as $key => $value) {
                                if ($value instanceof DateTime) {
                                    $cleanRow[$key] = $value->format('c');
                                } else {
                                    $cleanRow[$key] = $value;
                                }
                            }
                            $sampleData[] = $cleanRow;
                        }
                    }
                    
                    $schemaData['table_stats'][$tableName] = [
                        'actual_row_count' => $actualCount,
                        'sample_data' => $sampleData,
                        'has_data' => $actualCount > 0
                    ];
                    
                } catch (Exception $e) {
                    echo "      Could not get stats for $tableName: " . $e->getMessage() . "\n";
                    $schemaData['table_stats'][$tableName] = [
                        'actual_row_count' => 0,
                        'sample_data' => [],
                        'has_data' => false,
                        'error' => $e->getMessage()
                    ];
                }
            }
            
            return $schemaData;
            
        } catch (Exception $e) {
            echo "ERROR: Schema scanning failed: " . $e->getMessage() . "\n";
            return ['error' => $e->getMessage()];
        }
    }
    
    public function loadCachedSchema() {
        try {
            if (file_exists($this->cacheFile)) {
                $content = file_get_contents($this->cacheFile);
                return json_decode($content, true);
            }
        } catch (Exception $e) {
            echo "WARNING: Cache load error: " . $e->getMessage() . "\n";
        }
        return null;
    }
    
    public function saveSchemaCache($schemaData, $silent = false) {
        try {
            $jsonContent = json_encode($schemaData, JSON_PRETTY_PRINT);
            file_put_contents($this->cacheFile, $jsonContent);
            
            if (!$silent) {
                $sizeKb = round(filesize($this->cacheFile) / 1024, 1);
                echo "Schema cached successfully ({$sizeKb}KB)\n";
            }
            
        } catch (Exception $e) {
            if (!$silent) {
                echo "ERROR: Cache save error: " . $e->getMessage() . "\n";
            }
        }
    }
    
    public function calculateSchemaHash($schemaData) {
        // Create structure-only version for comparison
        $structureOnly = [];
        if (isset($schemaData['tables'])) {
            foreach ($schemaData['tables'] as $tableName => $tableData) {
                $structureOnly[$tableName] = [
                    'columns' => $tableData['columns'] ?? [],
                    'foreign_keys' => $tableData['foreign_keys'] ?? [],
                    'indexes' => $tableData['indexes'] ?? []
                ];
            }
        }
        
        return md5(json_encode($structureOnly));
    }
    
    public function needsRefresh($cachedSchema, $maxAgeHours = 24) {
        if (!$cachedSchema) {
            return true;
        }
        
        // Check age
        try {
            $scanTime = new DateTime($cachedSchema['scan_timestamp']);
            $now = new DateTime();
            $age = $now->diff($scanTime);
            $ageHours = ($age->days * 24) + $age->h;
            
            if ($ageHours > $maxAgeHours) {
                echo "🕐 Cache expired (age: {$ageHours}h)\n";
                return true;
            }
        } catch (Exception $e) {
            return true;
        }
        
        // Check if database structure changed by comparing table counts
        try {
            $conn = getDbConnection();
            $result = $conn->query("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = DATABASE()");
            $row = $result->fetch_assoc();
            $currentTableCount = $row['count'];
            
            $cachedTableCount = count($cachedSchema['tables'] ?? []);
            if ($currentTableCount != $cachedTableCount) {
                echo "Table count changed: $cachedTableCount → $currentTableCount\n";
                return true;
            }
            
        } catch (Exception $e) {
            echo "WARNING: Could not check table count: " . $e->getMessage() . "\n";
            return true;
        }
        
        return false;
    }
    
    public function getFreshSchema($forceRefresh = false, $silent = false) {
        ChatBotLogger::schemaAction("getFreshSchema", ['forceRefresh' => $forceRefresh, 'silent' => $silent]);
        
        if (!$silent) {
            echo "Checking database schema...\n";
        }
        
        // Load cached schema
        $timer = ChatBotLogger::startTimer('schema_cache_load');
        $cachedSchema = $this->loadCachedSchema();
        ChatBotLogger::endTimer('schema_cache_load', $timer);
        
        // Check if refresh needed
        if (!$forceRefresh && $cachedSchema && !$this->needsRefresh($cachedSchema)) {
            if (!$silent) {
                echo "Using cached schema (up to date)\n";
            }
            return $cachedSchema;
        }
        
        // Scan fresh schema
        if (!$silent) {
            echo "Refreshing schema from database...\n";
        }
        $freshSchema = $this->getTableSchema($silent);
        
        if (!isset($freshSchema['error'])) {
            $this->saveSchemaCache($freshSchema, $silent);
            if (!$silent) {
                echo "Schema scan completed\n";
            }
        }
        
        return $freshSchema;
    }
    
    public function getSchemaSummary($schemaData) {
        if (isset($schemaData['error'])) {
            return "Error loading schema: " . $schemaData['error'];
        }
        
        $lines = [];
        $lines[] = "Database: " . ($schemaData['database_info']['db_name'] ?? 'Unknown');
        $lines[] = "Last Scan: " . ($schemaData['scan_timestamp'] ?? 'Unknown');
        $lines[] = "Tables: " . count($schemaData['tables'] ?? []);
        $lines[] = "";
        
        // Table summaries
        foreach ($schemaData['tables'] ?? [] as $tableName => $tableData) {
            $stats = $schemaData['table_stats'][$tableName] ?? [];
            $rowCount = $stats['actual_row_count'] ?? 0;
            
            $lines[] = "Table: $tableName (" . number_format($rowCount) . " rows)";
            
            // Columns
            foreach ($tableData['columns'] ?? [] as $col) {
                $nullable = ($col['is_nullable'] == 'YES') ? '' : 'NOT NULL';
                $extra = $col['extra'] ? ' ' . $col['extra'] : '';
                $lines[] = "  - {$col['column_name']} ({$col['data_type']}) $nullable$extra";
            }
            
            // Foreign keys
            foreach ($tableData['foreign_keys'] ?? [] as $fk) {
                $lines[] = "  → {$fk['column_name']} → {$fk['referenced_table_name']}.{$fk['referenced_column_name']}";
            }
            
            $lines[] = "";
        }
        
        return implode("\n", $lines);
    }
}

// Global scanner instance
$scanner = new SchemaScanner();

function getCurrentSchema($forceRefresh = false, $silent = false) {
    global $scanner;
    return $scanner->getFreshSchema($forceRefresh, $silent);
}

function getSchemaForLLM($forceRefresh = false, $silent = true) {
    global $scanner;
    $schemaData = getCurrentSchema($forceRefresh, $silent);
    return $scanner->getSchemaSummary($schemaData);
}

// Test the schema scanner if this file is run directly
if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    echo "<h2>Database Schema Scanner Test</h2>";
    echo "<hr>";
    echo "<pre>";
    
    // Test schema scanning
    $schema = getCurrentSchema(true);
    if (!isset($schema['error'])) {
        echo "Schema scan successful!\n";
        echo "Found " . count($schema['tables']) . " tables\n\n";
        
        // Print summary
        $summary = $scanner->getSchemaSummary($schema);
        echo "Schema Summary:\n";
        echo substr($summary, 0, 2000) . (strlen($summary) > 2000 ? "..." : "");
    } else {
        echo "ERROR: Schema scan failed: " . $schema['error'] . "\n";
    }
    
    echo "</pre>";
}

?>