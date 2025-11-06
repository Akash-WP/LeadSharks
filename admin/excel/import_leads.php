<?php
require __DIR__ . '/../../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../config.php'; // your DB connection here


// Bulk import assigned to

// Get logged-in user ID from your settings/userdata
$user = $conn->query("SELECT * FROM users WHERE id = '" . $_settings->userdata('id') . "'");
$meta = [];
if ($user) {
    foreach ($user->fetch_array() as $k => $v) {
        $meta[$k] = $v;
    }
}
$uploaderUserId = $meta['id'] ?? null;

if (!$uploaderUserId) {
    die("Uploader user ID not found.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
        die("Upload failed, error code: " . ($_FILES['import_file']['error'] ?? 'No file'));
    }

    $fileTmpPath = $_FILES['import_file']['tmp_name'];

    // Load spreadsheet
    $spreadsheet = IOFactory::load($fileTmpPath);
    $sheet = $spreadsheet->getActiveSheet();
    $highestRow = $sheet->getHighestDataRow();

    // Expected header columns exactly as in your Excel
    $expectedHeaders = [
        'Company Name*',
        'Company Type',
        'Website',
        'Address',
        'Country',
        'State',
        'City',
        'Pincode',
        'Other Info',
        'Interested In',
        'Lead Source',
        'Remarks',
        'Assigned To',
        'Status',
        'Contact Person Name 1',
        'Contact Number 1',
        'Email 1',
        'Designation 1',
        'Contact Person Name 2',
        'Contact Number 2',
        'Email 2',
        'Designation 2',
        'Contact Person Name 3',
        'Contact Number 3',
        'Email 3',
        'Designation 3'
    ];

    $userLeads = []; // key = user ID, value = array of lead data

    // Find header row by scanning first 10 rows
    $headerRow = null;

    $correctSheet = null;

    //Loop through each sheet
    foreach ($spreadsheet->getAllSheets() as $sheet) {
        $maxCheckRows = 10;
        for ($row = 1; $row <= $maxCheckRows; $row++) {
            $foundHeaders = [];
            foreach ($expectedHeaders as $colIndex => $headerText) {
                $colLetter = chr(65 + $colIndex);
                $cellValue = preg_replace('/[\x00-\x1F\x7F\xA0]/u', '', trim($sheet->getCell("{$colLetter}{$row}")->getValue()));
                $foundHeaders[] = $cellValue;
            }

            $matched = true;
            foreach ($expectedHeaders as $i => $header) {
                if (strcasecmp($header, $foundHeaders[$i]) !== 0) {
                    $matched = false;
                    break;
                }
            }

            if ($matched) {
                $headerRow = $row;
                $correctSheet = $sheet;
                break 2; // Exit both loops
            }
        }
    }

    if (!$correctSheet || $headerRow === null) {
        die("Header row not found in any sheet.");
    }

    $sheet = $correctSheet;

    $maxCheckRows = 10; // max rows to look for header
    for ($row = 1; $row <= $maxCheckRows; $row++) {
        $foundHeaders = [];
        foreach ($expectedHeaders as $colIndex => $headerText) {
            $colLetter = chr(65 + $colIndex); // 0 => A, 1 => B, ...
            $cellValue = trim($sheet->getCell("{$colLetter}{$row}")->getValue());
            $foundHeaders[] = $cellValue;
        }
        // Check if all headers match case-insensitive
        $matched = true;
        foreach ($expectedHeaders as $i => $header) {
            if (strcasecmp($header, $foundHeaders[$i]) !== 0) {
                $matched = false;
                break;
            }
        }
        if ($matched) {
            $headerRow = $row;
            break;
        }
    }

    if ($headerRow === null) {
        die("Header row not found in the first $maxCheckRows rows.");
    }

    // Start data import from row below header
    $dataStartRow = $headerRow + 1;

    $statusMap = [
        'Lead – Uncontacted' => 0,  // was 'New/Prospect'
        'Prospect – Contact Made' => 1,  // was 'Open'
        'Qualified – Need Validated' => 2,  // was 'Working'
        'Solution Fit / Discovery' => 3,  // was 'Not a Target'
        'Proposal / Value Proposition' => 4, // was 'Disqualified'
        'Negotiation' => 5,  // was 'Nurture'
        'Closed – Won' => 6,  // was 'Opportunity Created'
        'Closed – Lost' => 7,  // was 'Opportunity Lost'
        // No 8 => 'Inactive' removed
    ];

    function getUserId($conn, $fullname)
    {
        $id = null;
        if (!$fullname)
            return null;

        // Convert to lowercase and trim to avoid spacing and case issues
        $fullname = trim(strtolower($fullname));

        // Try to match with no middlename first
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


    $processedCount = 0;
    $insertedCount = 0;
    $skippedCount = 0;

    for ($row = $dataStartRow; $row <= $highestRow; ++$row) {
        $processedCount++;

        $company = trim($sheet->getCell("A$row")->getValue());
        if (empty($company)) {
            // skip empty company rows
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
        $contact3 = [
            'name' => trim($sheet->getCell("W$row")->getValue()),
            'phone' => trim($sheet->getCell("X$row")->getValue()),
            'email' => trim($sheet->getCell("Y$row")->getValue()),
            'designation' => trim($sheet->getCell("Z$row")->getValue()),
        ];

        // Check if at least one contact person has any info (name, phone, or email)
        $contact1_filled = !empty($contact1['name']) || !empty($contact1['phone']) || !empty($contact1['email']);
        $contact2_filled = !empty($contact2['name']) || !empty($contact2['phone']) || !empty($contact2['email']);
        $contact3_filled = !empty($contact3['name']) || !empty($contact3['phone']) || !empty($contact3['email']);

        if (!$contact1_filled && !$contact2_filled && !$contact3_filled) {
            echo "Skipped: At least one contact person details required at row $row.<br>";
            $skippedCount++;
            continue; // skip this row and move to next
        }


        // Normalize AssignedTo name like "John Smith" => "Smith, John"
        if (!empty($assignedToName) && strpos($assignedToName, ',') === false) {
            $nameParts = explode(' ', trim($assignedToName));
            if (count($nameParts) >= 2) {
                $firstname = array_shift($nameParts);
                $lastname = implode(' ', $nameParts); // in case of last names like "Van Dyke"
                $assignedToName = $lastname . ', ' . $firstname;
            }
        }


        // Lookup user IDs
        $assignedToId = getUserId($conn, $assignedToName);
        if (!$assignedToId && $assignedToName) {
            echo "Warning: Could not match user '{$assignedToName}' at row $row. Try format: 'Lastname, Firstname'<br>";
        }


        // Lookup lead source id
        $sourceId = null;
        if ($sourceText) {
            $stmt = $conn->prepare("SELECT id FROM source_list WHERE name = ?");
            $stmt->bind_param("s", $sourceText);
            $stmt->execute();
            $stmt->bind_result($srcId);
            if ($stmt->fetch())
                $sourceId = $srcId;
            $stmt->close();
        }

        if (!array_key_exists($statusText, $statusMap)) {
            echo "Warning: Unknown status '{$statusText}' at row $row. Available options: " . implode(', ', array_keys($statusMap)) . ". Defaulting to 0.<br>";
        }

        $status = $statusMap[$statusText] ?? 0;
        $contactJson = json_encode(array_filter([$contact1, $contact2, $contact3]));

        $duplicateContact = false;

        $contactValues = array_unique(array_filter([
            $contact1['email'] ?? '',
            $contact2['email'] ?? '',
            $contact3['email'] ?? '',
            $contact1['contact'] ?? '',
            $contact2['contact'] ?? '',
            $contact3['contact'] ?? ''
        ]));

        if (!empty($contactValues)) {
            $placeholders = implode(',', array_fill(0, count($contactValues), '?'));
            $types = str_repeat('s', count($contactValues));
            $query = "SELECT email, contact FROM contact_persons WHERE email IN ($placeholders) OR contact IN ($placeholders)";

            $stmt = $conn->prepare($query);
            $stmt->bind_param($types . $types, ...$contactValues, ...$contactValues); // binding both for email and contact
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                echo "Skipped: Duplicate contact (email/phone) found for company '$company' at row $row.<br>";
                $skippedCount++;
                $stmt->close();
                continue; // Skip this row
            }
            $stmt->close();
        }


        // Generate unique lead code (similar to save_lead function)
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

        // Insert into lead_list (adjust columns as per your actual DB schema)
        $stmt = $conn->prepare("INSERT INTO lead_list (code, source_id, interested_in, remarks, assigned_to, user_id, status, contact) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sissiiss", $code, $sourceId, $interestedIn, $remarks, $assignedToId, $uploaderUserId, $status, $contactJson);

        if ($assignedToId) {
            $userLeads[$assignedToId][] = [
                'Company Name*' => $company,
                'Company Type' => $type,
                'Website' => $website,
                'Address' => $address,
                'Country' => $country,
                'State' => $state,
                'City' => $city,
                'Pincode' => $pincode,
                'Other Info' => $other_info,
                'Interested In' => $interestedIn,
                'Lead Source' => $sourceText,
                'Remarks' => $remarks,
                'Assigned To' => $assignedToName,
                'Status' => $statusText,
                'Contact Person Name 1' => $contact1['name'],
                'Contact Number 1' => $contact1['contact'],
                'Email 1' => $contact1['email'],
                'Designation 1' => $contact1['designation'],
                'Contact Person Name 2' => $contact2['name'],
                'Contact Number 2' => $contact2['contact'],
                'Email 2' => $contact2['email'],
                'Designation 2' => $contact2['designation'],
                'Contact Person Name 3' => $contact3['name'],
                'Contact Number 3' => $contact3['contact'],
                'Email 3' => $contact3['email'],
                'Designation 3' => $contact3['designation'],
            ];
        }

        if (!$stmt->execute()) {
            echo "Lead insert error at row $row: " . $stmt->error . "<br>";
            $skippedCount++;
            $stmt->close();
            continue;
        }
        $leadId = $stmt->insert_id;
        $stmt->close();

        // Insert into client_list
        $stmt = $conn->prepare("INSERT INTO client_list (lead_id, company_name, company_type, website, contact, address, city, state, country, pincode, other_info) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssssssss", $leadId, $company, $type, $website, $contactJson, $address, $city, $state, $country, $pincode, $other_info);
        if (!$stmt->execute()) {
            echo "Client insert error at row $row: " . $stmt->error . "<br>";
            $skippedCount++;
            $stmt->close();
            continue;
        }
        $stmt->close();

        

        // Insert contacts individually
        foreach ([$contact1, $contact2, $contact3] as $c) {
            if (!empty($c['name'])) {
                $stmt = $conn->prepare("INSERT INTO contact_persons (lead_id, name, contact, email, designation) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("issss", $leadId, $c['name'], $c['phone'], $c['email'], $c['designation']);
                if (!$stmt->execute()) {
                    echo "Contact insert error at row $row: " . $stmt->error . "<br>";
                }
                $stmt->close();
            }
        }
        $insertedCount++;
    }

    // Attach text file in mail containing leads
    foreach ($userLeads as $userId => $leads) {
            // Fetch email and name
            $stmt = $conn->prepare("SELECT email, firstname FROM users WHERE id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $stmt->bind_result($assignedEmail, $assignedFirstname);
            $stmt->fetch();
            $stmt->close();

            if (!$assignedEmail)
                continue;

            // Generate TXT content
            $lines = [];
            foreach ($leads as $lead) {
                foreach ($lead as $k => $v) {
                    $lines[] = "$k: $v";
                }
                $lines[] = str_repeat('-', 50); // separator between leads
            }

            $txtContent = implode(PHP_EOL, $lines);

            // Save to temp file
            $filePath = __DIR__ . "/../../temp/assigned_leads_{$userId}_" . time() . ".txt";
            file_put_contents($filePath, $txtContent);

            // Send mail with attachment
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.zoho.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'saspartner@woodpeckerind.com';
                $mail->Password = 'W@@dPecker@2025';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                $mail->setFrom('saspartner@woodpeckerind.com', 'Leads Management System');
                $mail->addAddress($assignedEmail, $assignedFirstname);
                $mail->isHTML(true);
                $mail->Subject = "New Leads Assigned";

                $mail->Body = "
            <p>Dear {$assignedFirstname},</p>
            <p>Multiple leads have been assigned to you. Please find the attached file with details.</p>
            <p>Regards,<br>Leads Management System</p>
        ";

                $mail->addAttachment($filePath);

                $mail->send();
                echo "✅ Bulk email sent to $assignedEmail with " . count($leads) . " leads<br>";
                unlink($filePath); // delete after sending
            } catch (Exception $e) {
                echo "❌ Failed to send bulk email to $assignedEmail: {$mail->ErrorInfo}<br>";
            }
        }

    echo "Import process completed successfully!<br>";
    echo "Total Rows Processed: $processedCount<br>";
    echo "Inserted: $insertedCount<br>";
    echo "Skipped: $skippedCount<br>";
} else {
    echo "Invalid request method.";
}
?>