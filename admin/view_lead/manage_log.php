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
<style>
    /* Basic hidden class for dynamic sections */
    .hidden {
        display: none !important;
    }
    .required-label::after {
        content: "*";
        color: red;
        margin-left: 0.25rem;
    }
</style>
<div class="container-fluid">
    <form action="" id="log-form">
        <input type="hidden" name="id" value="<?php echo isset($id) ? $id : '' ?>">
        <input type="hidden" name="lead_id" value="<?php echo isset($lead_id) ? $lead_id : '' ?>">

        <div class="form-group">
            <label for="log_type" class="control-label required-label">Log Type</label>
            <select name="log_type" id="log_type" class="form-control form-control-sm form-control-border" required>
                <option value="1" <?= isset($log_type) && $log_type == 1 ? 'selected' : '' ?>>Outbound</option>
                <option value="2" <?= isset($log_type) && $log_type == 2 ? 'selected' : '' ?>>Inbound</option>
            </select>
        </div>

        <div class="form-group">
            <label for="call_outcome" class="control-label required-label">Call Outcome</label>
            <select name="call_outcome" id="call_outcome" class="form-control form-control-sm form-control-border" required>
                <option value="">Select an outcome</option>
                <option value="1" <?= isset($call_outcome) && $call_outcome == 1 ? 'selected' : '' ?>>Answered</option>
                <option value="2" <?= isset($call_outcome) && $call_outcome == 2 ? 'selected' : '' ?>>Not Answered</option>
            </select>
        </div>

        <div id="answeredSection" class="hidden">
            <div class="form-group">
                <label for="conversationOutcome" class="control-label required-label">Conversation Outcome</label>
                <select name="conversation_outcome" id="conversationOutcome" class="form-control form-control-sm form-control-border">
                    <option value="">Select an option</option>
                    <option value="1" <?= isset($conversation_outcome) && $conversation_outcome == 1 ? 'selected' : '' ?>>Interested</option>
                    <option value="2" <?= isset($conversation_outcome) && $conversation_outcome == 2 ? 'selected' : '' ?>>Call Back Later</option>
                    <option value="3" <?= isset($conversation_outcome) && $conversation_outcome == 3 ? 'selected' : '' ?>>Busy – Try Again</option>
                    <option value="4" <?= isset($conversation_outcome) && $conversation_outcome == 4 ? 'selected' : '' ?>>Needs More Info</option>
                    <option value="5" <?= isset($conversation_outcome) && $conversation_outcome == 5 ? 'selected' : '' ?>>Not Interested</option>
                    <option value="6" <?= isset($conversation_outcome) && $conversation_outcome == 6 ? 'selected' : '' ?>>Already Purchased</option>
                    <option value="7" <?= isset($conversation_outcome) && $conversation_outcome == 7 ? 'selected' : '' ?>>Wrong Contact</option>
                </select>
            </div>

            <!-- <div id="interestedFields" class="hidden">
                <div class="form-group">
                    <label for="expectedClosureDate" class="control-label">Expected Closure Date</label>
                    <input type="date" name="expected_closure_date" id="expectedClosureDate" class="form-control" value="<?= isset($expected_closure_date) ? date('Y-m-d', strtotime($expected_closure_date)) : '' ?>">
                </div>
                <div class="form-group">
                    <label for="alternateContactNumber" class="control-label">Alternate Contact Number</label>
                    <input type="text" name="alternate_contact_number" id="alternateContactNumber" class="form-control" value="<?= isset($alternate_contact_number) ? $alternate_contact_number : '' ?>">
                </div>
            </div> -->

            <div id="notInterestedReasonFields" class="hidden">
                <div class="form-group">
                    <label for="reason" class="control-label">Reason (optional)</label>
                    <select name="reason_not_interested" id="reason" class="form-control form-control-sm form-control-border">
                        <option value="">Select reason</option>
                        <option value="1" <?= isset($reason_not_interested) && $reason_not_interested == 1 ? 'selected' : '' ?>>Purchased from Competitor</option>
                        <option value="2" <?= isset($reason_not_interested) && $reason_not_interested == 2 ? 'selected' : '' ?>>Purchased Directly from Us</option>
                        <option value="3" <?= isset($reason_not_interested) && $reason_not_interested == 3 ? 'selected' : '' ?>>Purchased via Partner</option>
                        <option value="4" <?= isset($reason_not_interested) && $reason_not_interested == 4 ? 'selected' : '' ?>>No Longer Needed</option>
                    </select>
                </div>
            </div>

            <div id="wrongContactFields" class="hidden">
                <div class="form-group">
    <label class="control-label">&nbsp;</label><br />
    <button type="button" id="updateWrongContactBtn" class="btn btn-outline-primary btn-sm">
        Update Contact Info
    </button>
