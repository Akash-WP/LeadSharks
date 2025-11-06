<?php
require_once('../config.php');
include_once('../classes/Master.php');

require_once __DIR__ . '/../vendor/phpmailer/src/Exception.php';
require_once __DIR__ . '/../vendor/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/src/SMTP.php';

$date = date('Y-m-d');

$status_labels = [
    0 => 'Lead - Uncontacted',
    1 => 'Prospect - Contact Made',
    2 => 'Qualified - Need Validated',
    3 => 'Solution Fit / Discovery',
    4 => 'Proposal / Value Proposition',
    5 => 'Negotiation',
    6 => 'Closed - Won',
    7 => 'Closed - Lost',
];

// Get all executives
$executives = $conn->query("SELECT id, firstname, lastname, email FROM users WHERE type = 2");

if (!$executives || $executives->num_rows == 0) {
    die("No executives found.");
}

while ($exec = $executives->fetch_assoc()) {
    $exec_id = $exec['id'];
    $exec_name = $exec['firstname'];
    $exec_email = $exec['email'];

    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));

    $lead_stmt = $conn->prepare("
        SELECT l.id, cl.company_name, DATE(cl.calling_date) AS calling_date 
        FROM lead_list l
        JOIN client_list cl ON cl.lead_id = l.id
        WHERE l.assigned_to = ? AND DATE(cl.calling_date) IN (?, ?)
        ORDER BY cl.calling_date ASC
    ");
    $lead_stmt->bind_param('iss', $exec_id, $yesterday, $today);
    $lead_stmt->execute();
    $lead_result = $lead_stmt->get_result();

    if ($lead_result->num_rows == 0) continue;

    // Initialize only once per executive
    $todayLeads = [];
    $yesterdayLeads = [];

    while ($lead = $lead_result->fetch_assoc()) {
        $lead_id = $lead['id'];
        $company_name = $lead['company_name'];
        $call_date = $lead['calling_date'];

        $status_res = $conn->query("SELECT status FROM lead_list WHERE id = {$lead_id} LIMIT 1");
        $status_val = $status_res && $status_res->num_rows > 0 ? (int)$status_res->fetch_assoc()['status'] : -1;
        $status_text = $status_labels[$status_val] ?? 'Unknown';

        $entry = "<strong>{$company_name}</strong> - Status: <em>{$status_text}</em><br>";

        if ($call_date == $today) {
    $todayLeads[] = $entry;
} else {
    // Check if completed via log_list
    $log_check = $conn->query("
        SELECT 1 FROM log_list 
        WHERE lead_id = {$lead_id} 
          AND DATE(date_created) = '{$yesterday}' 
          AND is_updated = 1 
        LIMIT 1
    ");
    
    if ($log_check->num_rows == 0) {
        // Not completed
        $yesterdayLeads[] = $entry;
    }
}

    }

    // Compose message
    $message = "Good Morning {$exec_name},<br><br>";

    if (!empty($todayLeads)) {
        $message .= "<strong>Today's Leads (" . date("d M Y") . "):</strong><br>";
        $count = 1;
        foreach ($todayLeads as $leadEntry) {
            $message .= "{$count}. {$leadEntry}";
            $count++;
        }
        $message .= "<br>";
    }

    if (!empty($yesterdayLeads)) {
        $message .= "<strong>Yesterday's Pending Leads (" . date("d M Y", strtotime('-1 day')) . "):</strong><br>";
        $count = 1;
        foreach ($yesterdayLeads as $leadEntry) {
            $message .= "{$count}. {$leadEntry}";
            $count++;
        }
        $message .= "<br>";
    }

    $message .= "Please ensure timely follow-up and log updates.<br><br>";
    $message .= "Regards,<br>Leads Management System";

    $lead_stmt->close();

    // Send email
    $master = new Master();
    $subject = "Today's Scheduled Leads - " . date("d M Y");
    $result = $master->send_email($exec_email, $subject, $message);

    echo $result === true 
        ? "Morning alert sent to: {$exec_email}<br>" 
        : "Failed to send to {$exec_email}: $result<br>";
}


$managers = $conn->query("SELECT firstname, email FROM users WHERE type IN (1,3)");
if ($managers && $managers->num_rows > 0) {

    $today = date('Y-m-d');
    $leads = $conn->query("
        SELECT 
            cl.company_name,
            CONCAT(u.firstname, ' ', u.lastname) AS executive_name,
            l.status,
        DATE(cl.calling_date) AS calling_date
        FROM lead_list l
        JOIN client_list cl ON cl.lead_id = l.id
        JOIN users u ON l.assigned_to = u.id
        WHERE DATE(cl.calling_date) IN ('{$yesterday}', '{$today}')
    ORDER BY cl.calling_date ASC
    ");

    if ($leads && $leads->num_rows > 0) {
        $body = "Good Morning,<br><br>";
        $body .= "Here is the list of all leads scheduled for today and pending from yesterday:<br><br>";
        $body .= "<table border='1' cellpadding='6' cellspacing='0'>";
        $body .= "<thead><tr><th>#</th><th>Company</th><th>Executive</th><th>Status</th><th>Date</th></tr></thead><tbody>";

        $count = 1;
        while ($row = $leads->fetch_assoc()) {
            $status_text = $status_labels[(int) $row['status']] ?? 'Unknown';
            $date_label = ($row['calling_date'] == $today) ? "Today" : "Yesterday";
            $body .= "<tr>";
            $body .= "<td>{$count}</td>";
            $body .= "<td>{$row['company_name']}</td>";
            $body .= "<td>{$row['executive_name']}</td>";
            $body .= "<td>{$status_text}</td>";
            $body .= "<td>{$date_label}</td>";
            $body .= "</tr>";
            $count++;
        }

        $body .= "</tbody></table><br>";
        $body .= "Regards,<br>Leads Management System";

        // Send to each admin/manager
        $master = new Master();
        while ($mgr = $managers->fetch_assoc()) {
            $subject = "Today's Complete Lead Summary - " . date("d M Y");
            $result = $master->send_email($mgr['email'], $subject, $body);
            if ($result === true) {
                echo "Summary sent to: {$mgr['email']}<br>";
            } else {
                echo "Failed to send to {$mgr['email']}<br>";
            }
        }
    }
}
