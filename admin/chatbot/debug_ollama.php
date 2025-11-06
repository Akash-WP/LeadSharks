<?php
/**
 * Debug Ollama Connection Issues
 */

echo "<h1>🔧 Ollama Debug Test</h1>";

// Test 1: Basic connectivity
echo "<h3>Test 1: Ollama Server Connectivity</h3>";
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => 'http://127.0.0.1:11434/api/tags',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 5,
    CURLOPT_VERBOSE => true
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "<p><strong>HTTP Code:</strong> $httpCode</p>";
echo "<p><strong>Error:</strong> " . ($error ?: "None") . "</p>";
echo "<p><strong>Response:</strong> <pre>" . htmlspecialchars($response) . "</pre></p>";

// Test 2: Chat API
echo "<h3>Test 2: Chat API Test</h3>";

$testRequest = [
    "model" => "deepseek-coder",
    "messages" => [
        [
            "role" => "system",
            "content" => "You are a helpful assistant. Respond with just 'Hello' and nothing else."
        ],
        [
            "role" => "user", 
            "content" => "Say hello"
        ]
    ],
    "stream" => false
];

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => 'http://127.0.0.1:11434/api/chat',
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($testRequest),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_TIMEOUT => 30
]);

$chatResponse = curl_exec($ch);
$chatHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$chatError = curl_error($ch);
curl_close($ch);

echo "<p><strong>HTTP Code:</strong> $chatHttpCode</p>";
echo "<p><strong>Error:</strong> " . ($chatError ?: "None") . "</p>";
echo "<p><strong>Raw Response:</strong></p>";
echo "<pre style='background: #f5f5f5; padding: 10px; max-height: 400px; overflow-y: auto;'>";
echo htmlspecialchars($chatResponse);
echo "</pre>";

// Test 3: Parse response
echo "<h3>Test 3: Response Parsing</h3>";

if ($chatHttpCode === 200 && !$chatError) {
    $lines = explode("\n", trim($chatResponse));
    echo "<p><strong>Response has " . count($lines) . " lines</strong></p>";
    
    foreach ($lines as $i => $line) {
        $line = trim($line);
        if (empty($line)) continue;
        
        echo "<p><strong>Line $i:</strong> " . htmlspecialchars(substr($line, 0, 100)) . "...</p>";
        
        $data = json_decode($line, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo "<p style='color: green;'>✅ Valid JSON</p>";
            if (isset($data['message']['content'])) {
                echo "<p><strong>Content:</strong> " . htmlspecialchars($data['message']['content']) . "</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ Invalid JSON: " . json_last_error_msg() . "</p>";
        }
        echo "<hr>";
    }
} else {
    echo "<p style='color: red;'>Cannot parse - request failed</p>";
}

// Test 4: Include the actual LLM generator
echo "<h3>Test 4: LLM Generator Test</h3>";

try {
    require_once 'llm_generator.php';
    
    $result = generateSql("Show me all leads");
    
    echo "<p><strong>Result:</strong></p>";
    echo "<pre>" . htmlspecialchars(print_r($result, true)) . "</pre>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

?>