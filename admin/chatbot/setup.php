<?php
/**
 * ChatBot Installation and Setup Script
 * Run this once to setup the PHP ChatBot system
 */

echo "<h1>🚀 ChatBot PHP Installation & Setup</h1>";
echo "<hr>";

// Check PHP version
echo "<h3>1. System Requirements Check</h3>";
$phpVersion = phpversion();
echo "PHP Version: $phpVersion ";
if (version_compare($phpVersion, '7.4.0', '>=')) {
    echo "<span style='color: green;'>✅ OK</span><br>";
} else {
    echo "<span style='color: red;'>❌ PHP 7.4+ required</span><br>";
    exit();
}

// Check required extensions
$requiredExtensions = ['mysqli', 'json', 'curl'];
foreach ($requiredExtensions as $ext) {
    echo "Extension $ext: ";
    if (extension_loaded($ext)) {
        echo "<span style='color: green;'>✅ Available</span><br>";
    } else {
        echo "<span style='color: red;'>❌ Missing</span><br>";
        exit("Please install PHP extension: $ext");
    }
}

echo "<h3>2. Directory Structure Setup</h3>";

// Create required directories
$directories = [
    'cache',
    'feedback',
    'logs'
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        echo "Created directory: $dir ✅<br>";
    } else {
        echo "Directory exists: $dir ✅<br>";
    }
}

echo "<h3>3. Configuration Files</h3>";

// Check config files
$configFiles = [
    'config.json' => 'Database configuration',
    'db_config.php' => 'Database class',
    'schema_scanner.php' => 'Schema scanner',
    'query_executor.php' => 'Query executor',
    'llm_generator.php' => 'LLM generator',
    'api.php' => 'API endpoints',
    'chatbot.html' => 'ChatBot UI'
];

foreach ($configFiles as $file => $description) {
    if (file_exists($file)) {
        echo "$description ($file): <span style='color: green;'>✅ Present</span><br>";
    } else {
        echo "$description ($file): <span style='color: red;'>❌ Missing</span><br>";
    }
}

echo "<h3>4. Database Connection Test</h3>";

try {
    require_once 'db_config.php';
    $dbTest = testDatabase();
    
    if ($dbTest['success']) {
        echo "<span style='color: green;'>✅ " . $dbTest['message'] . "</span><br>";
        
        // Test database info
        $dbInfo = getDatabaseInfo();
        if ($dbInfo['success']) {
            echo "Database: " . $dbInfo['database_name'] . "<br>";
            echo "Tables: " . $dbInfo['total_tables'] . "<br>";
            echo "MySQL Version: " . $dbInfo['mysql_version'] . "<br>";
        }
    } else {
        echo "<span style='color: red;'>❌ " . $dbTest['message'] . "</span><br>";
        echo "<p><strong>Action Required:</strong> Update database credentials in config.json</p>";
    }
} catch (Exception $e) {
    echo "<span style='color: red;'>❌ Database connection error: " . $e->getMessage() . "</span><br>";
}

echo "<h3>5. Schema Scanner Test</h3>";

try {
    require_once 'schema_scanner.php';
    echo "Testing schema scanner...<br>";
    
    $schema = getCurrentSchema();
    if (!isset($schema['error'])) {
        echo "<span style='color: green;'>✅ Schema scanned successfully</span><br>";
        echo "Found " . count($schema['tables']) . " tables<br>";
        
        // Show first few tables
        $tableNames = array_keys($schema['tables']);
        echo "Tables: " . implode(', ', array_slice($tableNames, 0, 5));
        if (count($tableNames) > 5) {
            echo " and " . (count($tableNames) - 5) . " more";
        }
        echo "<br>";
    } else {
        echo "<span style='color: red;'>❌ Schema scan failed: " . $schema['error'] . "</span><br>";
    }
} catch (Exception $e) {
    echo "<span style='color: red;'>❌ Schema scanner error: " . $e->getMessage() . "</span><br>";
}

echo "<h3>6. Ollama Connection Test</h3>";

