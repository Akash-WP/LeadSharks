<?php
/**
 * Logger Viewer API
 * Provides logs and statistics for the dashboard
 */

require_once 'logger.php';

header('Content-Type: application/json');

try {
    $action = $_GET['action'] ?? 'view';
    
    switch ($action) {
        case 'view':
            viewLogs();
            break;
            
        case 'clear':
            clearLogs();
            break;
            
        case 'stats':
            getStats();
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function viewLogs() {
    $lines = $_GET['lines'] ?? 200;
    $logs = ChatBotLogger::getRecentLogs($lines);
    
    // Calculate statistics
    $stats = [
        'total' => count($logs),
        'errors' => 0,
        'warnings' => 0,
        'api_requests' => 0,
        'sql_queries' => 0,
        'llm_requests' => 0
    ];
    
    foreach ($logs as $log) {
        if (strpos($log, '] ERROR [') !== false || strpos($log, '] CRITICAL [') !== false) {
            $stats['errors']++;
        }
        if (strpos($log, '] WARNING [') !== false) {
            $stats['warnings']++;
        }
        if (strpos($log, 'API Request Received') !== false) {
            $stats['api_requests']++;
        }
        if (strpos($log, 'Database Query Executed') !== false) {
            $stats['sql_queries']++;
        }
        if (strpos($log, 'LLM Request Started') !== false) {
            $stats['llm_requests']++;
        }
    }
    
    echo json_encode([
        'success' => true,
        'logs' => array_reverse($logs), // Most recent first
        'stats' => $stats,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}

function clearLogs() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        ChatBotLogger::clearLogs();
        echo json_encode(['success' => true, 'message' => 'Logs cleared successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'POST method required']);
    }
}

function getStats() {
    $logs = ChatBotLogger::getRecentLogs(1000);
    
    $hourlyStats = [];
    $currentHour = date('Y-m-d H');
    
    // Initialize last 24 hours
    for ($i = 23; $i >= 0; $i--) {
        $hour = date('Y-m-d H', strtotime("-$i hours"));
        $hourlyStats[$hour] = [
            'total' => 0,
            'errors' => 0,
            'api_requests' => 0,
            'sql_queries' => 0
        ];
    }
    
    // Count logs by hour
    foreach ($logs as $log) {
        if (preg_match('/^\[([\d\-: .]+)\]/', $log, $matches)) {
            $logTime = $matches[1];
            $hour = substr($logTime, 0, 13); // YYYY-MM-DD HH
            
            if (isset($hourlyStats[$hour])) {
                $hourlyStats[$hour]['total']++;
                
                if (strpos($log, '] ERROR [') !== false || strpos($log, '] CRITICAL [') !== false) {
                    $hourlyStats[$hour]['errors']++;
                }
                if (strpos($log, 'API Request Received') !== false) {
                    $hourlyStats[$hour]['api_requests']++;
                }
                if (strpos($log, 'Database Query Executed') !== false) {
                    $hourlyStats[$hour]['sql_queries']++;
                }
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'hourly_stats' => $hourlyStats,
        'summary' => [
            'total_logs' => count($logs),
            'error_rate' => count($logs) > 0 ? round(array_sum(array_column($hourlyStats, 'errors')) / count($logs) * 100, 2) : 0,
            'most_active_hour' => array_keys($hourlyStats, max($hourlyStats))[0] ?? null
        ]
    ]);
}

?>