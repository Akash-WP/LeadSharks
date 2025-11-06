<?php
require_once('./../../config.php');
if(isset($_GET['id'])){
    $qry = $conn->query("SELECT * FROM `log_list` where id = '{$_GET['id']}'");
    if($qry->num_rows > 0){
        $res = $qry->fetch_array();
        foreach($res as $k => $v){
            if(!is_numeric($k))
            $$k = $v;
        }
    }
}
$lead_id = isset($lead_id) ? $lead_id : '';
$users = $conn->query("SELECT id,CONCAT(lastname,', ', firstname, '', COALESCE(middlename,'')) as fullname FROM `users` where id in (SELECT `user_id` FROM `log_list` where lead_id = '{$lead_id}')");
$user_arr = array_column($users->fetch_all(MYSQLI_ASSOC),'fullname','id');

// Optional: Status name mapping
function getStatusName($status_code) {
    $status_map = [
        0 => 'Lead – Uncontacted',
        1 => 'Prospect – Contact Made',
        2 => 'Qualified – Need Validated',
        3 => 'Solution Fit / Discovery',
        4 => 'Proposal / Value Proposition',
        5 => 'Negotiation',
        6 => 'Closed – Won',
        7 => 'Closed – Lost'
    ];
    return $status_map[$status_code] ?? 'N/A';
}

// Fetch the most recent status change for the lead (optional: LIMIT 1 for latest only)
$latest_status = $conn->query("SELECT * FROM `lead_status_history` WHERE lead_id = '{$lead_id}' ORDER BY changed_at DESC LIMIT 1");
$status_change = $latest_status->num_rows > 0 ? $latest_status->fetch_assoc() : null;
?>
<style>
    #uni_modal .modal-footer{
        display:none;
    }
</style>
<div class="container-fluid">
    <div class="row">
        <dl>
            <dt class="text-muted">Log Type</dt>
            <dd class='pl-4 fs-4 fw-bold'><?= isset($log_type) ? (($log_type == 1)? 'Outbound' : 'Inbound') : 'N/A' ?></dd>
            
            <!-- <?//php if ($status_change): ?>
            <dt class="text-muted">Latest Status Change</dt>
            <dd class='pl-4 fs-5 fw-bold'>
                <?= getStatusName($status_change['old_status']) ?> 
                <i class="fa fa-arrow-right mx-2"></i> 
                <?= getStatusName($status_change['new_status']) ?>
                <br>
                <small class="text-muted">On <?= date("M d, Y h:i A", strtotime($status_change['changed_at'])) ?> 
                by <?= isset($user_arr[$status_change['changed_by']]) ? $user_arr[$status_change['changed_by']] : 'System' ?></small>
            </dd>
            <?//php endif; ?> -->

<?php if ($status_change): ?>
    <dt class="text-muted"><?= $status_change['old_status'] != $status_change['new_status'] ? 'Status Change' : 'Current Status' ?></dt>
    <dd class='pl-4 fs-5 fw-bold'>
        <?php if ($status_change['old_status'] != $status_change['new_status']): ?>
            <?= getStatusName($status_change['old_status']) ?>
            <i class="fa fa-arrow-right mx-2"></i>
            <?= getStatusName($status_change['new_status']) ?>
        <?php else: ?>
            <?= getStatusName($status_change['new_status']) ?>
        <?php endif; ?>

        <br>
        <small class="text-muted">
            On <?= date("M d, Y h:i A", strtotime($status_change['changed_at'])) ?>
            by <?= isset($user_arr[$status_change['changed_by']]) ? $user_arr[$status_change['changed_by']] : 'System' ?>
        </small>

        <?php if (
            !empty($status_change['date_updated']) &&
            $status_change['date_updated'] !== '0000-00-00 00:00:00' &&
            $status_change['date_updated'] !== $status_change['changed_at']
        ): ?>
            <br>
            <small class="text-muted text-info">
                <i class="fa fa-edit"></i> Last Edited on <?= date("M d, Y h:i A", strtotime($status_change['date_updated'])) ?>
            </small>
        <?php endif; ?>
    </dd>
<?php endif; ?>



            <dt class="text-muted">Remarks</dt>
            <dd class='pl-4 fs-4 fw-bold'><?= isset($remarks) ? $remarks : 'N/A' ?></dd>

            <dt class="text-muted">Follow-Up Call Date</dt>
            <dd class='pl-4 fs-4 fw-bold'>
                <?= !empty($follow_up_date) ? date("M d, Y h:i A", strtotime($follow_up_date)) : 'None Scheduled' ?>
            </dd>

            <dt class="text-muted">Created By</dt>
            <dd class='pl-4 fs-4 fw-bold'><?= isset($user_arr[$user_id]) ? $user_arr[$user_id] : 'N/A' ?></dd>

            <dt class="text-muted">Date Created</dt>
            <dd class='pl-4 fs-4 fw-bold'><?= isset($date_created) ? date("M d, Y", strtotime($date_created)) : 'N/A' ?></dd>
        </dl>
    </div>
    <div class="text-right">
        <button class="btn btn-dark btn-sm btn-flat" type="button" data-dismiss="modal">
            <i class="fa fa-close"></i> Close
        </button>
    </div>
</div>