try {
    // Test Ollama connection
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'http://127.0.0.1:11434/api/tags',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo "<span style='color: red;'>❌ Ollama connection failed: $error</span><br>";
        echo "<p><strong>Action Required:</strong> Start Ollama server: <code>ollama serve</code></p>";
    } elseif ($httpCode === 200) {
        echo "<span style='color: green;'>✅ Ollama is running and accessible</span><br>";
        
        // Check for DeepSeek model
        $data = json_decode($response, true);
        $models = array_column($data['models'] ?? [], 'name');
        
        if (in_array('deepseek-coder', $models) || in_array('deepseek-coder:latest', $models)) {
            echo "<span style='color: green;'>✅ DeepSeek-Coder model is available</span><br>";
        } else {
            echo "<span style='color: orange;'>⚠️ DeepSeek-Coder model not found</span><br>";
            echo "<p><strong>Action Required:</strong> Install model: <code>ollama pull deepseek-coder</code></p>";
        }
    } else {
        echo "<span style='color: red;'>❌ Ollama returned HTTP $httpCode</span><br>";
    }
} catch (Exception $e) {
    echo "<span style='color: red;'>❌ Ollama test error: " . $e->getMessage() . "</span><br>";
}

echo "<h3>7. API Endpoints Test</h3>";

echo "Testing API endpoints...<br>";

// Test health check endpoint
$healthUrl = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/api.php';
echo "API URL: $healthUrl<br>";

try {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $healthUrl,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode(['action' => 'health_check']),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        echo "<span style='color: green;'>✅ API endpoints are working</span><br>";
    } else {
        echo "<span style='color: red;'>❌ API test failed (HTTP $httpCode)</span><br>";
    }
} catch (Exception $e) {
    echo "<span style='color: red;'>❌ API test error: " . $e->getMessage() . "</span><br>";
}

echo "<h3>8. Integration Instructions</h3>";

echo "<div style='background: #f8f9fa; padding: 15px; border-left: 4px solid #007bff; margin: 15px 0;'>";
echo "<h4>📋 To integrate with your dashboard (home.php):</h4>";
echo "<ol>";
echo "<li><strong>Copy chatbot folder</strong> to: <code>/opt/lampp/htdocs/lms/admin/chatbot/</code></li>";
echo "<li><strong>Add chatbot button</strong> to your sidebar in home.php:</li>";
echo "<pre style='background: #e9ecef; padding: 10px; border-radius: 5px;'>";
echo htmlspecialchars('<li class="nav-item">
    <a href="#" class="chatbot-sidebar-btn" onclick="openChatBot()" title="SQL Assistant">
        <i class="fas fa-robot"></i>
        <span>SQL Assistant</span>
        <span class="chatbot-badge">AI</span>
    </a>
</li>');
echo "</pre>";
echo "<li><strong>Include integration file</strong> before closing &lt;/body&gt; tag in home.php:</li>";
echo "<pre style='background: #e9ecef; padding: 10px; border-radius: 5px;'>";
echo htmlspecialchars('<?php include "chatbot/chatbot_integration.php"; ?>');
echo "</pre>";
echo "<li><strong>Ensure Bootstrap 5.x</strong> is loaded for modal functionality</li>";
echo "<li><strong>Ensure Font Awesome</strong> is loaded for icons</li>";
echo "</ol>";
echo "</div>";

echo "<h3>9. Summary</h3>";

echo "<div style='background: #d4edda; padding: 15px; border-left: 4px solid #28a745; margin: 15px 0;'>";
echo "<h4>✅ Installation Complete!</h4>";
echo "<p>Your PHP ChatBot is ready to use. Users can now:</p>";
echo "<ul>";
echo "<li>🤖 Ask questions in natural language</li>";
echo "<li>🔍 Get AI-generated SQL queries</li>";
echo "<li>📊 See executed results with insights</li>";
echo "<li>👍👎 Provide feedback for improvements</li>";
echo "</ul>";
echo "<p><strong>Next steps:</strong> Integrate the chatbot button into your dashboard sidebar and test with real queries!</p>";
echo "</div>";

echo "<hr>";
echo "<p><em>Setup completed at " . date('Y-m-d H:i:s') . "</em></p>";
?>