</div>
            </div>
        </div>

        <div id="notAnsweredSection" class="hidden">
            <div class="form-group">
                <label for="attemptResult" class="control-label required-label">Attempt Result</label>
                <select name="attempt_result" id="attemptResult" class="form-control form-control-sm form-control-border">
                    <option value="">Select</option>
                    <option value="1" <?= isset($attempt_result) && $attempt_result == 1 ? 'selected' : '' ?>>Ringing, No Answer</option>
                    <option value="2" <?= isset($attempt_result) && $attempt_result == 2 ? 'selected' : '' ?>>Switched Off</option>
                    <option value="3" <?= isset($attempt_result) && $attempt_result == 3 ? 'selected' : '' ?>>Busy</option>
                    <option value="4" <?= isset($attempt_result) && $attempt_result == 4 ? 'selected' : '' ?>>Call Dropped</option>
                    <option value="5" <?= isset($attempt_result) && $attempt_result == 5 ? 'selected' : '' ?>>Invalid Number</option>
                </select>
            </div>

            <div id="invalidNumberFields" class="hidden">
                <div class="form-group">
    <label class="control-label">&nbsp;</label><br />
    <button type="button" id="updateInvalidContactBtn" class="btn btn-outline-primary btn-sm">
        Update Contact Info
    </button>
</div>

            </div>
        </div>

        <div class="form-group" id="followUpDateSection">
            <label for="follow_up_date" class="control-label required-label">Follow-Up Call Date</label>
            <input type="datetime-local" name="follow_up_date" id="follow_up_date" class="form-control" value="<?= isset($follow_up_date) ? date('Y-m-d\TH:i', strtotime($follow_up_date)) : '' ?>">
        </div>

        <div class="form-group">
            <label for="remarks" class="control-label required-label">Remarks</label>
            <textarea rows="4" name="remarks" id="remarks" class="form-control form-control-sm rounded-0"
                placeholder="Write Remark here" required><?php echo isset($remarks) ? $remarks : '' ?></textarea>
        </div>
        
        <?php
        $selected_status = isset($_POST['lead_status']) ? $_POST['lead_status'] : (isset($status) ? $status : '');
        $status_labels = [
            '0' => 'Lead – Uncontacted',
            '1' => 'Prospect – Contact Made',
            '2' => 'Qualified – Need Validated',
            '3' => 'Solution Fit / Discovery',
            '4' => 'Proposal / Value Proposition',
            '5' => 'Negotiation',
            '6' => 'Closed – Won',
            '7' => 'Closed – Lost'
        ];
        $current_status_label = isset($status_labels[(string)$selected_status]) ? $status_labels[(string)$selected_status] : 'Not Set';
        ?>

        <div class="form-group" id="mainLeadStatusSection" class="hidden">
            <label for="lead_status" class="control-label">Update Lead Status</label>
            <p class="mb-1" style="font-size: 12px;color:green;">
        <i class="fas fa-info-circle"></i>
        <strong>Current Status:</strong> <?= $current_status_label ?>
    </p>

    <div class="alert alert-warning py-2 px-3 mb-2" style="font-size: 12px;">
        <i class="fas fa-exclamation-triangle mr-1"></i>
        <strong>Important:</strong> Please select a lead status before saving.  
        Even if you dont want to change it, re-select the current status.
        <br>
        <small style="color:red">This ensures your update is properly recorded.</small>
    </div>
            <select name="lead_status" id="lead_status" class="form-control form-control-sm form-control-border" required>
                <option value="">-- Select Status --</option>
                <option value="0" <?= $status == 0 ? 'selected' : '' ?>>Lead – Uncontacted</option>
                <option value="1" <?= $status == 1 ? 'selected' : '' ?>>Prospect – Contact Made</option>
                <option value="2" <?= $status == 2 ? 'selected' : '' ?>>Qualified – Need Validated</option>
                <option value="3" <?= $status == 3 ? 'selected' : '' ?>>Solution Fit / Discovery</option>
                <option value="4" <?= $status == 4 ? 'selected' : '' ?>>Proposal / Value Proposition</option>
                <option value="5" <?= $status == 5 ? 'selected' : '' ?>>Negotiation</option>
                <option value="6" <?= $status == 6 ? 'selected' : '' ?>>Closed – Won</option>
                <option value="7" <?= $status == 7 ? 'selected' : '' ?>>Closed – Lost</option>
            </select>
        </div>
    </form>
