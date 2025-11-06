<?php
require __DIR__ . '/../../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

header('Content-Type: application/json');

if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded']);
    exit;
}

$tmpName = $_FILES['import_file']['tmp_name'];

try {
    $spreadsheet = IOFactory::load($tmpName);
    $sheet = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

    $rows = array_values($sheet);
    if (count($rows) < 2) {
        echo json_encode(['success' => false, 'message' => 'Excel file is empty']);
        exit;
    }

    $headers = array_values($rows[0]);
    $data = [];

    for ($i = 1; $i < count($rows); $i++) {
        $data[] = array_values($rows[$i]);
    }

    echo json_encode([
        'success' => true,
        'headers' => $headers,
        'data' => $data
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error reading Excel: ' . $e->getMessage()]);
}
