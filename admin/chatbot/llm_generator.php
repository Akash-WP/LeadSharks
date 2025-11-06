<?php
/**
 * PHP LLM SQL Generator
 * Integrates with Ollama/DeepSeek for SQL generation
 */

require_once 'logger.php';
require_once 'schema_scanner.php';

class LLMSqlGenerator {
    private $ollamaUrl;
    private $model;
    private $userMappings;
    private $statusMappings;
    
    public function __construct($ollamaUrl = 'http://127.0.0.1:11434/api/chat', $model = 'deepseek-coder') {
        $this->ollamaUrl = $ollamaUrl;
        $this->model = $model;
        $this->initializeMappings();
    }
    
    private function initializeMappings() {
        // User mappings (same as Python version)
        $this->userMappings = [
            "Administrator" => 1,
            "John" => 11,
            "David" => 11,
            "Nikita" => 15,
            "Ritik" => 17,
            "Shivani" => 16
        ];
        
        // Status mappings (same as Python version)
        $this->statusMappings = [
            0 => ["Lead - Uncontacted", "Uncontacted"],
            1 => ["Prospect - Contact Made", "Prospect"],
            2 => ["Qualified - Need Validated", "Qualified"],
            3 => ["Solution Fit / Discovery", "Solution Fit"],
            4 => ["Proposal / Value Proposition", "Proposal"],
            5 => ["Negotiation"],
            6 => ["Closed - Won", "Won"],
            7 => ["Closed - Lost", "Lost"]
        ];
    }
    
    private function formatMappingsForPrompt() {
        // Format user mappings
        $userLines = [];
        foreach ($this->userMappings as $name => $userId) {
            $userLines[] = "      - $name → $userId";
        }
        $userText = implode("\n", $userLines);
        
        // Format status mappings
        $statusLines = [];
        foreach ($this->statusMappings as $statusId => $terms) {
            $quotedTerms = array_map(function($term) { return "\"$term\""; }, $terms);
            $termsText = implode(" OR ", $quotedTerms);
            $statusLines[] = "      - $termsText → $statusId";
        }
        $statusText = implode("\n", $statusLines);
        
        return [$userText, $statusText];
    }
    
    private function extractSqlFromText($text) {
        if (empty($text)) {
            return null;
        }
        
        // Remove markdown or JSON formatting
        $text = str_replace(['```sql', '```', '`'], '', $text);
        $text = trim($text);
        
        // Multiple strategies to extract clean SQL
        
        // Strategy 1: Look for SQL pattern and extract everything until semicolon
        if (preg_match('/(SELECT|INSERT|UPDATE|DELETE|CREATE|DROP|ALTER|WITH)\s+.*?;/is', $text, $matches)) {
            $sql = trim($matches[0]);
            // Clean up any remaining non-SQL text
            $sql = $this->cleanSqlString($sql);
            return $sql;
        }
        
        // Strategy 2: Look for SQL without semicolon and add one
        if (preg_match('/(SELECT|INSERT|UPDATE|DELETE|CREATE|DROP|ALTER|WITH)\s+[^;]+/is', $text, $matches)) {
            $sql = trim($matches[0]) . ';';
            $sql = $this->cleanSqlString($sql);
            return $sql;
        }
        
        // Strategy 3: Fallback - if text starts with SQL keyword
        if (preg_match('/^(select|insert|update|delete|create|alter|drop|with)\s/i', $text)) {
            $sql = rtrim($text, ';') . ';';
            $sql = $this->cleanSqlString($sql);
            return $sql;
        }
        
        return null;
    }
    
    private function cleanSqlString($sql) {
        // Remove any explanatory text that commonly appears
        $sql = preg_replace('/^.*?(?=SELECT|INSERT|UPDATE|DELETE|CREATE|DROP|ALTER|WITH)/is', '', $sql);
        
        // Remove common LLM explanations
        $patterns = [
            '/with their associated.*?:\s*/i',
            '/in your database.*?:\s*/i',
            '/assuming.*?:\s*/i',
            '/here.*?:\s*/i',
            '/the.*?query.*?:\s*/i'
        ];
        
        foreach ($patterns as $pattern) {
            $sql = preg_replace($pattern, '', $sql);
        }
        
        // Clean up extra newlines and spaces
        $sql = preg_replace('/\s+/', ' ', $sql);
        $sql = trim($sql);
        
        return $sql;
    }
    
