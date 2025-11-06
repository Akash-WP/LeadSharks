<?php
ob_clean();
ob_start();

require __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Include your DB connection
require_once __DIR__ . '/../../config.php'; // adjust path as needed

// Optional: Apply same user filter logic if needed
$uwhere = "";
if ($_settings->userdata('type') != 1) {
    $uwhere = " AND assigned_to = '{$_settings->userdata('id')}' ";
}

// Fetch users to get assigned_to names
$users = $conn->query("SELECT id, CONCAT(lastname, ', ', firstname, ' ', COALESCE(middlename, '')) as fullname FROM users WHERE id IN (SELECT user_id FROM lead_list WHERE in_opportunity = 0 $uwhere) OR id IN (SELECT assigned_to FROM lead_list WHERE in_opportunity = 0 $uwhere)");
$user_arr = array_column($users->fetch_all(MYSQLI_ASSOC), 'fullname', 'id');

// Fetch leads data with client info
$leads = $conn->query("SELECT l.*, c.company_name as client, c.website, c.city, c.state, c.country FROM lead_list l INNER JOIN client_list c ON c.lead_id = l.id WHERE l.in_opportunity = 0 $uwhere ORDER BY l.status ASC, UNIX_TIMESTAMP(l.date_created) ASC");

// Create new Spreadsheet and sheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set headers as per your table columns (excluding 'Actions' column)
$headers = ['Ref. Code', 'Company', 'City', 'State', 'Country', 'Website', 'Interested In', 'Contacts', 'Assigned To', 'Status', 'Created By', 'Date Created'];
$sheet->fromArray($headers, NULL, 'A1');

$rowNum = 2; // Data starts from second row

while ($row = $leads->fetch_assoc()) {
    // Decode contacts JSON and format it as a multiline string
    $contacts = [];
    if (!empty($row['contact'])) {
        $contactsArr = json_decode($row['contact'], true);
        if (is_array($contactsArr)) {
            foreach ($contactsArr as $c) {
                $contacts[] = "{$c['name']}\n{$c['contact']}\n{$c['email']}\n{$c['designation']}";
            }
        }
    }
    $contactsStr = empty($contacts) ? "No Contacts" : implode("\n\n", $contacts);

    // Map status codes to text (same as your table)
    $statusText = match($row['status']) {
        0 => 'New/Prospect',
        1 => 'Open',
        2 => 'Working',
        3 => 'Not a Target',
        4 => 'Disqualified',
        5 => 'Nurture',
        6 => 'Opportunity Created',
        7 => 'Opportunity Lost',
        8 => 'Inactive',
        default => 'N/A',
    };

    // Prepare row data array matching headers order
    $dataRow = [
        $row['code'],
        ucwords($row['client']),
        $row['city'],
        $row['state'],
        $row['country'],
        $row['website'],
        $row['interested_in'],
        $contactsStr,
        isset($user_arr[$row['assigned_to']]) ? ucwords($user_arr[$row['assigned_to']]) : 'Not Assigned Yet.',
        $statusText,
        isset($user_arr[$row['user_id']]) ? ucwords($user_arr[$row['user_id']]) : 'N/A',
        date("D M d, Y h:i A", strtotime($row['date_created']))
    ];

    // Write data to current row
    $sheet->fromArray($dataRow, NULL, "A{$rowNum}");

    // Enable text wrap for contacts column (H = 8th column)
    $sheet->getStyle("H{$rowNum}")->getAlignment()->setWrapText(true);

    $rowNum++;
}

// Set header for download
ob_end_clean(); // clean any output
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="leads_data.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
