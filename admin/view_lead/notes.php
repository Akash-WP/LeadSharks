<div class="container-fluid">
    <div class="text-right">
        <button class="btn btn-primary btn-flat btn-sm" id="add_note"><i class="fa fa-plus"></i> Add New Note</button>
    </div>
    <hr>
    <div class="list-group" id="lead-list">
        <?php 
        $users = $conn->query("SELECT id,CONCAT(lastname,', ', firstname, '', COALESCE(middlename,'')) as fullname FROM `users` where id in (SELECT `user_id` FROM `note_list` where lead_id = '{$id}')");
        $user_arr = array_column($users->fetch_all(MYSQLI_ASSOC),'fullname','id');
        $notes = $conn->query("SELECT * FROM `note_list` where lead_id = '{$id}' order by unix_timestamp(date_created) asc ");
        while($row = $notes->fetch_assoc()):
        ?>
        <div class="list-group-item list-group-item-action list-item rounded-0">
            <div class="d-flex">
                <div class="col-auto text-muted pl-3">Note:</div>
                <div class="col-auto flex-grow-1 flex-shrink-1"><p class="m-0 truncate-1"><b><?= $row['note'] ?></b></p></div>
            </div>
            <div class="clear-fix my-2"></div>
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
        $('#add_note').click(function(){
            uni_modal("Add New Note","view_lead/manage_note.php?lid=<?= isset($id) ? $id : '' ?>")
        })
        $('.edit_data').click(function(){
            uni_modal("Update Note","view_lead/manage_note.php?lid=<?= isset($id) ? $id : '' ?>&id="+$(this).attr('data-id'))
        })
        $('.view_data').click(function(){
            uni_modal("View Note","view_lead/view_note.php?id="+$(this).attr('data-id'))
        })
        $('.delete_data').click(function(){
			_conf("Are you sure to delete this Note Information permanently?","delete_note",[$(this).attr('data-id')])
		})
    })
    function delete_note($id){
		start_loader();
		$.ajax({
			url:_base_url_+"classes/Master.php?f=delete_note",
			method:"POST",
			data:{id: $id},
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