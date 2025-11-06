<?php
require_once '../config.php';

require_once '../config.php';

if ($_GET['f'] == 'send') {
    $recipient = $_POST['recipient'] ?? '';
    $message = $_POST['message'] ?? '';
    $sender = $_settings->userdata('id');

    if (!$recipient || !$message) {
        echo json_encode(['status' => 'error', 'reason' => 'Missing fields']);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO messages (sender_id, recipient_id, message, date_sent) VALUES (?, ?, ?, NOW())");
    if (!$stmt) {
        echo json_encode(['status' => 'error', 'reason' => 'Prepare failed: ' . $conn->error]);
        exit;
    }

    $stmt->bind_param("iis", $sender, $recipient, $message);
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'reason' => 'Execute failed: ' . $stmt->error]);
    }

    $stmt->close();
    exit;
}

if ($_GET['f'] == 'mark_read') {
    $uid = $_settings->userdata('id');
    $from = $_POST['from_user'] ?? 0;

    $conn->query("UPDATE messages SET is_read = 1 WHERE recipient_id = $uid AND sender_id = $from");
    echo json_encode(['status' => 'ok']);
    exit;
}

if ($_GET['f'] == 'unread_count') {
    $uid = $_settings->userdata('id');
    $qry = $conn->query("SELECT COUNT(*) as cnt FROM messages WHERE recipient_id = {$uid} AND is_read = 0");
    $res = $qry->fetch_assoc();
    echo json_encode(['count' => $res['cnt'] ?? 0]);
    exit;
}
