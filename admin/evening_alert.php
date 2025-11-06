<?php
require_once('../config.php');
include_once('../classes/Master.php');
$headers[] = "Content-Type: text/html; charset=UTF-8";

// Ensure proper date format
// $date = isset($_GET['date']) && strtotime($_GET['date']) ? date('Y-m-d', strtotime($_GET['date'])) : date('Y-m-d');

// Fix date format handling
if (isset($_GET['date'])) {
    $rawDate = str_replace('/', '-', $_GET['date']); // Normalize slashes to dashes
    $timestamp = strtotime($rawDate);
    $date = $timestamp ? date('Y-m-d', $timestamp) : date('Y-m-d');
} else {
    $date = date('Y-m-d');
}
// Managers to email
$managers = $conn->query("SELECT firstname, email FROM users WHERE type IN (1, 3)");
if (!$managers || $managers->num_rows == 0) {
    die("No managers found.");
}

// Mapping arrays
$status_labels = [
    0 => 'Lead  Uncontacted',
    1 => 'Prospect  Contact Made',
    2 => 'Qualified  Need Validated',
    3 => 'Solution Fit  Discovery',
    4 => 'Proposal  Value Proposition',
    5 => 'Negotiation',
    6 => 'Closed  Won',
    7 => 'Closed  Lost'
];

$conversation_outcome_map = [
    1 => 'Interested',
    2 => 'Call Back Later',
    3 => 'Busy  Try Again',
    4 => 'Needs More Info',
    5 => 'Not Interested',
    6 => 'Already Purchased',
    7 => 'Wrong Contact'
];

$reason_not_interested_map = [
    1 => 'Purchased from Competitor',
    2 => 'Purchased Directly from Us',
    3 => 'Purchased via Partner',
    4 => 'No Longer Needed'
];

$attempt_result_map = [
    1 => 'Ringing, No Answer',
    2 => 'Switched Off',
    3 => 'Busy',
    4 => 'Call Dropped',
    5 => 'Invalid Number'
];

$outcome_map = [
    1 => 'Answered',
    2 => 'Not Answered',
    3 => 'Invalid Number',
    4 => 'Not Interested'
];

// Executives with any log activity today
$executive_sql = "
    SELECT DISTINCT u.id, CONCAT(u.firstname, ' ', u.lastname) AS name
    FROM users u
    JOIN lead_list l ON u.id = l.assigned_to
    JOIN client_list cl ON cl.lead_id = l.id
    WHERE u.type = 2 AND DATE(cl.calling_date) = ?
    
    UNION

    SELECT DISTINCT u.id, CONCAT(u.firstname, ' ', u.lastname) AS name
    FROM users u
    JOIN lead_list l ON u.id = l.assigned_to
    JOIN log_list lg ON lg.lead_id = l.id
    WHERE u.type = 2 AND DATE(lg.date_created) = ? AND lg.is_updated = 1
";
$executive_stmt = $conn->prepare($executive_sql);
$executive_stmt->bind_param('ss', $date, $date);
$executive_stmt->execute();
$executive_result = $executive_stmt->get_result();
$executives = [];
while ($row = $executive_result->fetch_assoc()) {
    $executives[] = $row;
}
$executive_stmt->close();

