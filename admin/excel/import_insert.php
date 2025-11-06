<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once __DIR__ . '/../../vendor/autoload.php'; // For PHPMailer




require_once __DIR__ . '/../../config.php';

header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['data'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid or missing data']);
    exit;
}

$rows = $data['data'];
$inserted = 0;
$skipped = 0;

$userLeads = []; // user_id => list of leads

// Utilities
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}
function isValidPhone($phone) {
    return preg_match('/^\d{7,15}$/', $phone); // basic length check
}
function generateUniqueCode($conn) {
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
    return $code;
}

// Get uploader ID
$user = $conn->query("SELECT * FROM users WHERE id = '" . $_settings->userdata('id') . "'");
$meta = [];
if ($user) {
    foreach ($user->fetch_array() as $k => $v) {
        $meta[$k] = $v;
    }
}
$uploaderUserId = $meta['id'] ?? 1;

foreach ($rows as $index => $row) {
    $company = trim($row[0] ?? '');

    if (!$company || stripos($company, 'company name') !== false) {
        error_log("⛔ Skipped row $index — Header or blank company name");
        $skipped++;
        continue;
    }

    $type = trim($row[1] ?? '');
    $website = trim($row[2] ?? '');
    $address = trim($row[3] ?? '');
    $country = trim($row[4] ?? '');
    $state = trim($row[5] ?? '');
    $city = trim($row[6] ?? '');
    $pincode = trim($row[7] ?? '');
    $other_info = trim($row[8] ?? '');
    $interestedIn = trim($row[9] ?? '');
    $sourceText = trim($row[10] ?? '');
    $remarks = trim($row[11] ?? '');
    $assignedToName = trim($row[12] ?? '');
    $statusText = trim($row[13] ?? '');

    $contacts = [
        ['name' => trim($row[14] ?? ''), 'phone' => trim($row[15] ?? ''), 'email' => trim($row[16] ?? ''), 'designation' => trim($row[17] ?? '')],
        ['name' => trim($row[18] ?? ''), 'phone' => trim($row[19] ?? ''), 'email' => trim($row[20] ?? ''), 'designation' => trim($row[21] ?? '')],
        ['name' => trim($row[22] ?? ''), 'phone' => trim($row[23] ?? ''), 'email' => trim($row[24] ?? ''), 'designation' => trim($row[25] ?? '')],
    ];

    // Clean invalid emails/phones
    foreach ($contacts as &$c) {
        if (!isValidEmail($c['email'])) $c['email'] = '';
        if (!isValidPhone($c['phone'])) $c['phone'] = '';
    }
    unset($c);

    // Check if at least one contact person exists
    $validContact = false;
    foreach ($contacts as $c) {
        if (!empty($c['name']) || !empty($c['phone']) || !empty($c['email'])) {
            $validContact = true;
            break;
        }
    }
    if (!$validContact) {
        error_log("⛔ Skipped row $index — No valid contact info");
        $skipped++;
        continue;
    }

    // Lookup source_id
    $sourceId = null;
    if ($sourceText) {
        $stmt = $conn->prepare("SELECT id FROM source_list WHERE name = ?");
        $stmt->bind_param("s", $sourceText);
        $stmt->execute();
        $stmt->bind_result($sourceId);
        $stmt->fetch();
        $stmt->close();
    }

    // Status map
    $statusMap = [
        'Lead – Uncontacted' => 0,
        'Prospect – Contact Made' => 1,
        'Qualified – Need Validated' => 2,
        'Solution Fit / Discovery' => 3,
        'Proposal / Value Proposition' => 4,
        'Negotiation' => 5,
        'Closed – Won' => 6,
        'Closed – Lost' => 7,
    ];
    $status = $statusMap[$statusText] ?? 0;

    // Normalize assignedToName if not already in "Last, First" format
    if ($assignedToName && strpos($assignedToName, ',') === false) {
        $parts = explode(' ', $assignedToName);
        if (count($parts) >= 2) {
            $first = array_shift($parts);
            $last = implode(' ', $parts);
            $assignedToName = "$last, $first";
        }
    }

    // Lookup assigned_to
    $assignedToId = null;
    if ($assignedToName) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE CONCAT(lastname, ', ', firstname) = ?");
        $stmt->bind_param("s", $assignedToName);
        $stmt->execute();
        $stmt->bind_result($assignedToId);
        $stmt->fetch();
        $stmt->close();
    }

    // Duplicate check
    $emails = array_unique(array_filter(array_column($contacts, 'email')));
    $phones = array_unique(array_filter(array_column($contacts, 'phone')));
    $dupCheck = array_merge($emails, $phones);
    if (!empty($dupCheck)) {
        $placeholders = implode(',', array_fill(0, count($dupCheck), '?'));
        $types = str_repeat('s', count($dupCheck));
        $query = "SELECT id FROM contact_persons WHERE email IN ($placeholders) OR contact IN ($placeholders)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types . $types, ...$dupCheck, ...$dupCheck);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            error_log("⛔ Duplicate contact found at row $index — Skipping");
            $stmt->close();
            $skipped++;
            continue;
        }
        $stmt->close();
    }

    // Generate lead code
    $code = generateUniqueCode($conn);
    $contactJson = json_encode($contacts);

    // Insert into lead_list
    $stmt = $conn->prepare("INSERT INTO lead_list (code, source_id, interested_in, remarks, assigned_to, user_id, status, contact) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sissiiss", $code, $sourceId, $interestedIn, $remarks, $assignedToId, $uploaderUserId, $status, $contactJson);
    if (!$stmt->execute()) {
        error_log("❌ Lead insert failed at row $index: " . $stmt->error);
        $stmt->close();
        $skipped++;
        continue;
    }
    $leadId = $stmt->insert_id;
    $stmt->close();

    // Insert into client_list
    $stmt = $conn->prepare("INSERT INTO client_list (lead_id, company_name, company_type, website, contact, address, city, state, country, pincode, other_info) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssssssss", $leadId, $company, $type, $website, $contactJson, $address, $city, $state, $country, $pincode, $other_info);
    $stmt->execute();
    $stmt->close();

    // Insert contacts
    foreach ($contacts as $c) {
        if (!empty($c['name']) || !empty($c['phone']) || !empty($c['email'])) {
            $stmt = $conn->prepare("INSERT INTO contact_persons (lead_id, name, contact, email, designation) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("issss", $leadId, $c['name'], $c['phone'], $c['email'], $c['designation']);
            $stmt->execute();
            $stmt->close();
        }
    }

    $inserted++;

$contact1 = $contacts[0] ?? ['name'=>'', 'contact'=>'', 'email'=>'', 'designation'=>''];
$contact2 = $contacts[1] ?? ['name'=>'', 'contact'=>'', 'email'=>'', 'designation'=>''];
$contact3 = $contacts[2] ?? ['name'=>'', 'contact'=>'', 'email'=>'', 'designation'=>''];


// Save assigned lead details for mailing
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
                'Contact Number 1' => $contact1['phone'],
                'Email 1' => $contact1['email'],
                'Designation 1' => $contact1['designation'],
                'Contact Person Name 2' => $contact2['name'],
                'Contact Number 2' => $contact2['phone'],
                'Email 2' => $contact2['email'],
                'Designation 2' => $contact2['designation'],
                'Contact Person Name 3' => $contact3['name'],
                'Contact Number 3' => $contact3['phone'],
                'Email 3' => $contact3['email'],
                'Designation 3' => $contact3['designation'],
            ];
        }
}

// Send email to assigned users with their leads as .txt attachment
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
        $lines[] = str_repeat('-', 50);
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
        error_log("✅ Email sent to $assignedEmail with " . count($leads) . " leads.");
        unlink($filePath);
    } catch (Exception $e) {
        error_log("❌ Failed to send email to $assignedEmail: {$mail->ErrorInfo}");
    }
}


// echo json_encode([
//     'status' => 'success',
//     'inserted' => $inserted,
//     'skipped' => $skipped,
//     'message' => "$inserted leads inserted, $skipped skipped."
// ]);
$_settings->set_flashdata('success', "$inserted leads inserted, $skipped skipped, " . count($userLeads) . " users notified.");

echo json_encode([
    'success' => true,
    'inserted' => $inserted,
    'skipped' => $skipped,
    'emails_sent' => count($userLeads),
    'message' => "$inserted leads inserted, $skipped skipped, " . count($userLeads) . " users notified."
]);

