<?php
/**
 * PHP API Endpoints for ChatBot
 * Handles generate_sql, execute_query, and feedback requests
 */

// Enable CORS for cross-origin requests
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Handle OPTIONS request for CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Set content type to JSON
header('Content-Type: application/json');

// Include required files
require_once 'logger.php';  // Add logger first
require_once 'db_config.php';
require_once 'llm_generator.php';
require_once 'query_executor.php';

// Error reporting for development (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to user, but log them

// Function to send JSON response
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    
    // Clean any special characters that might break JSON
    $cleanData = cleanArrayForJson($data);
    
    // Use JSON flags for better encoding
    $json = json_encode($cleanData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        // Fallback if encoding fails
        $json = json_encode([
            'success' => false,
            'error' => 'JSON encoding failed: ' . json_last_error_msg()
        ]);
    }
    
    echo $json;
    exit();
}

// Helper function to clean data for JSON encoding
function cleanArrayForJson($data) {
    if (is_array($data)) {
        $cleaned = [];
        foreach ($data as $key => $value) {
            $cleaned[$key] = cleanArrayForJson($value);
        }
        return $cleaned;
    } else if (is_string($data)) {
        // Remove any non-printable characters and emojis that might break JSON
        $cleaned = preg_replace('/[\x00-\x1F\x7F-\x9F]/', '', $data);
        $cleaned = mb_convert_encoding($cleaned, 'UTF-8', 'UTF-8');
        return $cleaned;
    } else {
        return $data;
    }
}

// Use ChatBotLogger for all logging (old logError function removed to avoid conflicts)