if (empty($executives)) {
    $next_day = date('Y-m-d', strtotime($date . ' +1 day'));

    // Check if follow-ups are scheduled for tomorrow
    $check_followups = $conn->prepare("
        SELECT COUNT(*) as total
        FROM (
            SELECT lg.lead_id
            FROM log_list lg
            INNER JOIN (
                SELECT lead_id, MAX(date_created) AS latest
                FROM log_list
                WHERE is_updated = 1
                GROUP BY lead_id
            ) latest_log ON latest_log.lead_id = lg.lead_id AND latest_log.latest = lg.date_created
            WHERE lg.is_updated = 1 AND DATE(lg.follow_up_date) = ?
        ) AS upcoming
    ");
    $check_followups->bind_param('s', $next_day);
    $check_followups->execute();
    $res = $check_followups->get_result();
    $total_followups = $res->fetch_assoc()['total'] ?? 0;
    $check_followups->close();

    // Start email
    $html = "<h2>Executive Summary for " . date("d M Y", strtotime($date)) . "</h2>";

    if ($total_followups == 0) {
        $html .= "<p><strong>No activity was recorded today by any executive, and no follow-ups are scheduled for tomorrow.</strong></p>";
    } else {
        $html .= "<p><strong>No activity was recorded today by any executive. However, follow-ups are scheduled for tomorrow.</strong></p>";

        // ➕ Add follow-up table
        $html .= "<h3>Upcoming Follow-ups for " . date('d M Y', strtotime($next_day)) . "</h3>";
        $html .= "<table border='1' cellpadding='6' cellspacing='0' style='border-collapse: collapse; font-family:Arial, sans-serif;'>";
        $html .= "<tr style='background-color:#f2f2f2;'>
                    <th>Executive</th>
                    <th>Company Name</th>
                    <th>Follow-up Date</th>
                    <th>Call Outcome</th>
                    <th>Result</th>
                    <th>Status</th>
                    <th>Remark</th>
                  </tr>";

        $followup_sql = "
    SELECT 
        CONCAT(u.firstname, ' ', u.lastname) AS executive_name,
        cl.company_name,
        lg.follow_up_date,
        lg.call_outcome,
        lg.conversation_outcome,
        lg.attempt_result,
        lg.remarks,
        lg.reason_not_interested,
        l.status
    FROM log_list lg
    INNER JOIN (
        SELECT lead_id, MAX(date_created) AS latest
        FROM log_list
        WHERE is_updated = 1
        GROUP BY lead_id
    ) latest_logs ON latest_logs.lead_id = lg.lead_id AND latest_logs.latest = lg.date_created
    JOIN lead_list l ON l.id = lg.lead_id
    JOIN client_list cl ON cl.lead_id = l.id
    JOIN users u ON u.id = l.assigned_to
    WHERE lg.is_updated = 1 AND DATE(lg.follow_up_date) = ?
    ORDER BY u.firstname, cl.company_name
";
        $follow_stmt = $conn->prepare($followup_sql);
        $follow_stmt->bind_param('s', $next_day);
        $follow_stmt->execute();
        $follow_result = $follow_stmt->get_result();

        if ($follow_result->num_rows > 0) {
            while ($row = $follow_result->fetch_assoc()) {
                $exec_name = $row['executive_name'];
                $company = $row['company_name'];
                $fup_date = date('d M Y', strtotime($row['follow_up_date']));
                $status_text = $status_labels[$row['status']] ?? '-';
                $call_outcome_text = $outcome_map[$row['call_outcome']] ?? '-';

                $result_text = '-';
                if ($row['call_outcome'] == 1) {
                    $result_text = $conversation_outcome_map[$row['conversation_outcome']] ?? '-';
                    if ($row['conversation_outcome'] == 6 && !empty($row['reason_not_interested'])) {
                        $reason = $reason_not_interested_map[$row['reason_not_interested']] ?? '';
                        $result_text .= " – $reason";
                    }
                } elseif ($row['call_outcome'] == 2) {
                    $result_text = $attempt_result_map[$row['attempt_result']] ?? '-';
                }

                $html .= "<tr>
                            <td>{$exec_name}</td>
                            <td>{$company}</td>
                            <td>{$fup_date}</td>
                            <td>{$call_outcome_text}</td>
                            <td>{$result_text}</td>
                            <td>{$status_text}</td>
                            <td>{$row['remarks']}</td>
                          </tr>";
            }
        } else {
            $html .= "<tr><td colspan='7' style='text-align:center;'>No follow-ups scheduled for tomorrow.</td></tr>";
        }

        $follow_stmt->close();
        $html .= "</table><br>";
    }

    // Send to all managers
    $master = new Master();
    while ($mgr = $managers->fetch_assoc()) {
        $email = $mgr['email'];
        $name = $mgr['firstname'];
        $subject = "Daily Executive Summary - " . date("d M Y", strtotime($date));
        $result = $master->send_email($email, $subject, $html);
        echo $email . ': ' . ($result === true ? 'Sent' : 'Failed - ' . $result) . "<br>";
    }

    exit;
}

// Start HTML
$html = "<h2>Executive Summary for " . date("d M Y", strtotime($date)) . "</h2>";
$html .= "<table border='1' cellpadding='6' cellspacing='0' style='border-collapse: collapse; font-family:Arial, sans-serif;'>";
$html .= "<tr><th>Executive Name</th><th>Assigned Leads</th><th>Completed</th><th>Pending</th><th>Follow-ups</th></tr>";

$notes = [];

foreach ($executives as $exec) {
    $eid = $exec['id'];
    $ename = $exec['name'];

    // Assigned leads today
    $assigned_today_ids = [];
$assigned_res = $conn->query("
    SELECT l.id 
    FROM lead_list l
    JOIN client_list cl ON cl.lead_id = l.id
    WHERE l.assigned_to = {$eid} AND DATE(cl.calling_date) = '{$date}'
");
while ($row = $assigned_res->fetch_assoc()) {
    $assigned_today_ids[] = $row['id'];
}
    $assigned_total = count($assigned_today_ids);

    // Completed
    $completed_today = 0;
    foreach ($assigned_today_ids as $lid) {
        $log = $conn->query("SELECT COUNT(*) as cnt FROM log_list WHERE lead_id = {$lid} AND is_updated = 1");
        $completed_today += ($log && $log->fetch_assoc()['cnt'] > 0) ? 1 : 0;
    }

    $pending = $assigned_total - $completed_today;

    // Follow-ups (updated leads not assigned today)
    // $updated_res = $conn->query("SELECT DISTINCT l.id FROM lead_list l JOIN log_list lg ON lg.lead_id = l.id WHERE l.assigned_to = {$eid} AND DATE(l.date_updated) = '{$date}' AND lg.is_updated = 1");
    $updated_res = $conn->query("SELECT DISTINCT l.id FROM lead_list l JOIN log_list lg ON lg.lead_id = l.id WHERE l.assigned_to = {$eid} AND DATE(lg.date_created) = '{$date}' AND lg.is_updated = 1");
    $updated_not_assigned = 0;
    while ($row = $updated_res->fetch_assoc()) {
        if (!in_array($row['id'], $assigned_today_ids)) {
            $updated_not_assigned++;
        }
    }

    $html .= "<tr><td>{$ename}</td><td>{$assigned_total}</td><td>{$completed_today}</td><td>{$pending}</td><td>{$updated_not_assigned}</td></tr>";

    // Notes section
    if ($assigned_total == 0 && $updated_not_assigned > 0) {
        $notes[] = "No new leads were assigned to <b>$ename</b> today, but they followed up on <b>$updated_not_assigned</b> older leads.";
    } elseif ($assigned_total > 0) {
        $notes[] = "<b>$ename</b> was assigned <b>$assigned_total</b> leads, completed <b>$completed_today</b>, and has <b>$pending</b> pending. Also followed up on <b>$updated_not_assigned</b> older leads.";
    }
}

$html .= "</table><br>";

$html .= "<h3>Quick Notes</h3><ul>";
foreach ($notes as $n) {
    $html .= "<li>$n</li>";
}
$html .= "</ul>";

$html .= "<h4>Call Outcome Legend</h4><ul>
    <li><b>Answered</b>  Executive spoke to the lead.</li>
    <li><b>Not Answered</b>  No conversation could happen.</li>
    <li><b>Invalid Number</b>, <b>Already Purchased</b>, etc.  Based on lead response.</li>
</ul>";

// Activity Log
$html .= "<h3>Detailed Activity</h3>";
$html .= "<table border='1' cellpadding='6' cellspacing='0' style='border-collapse: collapse; font-family:Arial, sans-serif;'>";
$html .= "<tr style='background-color:#f2f2f2;'>
            <th>Executive</th>
            <th>Company Name</th>
            <th>Call Outcome</th>
            <th>Result</th>
            <th>Remark</th>
            <th>Status</th>
            <th>Follow-up Date</th>
            <th>Type</th>
          </tr>";

// Step 1: Fetch and group data
$log_sql = "
    SELECT 
        CONCAT(u.firstname, ' ', u.lastname) AS executive_name,
        cl.company_name,
        lg.call_outcome,
        lg.conversation_outcome,
        lg.attempt_result,
        lg.remarks,
        lg.reason_not_interested,
        l.status,
        lg.follow_up_date,
        DATE(cl.calling_date) AS calling_day
    FROM lead_list l
    JOIN client_list cl ON cl.lead_id = l.id
    JOIN users u ON u.id = l.assigned_to
    JOIN (
        SELECT lg1.*
        FROM log_list lg1
        JOIN (
            SELECT lead_id, MAX(date_created) AS latest
            FROM log_list
            WHERE is_updated = 1
            GROUP BY lead_id
        ) lg2 ON lg1.lead_id = lg2.lead_id AND lg1.date_created = lg2.latest
        WHERE lg1.is_updated = 1
    ) lg ON lg.lead_id = l.id
    WHERE DATE(lg.date_created) = ?
    ORDER BY executive_name, cl.company_name
";

$log_stmt = $conn->prepare($log_sql);
$log_stmt->bind_param('s', $date);
$log_stmt->execute();
$log_result = $log_stmt->get_result();

// Step 2: Group by executive
$grouped = [];
while ($row = $log_result->fetch_assoc()) {
    $exec = $row['executive_name'];
    if (!isset($grouped[$exec])) {
        $grouped[$exec] = [];
    }
    $grouped[$exec][] = $row;
}
$log_stmt->close();

// Step 3: Render with rowspan
foreach ($grouped as $exec_name => $rows) {
    $rowspan = count($rows);
    $first = true;
    foreach ($rows as $row) {
        $call_outcome_text = $outcome_map[$row['call_outcome']] ?? '-';
        $result_text = '-';

        if ($row['call_outcome'] == 1) {
            $result_text = $conversation_outcome_map[$row['conversation_outcome']] ?? '-';
            if ($row['conversation_outcome'] == 6 && !empty($row['reason_not_interested'])) {
                $reason = $reason_not_interested_map[$row['reason_not_interested']] ?? '';
                $result_text .= " – $reason";
            }
        } elseif ($row['call_outcome'] == 2) {
            $result_text = $attempt_result_map[$row['attempt_result']] ?? '-';
        }

        $status_text = $status_labels[$row['status']] ?? '-';
        $type = ($row['calling_day'] == $date) ? 'Assigned Today' : 'Follow-up';
        $follow_up_date = $row['follow_up_date'] ? date('d M Y', strtotime($row['follow_up_date'])) : '-';

        $html .= "<tr>";
        if ($first) {
            $html .= "<td rowspan='{$rowspan}'>{$exec_name}</td>";
            $first = false;
        }
        $html .= "
            <td>{$row['company_name']}</td>
            <td>{$call_outcome_text}</td>
            <td>{$result_text}</td>
            <td>{$row['remarks']}</td>
            <td>{$status_text}</td>
            <td>{$follow_up_date}</td>
            <td>{$type}</td>
        </tr>";
    }
}

$html .= "</table><br>";

// 🔔 Upcoming Follow-ups
$next_day = date('Y-m-d', strtotime($date . ' +1 day'));

$html .= "<h3> Upcoming Follow-ups for " . date('d M Y', strtotime($next_day)) . "</h3>";
$html .= "<table border='1' cellpadding='6' cellspacing='0' style='border-collapse: collapse; font-family:Arial, sans-serif;'>";
$html .= "<tr style='background-color:#f2f2f2;'>
            <th>Executive</th>
            <th>Company Name</th>
            <th>Follow-up Date</th>
            <th>Call Outcome</th>
            <th>Result</th>
            <th>Status</th>
            <th>Remark</th>
          </tr>";

$followup_sql = "
    SELECT 
        CONCAT(u.firstname, ' ', u.lastname) AS executive_name,
        cl.company_name,
        lg.follow_up_date,
        lg.call_outcome,
        lg.conversation_outcome,
        lg.attempt_result,
        lg.remarks,
        lg.reason_not_interested,
        l.status
    FROM log_list lg
    INNER JOIN (
        SELECT lead_id, MAX(date_created) AS latest
        FROM log_list
        WHERE is_updated = 1
        GROUP BY lead_id
    ) latest_log ON latest_log.lead_id = lg.lead_id AND latest_log.latest = lg.date_created
    JOIN lead_list l ON l.id = lg.lead_id
    JOIN client_list cl ON cl.lead_id = l.id
    JOIN users u ON u.id = l.assigned_to
    WHERE lg.is_updated = 1 AND DATE(lg.follow_up_date) = ?
    ORDER BY u.firstname, cl.company_name
";

$follow_stmt = $conn->prepare($followup_sql);
$follow_stmt->bind_param('s', $next_day);
$follow_stmt->execute();
$follow_result = $follow_stmt->get_result();

if ($follow_result->num_rows > 0) {
    while ($row = $follow_result->fetch_assoc()) {
        $exec_name = $row['executive_name'];
        $company = $row['company_name'];
        $fup_date = date('d M Y', strtotime($row['follow_up_date']));
        $status_text = $status_labels[$row['status']] ?? '-';
        $call_outcome_text = $outcome_map[$row['call_outcome']] ?? '-';

        $result_text = '-';
        if ($row['call_outcome'] == 1) {
            $result_text = $conversation_outcome_map[$row['conversation_outcome']] ?? '-';
            if ($row['conversation_outcome'] == 6 && !empty($row['reason_not_interested'])) {
                $reason = $reason_not_interested_map[$row['reason_not_interested']] ?? '';
                $result_text .= " – $reason";
            }
        } elseif ($row['call_outcome'] == 2) {
            $result_text = $attempt_result_map[$row['attempt_result']] ?? '-';
        }

        $html .= "<tr>
                    <td>{$exec_name}</td>
                    <td>{$company}</td>
                    <td>{$fup_date}</td>
                    <td>{$call_outcome_text}</td>
                    <td>{$result_text}</td>
                    <td>{$status_text}</td>
                    <td>{$row['remarks']}</td>
                  </tr>";
    }
} else {
    $html .= "<tr><td colspan='7' style='text-align:center;'>No follow-ups scheduled for tomorrow.</td></tr>";
}

$follow_stmt->close();
$html .= "</table><br>";




// Send to managers
$master = new Master();
while ($mgr = $managers->fetch_assoc()) {
    $email = $mgr['email'];
    $name = $mgr['firstname'];
    $subject = "Daily Executive Summary - " . date("d M Y", strtotime($date));
    $result = $master->send_email($email, $subject, $html);
    echo $email . ': ' . ($result === true ? 'Sent' : 'Failed - ' . $result) . "<br>";
}
?>