    public function generateSql($prompt, $model = null) {
        ChatBotLogger::debug("LLM SQL Generation Started", ['prompt' => substr($prompt, 0, 100), 'model' => $model]);
        
        if ($model === null) {
            $model = $this->model;
        }
        
        // Get current schema dynamically
        ChatBotLogger::debug("Loading schema for LLM");
        $currentSchemaText = $this->getCurrentSchemaText();
        ChatBotLogger::debug("Schema loaded", ['schema_length' => strlen($currentSchemaText)]);
        
        // Build the system prompt dynamically
        list($userMapText, $statusMapText) = $this->formatMappingsForPrompt();
        
        $systemPrompt = "
You are an expert MySQL query generator.  
Your task is to generate **only valid MySQL queries** based strictly on the schema and mappings provided below.

---

### STRICT RULES

1. **Literal Interpretation**
    - Only answer the exact user question.
    - Do NOT assume or infer any missing details.
    - Do NOT add filters, joins, or columns unless explicitly required.

2. **Schema Adherence**
    - Use ONLY the tables and columns listed in the schema below.
    - Column and table names must exactly match (case-sensitive and spelling-accurate).
    - Never invent or guess column names.

3. **Value Mapping**
    - When the user mentions a **person's name**, use their mapped `assigned_to` IDs:
$userMapText
    - When the user mentions a **lead status**, use their mapped numeric IDs from the `lead_list.status` column:
$statusMapText
    - Example:  
      - \"Show me leads assigned to Ritik\" → use `WHERE ll.assigned_to = 17`
      - \"Show me all 'Won' leads\" → use `WHERE ll.status = 6`

4. **Joins**
    - Use joins ONLY when necessary and according to these exact relationships:
      - `lead_list.id = client_list.lead_id`
      - `lead_list.id = contact_persons.lead_id`
      - `lead_list.source_id = source_list.id`
      - `lead_list.assigned_to = users.id`

5. **Output Rules**
    - Output only **one valid MySQL query**, ending with a semicolon `;`
    - Do NOT explain, comment, or format with markdown/code blocks.
    - The query must be syntactically correct MySQL.

---

### CURRENT DATABASE SCHEMA
$currentSchemaText

---

### TABLE USAGE GUIDE
* **lead_list** → main table for all leads (`status`, `assigned_to`, `source_id`, `interested_in`, `remarks`)
* **client_list** → company details (`company_name`, `city`, `state`, `country`, `follow_up_date`)
* **contact_persons** → person details (`name`, `email`, `designation`)
* **source_list** → lead source names (`name`)
* **users** → assigned user details (`id`, `name`)

---

### EXAMPLES

Q: How many leads are assigned to Ritik  
A: SELECT COUNT(*) AS total_leads FROM lead_list WHERE assigned_to = 17;

Q: Show all leads handled by Nikita  
A: SELECT ll.id, cl.company_name, ll.status, ll.interested_in FROM lead_list AS ll LEFT JOIN client_list AS cl ON ll.id = cl.lead_id WHERE ll.assigned_to = 15;

Q: Total leads by source  
A: SELECT sl.name AS source_name, COUNT(ll.id) AS total_leads FROM lead_list AS ll JOIN source_list AS sl ON ll.source_id = sl.id GROUP BY sl.name ORDER BY total_leads DESC;

Q: Clients from Pune  
A: SELECT company_name, city, state FROM client_list WHERE city = 'Pune';

Q: Show me all \"Won\" leads
A: SELECT ll.id, cl.company_name FROM lead_list AS ll LEFT JOIN client_list AS cl ON ll.id = cl.lead_id WHERE ll.status = 6;

---

### OUTPUT FORMAT
Return **only the SQL query**, with no extra text, comments, or markdown.  
Always end the query with a semicolon `;`.
";

        try {
            $requestData = [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $prompt]
                ],
                'stream' => false
            ];
            
            ChatBotLogger::debug("Sending request to Ollama", ['model' => $model, 'prompt_length' => strlen($prompt)]);
            
            // Make HTTP request to Ollama
            $timer = ChatBotLogger::startTimer('ollama_api_call');
            $response = $this->callOllamaAPI($requestData);
            ChatBotLogger::endTimer('ollama_api_call', $timer);
            
