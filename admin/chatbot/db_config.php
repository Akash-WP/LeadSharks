<?php
/**
 * PHP Database Configuration Class
 * Handles MySQL connections and configuration for ChatBot
 */

class DatabaseConfig {
    private $config;
    private $connection;
    private static $instance = null;
    
    // Database configuration
    private $host = 'localhost';
    private $username = 'root';
    private $password = '';
    private $database = 'lms_db';
    private $charset = 'utf8mb4';
    
    private function __construct() {
        $this->loadConfig();
        $this->createConnection();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new DatabaseConfig();
        }
        return self::$instance;
    }
    
    private function loadConfig() {
        // Load configuration from config file or use defaults
        $configFile = __DIR__ . '/config.json';
        
        if (file_exists($configFile)) {
            $configJson = file_get_contents($configFile);
            $this->config = json_decode($configJson, true);
            
            if ($this->config && isset($this->config['database'])) {
                $dbConfig = $this->config['database'];
                $this->host = $dbConfig['host'] ?? $this->host;
                $this->username = $dbConfig['username'] ?? $this->username;
                $this->password = $dbConfig['password'] ?? $this->password;
                $this->database = $dbConfig['database'] ?? $this->database;
                $this->charset = $dbConfig['charset'] ?? $this->charset;
            }
        } else {
            // Create default config file
            $defaultConfig = [
                'database' => [
                    'host' => 'localhost',
                    'username' => 'root',
                    'password' => '',
                    'database' => 'lms_db',
                    'charset' => 'utf8mb4'
                ],
                'cache' => [
                    'schema_cache_file' => 'cache/schema_cache.json',
                    'cache_refresh_hours' => 24
                ],
                'ollama' => [
                    'url' => 'http://127.0.0.1:11434/api/chat',
                    'model' => 'deepseek-coder'
                ]
            ];
            
            // Create cache directory
            if (!is_dir(__DIR__ . '/cache')) {
                mkdir(__DIR__ . '/cache', 0755, true);
            }
            
            file_put_contents($configFile, json_encode($defaultConfig, JSON_PRETTY_PRINT));
            $this->config = $defaultConfig;
        }
    }
    
    private function createConnection() {
        try {
            $this->connection = new mysqli(
                $this->host,
                $this->username,
                $this->password,
                $this->database
            );
            
            if ($this->connection->connect_error) {
                throw new Exception("Connection failed: " . $this->connection->connect_error);
            }
            
            // Set charset
            $this->connection->set_charset($this->charset);
            
            return true;
        } catch (Exception $e) {
            error_log("Database connection error: " . $e->getMessage());
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    public function getConnection() {
        // Check if connection is still alive
        if (!$this->connection || !$this->connection->ping()) {
            $this->createConnection();
        }
        return $this->connection;
    }
    
    public function testConnection() {
        try {
            $conn = $this->getConnection();
            $result = $conn->query("SELECT 1");
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Database connection successful'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Connection test failed'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection error: ' . $e->getMessage()
            ];
        }
    }
    
    public function getDatabaseInfo() {
        try {
            $conn = $this->getConnection();
            
            // Get database info
            $result = $conn->query("SELECT DATABASE() as current_db, VERSION() as version");
            $dbInfo = $result->fetch_assoc();
            
            // Get table count
            $result = $conn->query("SELECT COUNT(*) as table_count FROM information_schema.tables WHERE table_schema = DATABASE()");
            $tableInfo = $result->fetch_assoc();
            
            return [
                'success' => true,
                'database_name' => $dbInfo['current_db'],
                'mysql_version' => $dbInfo['version'],
                'total_tables' => $tableInfo['table_count'],
                'host' => $this->host
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    public function getConfig($key = null) {
        if ($key) {
            return $this->config[$key] ?? null;
        }
        return $this->config;
    }
    
    public function close() {
        if ($this->connection) {
            $this->connection->close();
        }
    }
}

// Helper functions for global access
function getDbConnection() {
    return DatabaseConfig::getInstance()->getConnection();
}

function testDatabase() {
    return DatabaseConfig::getInstance()->testConnection();
}

function getDatabaseInfo() {
    return DatabaseConfig::getInstance()->getDatabaseInfo();
}

// Test the connection if this file is run directly
if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    echo "<h2>🔧 Database Configuration Test</h2>";
    echo "<hr>";
    
    $testResult = testDatabase();
    if ($testResult['success']) {
        echo "<p style='color: green;'>✅ " . $testResult['message'] . "</p>";
        
        $info = getDatabaseInfo();
        if ($info['success']) {
            echo "<p><strong>📊 Database:</strong> " . $info['database_name'] . "</p>";
            echo "<p><strong>🔢 MySQL Version:</strong> " . $info['mysql_version'] . "</p>";
            echo "<p><strong>📋 Tables:</strong> " . $info['total_tables'] . "</p>";
            echo "<p><strong>🖥️ Host:</strong> " . $info['host'] . "</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ " . $testResult['message'] . "</p>";
        echo "<p>⚠️ Please update database credentials in config.json</p>";
    }
}

?>