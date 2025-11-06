<?php
include '../config.php'; // path to your DB config

if (!isset($_GET['lead_id']) || !is_numeric($_GET['lead_id'])) {
    echo "<p class='text-danger'>Invalid lead ID.</p>";
    exit;
}

$lead_id = (int) $_GET['lead_id'];

$statusLabels = [
    0 => 'Lead – Uncontacted',
    1 => 'Prospect – Contact Made',
    2 => 'Qualified – Need Validated',
    3 => 'Solution Fit / Discovery',
    4 => 'Proposal / Value Proposition',
    5 => 'Negotiation',
    6 => 'Closed – Won',
    7 => 'Closed – Lost'
];

$qry = $conn->query("
    SELECT h.*, u.firstname, u.lastname 
    FROM lead_status_history h 
    LEFT JOIN users u ON h.changed_by = u.id 
    WHERE h.lead_id = $lead_id 
    ORDER BY h.changed_at ASC
");

if ($qry->num_rows == 0) {
    echo "<p class='text-muted'>No status history found for this lead.</p>";
    exit;
}

echo '<div class="timeline-container d-flex overflow-auto px-2 py-3" style="gap: 20px; white-space: nowrap;">';

while ($row = $qry->fetch_assoc()) {
    $old = $statusLabels[$row['old_status']] ?? 'Unknown';
    $new = $statusLabels[$row['new_status']] ?? 'Unknown';
    $time = date("d M Y, h:i A", strtotime($row['changed_at']));
    $by = $row['firstname'] ? $row['firstname'] . ' ' . $row['lastname'] : 'System';

    echo <<<HTML
    <div class="timeline-step position-relative text-center">
        <div class="timeline-card p-3 bg-white rounded shadow" style="border-radius: 15px;">
            <div class="emoji-icon mb-2" style="font-size: 2rem;">🔁</div>
            <div class="fw-bold mb-2">
                <span class="badge bg-secondary">{$old}</span>
                <i class="fa fa-arrow-right text-muted mx-1"></i>
                <span class="badge bg-success">{$new}</span>
            </div>
            <small class="text-muted">{$time} by {$by}</small>
        </div>
    </div>
HTML;
}

echo '</div>';
?>
