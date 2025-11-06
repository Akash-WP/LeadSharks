<?php
require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

if ($_FILES['excel_file']['error'] == 0) {
    $filePath = $_FILES['excel_file']['tmp_name'];

    // Load Excel
    $spreadsheet = IOFactory::load($filePath);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray();

    $found = [];
    $missing = [];

    // Current logged-in user
    $user_id = $conn->real_escape_string($_settings->userdata('id'));

    // Skip header (start from row 2)
    for ($i = 1; $i < count($rows); $i++) {
        $companyName = trim($rows[$i][1]); // "Company Name" column
        if (empty($companyName)) continue;

        //  Check company, its lead, and assigned user
        $query = $conn->prepare("
            SELECT cl.lead_id, ll.assigned_to, u.firstname AS assigned_name
            FROM client_list cl
            INNER JOIN lead_list ll ON cl.lead_id = ll.id
            LEFT JOIN users u ON ll.assigned_to = u.id
            WHERE cl.company_name = ?
            LIMIT 1
        ");
        $query->bind_param("s", $companyName);
        $query->execute();
        $result = $query->get_result();

        if ($result->num_rows > 0) {
            $rowData = $result->fetch_assoc();
            $assignedTo = $rowData['assigned_to'];
            $assignedName = $rowData['assigned_name'] ?? 'Unknown';

            if ($assignedTo == $user_id) {
                //  Assigned to uploader
                $found[] = [
                    'company' => $companyName,
                    'lead_id' => $rowData['lead_id'],
                    'row_data' => $rows[$i]
                ];
            } else {
                // Exists but assigned to another user
                $missing[] = "$companyName (Assigned to: $assignedName)";
            }
        } else {
            //  Not found in database
            $missing[] = "$companyName (Not found)";
        }
    }

    // Step 2: Show verification summary
    if (!isset($_POST['confirm'])) {
        $found_names = array_map(fn($f) => $f['company'], $found);
        echo json_encode([
            'status' => 'verify',
            'found_count' => count($found),
            'missing_count' => count($missing),
            'found_list' => $found_names,
            'missing_list' => $missing
        ]);
        exit;
    }

    //  Step 3: Proceed with DB updates for valid leads
    $inserted = 0;

    foreach ($found as $data) {
        $row = $data['row_data'];
        $lead_id = $data['lead_id'];

        // Adjusted indexes (since "Status" column removed)
        $log_type = (int) filter_var($row[2], FILTER_SANITIZE_NUMBER_INT);
        $call_outcome = (int) filter_var($row[3], FILTER_SANITIZE_NUMBER_INT);
        $conversation_outcome = (int) filter_var($row[4], FILTER_SANITIZE_NUMBER_INT);

        // Handle FollowUp Date
        $rawDate = trim($row[5]);
        $follow_up_date = null;

        if (!empty($rawDate)) {
            if (is_numeric($rawDate)) {
                $follow_up_date = date(
                    'Y-m-d H:i:s',
                    \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp($rawDate)
                );
            } else {
                $rawDate = str_replace('-', '/', $rawDate);
                $dt = DateTime::createFromFormat('d/m/Y', $rawDate);
                if ($dt) {
                    $follow_up_date = $dt->format('Y-m-d H:i:s');
                } else {
                    $follow_up_date = date('Y-m-d H:i:s', strtotime($rawDate));
                }
            }
        }

        $remarks = $conn->real_escape_string($row[6]);
        $attempt_result = (int) filter_var($row[7], FILTER_SANITIZE_NUMBER_INT);
        $lead_status = (int) filter_var($row[8], FILTER_SANITIZE_NUMBER_INT);

        // Insert log_list
        $conn->query("
            INSERT INTO log_list (
                lead_id, log_type, remarks, follow_up_date, user_id, 
                date_created, date_updated, call_outcome, is_updated, 
                conversation_outcome, reason_not_interested, attempt_result
            ) VALUES (
                '$lead_id', '$log_type', '$remarks',  " . ($follow_up_date ? "'$follow_up_date'" : "NULL") . ",  '$user_id',
                NOW(), NULL, '$call_outcome', 1, '$conversation_outcome', 0, '$attempt_result'
            )
        ");

        // Get latest old_status from history
        $oldStatusQuery = $conn->prepare("
            SELECT new_status 
            FROM lead_status_history 
            WHERE lead_id = ? 
            ORDER BY changed_at DESC 
            LIMIT 1
        ");
        $oldStatusQuery->bind_param("i", $lead_id);
        $oldStatusQuery->execute();
        $oldStatusResult = $oldStatusQuery->get_result();

        $old_status = $oldStatusResult->num_rows > 0
            ? $oldStatusResult->fetch_assoc()['new_status']
            : 0;

        // Insert into lead_status_history
        $conn->query("
            INSERT INTO lead_status_history (
                lead_id, old_status, new_status, changed_at, changed_by, date_updated
            ) VALUES (
                '$lead_id', '$old_status', '$lead_status', NOW(), '$user_id', NULL
            )
        ");

        if ($conn->affected_rows > 0) $inserted++;
    }

    // ==========================================================
//  Notify Admin & Manager if leads missing or assigned
// ==========================================================
if (!empty($missing)) {
    $user_id = $_settings->userdata('id');

    // Fetch all Admins and Managers
    $admins = $conn->query("SELECT id FROM users WHERE type IN (1,3)");
    $recipients = [];
    while ($r = $admins->fetch_assoc()) {
        $recipients[] = $r['id'];
    }

    // Prepare message
    $msg = '
<div class="p-3 bg-gray-50 border border-gray-200 rounded-lg">
  <h2 class="text-sm font-semibold text-blue-600 mb-3 text-center whitespace-nowrap">
  Bulk Upload Summary
</h2>
  <p class="text-xs text-gray-700 mb-2"> Leads requiring attention:</p>
  <table class="min-w-full border border-gray-300 rounded-md">
    <thead class="bg-gray-100">
      <tr>
        <th class="border px-1 py-1 text-center text-xs font-medium text-gray-700">#</th>
        <th class="border px-1 py-1 text-center text-xs font-medium text-gray-700">Company Name</th>
        <th class="border px-1 py-1 text-center text-xs font-medium text-gray-700">Status</th>
      </tr>
    </thead>
    <tbody>
';

$count = 1;
foreach ($missing as $m) {
    // Parse messages like "KSB Limited (Assigned to: Ritik)" or "Ram bharosa (Not found)"
    if (preg_match('/^(.*?)\s*\((.*?)\)$/', $m, $matches)) {
        $company = htmlspecialchars($matches[1]);
        $status = htmlspecialchars($matches[2]);
    } else {
        $company = htmlspecialchars($m);
        $status = 'Unknown';
    }

    $msg .= "
      <tr>
        <td class='border px-1 py-1 text-xs text-gray-600'>{$count}</td>
        <td class='border px-1 py-1 text-xs text-gray-800'>{$company}</td>
        <td class='border px-1 py-1 text-xs text-red-600 font-medium'>{$status}</td>
      </tr>
    ";
    $count++;
}

$uploaded_by = $_settings->userdata('username'); // or 'firstname' or 'id' — whichever you store

$msg .= '
    </tbody>
  </table>
  <p class="text-xs text-gray-500 mt-3"> Sent automatically by 
    <span class="text-green-600 font-semibold">' . htmlspecialchars($uploaded_by) . '</span>
  </p>
</div>
';

    // Send messages
    $stmt = $conn->prepare("INSERT INTO messages (sender_id, recipient_id, message, date_sent, is_read) VALUES (?, ?, ?, NOW(), 0)");
    foreach ($recipients as $recipient_id) {
        $stmt->bind_param("iis", $user_id, $recipient_id, $msg);
        $stmt->execute();
    }
    $stmt->close();
}


    echo json_encode([
        'status' => 'success',
        'inserted' => $inserted,
        'total' => count($found)
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'File upload failed']);
}
?>
