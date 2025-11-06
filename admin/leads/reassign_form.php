<?php
require_once('../../config.php');
// $lead_id = isset($_GET['id']) ? $_GET['id'] : '';
$lead_ids = isset($_GET['ids']) ? $_GET['ids'] : ''; // comma-separated string

// Explode to array for internal use
$lead_ids_arr = array_filter(array_map('intval', explode(',', $lead_ids)));

if (empty($lead_ids_arr)) {
    echo "No lead selected";
    exit;
}
$current_assigned_to = '';
$assigned_users = [];

// if (count($lead_ids_arr) === 1) {
//     $id = $lead_ids_arr[0];
//     $qry = $conn->query("SELECT assigned_to FROM lead_list WHERE id = '{$id}'");
//     if ($qry->num_rows > 0) {
//         $row = $qry->fetch_assoc();
//         $current_assigned_to = $row['assigned_to'];
//     }
// }


if (!empty($lead_ids_arr)) {
    $ids_str = implode(",", $lead_ids_arr);
    $res = $conn->query("SELECT DISTINCT assigned_to FROM lead_list WHERE id IN ($ids_str)");

    while ($row = $res->fetch_assoc()) {
        $assigned_users[] = $row['assigned_to'];
    }

    // If only one unique user is assigned to all leads
    if (count($assigned_users) === 1) {
        $current_assigned_to = $assigned_users[0];
    }
}

// Fetch all users (optionally filtered)
$users = $conn->query("SELECT id, CONCAT(lastname,', ', firstname, ' ', COALESCE(middlename,'')) as fullname FROM users ORDER BY lastname, firstname");
?>
<div class="container-fluid">
    <form id="reassign-form">
        <!-- <input type="hidden" name="lead_id" value="<?= htmlspecialchars($lead_id) ?>"> -->
        <input type="hidden" id="lead_ids" name="lead_ids" value="<?= htmlspecialchars($lead_ids) ?>">
        <input type="hidden" id="current_assigned_to" name="current_assigned_to" value="<?= $current_assigned_to ?>">


        <div class="form-group">
            <label for="assigned_to" class="control-label">Assign To</label>
            <select name="assigned_to" id="assigned_to" class="form-control form-control-sm form-control-border select2"
                required>
                <option value="" disabled <?= $current_assigned_to == '' ? 'selected' : '' ?>>-- Select User --</option>
                <?php while ($user = $users->fetch_assoc()): ?>
                    <option value="<?= $user['id'] ?>" <?= $current_assigned_to == $user['id'] ? 'selected' : '' ?>>
                        <?= ucwords($user['fullname']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="calling_date" class="control-label">Calling Date</label>
            <input type="datetime-local" name="calling_date" id="calling_date"
                class="form-control form-control-sm form-control-border" required>
        </div>
    </form>
</div>

<script>
    $(function () {
        $('#uni_modal').on('shown.bs.modal', function () {
            $('.select2').select2({
                placeholder: 'Select user',
                width: '100%',
                dropdownParent: $('#uni_modal')
            });
        });

        $('#uni_modal #reassign-form').submit(function (e) {
            e.preventDefault();
            var _this = $(this);
            if (_this[0].checkValidity() === false) {
                _this[0].reportValidity();
                return false;
            }

            const selectedUser = $('#assigned_to').val();
            const currentUser = $('#current_assigned_to').val();
            const leadIds = $('input[name="lead_ids"]').val();

            // Debug logs (optional)
            console.log("Selected:", selectedUser, "| Current:", currentUser, "| Leads:", leadIds);

            // ✅ Alert if trying to assign to same user (single or multiple leads)
            if (selectedUser === currentUser && currentUser !== '' && leadIds) {
                alert("These lead(s) are already assigned to the selected user.");
                return false;
            }

            $('.pop-msg').remove();
            var el = $('<div>').addClass("pop-msg alert").hide();
            start_loader();
            $.ajax({
                url: _base_url_ + "classes/Master.php?f=reassign_lead",
                data: new FormData(_this[0]),
                cache: false,
                contentType: false,
                processData: false,
                method: 'POST',
                dataType: 'json',
                error: err => {
                    console.log(err);
                    alert_toast("An error occurred", 'error');
                    end_loader();
                },
                success: function (resp) {
                    if (resp.status == 'success') {
                        location.reload();
                    } else if (resp.msg) {
                        el.addClass("alert-danger").text(resp.msg);
                        _this.prepend(el);
                    } else {
                        el.addClass("alert-danger").text("An error occurred due to unknown reason.");
                        _this.prepend(el);
                    }
                    el.show('slow');
                    $('html,body,.modal').animate({ scrollTop: 0 }, 'fast');
                    end_loader();
                }
            });
        });
    });
</script>