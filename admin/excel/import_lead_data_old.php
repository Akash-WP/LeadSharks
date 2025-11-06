<?php
require __DIR__ . '/../../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

require_once __DIR__ . '/../../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
    die("Upload failed, error code: " . ($_FILES['import_file']['error'] ?? 'No file'));
}

    $fileTmpPath = $_FILES['import_file']['tmp_name'];
    $spreadsheet = IOFactory::load($fileTmpPath);
    $sheet = $spreadsheet->getActiveSheet();
    $highestRow = $sheet->getHighestDataRow();

    $statusMap = [
        'New/Prospect' => 0,
        'Open' => 1,
        'Working' => 2,
        'Not a Target' => 3,
        'Disqualified' => 4,
        'Nurture' => 5,
        'Opportunity Created' => 6,
        'Opportunity Lost' => 7,
        'Inactive' => 8,
    ];

    for ($row = 2; $row <= $highestRow; ++$row) {
        $code = trim($sheet->getCell("A$row")->getValue());
        $company = trim($sheet->getCell("B$row")->getValue());
        $type = trim($sheet->getCell("C$row")->getValue());
        $website = trim($sheet->getCell("D$row")->getValue());
        $address = trim($sheet->getCell("E$row")->getValue());
        $country = trim($sheet->getCell("F$row")->getValue());
        $state = trim($sheet->getCell("G$row")->getValue());
        $city = trim($sheet->getCell("H$row")->getValue());
        $pincode = trim($sheet->getCell("I$row")->getValue());
        $other_info = trim($sheet->getCell("J$row")->getValue());
        $interestedIn = trim($sheet->getCell("K$row")->getValue());
        $sourceText = trim($sheet->getCell("L$row")->getValue());
        $remarks = trim($sheet->getCell("M$row")->getValue());
        $statusText = trim($sheet->getCell("N$row")->getValue());
        $assignedToName = trim($sheet->getCell("O$row")->getValue());
        $createdByName = trim($sheet->getCell("P$row")->getValue());

        $contact1 = [
            'name' => trim($sheet->getCell("Q$row")->getValue()),
            'phone' => trim($sheet->getCell("R$row")->getValue()),
            'email' => trim($sheet->getCell("S$row")->getValue()),
            'designation' => trim($sheet->getCell("T$row")->getValue()),
        ];
        $contact2 = [
            'name' => trim($sheet->getCell("U$row")->getValue()),
            'phone' => trim($sheet->getCell("V$row")->getValue()),
            'email' => trim($sheet->getCell("W$row")->getValue()),
            'designation' => trim($sheet->getCell("X$row")->getValue()),
        ];

        // Lookup user IDs
        function getUserId($conn, $fullname) {
    if (!$fullname) return null;
    $stmt = $conn->prepare("SELECT id FROM users WHERE CONCAT(lastname, ', ', firstname, ' ', COALESCE(middlename, '')) = ?");
    $stmt->bind_param("s", $fullname);
    $stmt->execute();
    $id = null;
    $stmt->bind_result($id);
    $userId = null;
    if ($stmt->fetch()) {
        $userId = $id;
    }
    $stmt->close();
    return $userId;
}


        $assignedToId = getUserId($conn, $assignedToName);
        $createdById = getUserId($conn, $createdByName);

        // Lookup lead source id
        $sourceId = null;
        if ($sourceText) {
            $stmt = $conn->prepare("SELECT id FROM source_list WHERE name = ?");
            $stmt->bind_param("s", $sourceText);
            $stmt->execute();
            $stmt->bind_result($srcId);
            if ($stmt->fetch()) $sourceId = $srcId;
            $stmt->close();
        }

        $status = $statusMap[$statusText] ?? 0;
        $contactJson = json_encode(array_filter([$contact1, $contact2]));

        // Insert into lead_list
        $stmt = $conn->prepare("INSERT INTO lead_list (code, source_id, interested_in, remarks, assigned_to, user_id, status, contact) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sissiiis", $code, $sourceId, $interestedIn, $remarks, $assignedToId, $createdById, $status, $contactJson);
        $stmt->execute();
        $leadId = $stmt->insert_id;
        $stmt->close();

        // Insert into client_list
        $stmt = $conn->prepare("INSERT INTO client_list (lead_id, company_name, company_type, website, contact, address, city, state, country, pincode, other_info) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssssssss", $leadId, $company, $type, $website, $contactJson, $address, $city, $state, $country, $pincode, $other_info);
        $stmt->execute();
        $stmt->close();

        // Insert contacts individually
        foreach ([$contact1, $contact2] as $c) {
            if (!empty($c['name'])) {
                $stmt = $conn->prepare("INSERT INTO contact_persons (lead_id, name, contact, email, designation) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("issss", $leadId, $c['name'], $c['phone'], $c['email'], $c['designation']);
                $stmt->execute();
                $stmt->close();
            }
        }
    }

    echo "Import completed!";
} else {
    echo "Invalid request method.";
}