            if ($response['success']) {
                $content = trim($response['data']['message']['content']);
                ChatBotLogger::debug("Ollama response received", ['response_length' => strlen($content)]);
                
                $sql = $this->extractSqlFromText($content);
                ChatBotLogger::debug("SQL extraction attempt", ['extracted' => !empty($sql), 'sql_preview' => substr($sql, 0, 50)]);
                
                // If model outputs in markdown, clean it
                if (!$sql && stripos($content, 'SELECT') !== false) {
                    ChatBotLogger::debug("Attempting to clean markdown response");
                    $contentClean = preg_replace('/[^A-Za-z0-9\s,.*_=<>();`\'%-]/', ' ', $content);
                    $sql = $this->extractSqlFromText($contentClean);
                    ChatBotLogger::debug("Cleaned SQL extraction", ['extracted' => !empty($sql)]);
                }
                
                if ($sql) {
                    ChatBotLogger::info("SQL generation successful", ['sql' => $sql]);
                    return [
                        'success' => true,
                        'sql' => $sql
                    ];
                } else {
                    ChatBotLogger::warning("No SQL found in response", ['raw_content' => substr($content, 0, 200)]);
                    return [
                        'success' => false,
                        'error' => 'No SQL found',
                        'raw' => $content
                    ];
                }
            } else {
                ChatBotLogger::error("Ollama API call failed", ['error' => $response['error']]);
                return [
                    'success' => false,
                    'error' => $response['error']
                ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function getCurrentSchemaText() {
        try {
            return getSchemaForLLM();
        } catch (Exception $e) {
            error_log("Schema loading error: " . $e->getMessage());
            return "Schema not available";
        }
    }
    
    private function callOllamaAPI($requestData) {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->ollamaUrl,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($requestData),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json'
            ],
            CURLOPT_TIMEOUT => 120,
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            return [
                'success' => false,
                'error' => "CURL Error: $error"
            ];
        }
        
        if ($httpCode !== 200) {
            return [
                'success' => false,
                'error' => "HTTP Error: $httpCode - Response: " . substr($response, 0, 200)
            ];
        }
        
        // Clean response of any BOM or special characters
        $response = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $response);
        $response = mb_convert_encoding($response, 'UTF-8', 'UTF-8');
        
        // Handle different response formats from Ollama
        $fullContent = '';
        
        // First try: Parse as single JSON response (non-streaming)
        $data = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            if (isset($data['message']['content'])) {
                $fullContent = $data['message']['content'];
            } else if (isset($data['response'])) {
                $fullContent = $data['response'];
            }
        }
        
        // Second try: Handle streaming JSON responses
        if (empty($fullContent)) {
            $lines = explode("\n", trim($response));
            
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;
                
                $lineData = json_decode($line, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    if (isset($lineData['message']['content'])) {
                        $fullContent .= $lineData['message']['content'];
                    } else if (isset($lineData['response'])) {
                        $fullContent .= $lineData['response'];
                    }
                }
            }
        }
        
        // Third try: Extract content directly if JSON parsing failed
        if (empty($fullContent)) {
            // Look for content between quotes or after common patterns
            if (preg_match('/"content"\s*:\s*"([^"]*)"/', $response, $matches)) {
                $fullContent = $matches[1];
            } else if (preg_match('/SELECT\s+.+?;/i', $response, $matches)) {
                $fullContent = $matches[0];
            }
        }
        
        if (empty($fullContent)) {
            return [
                'success' => false,
                'error' => 'No content in response. JSON Error: ' . json_last_error_msg() . '. Raw: ' . substr($response, 0, 500)
            ];
        }
        
        return [
            'success' => true,
            'data' => ['message' => ['content' => $fullContent]]
        ];
    }
}

// Global generator instance
$llmGenerator = new LLMSqlGenerator();

function generateSql($prompt, $model = null) {
    global $llmGenerator;
    return $llmGenerator->generateSql($prompt, $model);
}

// Test function
function testLLMGenerator() {
    echo "<h2>🧠 Testing LLM SQL Generator</h2>";
    echo "<hr>";
    
    $testPrompts = [
        "How many leads are assigned to Ritik?",
        "Show me all companies from Pune",
        "List all Won leads with company names"
    ];
    
    foreach ($testPrompts as $i => $prompt) {
        echo "<h4>" . ($i + 1) . ". Testing: \"$prompt\"</h4>";
        
        $result = generateSql($prompt);
        
        if ($result['success']) {
            echo "<p style='color: green;'>✅ Generated SQL:</p>";
            echo "<pre style='background: #f5f5f5; padding: 10px; border-left: 3px solid #007bff;'>";
            echo htmlspecialchars($result['sql']);
            echo "</pre>";
        } else {
            echo "<p style='color: red;'>❌ Failed: " . $result['error'] . "</p>";
            if (isset($result['raw'])) {
                echo "<p><strong>Raw response:</strong> " . htmlspecialchars($result['raw']) . "</p>";
            }
        }
        echo "<hr>";
    }
}

// Test the LLM generator if this file is run directly
if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    testLLMGenerator();
}

?>