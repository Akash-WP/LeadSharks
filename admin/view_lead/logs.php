<?php
$id = $_GET['id'] ?? null;
$history_res = $conn->query("SELECT * FROM lead_status_history WHERE lead_id = '{$id}' ORDER BY changed_at DESC");
$history_arr = [];
while($h = $history_res->fetch_assoc()){
    $history_arr[] = $h;
}

function getStatusName($status_code) {
    $status_map = [
        0 => 'Lead  Uncontacted',
        1 => 'Prospect  Contact Made',
        2 => 'Qualified  Need Validated',
        3 => 'Solution Fit  Discovery',
        4 => 'Proposal  Value Proposition',
        5 => 'Negotiation',
        6 => 'Closed  Won',
        7 => 'Closed  Lost'
    ];
    return $status_map[$status_code] ?? 'N/A';
}

// Get the latest status history entry for current status
$current_status = null;
if (!empty($history_arr)) {
    $latest_history = end($history_arr); // last element
    $current_status = $latest_history['new_status'];
}

$users = $conn->query("SELECT id, CONCAT(lastname,', ', firstname, '', COALESCE(middlename,'')) as fullname FROM `users` WHERE id IN (SELECT `user_id` FROM `log_list` WHERE lead_id = '{$id}')");
$user_arr = array_column($users->fetch_all(MYSQLI_ASSOC), 'fullname', 'id');

$logs = $conn->query("SELECT * FROM `log_list` WHERE lead_id = '{$id}' ORDER BY unix_timestamp(date_created) ASC ");
?>

<div class="container-fluid">
    <div class="mb-3">
        <h5>Current Lead Status: 
            <span class="badge badge-info"><?= $current_status !== null ? getStatusName($current_status) : 'No status available' ?></span>
        </h5>
    </div>

    <div class="text-right mb-3">
        <button class="btn btn-primary btn-flat btn-sm" id="add_log"><i class="fa fa-plus"></i> Add New Log</button>
    </div>

    <hr>

    <!-- Status History Timeline Section -->
    <div class="status-history mb-4">
    <h5 class="mb-3">Status History Timeline</h5>
    <?php if (empty($history_arr)): ?>
        <p>No status history available for this lead.</p>
    <?php else: ?>
        <div class="timeline-container d-flex overflow-auto px-2 py-3" style="gap: 20px; white-space: nowrap;">
            <?php foreach ($history_arr as $index => $history): 
                $changed_at = date("D M d, Y h:i A", strtotime($history['changed_at']));
                $old_status_name = getStatusName($history['old_status']);
                $new_status_name = getStatusName($history['new_status']);
            ?>
            <div class="timeline-step position-relative text-center">
                <div class="timeline-card p-3 bg-white rounded shadow" style="border-radius: 15px;">
                    <div class="emoji-icon mb-2" style="font-size: 2rem;">🎯</div>
                    <div class="fw-bold mb-2">
                        <span class="badge bg-secondary"><?= $old_status_name ?></span>
                        <i class="fa fa-arrow-right text-muted mx-1"></i>
                        <span class="badge bg-success"><?= $new_status_name ?></span>
                    </div>
                    <small class="text-muted"><?= $changed_at ?></small>
                </div>
                <?php if ($index < count($history_arr) - 1): ?>
                <div class="timeline-line" style="position: absolute; top: 40%; left: 100%; height: 2px; width: 40px; background-color: #6c757d;"></div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
    .timeline-container {
    background-color: #f9f9f9;
    border-radius: 12px;
}

.timeline-step {
    display: inline-block;
    vertical-align: top;
}

.timeline-card {
    transition: transform 0.3s ease;
}
.timeline-card:hover {
    transform: scale(1.05);
    box-shadow: 0 6px 20px rgba(0,0,0,0.1);
}

.emoji-icon {
    line-height: 1;
}

