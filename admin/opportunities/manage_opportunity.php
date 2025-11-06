<?php
if(isset($_GET['id'])){
    $qry = $conn->query("SELECT * FROM `lead_list` where id = '{$_GET['id']}'");
    if($qry->num_rows > 0){
        $res = $qry->fetch_array();
        foreach($res as $k => $v){
            if(!is_numeric($k))
            $$k = $v;
        }
    }
    if(isset($id)){
    $client_qry = $conn->query("SELECT * FROM `client_list` where lead_id = '{$id}' ");
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
}
?>
<div class="content py-3">
    <div class="card card-outline card-navy shadow rounded-0">
        <div class="card-header">
            <div class="card-title">
                <h5 class="card-title"><?= !isset($id) ? "Add New Lead" : "Update Lead's Information - ".$code ?></h5>
            </div>
        </div>
        <div class="card-body">
            <div class="container-fluid">
                <form action="" id="lead-form">
    <input type="hidden" name="id" value="<?php echo isset($id) ? $id : '' ?>">
    <div class="row">
        <!-- Company Information -->
        <div class="col-lg-6 col-md-6 col-sm-12">
            <fieldset>
                <legend class="text-muted h4">Company Information</legend>
                <div class="callout rounded-0 shadow">
                    <div class="form-group">
                        <label for="company_name" class="control-label">Company Name</label>
                        <input type="text" name="company_name" id="company_name" autofocus class="form-control form-control-sm form-control-border" value ="<?php echo isset($company_name) ? $company_name : '' ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="company_type" class="control-label">Type</label>
                        <select name="company_type" id="company_type" class="form-control form-control-sm form-control-border" required>
                            <option value="" disabled <?= !isset($company_type) ? 'selected' : '' ?>>Select Type</option>
                            <option value="Product-based" <?= isset($company_type) && $company_type == 'Product-based' ? 'selected' : '' ?>>Product-based</option>
                            <option value="Service-based" <?= isset($company_type) && $company_type == 'Service-based' ? 'selected' : '' ?>>Service-based</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="website" class="control-label">Website (Optional)</label>
                        <input type="url" name="website" id="website" class="form-control form-control-sm form-control-border" value ="<?php echo isset($website) ? $website : '' ?>" placeholder="https://example.com">
                    </div>
                    <div class="form-group">
                        <label for="address" class="control-label">Address</label>
                        <textarea name="address" rows="3" id="address" class="form-control form-control-sm rounded-0" required><?php echo isset($address) ? $address : '' ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="other_info" class="control-label">Other Information</label>
                        <textarea name="other_info" rows="3" id="other_info" class="form-control form-control-sm rounded-0"><?php echo isset($other_info) ? $other_info : '' ?></textarea>
                    </div>
                </div>
            </fieldset>
        </div>

        <!-- Lead Information -->
        <div class="col-lg-6 col-md-6 col-sm-12">
            <fieldset>
                <legend class="text-muted h4">Lead's Information</legend>
                <div class="callout rounded-0 shadow">
                    <div class="form-group">
                        <label for="interested_in" class="control-label">Interested In</label>
                        <input type="text" name="interested_in" id="interested_in" class="form-control form-control-sm form-control-border" value ="<?php echo isset($interested_in) ? $interested_in : '' ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="source_id" class="control-label">Lead Source</label>
                        <select name="source_id" id="source_id" class="form-control form-control-sm form-control-border select2" required>
                            <option value="" disabled <?= !isset($source_id) ? 'selected' : '' ?>></option>
                            <?php 
                            $source = $conn->query("SELECT * FROM `source_list` where delete_flag = 0 and `status` = 1 ".(isset($source_id)? " or id = '{$source_id}'" : "")." order by `name` asc ");
                            while($row = $source->fetch_assoc()):
                            ?>
                            <option value="<?= $row['id'] ?>" <?= isset($source_id) && $source_id == $row['id'] ? 'selected' : '' ?>><?= $row['name'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="remarks" class="control-label">Remarks</label>
                        <textarea name="remarks" rows="3" id="remarks" class="form-control form-control-sm rounded-0" required><?php echo isset($remarks) ? $remarks : '' ?></textarea>
                    </div>
                    <div class="form-group">
    <label for="assigned_to" class="control-label">Assigned to</label>
    <select name="assigned_to" id="assigned_to" class="form-control form-control-sm form-control-border select2">
        <option value="" disabled <?= !isset($assigned_to) ? 'selected' : '' ?>></option>
        <option value="" <?= isset($assigned_to) && $assigned_to == null ? 'selected' : '' ?>>Unset</option>
        <?php 
        $current_user_type = $_settings->userdata('type');
        $filter = "";

        // Restrict based on logged-in user type
        if ($current_user_type == 2) {
            // Executive → assign only to Managers
            $filter = "WHERE type = 3";
        } elseif ($current_user_type == 3) {
            // Manager → assign only to Executives
            $filter = "WHERE type = 2";
        } // Admins (type 1) see everyone (no filter)

        $query = "SELECT *, CONCAT(lastname, ', ', firstname, ' ', COALESCE(middlename,'')) as fullname FROM `users`";
        if ($filter) $query .= " $filter";
        $query .= " ORDER BY fullname ASC";

        $user = $conn->query($query);
        while($row = $user->fetch_assoc()):
        ?>
        <option value="<?= $row['id'] ?>" <?= isset($assigned_to) && $assigned_to == $row['id'] ? 'selected' : '' ?>>
            <?= $row['fullname'] ?> 
            <?php 
                if ($row['type'] == 1) echo "(Admin)";
                elseif ($row['type'] == 2) echo "(Executive)";
                elseif ($row['type'] == 3) echo "(Manager)";
                else echo "(Staff)";
            ?>
        </option>
        <?php endwhile; ?>
    </select>
</div>
                    <div class="form-group">
                        <label for="status" class="control-label">Status</label>
                        <select name="status" id="status" class="form-control form-control-sm form-control-border select2" required>
                            <option value="0" <?= isset($status) && $status == 0 ? 'selected' : '' ?>>New/Prospect</option>
                            <option value="1" <?= isset($status) && $status == 1 ? 'selected' : '' ?>>Open</option>
                            <option value="2" <?= isset($status) && $status == 2 ? 'selected' : '' ?>>Working</option>
                            <option value="3" <?= isset($status) && $status == 3 ? 'selected' : '' ?>>Not a Target</option>
                            <option value="4" <?= isset($status) && $status == 4 ? 'selected' : '' ?>>Disqualified</option>
                            <option value="5" <?= isset($status) && $status == 5 ? 'selected' : '' ?>>Nurture</option>
                            <?php if(isset($status) && $status == 6): ?>
                                <option value="6" selected>Opportunity Created</option>
                            <?php endif; ?>
                            <option value="7" <?= isset($status) && $status == 7 ? 'selected' : '' ?>>Opportunity Lost</option>
                            <option value="8" <?= isset($status) && $status == 8 ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                </div>
            </fieldset>
        </div>

        <!-- Contact Person Information -->
        <div class="col-12 mt-4">
            <fieldset>
                <legend class="text-muted h4">Contact Person(s)</legend>
                <div class="callout rounded-0 shadow" id="contact-person-container">
                    <div class="contact-person-entry border p-3 mb-3 bg-light">
                        <?php
$lead_id = isset($_GET['id']) ? $_GET['id'] : 0;
$contacts = $conn->query("SELECT id, lead_id, name, contact, email, designation FROM contact_persons WHERE lead_id = '{$lead_id}'");
while($row = $contacts->fetch_assoc()):
?>
<div class="row">
    <input type="hidden" name="contact_person_id[]" value="<?= $row['id'] ?>">
    <div class="form-group col-md-3">
        <label>Contact Person Name</label>
        <input type="text" name="contact_person_name[]" value="<?= htmlspecialchars($row['name']) ?>" class="form-control form-control-sm" required>
    </div>
    <div class="form-group col-md-3">
        <label>Contact Number</label>
        <input type="text" name="contact_person_contact[]" value="<?= htmlspecialchars($row['contact']) ?>" class="form-control form-control-sm" required>
    </div>
    <div class="form-group col-md-3">
        <label>Email</label>
        <input type="email" name="contact_person_email[]" value="<?= htmlspecialchars($row['email']) ?>" class="form-control form-control-sm">
    </div>
    <div class="form-group col-md-3">
        <label>Designation</label>
        <input type="text" name="contact_person_designation[]" value="<?= htmlspecialchars($row['designation']) ?>" class="form-control form-control-sm">
    </div>
</div>
<?php endwhile; ?>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-primary" onclick="addContactPerson()">+ Add Contact Person</button>
            </fieldset>
        </div>
    </div>
</form>
            </div>
        </div>
        <div class="card-footer py-2 text-right">
                <button class="btn btn-primary btn-flat" type="submit" form="lead-form">Save Lead Information</button>
                <a class="btn btn-light border btn-flat" href="./?page=leads" form="lead-form">Cancel</a>
        </div>
    </div>
</div>
<script>
    $(function(){
        $('.select2').select2({
            placeholder:'Please Select Here',
            width:'100%'
        })
        $('#lead-form').submit(function(e){
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
                url:_base_url_+"classes/Master.php?f=save_lead",
				data: new FormData($(this)[0]),
                cache: false,
                contentType: false,
                processData: false,
                method: 'POST',
                type: 'POST',
                dataType: 'json',
				error:err=>{
					console.log(err)
					alert_toast("An error occured",'error');
					end_loader();
				},
                success:function(resp){
                    if(resp.status == 'success'){
                        location.href = "./?page=opportunities";
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
<script>
function addContactPerson() {
    const container = document.getElementById('contact-person-container');
    const template = `
        <div class="contact-person-entry border p-3 mb-3 bg-light">
            <div class="row">
                <div class="form-group col-md-3">
                    <label>Contact Person Name</label>
                    <input type="text" name="contact_person_name[]" class="form-control form-control-sm" required>
                </div>
                <div class="form-group col-md-3">
                    <label>Contact Number</label>
                    <input type="text" name="contact_person_contact[]" class="form-control form-control-sm" required>
                </div>
                <div class="form-group col-md-3">
                    <label>Email</label>
                    <input type="email" name="contact_person_email[]" class="form-control form-control-sm">
                </div>
                <div class="form-group col-md-3">
                    <label>Designation</label>
                    <input type="text" name="contact_person_designation[]" class="form-control form-control-sm">
                </div>
            </div>
        </div>`;
    container.insertAdjacentHTML('beforeend', template);
}
</script>