</div>
<div class="modal fade" id="contactModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="updateContactsForm">
        <div class="modal-header">
          <h5 class="modal-title">Update Contact Info</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body" id="contactPersonContainer">
          <!-- Contact inputs loaded here dynamically -->
        </div>
        <div class="modal-footer">
          <input type="hidden" name="lead_id" id="contactLeadId" />
          <button type="submit" class="btn btn-success">Update</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script>
    $('#updateWrongContactBtn, #updateInvalidContactBtn').on('click', function () {
  const leadId = $('input[name="lead_id"]').val(); // from form hidden input
  if (leadId) {
    openContactModal(leadId); // defined in next step
  } else {
    alert("Lead ID not found.");
  }
});
function openContactModal(leadId) {
  $('#contactLeadId').val(leadId);
  $.ajax({
    url: _base_url_ + "classes/Master.php?f=get_contact_persons",
    method: 'POST',
    data: { lead_id: leadId },
    dataType: 'json',
    success: function (contacts) {
      const container = $('#contactPersonContainer');
      container.empty();

      contacts.forEach((person, index) => {
        container.append(`
          <div class="contact-group border p-3 rounded mb-3">
            <h6>Contact Person ${index + 1}</h6>
            <input type="hidden" name="contacts[${index}][id]" value="${person.id}">
            <input class="form-control mb-2" name="contacts[${index}][name]" value="${person.name}" placeholder="Name">
            <input class="form-control mb-2" name="contacts[${index}][contact]" value="${person.contact}" placeholder="Contact Number">
            <input class="form-control mb-2" name="contacts[${index}][email]" value="${person.email}" placeholder="Email">
            <input class="form-control mb-2" name="contacts[${index}][designation]" value="${person.designation}" placeholder="Designation">
          </div>
        `);
      });

      $('#contactModal').modal('show');
    },
    error: function () {
      alert("Failed to fetch contact persons.");
    }
  });
}
$('#updateContactsForm').on('submit', function (e) {
  e.preventDefault();
  $.ajax({
    url: _base_url_ + "classes/Master.php?f=update_contact_persons",
    method: 'POST',
    data: $(this).serialize(),
    success: function (resp) {
      if (resp === 'success') {
        alert('Contact info updated!');
        $('#contactModal').modal('hide');
        
      } else {
        // alert('Update failed.');
      }
    },
    error: function () {
      alert("Something went wrong.");
    }
  });
});
</script>

