<?php
require_once('./../../config.php');
if(isset($_GET['id'])){
    $qry = $conn->query("SELECT * FROM `note_list` where id = '{$_GET['id']}'");
    if($qry->num_rows > 0){
        $res = $qry->fetch_array();
        foreach($res as $k => $v){
            if(!is_numeric($k))
            $$k = $v;
        }
    }
}
$lead_id = isset($lead_id) ? $lead_id : '';
$users = $conn->query("SELECT id,CONCAT(lastname,', ', firstname, '', COALESCE(middlename,'')) as fullname FROM `users` where id in (SELECT `user_id` FROM `note_list` where lead_id = '{$lead_id}')");
$user_arr = array_column($users->fetch_all(MYSQLI_ASSOC),'fullname','id');
?>
<style>
    #uni_modal .modal-footer{
        display:none;
    }
</style>
<div class="container-fluid">
    <div class="row">
            <dl>
                <dt class="text-muted">Note</dt>
                <dd class='pl-4 fs-4 fw-bold'><?= isset($note) ? $note : 'N/A' ?></dd>
                <dt class="text-muted">Created By</dt>
                <dd class='pl-4 fs-4 fw-bold'><?= isset($user_arr[$user_id]) ? $user_arr[$user_id] : 'N/A' ?></dd>
                <dt class="text-muted">Date Created</dt>
                <dd class='pl-4 fs-4 fw-bold'><?= isset($date_created) ? date("M d, Y", strtotime($date_created)) : 'N/A' ?></dd>
            </dl>
    </div>
    <div class="text-right">
        <button class="btn btn-dark btn-sm btn-flat" type="button" data-dismiss="modal"><i class="fa fa-close"></i> Close</button>
    </div>
</div>
