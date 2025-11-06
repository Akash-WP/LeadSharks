<?php
require __DIR__ . '/../../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;
require_once __DIR__ . '/../../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['confirm_rows'])) {
    die("No duplicates selected to import.");
}

$confirmedRows = $_POST['confirm_rows'];


// Directly load spreadsheet from temporary uploaded file
$tmpFilePath = $_FILES['uploaded_file']['tmp_name'];

// Load spreadsheet
$spreadsheet = IOFactory::load($tmpFilePath);
$sheet = $spreadsheet->getActiveSheet();

// Status mapping same as original
$statusMap = [
    'Lead – Uncontacted'         => 0,
    'Prospect – Contact Made'    => 1,
    'Qualified – Need Validated' => 2,
    'Solution Fit / Discovery'   => 3,
    'Proposal / Value Proposition' => 4,
    'Negotiation'                => 5,
    'Closed – Won'               => 6,
    'Closed – Lost'              => 7,
];

// Get logged-in user ID from your settings/userdata
$user = $conn->query("SELECT * FROM users WHERE id = '".$_settings->userdata('id')."'");
$meta = [];
if ($user) {
    foreach ($user->fetch_array() as $k => $v) {
        $meta[$k] = $v;
    }
}
$uploaderUserId = $meta['id'] ?? null;

// Function getUserId same as your original to resolve Assigned To names, etc.
function getUserId($conn, $fullname) {
    $id = null;    
    if (!$fullname) return null;
    $fullname = trim(strtolower($fullname));
    $stmt = $conn->prepare("SELECT id FROM users 
        WHERE LOWER(CONCAT(lastname, ', ', firstname)) = ? 
        OR LOWER(CONCAT(lastname, ', ', firstname, ' ', COALESCE(middlename, ''))) = ?
        LIMIT 1");
    $stmt->bind_param("ss", $fullname, $fullname);
    $stmt->execute();
    $stmt->bind_result($id);
    $userId = null;
    if ($stmt->fetch()) {
        $userId = $id;
    }
    $stmt->close();
    return $userId;
}

$insertedCount = 0;
$skippedCount = 0;

foreach ($confirmedRows as $row) {
    // Extract all needed fields (example below, add all columns you require)
    $company = trim($sheet->getCell("A$row")->getValue());
    if (empty($company)) {
        echo "Row $row skipped: Company name empty.<br>";
        $skippedCount++;
        continue;
    }
    $type = trim($sheet->getCell("B$row")->getValue());
    $website = trim($sheet->getCell("C$row")->getValue());
    $address = trim($sheet->getCell("D$row")->getValue());
    $country = trim($sheet->getCell("E$row")->getValue());
    $state = trim($sheet->getCell("F$row")->getValue());
    $city = trim($sheet->getCell("G$row")->getValue());
    $pincode = trim($sheet->getCell("H$row")->getValue());
    $other_info = trim($sheet->getCell("I$row")->getValue());
    $interestedIn = trim($sheet->getCell("J$row")->getValue());
    $sourceText = trim($sheet->getCell("K$row")->getValue());
    $remarks = trim($sheet->getCell("L$row")->getValue());
    $assignedToName = trim($sheet->getCell("M$row")->getValue());
    $statusText = trim($sheet->getCell("N$row")->getValue());

    $contact1 = [
        'name' => trim($sheet->getCell("O$row")->getValue()),
        'phone' => trim($sheet->getCell("P$row")->getValue()),
        'email' => trim($sheet->getCell("Q$row")->getValue()),
        'designation' => trim($sheet->getCell("R$row")->getValue()),
    ];
    $contact2 = [
        'name' => trim($sheet->getCell("S$row")->getValue()),
        'phone' => trim($sheet->getCell("T$row")->getValue()),
        'email' => trim($sheet->getCell("U$row")->getValue()),
        'designation' => trim($sheet->getCell("V$row")->getValue()),
    ];

    // Normalize assignedToName like in your original code
    if (!empty($assignedToName) && strpos($assignedToName, ',') === false) {
        $nameParts = explode(' ', trim($assignedToName));
        if (count($nameParts) >= 2) {
            $firstname = array_shift($nameParts);
            $lastname = implode(' ', $nameParts);
            $assignedToName = $lastname . ', ' . $firstname;
        }
    }

    // Lookup assignedToId
    $assignedToId = getUserId($conn, $assignedToName);
    if (!$assignedToId && $assignedToName) {
        echo "Warning: Could not match assigned user '$assignedToName' at row $row.<br>";
    }

    // Lookup sourceId
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

    // Check duplicate company again before insert
    $companyCheck = $conn->prepare("SELECT id FROM client_list WHERE company_name = ?");
    $companyCheck->bind_param("s", $company);
    $companyCheck->execute();
    $companyCheck->store_result();
    if ($companyCheck->num_rows > 0) {
        echo "Skipped duplicate company '$company' at row $row.<br>";
        $skippedCount++;
        $companyCheck->close();
        continue;
    }
    $companyCheck->close();

    // Generate unique lead code
    $prefix = date("Ym-");
    $codeNum = 1;
    do {
        $code = $prefix . str_pad($codeNum, 5, '0', STR_PAD_LEFT);
        $stmt = $conn->prepare("SELECT COUNT(*) FROM lead_list WHERE code = ?");
        $stmt->bind_param("s", $code);
        $stmt->execute();
        $stmt->bind_result($existing);
        $stmt->fetch();
        $stmt->close();
        $codeNum++;
    } while ($existing > 0);

    // Insert lead_list
    $stmt = $conn->prepare("INSERT INTO lead_list (code, source_id, interested_in, remarks, assigned_to, user_id, status, contact) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sissiiss", $code, $sourceId, $interestedIn, $remarks, $assignedToId, $uploaderUserId, $status, $contactJson);
    if (!$stmt->execute()) {
        echo "Error inserting lead at row $row: " . $stmt->error . "<br>";
        $skippedCount++;
        $stmt->close();
        continue;
    }
    $leadId = $stmt->insert_id;
    $stmt->close();

    // Insert client_list
    $stmt = $conn->prepare("INSERT INTO client_list (lead_id, company_name, company_type, website, contact, address, city, state, country, pincode, other_info) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssssssss", $leadId, $company, $type, $website, $contactJson, $address, $city, $state, $country, $pincode, $other_info);
    if (!$stmt->execute()) {
        echo "Error inserting client at row $row: " . $stmt->error . "<br>";
        $skippedCount++;
        $stmt->close();
        continue;
    }
    $stmt->close();

    // Insert contact persons
    foreach ([$contact1, $contact2] as $c) {
        if (!empty($c['name'])) {
            $stmt = $conn->prepare("INSERT INTO contact_persons (lead_id, name, contact, email, designation) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("issss", $leadId, $c['name'], $c['phone'], $c['email'], $c['designation']);
            if (!$stmt->execute()) {
                echo "Error inserting contact person for lead $leadId at row $row: " . $stmt->error . "<br>";
            }
            $stmt->close();
        }
    }

    $insertedCount++;
}

echo "Import complete. $insertedCount rows inserted, $skippedCount rows skipped.";
?>