<script>
    $(function () {
        // Cache DOM elements for better performance
        const callOutcomeSelect = $('#call_outcome');
        const conversationOutcomeSelect = $('#conversationOutcome');
        const attemptResultSelect = $('#attemptResult');
        const answeredSection = $('#answeredSection');
        const notAnsweredSection = $('#notAnsweredSection');
        const interestedFields = $('#interestedFields');
        const notInterestedReasonFields = $('#notInterestedReasonFields');
        const wrongContactFields = $('#wrongContactFields');
        const invalidNumberFields = $('#invalidNumberFields');
        const followUpDateSection = $('#followUpDateSection');
        const mainLeadStatusSection = $('#mainLeadStatusSection'); // The main lead status section container
        const leadStatusSelect = $('#lead_status'); // The main lead status dropdown

        // Function to reset all dynamic sections to hidden
        function hideAllDynamicSections() {
            answeredSection.addClass('hidden');
            notAnsweredSection.addClass('hidden');
            interestedFields.addClass('hidden');
            notInterestedReasonFields.addClass('hidden');
            wrongContactFields.addClass('hidden');
            invalidNumberFields.addClass('hidden');
            followUpDateSection.addClass('hidden'); // Initially hide follow-up
            mainLeadStatusSection.addClass('hidden'); // Hide the main lead status by default
            leadStatusSelect.val(''); // Clear selected value when hidden
        }

        // Function to update visibility based on Call Outcome
        function updateCallOutcomeVisibility() {
            hideAllDynamicSections(); // Reset all before showing
            const callOutcomeVal = callOutcomeSelect.val();

            if (callOutcomeVal === '1') { // Answered
                answeredSection.removeClass('hidden');
                // Trigger conversation outcome logic to set initial visibility for its children
                updateConversationOutcomeVisibility();
                // Show main lead status when answered
                mainLeadStatusSection.removeClass('hidden');
            } else if (callOutcomeVal === '2') { // Not Answered
                notAnsweredSection.removeClass('hidden');
                // Trigger attempt result logic to set initial visibility for its children
                updateAttemptResultVisibility();
                // Show main lead status when not answered
                mainLeadStatusSection.removeClass('hidden');
            }
        }

        // Function to update visibility based on Conversation Outcome
        function updateConversationOutcomeVisibility() {
            interestedFields.addClass('hidden');
            notInterestedReasonFields.addClass('hidden');
            wrongContactFields.addClass('hidden');

            const conversationOutcomeVal = conversationOutcomeSelect.val();

            if (conversationOutcomeVal === '1') { // Interested
                interestedFields.removeClass('hidden');
            } else if (conversationOutcomeVal === '6') { // Already Purchased (maps to Not Interested reason)
                notInterestedReasonFields.removeClass('hidden');
                leadStatusSelect.val('7'); // Automatically set main lead status to Closed – Lost
            } else if (conversationOutcomeVal === '7') { // Wrong Contact
                wrongContactFields.removeClass('hidden');
            }

            // Always call the general follow-up date visibility updater
            updateFollowUpDateVisibility();
        }

        // Function to update visibility based on Attempt Result
        function updateAttemptResultVisibility() {
            invalidNumberFields.addClass('hidden');

            const attemptResultVal = attemptResultSelect.val();

            if (attemptResultVal === '5') { // Invalid Number
                invalidNumberFields.removeClass('hidden');
                leadStatusSelect.val('7'); // Automatically set main lead status to Closed – Lost
            }

            // Always call the general follow-up date visibility updater
            updateFollowUpDateVisibility();
        }

        // Function to manage Follow-up Date visibility based on all relevant selections
        function updateFollowUpDateVisibility() {
            const callOutcomeVal = callOutcomeSelect.val();
            const conversationOutcomeVal = conversationOutcomeSelect.val();
            const attemptResultVal = attemptResultSelect.val();

            let showFollowUp = false;

            if (callOutcomeVal === '1') { // Answered
                if (['1', '2', '3', '4'].includes(conversationOutcomeVal)) { // Interested, Call Back Later, Busy – Try Again, Needs More Info
                    showFollowUp = true;
                }
            } else if (callOutcomeVal === '2') { // Not Answered
                 if (attemptResultVal !== '' && attemptResultVal !== '5') { // Any attempt result except 'Invalid Number'
                     showFollowUp = true;
                 }
            }

            if (showFollowUp) {
                followUpDateSection.removeClass('hidden');
               // $('#follow_up_date').prop('required', true); // Make required when visible
            } else {
                followUpDateSection.addClass('hidden');
                $('#follow_up_date').val(''); // Clear the date if hidden
               // $('#follow_up_date').prop('required', false); // Remove required when hidden
            }
        }

        // Event Listeners
        callOutcomeSelect.on('change', updateCallOutcomeVisibility);
        conversationOutcomeSelect.on('change', updateConversationOutcomeVisibility);
        attemptResultSelect.on('change', updateAttemptResultVisibility);

        // Initial setup on modal shown or page load
        $('#uni_modal').on('shown.bs.modal', function () {
            $('.select2').select2({
                placeholder: 'Please select here',
                width: '100%',
                dropdownParent: $('#uni_modal')
            });
            // Trigger initial visibility states based on pre-selected values
            updateCallOutcomeVisibility();
        });

        // Form Submission Logic (mostly unchanged, but consider dynamic fields)
        $('#uni_modal #log-form').submit(function (e) {
            e.preventDefault();
            var _this = $(this);
            if (_this[0].checkValidity() == false) {
                _this[0].reportValidity();
                return false;
            }
            $('.pop-msg').remove();
            var el = $('<div>');
            el.addClass("pop-msg alert");
            el.hide();
            start_loader();

            const dataToSend = new FormData(_this[0]);

            $.ajax({
                url: _base_url_ + "classes/Master.php?f=save_log",
                data: dataToSend,
                cache: false,
                contentType: false,
                processData: false,
                method: 'POST',
                type: 'POST',
                dataType: 'json',
                error: err => {
                    console.log(err);
                    alert_toast("An error occurred", 'error');
                    end_loader();
                },
                success: function (resp) {
                    if (resp.status == 'success') {
                        alert_toast(resp.msg, 'success');
                        location.reload(); // This will refresh the parent page/modal

                        // Enabling NEXT button and adding checkmark (if elements exist in parent page)
                        const nextBtn = document.getElementById('next-btn');
                        if (nextBtn) {
                            nextBtn.classList.remove('disabled');
                            nextBtn.removeAttribute('aria-disabled');
                            nextBtn.removeAttribute('disabled');
                        }

                        const leadIcon = document.querySelector(`#lead-status-icon-<?php echo $lead_id; ?>`);
                        if (leadIcon) {
                            leadIcon.classList.remove('text-danger', 'fa-times');
                            leadIcon.classList.add('text-success', 'fa-check');
                        }
                        $('#uni_modal').modal('hide');
                    } else if (!!resp.msg) {
                        el.addClass("alert-danger");
                        el.text(resp.msg);
                        _this.prepend(el);
                    } else {
                        el.addClass("alert-danger");
                        el.text("An error occurred due to unknown reason.");
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