try {
    // Check if request method is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        ChatBotLogger::warning("Invalid request method", ['method' => $_SERVER['REQUEST_METHOD']]);
        sendResponse(['error' => 'Only POST method allowed'], 405);
    }
    
    // Get JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        ChatBotLogger::error("Invalid JSON input", ['json_error' => json_last_error_msg(), 'raw_input' => substr($input, 0, 200)]);
        sendResponse(['error' => 'Invalid JSON input'], 400);
    }
    
    // Check if action is specified
    if (!isset($data['action'])) {
        ChatBotLogger::error("No action specified", ['data' => $data]);
        sendResponse(['error' => 'Action parameter required'], 400);
    }
    
    $action = $data['action'];
    ChatBotLogger::apiRequest($action, $data);
    
    // Route to appropriate handler
    switch ($action) {
        case 'generate_sql':
            handleGenerateSQL($data);
            break;
            
        case 'execute_query':
            handleExecuteQuery($data);
            break;
            
        case 'feedback':
            handleFeedback($data);
            break;
            
        case 'schema_info':
            handleSchemaInfo();
            break;
            
        case 'health_check':
            handleHealthCheck();
            break;
            
        default:
            sendResponse(['error' => 'Unknown action: ' . $action], 400);
    }
    
} catch (Exception $e) {
    ChatBotLogger::error('API Error: ' . $e->getMessage(), [
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
    
    sendResponse([
        'error' => 'Internal server error',
        'message' => 'An unexpected error occurred'
    ], 500);
}

/**
 * Handle SQL generation request
 */
function handleGenerateSQL($data) {
    ChatBotLogger::stepStart("SQL Generation");
    
    // Validate input
    if (!isset($data['prompt']) || empty(trim($data['prompt']))) {
        ChatBotLogger::stepEnd("SQL Generation", false, ['error' => 'Missing prompt']);
        sendResponse(['error' => 'Prompt parameter required'], 400);
    }
    
    $prompt = trim($data['prompt']);
    $model = $data['model'] ?? null;
    
    try {
        ChatBotLogger::llmRequest($prompt, $model);
        
        // Generate SQL using LLM
        $timer = ChatBotLogger::startTimer('llm_generation');
        $result = generateSql($prompt, $model);
        ChatBotLogger::endTimer('llm_generation', $timer);
        
        if ($result['success']) {
            ChatBotLogger::llmResponse(true, $result['sql']);
            ChatBotLogger::stepEnd("SQL Generation", true, ['sql_length' => strlen($result['sql'])]);
            sendResponse([
                'success' => true,
                'sql' => $result['sql'],
                'prompt' => $prompt
            ]);
        } else {
            ChatBotLogger::llmResponse(false, null, $result['error']);
            ChatBotLogger::stepEnd("SQL Generation", false, $result);
            sendResponse([
                'success' => false,
                'error' => $result['error'],
                'raw' => $result['raw'] ?? null
            ], 500);
        }
        
    } catch (Exception $e) {
        ChatBotLogger::error('SQL Generation Error: ' . $e->getMessage());
        sendResponse([
            'success' => false,
            'error' => 'Failed to generate SQL: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Handle query execution request
 */
function handleExecuteQuery($data) {
    // Validate input
    if (!isset($data['query']) || empty(trim($data['query']))) {
        sendResponse(['error' => 'Query parameter required'], 400);
    }
    
    $query = trim($data['query']);
    $originalQuestion = $data['question'] ?? '';
    
    try {
        ChatBotLogger::error("Executing query", ['query' => $query]);
        
        // Execute query
        $result = executeSqlQuery($query);
        
        if ($result['success']) {
            // Generate AI summary if result summarizer is available
            $aiSummary = null;
            if (!empty($originalQuestion)) {
                $aiSummary = generateAISummary($result, $originalQuestion);
            }
            
            if ($aiSummary) {
                $result['ai_summary'] = $aiSummary;
            }
            
            ChatBotLogger::error("Query executed successfully", [
                'rows' => $result['metadata']['row_count'],
                'time' => $result['metadata']['execution_time_seconds']
            ]);
            
            sendResponse($result);
        } else {
            ChatBotLogger::error("Query execution failed", $result);
            sendResponse($result, 500);
        }
        
    } catch (Exception $e) {
        ChatBotLogger::error('Query Execution Error: ' . $e->getMessage());
        sendResponse([
            'success' => false,
            'error' => 'Failed to execute query: ' . $e->getMessage(),
            'error_type' => 'EXECUTION_ERROR'
        ], 500);
    }
}

/**
 * Handle feedback submission
 */
function handleFeedback($data) {
    // Validate input
    $requiredFields = ['question', 'sql', 'is_correct'];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field])) {
            sendResponse(['error' => "Missing required field: $field"], 400);
        }
    }
    
    try {
        $feedbackData = [
            'timestamp' => date('c'),
            'question' => $data['question'],
            'generated_sql' => $data['sql'],
            'executed_sql' => $data['executed_query'] ?? $data['sql'],
            'is_correct' => (bool)$data['is_correct'],
            'user_feedback' => $data['feedback'] ?? '',
            'session_id' => $data['session_id'] ?? uniqid(),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ];
        
        // Create feedback directory if it doesn't exist
        $feedbackDir = __DIR__ . '/feedback';
        if (!is_dir($feedbackDir)) {
            mkdir($feedbackDir, 0755, true);
        }
        
        $feedbackFile = $feedbackDir . '/feedback.json';
        
        // Load existing feedback
        $existingFeedback = [];
        if (file_exists($feedbackFile)) {
            $content = file_get_contents($feedbackFile);
            $existingFeedback = json_decode($content, true) ?? [];
        }
        
        // Append new feedback
        $existingFeedback[] = $feedbackData;
        
        // Save updated feedback
        file_put_contents($feedbackFile, json_encode($existingFeedback, JSON_PRETTY_PRINT));
        
        ChatBotLogger::error("Feedback saved", [
            'is_correct' => $feedbackData['is_correct'],
            'question' => $feedbackData['question']
        ]);
        
        sendResponse([
            'success' => true,
            'message' => 'Feedback saved successfully'
        ]);
        
    } catch (Exception $e) {
        ChatBotLogger::error('Feedback Error: ' . $e->getMessage());
        sendResponse([
            'success' => false,
            'error' => 'Failed to save feedback: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Handle schema information request
 */
function handleSchemaInfo() {
    try {
        $forceRefresh = isset($_GET['refresh']) && $_GET['refresh'] === 'true';
        
        // Get current schema using the schema scanner
        $schema = getCurrentSchema($forceRefresh);
        
        if (!isset($schema['error'])) {
            $summary = [
                'success' => true,
                'schema_summary' => [
                    'database_name' => $schema['database_info']['db_name'] ?? 'Unknown',
                    'table_count' => count($schema['tables'] ?? []),
                    'last_scan' => $schema['scan_timestamp'] ?? 'Unknown',
                    'tables' => array_keys($schema['tables'] ?? [])
                ]
            ];
            
            sendResponse($summary);
        } else {
            sendResponse([
                'success' => false,
                'error' => $schema['error']
            ], 500);
        }
        
    } catch (Exception $e) {
        ChatBotLogger::error('Schema Info Error: ' . $e->getMessage());
        sendResponse([
            'success' => false,
            'error' => 'Failed to get schema info: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Handle health check request
 */
function handleHealthCheck() {
    try {
        // Test database connection
        $dbTest = testDatabase();
        
        // Test Ollama connection (basic check)
        $ollamaTest = testOllamaConnection();
        
        $health = [
            'status' => 'ok',
            'timestamp' => date('c'),
            'services' => [
                'database' => $dbTest,
                'ollama' => $ollamaTest
            ]
        ];
        
        $allHealthy = $dbTest['success'] && $ollamaTest['success'];
        
        sendResponse($health, $allHealthy ? 200 : 503);
        
    } catch (Exception $e) {
        sendResponse([
            'status' => 'error',
            'error' => $e->getMessage()
        ], 503);
    }
}

/**
 * Generate AI summary for query results
 */
function generateAISummary($queryResult, $originalQuestion) {
    try {
        // Simple summary based on results
        $data = $queryResult['data'] ?? [];
        $metadata = $queryResult['metadata'] ?? [];
        
        $rowCount = count($data);
        
        $summary = [
            'summary' => "Found $rowCount record(s) for your query",
            'insights' => [],
            'type' => 'basic_analysis'
        ];
        
        // Basic insights
        if ($rowCount === 0) {
            $summary['insights'][] = "No matching records found";
            $summary['insights'][] = "Try adjusting your search criteria";
        } elseif ($rowCount === 1) {
            $summary['insights'][] = "Single specific record found";
        } elseif ($rowCount <= 10) {
            $summary['insights'][] = "Small result set - good for detailed analysis";
        } elseif ($rowCount <= 100) {
            $summary['insights'][] = "Moderate result set";
        } else {
            $summary['insights'][] = "Large result set - consider filtering";
        }
        
        // Execution time insights
        $execTime = $metadata['execution_time_seconds'] ?? 0;
        if ($execTime > 1) {
            $summary['insights'][] = "Query took " . $execTime . "s to execute";
        }
        
        return $summary;
        
    } catch (Exception $e) {
        ChatBotLogger::error('AI Summary Error: ' . $e->getMessage());
        return null;
    }
}

/**
 * Test Ollama connection
 */
function testOllamaConnection() {
    try {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => 'http://127.0.0.1:11434/api/tags',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_CONNECTTIMEOUT => 3
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return [
                'success' => false,
                'message' => 'Ollama connection failed: ' . $error
            ];
        }
        
        if ($httpCode === 200) {
            return [
                'success' => true,
                'message' => 'Ollama is accessible'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Ollama returned HTTP ' . $httpCode
            ];
        }
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Ollama test failed: ' . $e->getMessage()
        ];
    }
}

?>