</style>


    <hr>

    <!-- Existing Logs List -->
    <div class="list-group" id="lead-list">
        <?php while($row = $logs->fetch_assoc()): ?>
        <div class="list-group-item list-group-item-action list-item rounded-0">
            <div class="d-flex">
                <div class="col-auto text-muted pl-3">Log Type:</div>
                <div class="col-auto flex-grow-1 flex-shrink-1"><p class="m-0 truncate-1"><b><?= $row['log_type'] == 1 ? "Outbound" : "Inbound" ?></b></p></div>
            </div>
            <div class="d-flex">
                <div class="col-auto text-muted pl-3">Call Outcome:</div>
                <div class="col-auto flex-grow-1 flex-shrink-1"><p class="m-0 truncate-1"><b><?php
                    switch ($row['call_outcome']) {
                        case 1:
                            echo "Answered";
                            break;
                        case 2:
                            echo "Not Answered";
                            break;
                        case 3:
                            echo "Invalid Number";
                            break;
                        case 4:
                            echo "Not Interested";
                            break;
                        default:
                            echo "Unknown";
                    }
                ?></b></p></div>
            </div>
            <div class="d-flex">
                <div class="col-auto text-muted pl-3">Follow-Up Call:</div>
                <div class="col-auto flex-grow-1 flex-shrink-1">
                    <p class="m-0 truncate-1">
                        <b><?= !empty($row['follow_up_date']) ? date("D M d, Y h:i A", strtotime($row['follow_up_date'])) : "None" ?></b>
                    </p>
                </div>
            </div>

            <div class="d-flex">
                <div class="col-auto text-muted pl-3">Remarks:</div>
                <div class="col-auto flex-grow-1 flex-shrink-1"><p class="m-0 truncate-1"><b><?= $row['remarks'] ?></b></p></div>
            </div>
            <div class="clear-fix my-2"></div>
            
            <span class="text-muted"><?php
                // Find any status change near this log's created date (within 1 hour for example)
                $log_time = strtotime($row['date_created']);
                $matched_history = null;
                foreach($history_arr as $h){
                    $history_time = strtotime($h['changed_at']);
                    if(abs($history_time - $log_time) <= 3600){ // within 1 hour
                        $matched_history = $h;
                        break;
                    }
                }
                if($matched_history):
            ?>
                <div class="d-flex">
                    <div class="col-auto text-muted pl-3">Status Change:</div>
                    <div class="col-auto flex-grow-1 flex-shrink-1">
                        <p class="m-0 truncate-1">
                            <b><?= getStatusName($matched_history['old_status']) ?> <i class="fa fa-arrow-right mx-1"></i> <?= getStatusName($matched_history['new_status']) ?></b>
                        </p>
                    </div>
                </div>
                            <?php endif; ?></span>
                            <span class="text-muted"><em>Created by <?= isset($user_arr[$row['user_id']]) ? ucwords($user_arr[$row['user_id']]) : "N/A" ?> <?= date("D M d, Y h:i A",strtotime($row['date_created'])) ?></em></span>
                            <div class="clear-fix my-2"></div>
                            <div class="text-right">
                                <a class="btn btn-sm btn-flat btn-light border view_data" href="javascript:void(0)" data-id="<?= $row['id'] ?>"><i class="fa fa-eye"></i> View</a>
                                <a class="btn btn-sm btn-flat btn-primary edit_data" href="javascript:void(0)" data-id="<?= $row['id'] ?>"><i class="fa fa-edit"></i> Edit</a>
                                <?php if($_settings->userdata('type') == 1): ?>
                                <a class="btn btn-sm btn-flat btn-danger delete_data" data-id="<?= $row['id'] ?>"><i class="fa fa-trash"></i> Delete</a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>

<script>
    $(function(){
        $('#add_log').click(function(){
            uni_modal("Add New Log","view_lead/manage_log.php?lid=<?= isset($id) ? $id : '' ?>")
        })
        $('.edit_data').click(function(){
            uni_modal("Update Log","view_lead/manage_log.php?lid=<?= isset($id) ? $id : '' ?>&id="+$(this).attr('data-id'))
        })
        $('.view_data').click(function(){
            uni_modal("View Log","view_lead/view_log.php?id="+$(this).attr('data-id'))
        })
        $('.delete_data').click(function(){
            _conf("Are you sure to delete this Call Log Information permanently?","delete_log",[$(this).attr('data-id')])
        })
    })
    function delete_log($id){
        start_loader();
        $.ajax({
            url:_base_url_+"classes/Master.php?f=delete_log",
            method:"POST",
            data:{id: $id},
            dataType:"json",
            error:err=>{
                console.log(err)
                alert_toast("An error occured.",'error');
                end_loader();
            },
            success:function(resp){
                if(typeof resp== 'object' && resp.status == 'success'){
                    location.reload();
                }else{
                    alert_toast("An error occured.",'error');
                    end_loader();
                }
            }
        })
    }
</script>
