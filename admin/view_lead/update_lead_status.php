<?php
require_once('../../config.php');
if(isset($_GET['id'])){
    $qry = $conn->query("SELECT * FROM `lead_list` where id = '{$_GET['id']}'");
    if($qry->num_rows > 0){
        $res = $qry->fetch_array();
        foreach($res as $k => $v){
            if(!is_numeric($k))
            $$k = $v;
        }
    }
}
?>

<div class="container-fluid">
    <form action="" id="lead-form">
        <input type="hidden" name="id" value="<?php echo isset($id) ? $id : '' ?>">
        <input type="hidden" name="in_opportunity" value="<?php echo isset($in_opportunity) ? $in_opportunity : '' ?>">
        <div class="form-group">
            <label for="status" class="control-label">Lead Status</label>
            <select name="status" id="status" class="form-control form-control-sm form-control-border select2" required>
                <option value="0" <?= isset($status) && $status == 0 ? 'selected' : '' ?>>Lead - Uncontacted</option>
                <option value="1" <?= isset($status) && $status == 1 ? 'selected' : '' ?>>Prospect - Contact Made</option>
                <option value="2" <?= isset($status) && $status == 2 ? 'selected' : '' ?>>Qualified - Need Validated</option>
                <option value="3" <?= isset($status) && $status == 3 ? 'selected' : '' ?>>Solution Fit / Discovery</option>
                <option value="4" <?= isset($status) && $status == 4 ? 'selected' : '' ?>>Proposal / Value Proposition</option>
                <option value="5" <?= isset($status) && $status == 5 ? 'selected' : '' ?>>Negotiation</option>
                <option value="6" <?= isset($status) && $status == 6 ? 'selected' : '' ?>>Closed - Won</option>
                <option value="7" <?= isset($status) && $status == 7 ? 'selected' : '' ?>>Closed - Lost</option>
            </select>
        </div>
    </form>
</div>

<script>
    $(function(){
        $('#uni_modal').on('shown.bs.modal',function(){
            $('.select2').select2({
                placeholder:'Please select here',
                width:'100%',
                dropdownParent: $('#uni_modal')
            })
        })
        $('#uni_modal #lead-form').submit(function(e){
            e.preventDefault();
            var _this = $(this)
            if(_this[0].checkValidity() == false){
                _this[0].reportValidity();
                return false;
            }
            $('.pop-msg').remove()
            var el = $('<div>')
                el.addClass("pop-msg alert")
                el.hide()
            start_loader();
            $.ajax({
                url:_base_url_+"classes/Master.php?f=update_lead_status",
				data: new FormData($(this)[0]),
                cache: false,
                contentType: false,
                processData: false,
                method: 'POST',
                type: 'POST',
                dataType: 'json',
				error:err=>{
					console.lead(err)
					alert_toast("An error occured",'error');
					end_loader();
				},
                success:function(resp){
                    if(resp.status == 'success'){
                        location.reload();
                    }else if(!!resp.msg){
                        el.addClass("alert-danger")
                        el.text(resp.msg)
                        _this.prepend(el)
                    }else{
                        el.addClass("alert-danger")
                        el.text("An error occurred due to unknown reason.")
                        _this.prepend(el)
                    }
                    el.show('slow')
                    $('html,body,.modal').animate({scrollTop:0},'fast')
                    end_loader();
                }
            })
        })
    })
</script>