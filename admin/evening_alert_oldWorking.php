<?php
require_once('../config.php');
include_once('../classes/Master.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/phpmailer/src/Exception.php';
require_once __DIR__ . '/../vendor/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/src/SMTP.php';

$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Get list of all managers/admins
$managers = $conn->query("SELECT firstname, email FROM users WHERE type IN (1, 3)");

if (!$managers || $managers->num_rows == 0) {
    die("No managers found.");
}

// Fetch summary data
$sql = "
    SELECT
        u.id AS executive_id, 
        u.firstname AS executive_name,
        u.email AS executive_email,
        COUNT(l.id) AS total_leads,
        SUM(CASE WHEN l.status = 0 THEN 1 ELSE 0 END) AS uncontacted,
        SUM(CASE WHEN l.status = 1 THEN 1 ELSE 0 END) AS contact_made,
        SUM(CASE WHEN l.status = 2 THEN 1 ELSE 0 END) AS qualified,
        SUM(CASE WHEN l.status = 3 THEN 1 ELSE 0 END) AS discovery,
        SUM(CASE WHEN l.status = 4 THEN 1 ELSE 0 END) AS proposal,
        SUM(CASE WHEN l.status = 5 THEN 1 ELSE 0 END) AS negotiation,
        SUM(CASE WHEN l.status = 6 THEN 1 ELSE 0 END) AS closed_won,
        SUM(CASE WHEN l.status = 7 THEN 1 ELSE 0 END) AS closed_lost
    FROM lead_list l
    JOIN users u ON l.assigned_to = u.id
    WHERE DATE(l.date_updated) = '$date'
    GROUP BY u.id
";

$summary_result = $conn->query($sql);
$summary_data = [];

while ($row = $summary_result->fetch_assoc()) {
    $summary_data[] = $row;
}

// Build message body
$status_labels = [
    'uncontacted' => 'Lead - Uncontacted',
    'contact_made' => 'Prospect - Contact Made',
    'qualified' => 'Qualified - Need Validated',
    'discovery' => 'Solution Fit / Discovery',
    'proposal' => 'Proposal / Value Proposition',
    'negotiation' => 'Negotiation',
    'closed_won' => 'Closed - Won',
    'closed_lost' => 'Closed - Lost'
];

$message = "Good Evening,<br><br>Here is the summary of executive activities for " . date("d M Y", strtotime($date)) . ":<br><br>";

// foreach ($summary_data as $exec) {
//     $message .= "Executive: {$exec['executive_name']}<br>";
//     // foreach ($status_labels as $key => $label) {
//     //     $message .= "- {$label}: {$exec[$key]}<br>";
//     // }

//     // Additional Lead Summary (Assigned Today via calling_date)
//     $assigned_today_stmt = $conn->prepare("
//         SELECT l.id 
//         FROM lead_list l
//         JOIN client_list cl ON cl.lead_id = l.id
//         WHERE l.assigned_to = ? 
//         AND DATE(cl.calling_date) = ?
//     ");
//     $assigned_today_stmt->bind_param('is', $exec['executive_id'], $date);
//     $assigned_today_stmt->execute();
//     $assigned_result = $assigned_today_stmt->get_result();

//     $total_today = 0;
//     $completed_today = 0;

//     while ($lead_row = $assigned_result->fetch_assoc()) {
//         $total_today++;
//         $lead_id = $lead_row['id'];

//         $check = $conn->query("SELECT COUNT(*) as cnt FROM log_list WHERE lead_id = {$lead_id} AND is_updated = 1");
//         $updated = $check && $check->num_rows ? (int)$check->fetch_assoc()['cnt'] : 0;

//         if ($updated > 0) {
//             $completed_today++;
//         }
//     }
//     $assigned_today_stmt->close();

//     $not_completed_today = $total_today - $completed_today;
//     $completed_percent = $total_today > 0 ? ($completed_today / $total_today) * 100 : 0;
//     $not_completed_percent = 100 - $completed_percent;

//     $message .= "<br>[Today's Assigned Leads Summary]<br>";
//     $message .= "- Total Assigned Today: {$total_today}<br>";
//     $message .= "- Completed (Updated): {$completed_today}<br>";
//     $message .= "- Not Completed: {$not_completed_today}<br>";
//     //$message .= "- Completion: " . round($completed_percent) . "% Completed, " . round($not_completed_percent) . "% Pending<br><br>";

//     //$message .= "<br>";
// }

foreach ($summary_data as $exec) {
    $message .= "<strong>Executive:</strong> {$exec['executive_name']}<br><br>";

    // Assigned today via calling_date
    $assigned_today_stmt = $conn->prepare("
        SELECT l.id 
        FROM lead_list l
        JOIN client_list cl ON cl.lead_id = l.id
        WHERE l.assigned_to = ? 
        AND DATE(cl.calling_date) = ?
    ");
    $assigned_today_stmt->bind_param('is', $exec['executive_id'], $date);
    $assigned_today_stmt->execute();
    $assigned_result = $assigned_today_stmt->get_result();

    $total_today = 0;
    $completed_today = 0;

    while ($lead_row = $assigned_result->fetch_assoc()) {
        $total_today++;
        $lead_id = $lead_row['id'];

        $check = $conn->query("SELECT COUNT(*) as cnt FROM log_list WHERE lead_id = {$lead_id} AND is_updated = 1");
        $updated = $check && $check->num_rows ? (int)$check->fetch_assoc()['cnt'] : 0;

        if ($updated > 0) {
            $completed_today++;
        }
    }
    $assigned_today_stmt->close();

    $not_completed_today = $total_today - $completed_today;

    $message .= "<strong>Today's Assigned Leads Summary:</strong><br>";
    $message .= "- Total Assigned Today: {$total_today}<br>";
    $message .= "- Completed (Updated): {$completed_today}<br>";
    $message .= "- Not Completed: {$not_completed_today}<br><br>";

    // Table of leads that had action (is_updated = 1)
    $action_stmt = $conn->prepare("
        SELECT l.id, cl.company_name, l.status, DATE(l.date_updated) as updated_on
        FROM lead_list l
        JOIN client_list cl ON cl.lead_id = l.id
        WHERE l.assigned_to = ?
          AND DATE(l.date_updated) = ?
          AND EXISTS (
            SELECT 1 FROM log_list lg WHERE lg.lead_id = l.id AND lg.is_updated = 1
          )
    ");
    $action_stmt->bind_param('is', $exec['executive_id'], $date);
    $action_stmt->execute();
    $action_result = $action_stmt->get_result();

    if ($action_result->num_rows > 0) {
        $message .= "<strong>Leads Updated Today:</strong><br>";
        $message .= "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse:collapse; font-family:Arial, sans-serif; font-size:14px;'>";
        $message .= "<tr style='background-color:#f2f2f2;'>
                        <th>Lead ID</th>
                        <th>Company</th>
                        <th>Status</th>
                        <th>Updated On</th>
                     </tr>";

        while ($row = $action_result->fetch_assoc()) {
            $status_text = $status_labels[array_keys($status_labels)[$row['status']]] ?? 'Unknown';

            $message .= "<tr>
                            <td>{$row['id']}</td>
                            <td>{$row['company_name']}</td>
                            <td>{$status_text}</td>
                            <td>{$row['updated_on']}</td>
                         </tr>";
        }

        $message .= "</table><br><br>";
    } else {
        $message .= "<em>No leads updated today by this executive.</em><br><br>";
    }

    $action_stmt->close();
}


$message .= "Regards,<br>Leads Management System";

// ✅ Send to all managers using PHPMailer
echo "<table border='1' cellpadding='6' cellspacing='0' style='border-collapse: collapse; margin-top:20px;'>";
echo "<tr style='background:#f0f0f0; font-weight:bold;'><th>Email</th><th>Status</th></tr>";

$master = new Master();
while ($mgr = $managers->fetch_assoc()) {
    $email = $mgr['email'];
    $name = $mgr['firstname'];
    $subject = "Daily Executive Work Summary - " . date("d M Y", strtotime($date));
    $result = $master->send_email($email, $subject, $message);

    if ($result === true) {
        echo "<tr><td>{$email}</td><td><span style='color:green;'>Sent</span></td></tr>";
    } else {
        echo "<tr><td>{$email}</td><td><span style='color:red;'>Failed: $result</span></td></tr>";
    }
}

echo "</table>";

