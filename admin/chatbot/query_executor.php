<?php
/**
 * Safe Database Query Executor (PHP Version)
 * Executes generated SQL queries with security checks and result formatting
 */

require_once 'logger.php';
require_once 'db_config.php';

class QueryExecutor {
    private $maxResultRows;
    private $allowedStatements;
    private $forbiddenKeywords;
    
    public function __construct() {
        $this->maxResultRows = 1000; // Safety limit
        $this->allowedStatements = ['SELECT']; // Only allow SELECT for safety
        $this->forbiddenKeywords = [
            'DROP', 'DELETE', 'UPDATE', 'INSERT', 'ALTER', 'CREATE', 'TRUNCATE',
            'GRANT', 'REVOKE', 'SET', 'SHOW', 'DESCRIBE', 'EXPLAIN'
        ];
    }
    
    public function isSafeQuery($sql) {
        $sqlUpper = strtoupper(trim($sql));
        
        // Remove comments and extra whitespace
        $sqlClean = preg_replace('/--.*?\n/', ' ', $sql);
        $sqlClean = preg_replace('/\/\*.*?\*\//', ' ', $sqlClean);
        $sqlClean = preg_replace('/\s+/', ' ', $sqlClean);
        $sqlClean = trim($sqlClean);
        
        // Check if starts with SELECT
        if (!preg_match('/^SELECT\s/i', $sqlClean)) {
            return [
                'safe' => false,
                'message' => 'Only SELECT queries are allowed'
            ];
        }
        
        // Check for forbidden keywords
        foreach ($this->forbiddenKeywords as $keyword) {
            if (strpos($sqlUpper, $keyword) !== false) {
                return [
                    'safe' => false,
                    'message' => "Forbidden keyword detected: $keyword"
                ];
            }
        }
        
        // Check for multiple statements (basic check)
        if (substr_count($sqlClean, ';') > 1) {
            return [
                'safe' => false,
                'message' => 'Multiple statements not allowed'
            ];
        }
        
        // Check for suspicious patterns
        $suspiciousPatterns = [
            '/UNION.*SELECT.*FROM.*information_schema/i',
            '/SELECT.*FROM.*mysql\./i',
            '/LOAD_FILE\s*\(/i',
            '/INTO\s+OUTFILE/i',
            '/INTO\s+DUMPFILE/i'
        ];
        
        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $sqlUpper)) {
                return [
                    'safe' => false,
                    'message' => "Suspicious pattern detected"
                ];
            }
        }
        
        return [
            'safe' => true,
            'message' => 'Query is safe'
        ];
    }
    
    public function executeQuery($sql) {
        ChatBotLogger::stepStart("Query Execution");
        ChatBotLogger::databaseQuery($sql, 'user_generated');
        
        try {
            // Safety check
            $timer = ChatBotLogger::startTimer('security_validation');
            $safetyCheck = $this->isSafeQuery($sql);
            ChatBotLogger::endTimer('security_validation', $timer);
            
            if (!$safetyCheck['safe']) {
                ChatBotLogger::stepEnd("Query Execution", false, ['reason' => 'Security validation failed', 'message' => $safetyCheck['message']]);
                return [
                    'success' => false,
                    'error' => "Security violation: " . $safetyCheck['message'],
                    'error_type' => 'SECURITY_ERROR'
                ];
            }
            
            ChatBotLogger::debug("Query passed security validation");
            
            // Get database connection
            $timer = ChatBotLogger::startTimer('db_connection');
            $conn = getDbConnection();
            ChatBotLogger::endTimer('db_connection', $timer);
            
            // Execute query with timing
            $startTime = microtime(true);
            $result = $conn->query($sql);
            $executionTime = microtime(true) - $startTime;
            
            if (!$result) {
                throw new Exception($conn->error);
            }
            
            // Fetch results with limit
            $results = [];
            $rowCount = 0;
            
            while (($row = $result->fetch_assoc()) && $rowCount < $this->maxResultRows) {
                $results[] = $row;
                $rowCount++;
            }
            
            // Check if result set was truncated
            $wasTruncated = ($result->num_rows > $this->maxResultRows);
            
            // Get column information
            $columnInfo = [];
            if ($result->field_count > 0) {
                $fields = $result->fetch_fields();
                foreach ($fields as $field) {
                    $columnInfo[] = [
                        'name' => $field->name,
                        'type' => $this->getFieldType($field->type)
                    ];
                }
            }
            
            // Format results for JSON serialization
            $formattedResults = [];
            foreach ($results as $row) {
                $formattedRow = [];
                foreach ($row as $key => $value) {
                    if ($value === null) {
                        $formattedRow[$key] = null;
                    } else {
                        $formattedRow[$key] = (string)$value;
                    }
                }
                $formattedResults[] = $formattedRow;
            }
            
            return [
                'success' => true,
                'data' => $formattedResults,
                'metadata' => [
                    'row_count' => count($formattedResults),
                    'was_truncated' => $wasTruncated,
                    'max_rows_limit' => $this->maxResultRows,
                    'execution_time_seconds' => round($executionTime, 3),
                    'columns' => $columnInfo,
                    'executed_query' => $sql
                ],
                'summary' => $this->generateResultSummary($formattedResults, $sql)
            ];
            
        } catch (Exception $e) {
            $errorDetails = [
                'success' => false,
                'error' => $e->getMessage(),
                'error_type' => 'EXECUTION_ERROR',
                'executed_query' => $sql
            ];
            
            // Try to classify the error
            $errorStr = strtolower($e->getMessage());
            if (strpos($errorStr, 'table') !== false && strpos($errorStr, "doesn't exist") !== false) {
                $errorDetails['error_type'] = 'TABLE_NOT_FOUND';
            } elseif (strpos($errorStr, 'column') !== false && strpos($errorStr, 'unknown') !== false) {
                $errorDetails['error_type'] = 'COLUMN_NOT_FOUND';
            } elseif (strpos($errorStr, 'syntax error') !== false) {
                $errorDetails['error_type'] = 'SYNTAX_ERROR';
            }
            
            return $errorDetails;
        }
    }
    
    private function generateResultSummary($results, $sql) {
        if (empty($results)) {
            return [
                'message' => 'Query executed successfully but returned no results',
                'insights' => [
                    'The query found no matching records',
                    'Consider adjusting your search criteria'
                ]
            ];
        }
        
        $summary = [
            'message' => 'Found ' . count($results) . ' record(s)',
            'insights' => []
        ];
        
        // Basic insights based on result size
        $resultCount = count($results);
        if ($resultCount == 1) {
            $summary['insights'][] = 'Single record found - specific match';
        } elseif ($resultCount < 10) {
            $summary['insights'][] = 'Small result set - good for detailed analysis';
        } elseif ($resultCount < 100) {
            $summary['insights'][] = 'Moderate result set - consider filtering if needed';
        } else {
            $summary['insights'][] = 'Large result set - consider using LIMIT or more specific filters';
        }
        
        // Analyze columns with data
        if (!empty($results)) {
            $firstRow = $results[0];
            $nonNullColumns = 0;
            foreach ($firstRow as $value) {
                if ($value !== null && $value !== '') {
                    $nonNullColumns++;
                }
            }
            $totalColumns = count($firstRow);
            
            if ($nonNullColumns < $totalColumns) {
                $summary['insights'][] = "Some columns contain null values ($nonNullColumns/$totalColumns populated)";
            }
        }
        
        // Query type insights
        $sqlUpper = strtoupper($sql);
        if (strpos($sqlUpper, 'COUNT(') !== false) {
            $summary['insights'][] = 'Count query - shows aggregate numbers';
        } elseif (strpos($sqlUpper, 'GROUP BY') !== false) {
            $summary['insights'][] = 'Grouped data - showing summary by category';
        } elseif (strpos($sqlUpper, 'ORDER BY') !== false) {
            $summary['insights'][] = 'Sorted results - showing data in specific order';
        } elseif (strpos($sqlUpper, 'JOIN') !== false) {
            $summary['insights'][] = 'Multi-table query - combining related data';
        }
        
        return $summary;
    }
    
    public function validateQuerySyntax($sql) {
        try {
            $conn = getDbConnection();
            
            // Remove trailing semicolon for EXPLAIN
            $sqlForExplain = rtrim($sql, ';');
            
            $result = $conn->query("EXPLAIN $sqlForExplain");
            
            if ($result) {
                $explainInfo = [];
                while ($row = $result->fetch_assoc()) {
                    $explainInfo[] = $row;
                }
                
                return [
                    'valid' => true,
                    'message' => 'Query syntax is valid',
                    'explain_info' => $explainInfo
                ];
            } else {
                return [
                    'valid' => false,
                    'message' => 'Syntax error: ' . $conn->error,
                    'error' => $conn->error
                ];
            }
            
        } catch (Exception $e) {
            return [
                'valid' => false,
                'message' => 'Syntax error: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function getFieldType($type) {
        $types = [
            MYSQLI_TYPE_DECIMAL => 'decimal',
            MYSQLI_TYPE_TINY => 'tinyint',
            MYSQLI_TYPE_SHORT => 'smallint',
            MYSQLI_TYPE_LONG => 'int',
            MYSQLI_TYPE_FLOAT => 'float',
            MYSQLI_TYPE_DOUBLE => 'double',
            MYSQLI_TYPE_NULL => 'null',
            MYSQLI_TYPE_TIMESTAMP => 'timestamp',
            MYSQLI_TYPE_LONGLONG => 'bigint',
            MYSQLI_TYPE_INT24 => 'mediumint',
            MYSQLI_TYPE_DATE => 'date',
            MYSQLI_TYPE_TIME => 'time',
            MYSQLI_TYPE_DATETIME => 'datetime',
            MYSQLI_TYPE_YEAR => 'year',
            MYSQLI_TYPE_NEWDATE => 'date',
            MYSQLI_TYPE_VAR_STRING => 'varchar',
            MYSQLI_TYPE_BIT => 'bit',
            MYSQLI_TYPE_JSON => 'json',
            MYSQLI_TYPE_NEWDECIMAL => 'decimal',
            MYSQLI_TYPE_ENUM => 'enum',
            MYSQLI_TYPE_SET => 'set',
            MYSQLI_TYPE_TINY_BLOB => 'tinyblob',
            MYSQLI_TYPE_MEDIUM_BLOB => 'mediumblob',
            MYSQLI_TYPE_LONG_BLOB => 'longblob',
            MYSQLI_TYPE_BLOB => 'blob',
            MYSQLI_TYPE_STRING => 'char',
            MYSQLI_TYPE_GEOMETRY => 'geometry'
        ];
        
        return $types[$type] ?? 'unknown';
    }
}

// Global executor instance
$executor = new QueryExecutor();

function executeSqlQuery($sql) {
    global $executor;
    return $executor->executeQuery($sql);
}

function validateSqlSyntax($sql) {
    global $executor;
    return $executor->validateQuerySyntax($sql);
}

function testQueryExecution() {
    $testQueries = [
        "SELECT COUNT(*) as total_records FROM lead_list;",
        "SELECT * FROM users LIMIT 5;",
        "SELECT company_name, city FROM client_list WHERE city = 'Pune' LIMIT 10;"
    ];
    
    echo "<h2>🧪 Testing Query Execution</h2>";
    echo "<hr>";
    
    foreach ($testQueries as $i => $query) {
        echo "<h4>" . ($i + 1) . ". Testing: <code>$query</code></h4>";
        
        $result = executeSqlQuery($query);
        
        if ($result['success']) {
            echo "<p style='color: green;'>✅ Success: " . $result['metadata']['row_count'] . 
                 " rows in " . $result['metadata']['execution_time_seconds'] . "s</p>";
            echo "<p>💡 " . $result['summary']['message'] . "</p>";
            
            if (!empty($result['data']) && count($result['data']) <= 5) {
                echo "<pre>" . json_encode($result['data'], JSON_PRETTY_PRINT) . "</pre>";
            }
        } else {
            echo "<p style='color: red;'>❌ Failed: " . $result['error'] . "</p>";
        }
        echo "<hr>";
    }
}

// Test the query executor if this file is run directly
if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    testQueryExecution();
}

?>