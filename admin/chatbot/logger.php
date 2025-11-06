<?php
/**
 * Comprehensive Logger System for ChatBot Project
 * Logs all activities with timestamps, levels, and detailed context
 */

class ChatBotLogger {
    private static $instance = null;
    private $logFile;
    private $logLevel;
    private $maxLogSize;
    private $enableConsoleLog;
    
    // Log levels
    const LEVEL_DEBUG = 1;
    const LEVEL_INFO = 2;
    const LEVEL_WARNING = 3;
    const LEVEL_ERROR = 4;
    const LEVEL_CRITICAL = 5;
    
    private $levelNames = [
        1 => 'DEBUG',
        2 => 'INFO', 
        3 => 'WARNING',
        4 => 'ERROR',
        5 => 'CRITICAL'
    ];
    
    private function __construct() {
        $this->logFile = __DIR__ . '/logs/chatbot.log';
        $this->logLevel = self::LEVEL_DEBUG; // Log everything
        $this->maxLogSize = 10 * 1024 * 1024; // 10MB max
        $this->enableConsoleLog = false; // Disabled by default to prevent API interference
        
        // Create logs directory if it doesn't exist
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        // Initialize log file
        $this->log(self::LEVEL_INFO, "ChatBot Logger Initialized", [
            'session_id' => session_id() ?: uniqid(),
            'timestamp' => date('Y-m-d H:i:s'),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'CLI'
        ]);
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Main logging method
     */
    private function log($level, $message, $context = []) {
        if ($level < $this->logLevel) {
            return; // Skip if below log level
        }
        
        $timestamp = date('Y-m-d H:i:s.v');
        $levelName = $this->levelNames[$level];
        $sessionId = session_id() ?: uniqid();
        
        // Build log entry
        $logEntry = [
            'timestamp' => $timestamp,
            'level' => $levelName,
            'session' => substr($sessionId, 0, 8),
            'message' => $message,
            'context' => $context,
            'memory' => memory_get_usage(true),
            'file' => $this->getCallerInfo()
        ];
        
        // Format log line
        $logLine = $this->formatLogEntry($logEntry);
        
        // Write to file
        $this->writeToFile($logLine);
        
        // Console output if enabled (but not for AJAX/API requests)
        if ($this->enableConsoleLog && php_sapi_name() !== 'cli' && !$this->isApiRequest()) {
            $this->writeToConsole($logEntry);
        }
        
        // Rotate log if too large
        $this->rotateLogIfNeeded();
    }
    
    private function formatLogEntry($entry) {
        $contextStr = !empty($entry['context']) ? ' | ' . json_encode($entry['context']) : '';
        $memoryMb = round($entry['memory'] / 1024 / 1024, 2);
        
        return sprintf(
            "[%s] %s [%s] %s%s | Memory: %sMB | %s\n",
            $entry['timestamp'],
            $entry['level'],
            $entry['session'],
            $entry['message'],
            $contextStr,
            $memoryMb,
            $entry['file']
        );
    }
    
    private function writeToFile($logLine) {
        file_put_contents($this->logFile, $logLine, FILE_APPEND | LOCK_EX);
    }
    
    private function writeToConsole($entry) {
        $colors = [
            'DEBUG' => '37', // White
            'INFO' => '36',  // Cyan
            'WARNING' => '33', // Yellow
            'ERROR' => '31',   // Red
            'CRITICAL' => '35' // Magenta
        ];
        
        $color = $colors[$entry['level']] ?? '37';
        $icon = $this->getLevelIcon($entry['level']);
        
        echo "<script>console.log('" . $icon . " " . addslashes($entry['message']) . 
             ($entry['context'] ? " | " . addslashes(json_encode($entry['context'])) : "") . "');</script>";
    }
    
    private function getLevelIcon($level) {
        $icons = [
            'DEBUG' => '🔍',
            'INFO' => 'ℹ️',
            'WARNING' => '⚠️',
            'ERROR' => '❌',
            'CRITICAL' => '🚨'
        ];
        return $icons[$level] ?? '📝';
    }
    
    private function getCallerInfo() {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 4);
        
        // Skip this class and the wrapper functions
        for ($i = 1; $i < count($trace); $i++) {
            if (!isset($trace[$i]['class']) || $trace[$i]['class'] !== 'ChatBotLogger') {
                $file = basename($trace[$i]['file'] ?? 'unknown');
                $line = $trace[$i]['line'] ?? 0;
                $function = $trace[$i]['function'] ?? 'unknown';
                return "$file:$line in $function()";
            }
        }
        
        return 'unknown';
    }
    
    private function rotateLogIfNeeded() {
        if (file_exists($this->logFile) && filesize($this->logFile) > $this->maxLogSize) {
            $backupFile = str_replace('.log', '_' . date('Y-m-d_H-i-s') . '.log', $this->logFile);
            rename($this->logFile, $backupFile);
            $this->log(self::LEVEL_INFO, "Log rotated", ['backup_file' => $backupFile]);
        }
    }
    
