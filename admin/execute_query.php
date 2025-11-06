<?php
require '../classes/DBConnection.php'; // Your database connection file
require_once('../config.php');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Get POST data
$input = json_decode(file_get_contents("php://input"), true);

if (!isset($input['query']) || empty(trim($input['query']))) {
    echo json_encode(['error' => 'No query provided']);
    exit;
}

$query = trim($input['query']);

try {
    $stmt = $conn->query($query); // $conn comes from db.php (MySQLi or PDO)
    $results = [];

    // Fetch results only if it's a SELECT query
    if (stripos($query, 'SELECT') === 0) {
        while ($row = $stmt->fetch_assoc()) {
            $results[] = $row;
        }
        echo json_encode(['data' => $results]);
    } else {
        // For INSERT/UPDATE/DELETE
        echo json_encode(['message' => 'Query executed successfully']);
    }
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
