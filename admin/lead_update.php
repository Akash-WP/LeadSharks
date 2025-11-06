<?php
require_once('../config.php');
// your DB connection and _settings setup

// Read JSON input
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['id'])) {
    echo json_encode(['success' => false, 'message' => 'Lead ID missing']);
    exit;
}

$id = intval($data['id']);

$remarks = isset($data['remarks']) ? $data['remarks'] : null;
$status = isset($data['status']) ? intval($data['status']) : null;

if ($remarks === null && $status === null) {
    echo json_encode(['success' => false, 'message' => 'No fields to update']);
    exit;
}

$updates = [];
$params = [];
$types = '';

// Prepare dynamic update fields
if ($remarks !== null) {
    $updates[] = 'remarks = ?';
    $params[] = $remarks;
    $types .= 's';
}

if ($status !== null) {
    $updates[] = 'status = ?';
    $params[] = $status;
    $types .= 'i';
}

$params[] = $id;
$types .= 'i';

$sql = "UPDATE lead_list SET " . implode(', ', $updates) . " WHERE id = ?";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
    exit;
}

// Bind parameters dynamically
$stmt->bind_param($types, ...$params);
$stmt->execute();

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Lead updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'No changes or lead not found']);
}

$stmt->close();
exit;
