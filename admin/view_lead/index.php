<?php
if(isset($_GET['id'])){
   // $qry = $conn->query("SELECT l.*,s.name as `source` FROM `lead_list` l inner join source_list s on l.source_id = s.id where l.id = '{$_GET['id']}'");
   $qry = $conn->query("SELECT l.* FROM `lead_list` l WHERE l.id = '{$_GET['id']}'");
    if($qry->num_rows > 0){
        $res = $qry->fetch_array();
        foreach($res as $k => $v){
            if(!is_numeric($k))
            $$k = $v;
        }
    }
    if(isset($id)){
		$uwhere = "";
if($_settings->userdata('type') != 1) {
    $uwhere = " and assigned_to = '{$_settings->userdata('id')}' ";
}
    $client_qry = $conn->query("SELECT l.*, c.company_name as client, c.website FROM `lead_list` l 
	INNER JOIN client_list c ON c.lead_id = l.id 
	WHERE l.in_opportunity = 0 {$uwhere} 
	ORDER BY l.`status` ASC, UNIX_TIMESTAMP(l.date_created) ASC");
    if($client_qry->num_rows > 0){
        $res = $client_qry->fetch_array();
        unset($res['id']);
        unset($res['date_created']);
        unset($res['date_updated']);
        foreach($res as $k => $v){
            if(!is_numeric($k))
            $$k = $v;
        }
    }
    }
	$assigned_to = isset($assigned_to) ? $assigned_to : null;
	$user_id = isset($user_id) ? $user_id : null;
	$users = $conn->query("SELECT id,CONCAT(lastname,', ', firstname,' ', COALESCE(middlename,'')) as fullname FROM `users` where id in ('$assigned_to','$user_id')");
	$user_arr = array_column($users->fetch_all(MYSQLI_ASSOC),'fullname','id');
}
$view = isset($_GET['view']) ? $_GET['view'] : 'info';
?>
<style>
	.list-group-item-action.active{
		color:#fff !important
	}
</style>
<div class="content py-3">
	<div class="card card-outline card-navy">
		<div class="card-header">
			<h4 class="card-title">Lead Ref. Code - <?= isset($code) ? $code : '' ?></h4>
			<!-- <div class="card-tools">
				<?php if(isset($in_opportunity) && $in_opportunity != 1): ?>
					<button class="btn btn-success btn-flat btn-sm" id="move_to_opportunity"><i class="fa fa-move"></i> Move to Opportunity</button>
				<?php endif; ?>
			</div> -->
		</div>
		<div class="card-body">
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <ul class="nav nav-tabs">
          <li class="nav-item">
            <a href="./?page=view_lead&id=<?= isset($id) ? $id : '' ?>" 
               class="nav-link <?= $view == 'info' ? 'active' : '' ?>">
              <i class="nav-icon fa fa-info-circle"></i> Information
            </a>
          </li>
          <li class="nav-item">
            <a href="./?page=view_lead&view=logs&id=<?= isset($id) ? $id : '' ?>" 
               class="nav-link <?= $view == 'logs' ? 'active' : '' ?>">
              <i class="nav-icon fa fa-phone"></i> Call Logs
            </a>
          </li>
          <li class="nav-item">
            <a href="./?page=view_lead&view=notes&id=<?= isset($id) ? $id : '' ?>" 
               class="nav-link <?= $view == 'notes' ? 'active' : '' ?>">
              <i class="nav-icon fa fa-sticky-note"></i> Note
            </a>
          </li>
        </ul>
      </div>
      <div class="col-12 mt-3">
        <?php include $view.'.php' ?>
      </div>
    </div>
  </div>
</div>

	</div>
</div>
<script>
	$(function(){
		$('#move_to_opportunity').click(function(){
			_conf("Are you sure to create opportunity for this lead?","update_status",[])
		})
	})
	function update_status(){
		start_loader();
		$.ajax({
			url:_base_url_+"classes/Master.php?f=update_lead_status",
			method:"POST",
			data:{id: '<?= isset($id) ? $id : '' ?>',in_opportunity: '<?= isset($in_opportunity) ? $in_opportunity : '' ?>', status: 6},
			dataType:"json",
			error:err=>{
				console.note(err)
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