<?php
require_once '../config.php';

$uid = $_settings->userdata('id');
$other = $_GET['with'] ?? 0;

$qry = $conn->query("SELECT m.*, 
                            s.firstname AS sender_fname, 
                            s.lastname AS sender_lname 
                     FROM messages m 
                     JOIN users s ON m.sender_id = s.id
                     WHERE (m.sender_id = $uid AND m.recipient_id = $other)
                        OR (m.sender_id = $other AND m.recipient_id = $uid)
                     ORDER BY m.date_sent ASC");

$data = [];
while ($row = $qry->fetch_assoc()) {
    $data[] = [
        'sender_id' => $row['sender_id'],
        'sender_name' => ucwords($row['sender_fname'].' '.$row['sender_lname']),
        'message' => $row['message'],
        'date_sent' => $row['date_sent']
    ];
}

echo json_encode($data);
exit;
