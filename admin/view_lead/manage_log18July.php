<?php
require_once('../../config.php');
$lead_id = isset($_GET['lid']) ? $_GET['lid'] : '';
$status = ''; // default empty

// Fetch current lead status from lead_list
if (!empty($lead_id)) {
    $lead_qry = $conn->query("SELECT status FROM lead_list WHERE id = '{$lead_id}' LIMIT 1");
    if ($lead_qry && $lead_qry->num_rows > 0) {
        $lead_row = $lead_qry->fetch_assoc();
        $status = $lead_row['status'];
    }
}

if (isset($_GET['id'])) {
    $qry = $conn->query("SELECT * FROM `log_list` where id = '{$_GET['id']}'");
    if ($qry->num_rows > 0) {
        $res = $qry->fetch_array();
        foreach ($res as $k => $v) {
            if (!is_numeric($k))
                $$k = $v;
        }
    }
}
?>
<div class="container-fluid">
    <form action="" id="log-form">
        <input type="hidden" name="id" value="<?php echo isset($id) ? $id : '' ?>">
        <input type="hidden" name="lead_id" value="<?php echo isset($lead_id) ? $lead_id : '' ?>">
        <div class="form-group">
            <label for="log_type" class="control-label">Log Type</label>
            <select name="log_type" id="log_type" class="form-control form-control-sm form-control-border" required>
                <option value="1" <?= isset($log_type) && $log_type == 1 ? 'selected' : '' ?>>Outbound</option>
                <option value="2" <?= isset($log_type) && $log_type == 2 ? 'selected' : '' ?>>Inbound</option>
            </select>
        </div>
        <div class="form-group">
            <label for="call_outcome" class="control-label">Call Outcome</label>
            <select name="call_outcome" id="call_outcome" class="form-control form-control-sm form-control-border"
                required>
                <option value="1" <?= isset($call_outcome) && $call_outcome == 1 ? 'selected' : '' ?>>Answered</option>
                <option value="2" <?= isset($call_outcome) && $call_outcome == 2 ? 'selected' : '' ?>>Not Answered</option>
                <option value="3" <?= isset($call_outcome) && $call_outcome == 3 ? 'selected' : '' ?>>Invalid Number
                </option>
                <option value="4" <?= isset($call_outcome) && $call_outcome == 4 ? 'selected' : '' ?>>Not Interested
                </option>
            </select>
        </div>
        <div class="form-group">
            <label for="remarks" class="control-label">Remarks</label>
            <textarea rows="4" name="remarks" id="remarks" class="form-control form-control-sm rounded-0"
                placeholder="Write Remark here" required><?php echo isset($remarks) ? $remarks : '' ?></textarea>
        </div>
        <div class="form-group">
            <label for="follow_up_date" class="control-label">Follow-Up Call Date</label>
            <input type="datetime-local" name="follow_up_date" id="follow_up_date" class="form-control"
                value="<?= isset($follow_up_date) ? date('Y-m-d\TH:i', strtotime($follow_up_date)) : '' ?>">
        </div>
        <?php
        $selected_status = isset($_POST['lead_status']) ? $_POST['lead_status'] : (isset($status) ? $status : '');
        ?>
        <div class="form-group">
            <label for="lead_status" class="control-label">Update Lead Status</label>
            <select name="lead_status" id="lead_status" class="form-control form-control-sm form-control-border">
                <option value="">-- Select Status --</option>
                <option value="0" <?= $selected_status == 0 ? 'selected' : '' ?>>Lead – Uncontacted</option>
                <option value="1" <?= $selected_status == 1 ? 'selected' : '' ?>>Prospect – Contact Made</option>
                <option value="2" <?= $selected_status == 2 ? 'selected' : '' ?>>Qualified – Need Validated</option>
                <option value="3" <?= $selected_status == 3 ? 'selected' : '' ?>>Solution Fit / Discovery</option>
                <option value="4" <?= $selected_status == 4 ? 'selected' : '' ?>>Proposal / Value Proposition</option>
                <option value="5" <?= $selected_status == 5 ? 'selected' : '' ?>>Negotiation</option>
                <option value="6" <?= $selected_status == 6 ? 'selected' : '' ?>>Closed – Won</option>
                <option value="7" <?= $selected_status == 7 ? 'selected' : '' ?>>Closed – Lost</option>
            </select>
        </div>
    </form>
</div>
<script>
    $(function () {
        $('#uni_modal').on('shown.bs.modal', function () {
            $('.select2').select2({
                placeholder: 'Please select here',
                width: '100%',
                dropdownParent: $('#uni_modal')
            })
        })
        $('#uni_modal #log-form').submit(function (e) {
            e.preventDefault();
            var _this = $(this)
            if (_this[0].checkValidity() == false) {
                _this[0].reportValidity();
                return false;
            }
            $('.pop-msg').remove()
            var el = $('<div>')
            el.addClass("pop-msg alert")
            el.hide()
            start_loader();
            $.ajax({
                url: _base_url_ + "classes/Master.php?f=save_log",
                data: new FormData($(this)[0]),
                cache: false,
                contentType: false,
                processData: false,
                method: 'POST',
                type: 'POST',
                dataType: 'json',
                error: err => {
                    console.log(err)
                    alert_toast("An error occured", 'error');
                    end_loader();
                },
                success: function (resp) {
                    if (resp.status == 'success') {
                       
                          alert_toast(resp.msg, 'success');
                           location.reload();
                        // ✅ Enable NEXT button
                            const nextBtn = document.getElementById('next-btn');
                            if (nextBtn) {
                                nextBtn.classList.remove('disabled');
                                nextBtn.removeAttribute('aria-disabled');
                                nextBtn.removeAttribute('disabled');
                            }

                        // ✅ Add checkmark to UI if desired (optional)
                        const leadIcon = document.querySelector(`#lead-status-icon-${<?= $lead_id ?>}`);
                        if (leadIcon) {
                            leadIcon.classList.remove('text-danger', 'fa-times');
                            leadIcon.classList.add('text-success', 'fa-check');
                        }
                    $('#uni_modal').modal('hide');
                    } else if (!!resp.msg) {
                        el.addClass("alert-danger")
                        el.text(resp.msg)
                        _this.prepend(el)
                    } else {
                        el.addClass("alert-danger")
                        el.text("An error occurred due to unknown reason.")
                        _this.prepend(el)
                    }
                    el.show('slow')
                    $('html,body,.modal').animate({ scrollTop: 0 }, 'fast')
                    end_loader();
                }
            })
        })
    })
</script>