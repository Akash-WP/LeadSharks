<?php
if (isset($_GET['id'])) {
    $qry = $conn->query("SELECT * FROM `lead_list` where id = '{$_GET['id']}'");
    if ($qry->num_rows > 0) {
        $res = $qry->fetch_array();
        foreach ($res as $k => $v) {
            if (!is_numeric($k))
                $$k = $v;
        }
    }
    if (isset($id)) {
        $client_qry = $conn->query("SELECT * FROM `client_list` where lead_id = '{$id}' ");
        if ($client_qry->num_rows > 0) {
            $res = $client_qry->fetch_array();
            unset($res['id']);
            unset($res['date_created']);
            unset($res['date_updated']);
            foreach ($res as $k => $v) {
                if (!is_numeric($k))
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
                <h5 class="card-title"><?= !isset($id) ? "Add New Lead" : "Update Lead's Information - " . $code ?></h5>
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
                                        <label for="company_name" class="control-label">Company Name <span style="color: red;">*</span></label>
                                        <input type="text" name="company_name" id="company_name" autofocus
                                            class="form-control form-control-sm form-control-border"
                                            value="<?php echo isset($company_name) ? $company_name : '' ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="company_type" class="control-label">Type <span style="color: red;">*</span></label>
                                        <select name="company_type" id="company_type"
                                            class="form-control form-control-sm form-control-border" required>
                                            <option value="" disabled <?= !isset($company_type) ? 'selected' : '' ?>>
                                                Select Type</option>
                                            <option value="Product-based" <?= isset($company_type) && $company_type == 'Product-based' ? 'selected' : '' ?>>Product-based
                                            </option>
                                            <option value="Service-based" <?= isset($company_type) && $company_type == 'Service-based' ? 'selected' : '' ?>>Service-based
                                            </option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="website" class="control-label">Website</label>
                                        <input type="url" name="website" id="website"
                                            class="form-control form-control-sm form-control-border"
                                            value="<?php echo isset($website) ? $website : '' ?>"
                                            placeholder="https://example.com">
                                    </div>
                                    <div class="form-group">
                                        <label for="address" class="control-label">Address <span style="color: red;">*</span></label>
                                        <textarea name="address" rows="3" id="address"
                                            class="form-control form-control-sm rounded-0"
                                            required><?php echo isset($address) ? $address : '' ?></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label for="country" class="control-label">Country <span style="color: red;">*</span></label>
                                        <select name="country" id="country"
                                            class="form-control form-control-sm rounded-0" required>
                                            <?php if (isset($country)): ?>
                                                <option selected><?= $country ?></option>
                                            <?php else: ?>
                                                <option value="" disabled selected>Select Country</option>
                                            <?php endif; ?>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label for="state" class="control-label">State <span style="color: red;">*</span></label>
                                        <select name="state" id="state" class="form-control form-control-sm rounded-0"
                                            required>
                                            <?php if (isset($state)): ?>
                                                <option selected><?= $state ?></option>
                                            <?php else: ?>
                                                <option value="" disabled selected>Select State</option>
                                            <?php endif; ?>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label for="city" class="control-label">City <span style="color: red;">*</span></label>
                                        <select name="city" id="city" class="form-control form-control-sm rounded-0"
                                            required style="display: none;"></select>
                                        <input type="text" name="city" id="cityInput"
                                            class="form-control form-control-sm rounded-0"
                                            placeholder="Enter city manually" style="display: none;">
                                        <?php if (isset($city)): ?>
                                            <script>
                                                $(document).ready(function () {
                                                    $('#city').append(`<option selected><?= $city ?></option>`).show();
                                                });
                                            </script>
                                        <?php endif; ?>
                                    </div>

                                    <div class="form-group">
                                        <label for="pincode" class="control-label">Pin Code</label>
                                        <input type="text" name="pincode" id="pincode"
                                            class="form-control form-control-sm rounded-0"
                                            value="<?= isset($pincode) ? $pincode : '' ?>">
                                    </div>

                                    <div class="form-group">
                                        <label for="other_info" class="control-label">Other Information</label>
                                        <textarea name="other_info" rows="3" id="other_info"
                                            class="form-control form-control-sm rounded-0"><?php echo isset($other_info) ? $other_info : '' ?></textarea>
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
                                        <label for="interested_in" class="control-label">Interested In <span style="color: red;">*</span></label>
                                        <input type="text" name="interested_in" id="interested_in"
                                            class="form-control form-control-sm form-control-border"
                                            value="<?php echo isset($interested_in) ? $interested_in : '' ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="source_id" class="control-label">Lead Source <span style="color: red;">*</span></label>
                                        <select name="source_id" id="source_id"
                                            class="form-control form-control-sm form-control-border select2" required>
                                            <option value="" disabled <?= !isset($source_id) ? 'selected' : '' ?>>
                                            </option>
                                            <?php
                                            $source = $conn->query("SELECT * FROM `source_list` where delete_flag = 0 and `status` = 1 " . (isset($source_id) ? " or id = '{$source_id}'" : "") . " order by `name` asc ");
                                            while ($row = $source->fetch_assoc()):
                                                ?>
                                                <option value="<?= $row['id'] ?>" <?= isset($source_id) && $source_id == $row['id'] ? 'selected' : '' ?>><?= $row['name'] ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="remarks" class="control-label">Remarks <span style="color: red;">*</span></label>
                                        <textarea name="remarks" rows="3" id="remarks"
                                            class="form-control form-control-sm rounded-0"
                                            required><?php echo isset($remarks) ? $remarks : '' ?></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label for="assigned_to" class="control-label">Assigned to <span style="color: red;">*</span></label>
                                        <select name="assigned_to" id="assigned_to"
                                            class="form-control form-control-sm form-control-border select2">
                                            <option value="" disabled <?= !isset($assigned_to) ? 'selected' : '' ?>>
                                            </option>
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
                                            } // Admins (type 1) see everyone
                                            
                                            $query = "SELECT *, CONCAT(lastname, ', ', firstname, ' ', COALESCE(middlename,'')) as fullname FROM `users`";
                                            if ($filter)
                                                $query .= " $filter";
                                            $query .= " ORDER BY fullname ASC";

                                            $user = $conn->query($query);
                                            $first_manager_id = null;

                                            while ($row = $user->fetch_assoc()):
                                                if ($current_user_type == 2 && $first_manager_id === null) {
                                                    $first_manager_id = $row['id'];
                                                }
                                                $isSelected = (isset($assigned_to) && $assigned_to == $row['id']) ||
                                                    (!isset($assigned_to) && $current_user_type == 2 && $row['id'] == $first_manager_id);
                                                ?>
                                                <option value="<?= $row['id'] ?>" <?= $isSelected ? 'selected' : '' ?>>
                                                    <?= $row['fullname'] ?>
                                                    <?php
                                                    if ($row['type'] == 1)
                                                        echo "(Admin)";
                                                    elseif ($row['type'] == 2)
                                                        echo "(Executive)";
                                                    elseif ($row['type'] == 3)
                                                        echo "(Manager)";
                                                    else
                                                        echo "(Staff)";
                                                    ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>

                                    </div>

                                    <div class="form-group">
                                        <label for="status" class="control-label">Status <span style="color: red;">*</span></label>
                                        <select name="status" id="status"
                                            class="form-control form-control-sm form-control-border select2" required>
                                            <option value="0" <?= isset($status) && $status == 0 ? 'selected' : '' ?>>Lead
                                                – Uncontacted</option>
                                            <option value="1" <?= isset($status) && $status == 1 ? 'selected' : '' ?>>
                                                Prospect – Contact Made</option>
                                            <option value="2" <?= isset($status) && $status == 2 ? 'selected' : '' ?>>
                                                Qualified – Need Validated</option>
                                            <option value="3" <?= isset($status) && $status == 3 ? 'selected' : '' ?>>
                                                Solution Fit / Discovery</option>
                                            <option value="4" <?= isset($status) && $status == 4 ? 'selected' : '' ?>>
                                                Proposal / Value Proposition</option>
                                            <option value="5" <?= isset($status) && $status == 5 ? 'selected' : '' ?>>
                                                Negotiation</option>
                                            <option value="6" <?= isset($status) && $status == 6 ? 'selected' : '' ?>>
                                                Closed – Won</option>
                                            <option value="7" <?= isset($status) && $status == 7 ? 'selected' : '' ?>>
                                                Closed – Lost</option>
                                            <!-- Removed: <option value="8">Inactive</option> -->
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label d-block">SignalHire <span style="color: red;">*</span></label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="signal_hire" id="signal_hire" value="1"
                                                <?= isset($signal_hire) && $signal_hire == 1 ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="signal_hire">Is SignalHire used?</label>
                                        </div>
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
                                        // Fetch default contact (you need to mark one in DB or determine logic)
//$default_contact_id = 0;
                                        $contacts_all = $conn->query("SELECT id, lead_id, name, contact, email, designation, is_lead_contact FROM contact_persons WHERE lead_id = '{$lead_id}'");
                                        $default_contact_id = 0;
                                        $contacts_all_arr = [];
                                        while ($row = $contacts_all->fetch_assoc()) {
                                            if ($row['is_lead_contact'] == 1) {
                                                $default_contact_id = $row['id'];
                                            }
                                            $contacts_all_arr[] = $row;
                                        }

                                        //$contacts = $conn->query("SELECT id, lead_id, name, contact, email, designation FROM contact_persons WHERE lead_id = '{$lead_id}'");
                                        foreach ($contacts_all_arr as $row):
                                            ?>
                                            <div class="row">
                                                <input type="hidden" name="contact_person_id[]" value="<?= $row['id'] ?>">
                                                <div class="form-group col-md-3">
                                                    <label>Contact Person Name <span style="color: red;">*</span></label>
                                                    <input type="text" name="contact_person_name[]"
                                                        value="<?= htmlspecialchars($row['name']) ?>"
                                                        class="form-control form-control-sm" required>
                                                </div>
                                                <div class="form-group col-md-2">
                                                    <label>Contact Number <span style="color: red;">*</span></label>
                                                    <input type="text" name="contact_person_contact[]"
                                                        value="<?= htmlspecialchars($row['contact']) ?>"
                                                        class="form-control form-control-sm" required>
                                                </div>
                                                <div class="form-group col-md-3">
                                                    <label>Email</label>
                                                    <input type="email" name="contact_person_email[]"
                                                        value="<?= htmlspecialchars($row['email']) ?>"
                                                        class="form-control form-control-sm">
                                                </div>
                                                <div class="form-group col-md-2">
                                                    <label>Designation</label>
                                                    <input type="text" name="contact_person_designation[]"
                                                        value="<?= htmlspecialchars($row['designation']) ?>"
                                                        class="form-control form-control-sm">
                                                </div>
                                                <div class="form-group col-md-2 d-flex align-items-center">
                                                    <div class="form-check mt-2">
                                                        <input class="form-check-input lead-contact-checkbox"
                                                            type="checkbox" name="lead_contact[]" value="<?= $row['id'] ?>"
                                                            <?= ($row['id'] == $default_contact_id ? 'checked' : '') ?>>
                                                        <label class="form-check-label">
                                                            Lead Contact
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>

                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-primary" onclick="addContactPerson()">+ Add
                                    Contact Person</button>
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
    $(function () {
        $('.select2').select2({
            placeholder: 'Please Select Here',
            width: '100%'
        })
        $('#lead-form').submit(function (e) {
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
                url: _base_url_ + "classes/Master.php?f=save_lead",
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
                        location.href = "./?page=leads";
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

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    // Populate Country Dropdown
    $.get("https://countriesnow.space/api/v0.1/countries", function (data) {
        if (data && data.data) {
            data.data.forEach(country => {
                $('#country').append(`<option value="${country.country}">${country.country}</option>`);
            });
        }
    });

    // Populate States based on Country
    $('#country').on('change', function () {
        let country = $(this).val();
        $('#state').empty().append(`<option value="">Select State</option>`);
        $('#city').empty().hide();
        $('#cityInput').hide();

        $.ajax({
            url: "https://countriesnow.space/api/v0.1/countries/states",
            method: "POST",
            contentType: "application/json",
            data: JSON.stringify({ country: country }),
            success: function (response) {
                if (response.data && response.data.states) {
                    response.data.states.forEach(state => {
                        $('#state').append(`<option value="${state.name}">${state.name}</option>`);
                    });
                }
            }
        });
    });

    // Populate Cities based on State
    $('#state').on('change', function () {
        let country = $('#country').val();
        let state = $(this).val();
        $('#city').empty();

        $.ajax({
            url: "https://countriesnow.space/api/v0.1/countries/state/cities",
            method: "POST",
            contentType: "application/json",
            data: JSON.stringify({ country: country, state: state }),
            success: function (response) {
                if (response.data && response.data.length > 0) {
                    $('#city').show().append(`<option value="">Select City</option>`);
                    response.data.forEach(city => {
                        $('#city').append(`<option value="${city}">${city}</option>`);
                    });
                    $('#cityInput').hide().prop("disabled", true);
                } else {
                    $('#city').hide().prop("disabled", true);
                    $('#cityInput').show().prop("disabled", false);
                }
            }
        });
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.lead-contact-checkbox').forEach(function (checkbox) {
            checkbox.addEventListener('change', function () {
                if (this.checked) {
                    document.querySelectorAll('.lead-contact-checkbox').forEach(function (cb) {
                        if (cb !== checkbox) cb.checked = false;
                    });
                }
            });
        });
    });
</script>