    private function isApiRequest() {
        // Check if this is an AJAX/API request
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                  strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        
        // Check if Content-Type is JSON (typical for API requests)
        $isJson = !empty($_SERVER['CONTENT_TYPE']) && 
                  strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false;
        
        // Check if we're in api.php or similar API endpoint
        $isApiFile = isset($_SERVER['SCRIPT_NAME']) && 
                     (strpos($_SERVER['SCRIPT_NAME'], 'api.php') !== false);
        
        // Check if Accept header requests JSON
        $acceptsJson = !empty($_SERVER['HTTP_ACCEPT']) && 
                       strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false;
        
        return $isAjax || $isJson || $isApiFile || $acceptsJson;
    }
    
    // Public logging methods
    public static function debug($message, $context = []) {
        self::getInstance()->log(self::LEVEL_DEBUG, $message, $context);
    }
    
    public static function info($message, $context = []) {
        self::getInstance()->log(self::LEVEL_INFO, $message, $context);
    }
    
    public static function warning($message, $context = []) {
        self::getInstance()->log(self::LEVEL_WARNING, $message, $context);
    }
    
    public static function error($message, $context = []) {
        self::getInstance()->log(self::LEVEL_ERROR, $message, $context);
    }
    
    public static function critical($message, $context = []) {
        self::getInstance()->log(self::LEVEL_CRITICAL, $message, $context);
    }
    
    // Special methods for chatbot workflow
    public static function apiRequest($endpoint, $data = []) {
        self::info("API Request Received", [
            'endpoint' => $endpoint,
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
            'data' => $data,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
    }
    
    public static function llmRequest($prompt, $model = null) {
        self::info("LLM Request Started", [
            'prompt' => substr($prompt, 0, 100) . (strlen($prompt) > 100 ? '...' : ''),
            'model' => $model,
            'prompt_length' => strlen($prompt)
        ]);
    }
    
    public static function llmResponse($success, $response = null, $error = null) {
        if ($success) {
            self::info("LLM Response Received", [
                'sql' => $response,
                'response_length' => strlen($response ?? '')
            ]);
        } else {
            self::error("LLM Request Failed", [
                'error' => $error
            ]);
        }
    }
    
    public static function databaseQuery($query, $type = 'unknown') {
        self::info("Database Query Executed", [
            'type' => $type,
            'query' => substr(preg_replace('/\s+/', ' ', $query), 0, 200) . (strlen($query) > 200 ? '...' : ''),
            'query_length' => strlen($query)
        ]);
    }
    
    public static function databaseResult($success, $rowCount = 0, $error = null) {
        if ($success) {
            self::info("Database Query Success", [
                'row_count' => $rowCount
            ]);
        } else {
            self::error("Database Query Failed", [
                'error' => $error
            ]);
        }
    }
    
    public static function schemaAction($action, $details = []) {
        self::debug("Schema Action", array_merge(['action' => $action], $details));
    }
    
    public static function userFeedback($feedback, $query = null) {
        self::info("User Feedback Received", [
            'feedback' => $feedback,
            'query' => $query
        ]);
    }
    
    // Utility methods
    public static function startTimer($name) {
        self::debug("Timer Started", ['timer' => $name]);
        return microtime(true);
    }
    
    public static function endTimer($name, $startTime) {
        $duration = round((microtime(true) - $startTime) * 1000, 2);
        self::debug("Timer Ended", [
            'timer' => $name,
            'duration_ms' => $duration
        ]);
        return $duration;
    }
    
    public static function stepStart($step, $context = []) {
        self::info("Step Started: $step", $context);
    }
    
    public static function stepEnd($step, $success = true, $context = []) {
        if ($success) {
            self::info("Step Completed: $step", $context);
        } else {
            self::error("Step Failed: $step", $context);
        }
    }
    
    // Get recent logs for debugging
    public static function getRecentLogs($lines = 50) {
        $logFile = self::getInstance()->logFile;
        if (!file_exists($logFile)) {
            return [];
        }
        
        $logs = file($logFile, FILE_IGNORE_NEW_LINES);
        return array_slice($logs, -$lines);
    }
    
    // Clear logs
    public static function clearLogs() {
        $logFile = self::getInstance()->logFile;
        if (file_exists($logFile)) {
            unlink($logFile);
        }
        self::info("Logs cleared");
    }
    
    // Enable console logging for debugging (use only on debug pages, not API)
    public static function enableConsoleLogging() {
        self::getInstance()->enableConsoleLog = true;
    }
    
    // Disable console logging (default for clean API responses)
    public static function disableConsoleLogging() {
        self::getInstance()->enableConsoleLog = false;
    }
}

// Global convenience functions (prefixed to avoid conflicts)
function logger() {
    return ChatBotLogger::getInstance();
}

function chatbotLogDebug($message, $context = []) {
    ChatBotLogger::debug($message, $context);
}

function chatbotLogInfo($message, $context = []) {
    ChatBotLogger::info($message, $context);
}

function chatbotLogWarning($message, $context = []) {
    ChatBotLogger::warning($message, $context);
}

function chatbotLogError($message, $context = []) {
    ChatBotLogger::error($message, $context);
}

function chatbotLogCritical($message, $context = []) {
    ChatBotLogger::critical($message, $context);
}

?>