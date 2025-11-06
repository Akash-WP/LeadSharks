<!doctype html>
<html lang="en">
<!-- this is the ui modification file -->

<head>
  <!-- Required meta tags -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Data Tables</title>
  <!-- Bootstrap CSS -->
  <link rel="stylesheet" href="../assets/vendor/bootstrap/css/bootstrap.min.css">
  <link href="../assets/vendor/fonts/circular-std/style.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/libs/css/style.css">
  <link rel="stylesheet" href="../assets/vendor/fonts/fontawesome/css/fontawesome-all.css">
  <link rel="stylesheet" type="text/css" href="../assets/vendor/datatables/css/dataTables.bootstrap4.css">
  <link rel="stylesheet" type="text/css" href="../assets/vendor/datatables/css/buttons.bootstrap4.css">
  <link rel="stylesheet" type="text/css" href="../assets/vendor/datatables/css/select.bootstrap4.css">
  <link rel="stylesheet" type="text/css" href="../assets/vendor/datatables/css/fixedHeader.bootstrap4.css">
</head>

<style>
  .smooth-toggle {
    transition: opacity 0.3s ease, visibility 0.3s ease;
    opacity: 1;
    visibility: visible;
  }

  .smooth-toggle.hidden {
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
  }

  .five-per-row {
    width: 20%;
    padding: 0.75rem;
    /* same as Bootstrap's mb-4 */
  }

  @media (max-width: 1200px) {
    .five-per-row {
      width: 50%;
    }
  }

  @media (max-width: 768px) {
    .five-per-row {
      width: 100%;
    }
  }
</style>

<body class="layout-fixed control-sidebar-slide-open layout-navbar-fixed">
   <div class="wrapper">
    <?php require_once('inc/topBarNav.php'); ?>
  <div class="dashboard-wrapper">
     
    <div class="container-fluid" style="margin-left: -250px;">
      <?php
      $exec_id = $conn->real_escape_string($_settings->userdata('id'));
      ?>

      <?php if (in_array($_settings->userdata('type'), [2])): ?>
        <div class="row">
          <?php
          // Only run if user type is 1 or 3 (e.g. admins or managers)
          if (in_array($_settings->userdata('type'), [2])) {
            // $exec_id = $conn->real_escape_string($_settings->userdata('id'));

            // Initialize counts
            $assigned_leads = 0;
            $assigned_opps = 0;
            $todays_followups = 0;
            $upcoming_followups = 0;
            $overdue_followups = 0;
            $total_leads = 0;
            $converted_opps = 0;
            $completion_rate = 0;
            $todays_updated_leads = 0;

            $total_leads = 0;
            $type = $_settings->userdata('type');
            $today = date('Y-m-d');

            if ($type == 1) {
              // Admin: Show all leads
              $result = $conn->query("SELECT COUNT(*) as count FROM `lead_list` WHERE delete_flag = 0");
            } elseif ($type == 3) {
              // Manager: Show leads assigned to self or team
              $team_ids = [$exec_id]; // Later replace with actual team IDs
              $team_ids_str = implode(',', array_map('intval', $team_ids));
              $result = $conn->query("SELECT COUNT(*) as count FROM `lead_list` WHERE assigned_to IN ($team_ids_str) AND delete_flag = 0");
            } else {
              // Default fallback (executives)
              $result = $conn->query("SELECT COUNT(*) as count FROM `lead_list` WHERE assigned_to = '{$exec_id}' AND delete_flag = 0");
            }
            if ($result) {
              $row = $result->fetch_assoc();
              $total_leads = $row['count'];
            }

            // Today's follow-ups
            if ($type == 1) {
              $query = "SELECT COUNT(*) as count FROM client_list WHERE DATE(follow_up_date) = CURDATE()";
            } else {
              // $query = "SELECT COUNT(*) as count FROM client_list c INNER JOIN lead_list l ON c.lead_id = l.id WHERE l.assigned_to = '{$exec_id}' AND DATE(c.follow_up_date) = CURDATE()";
              $query = "
  SELECT l.follow_up_date, cl.company_name 
  FROM log_list l
  INNER JOIN (
    SELECT lead_id, MAX(id) AS latest_id
    FROM log_list
    WHERE follow_up_date IS NOT NULL
    GROUP BY lead_id
  ) latest_log 
    ON latest_log.lead_id = l.lead_id AND latest_log.latest_id = l.id
  LEFT JOIN lead_list le ON le.id = l.lead_id
  LEFT JOIN client_list cl ON cl.lead_id = le.id
  WHERE DATE(l.follow_up_date) = '$today' AND le.assigned_to = $exec_id
  ORDER BY l.follow_up_date ASC
";
              $result = $conn->query($query);
              $todays_followups = ($result) ? $result->num_rows : 0;
            }
            $result = $conn->query($query);
            $todays_followups = ($result) ? $result->num_rows : 0;

            // Upcoming follow-ups (7 days)
            if ($type == 1) {
              $query = "SELECT COUNT(*) as count FROM client_list WHERE follow_up_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
            } else {
              // $query = "SELECT COUNT(*) as count FROM client_list c INNER JOIN lead_list l ON c.lead_id = l.id WHERE l.assigned_to = '{$exec_id}' AND c.follow_up_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
              $query = "
  SELECT l.follow_up_date, cl.company_name 
  FROM log_list l
  INNER JOIN (
    SELECT lead_id, MAX(id) AS latest_id
    FROM log_list
    WHERE follow_up_date IS NOT NULL
    GROUP BY lead_id
  ) latest_log 
    ON latest_log.lead_id = l.lead_id AND latest_log.latest_id = l.id
  LEFT JOIN lead_list le ON le.id = l.lead_id
  LEFT JOIN client_list cl ON cl.lead_id = le.id
  WHERE DATE(l.follow_up_date) > '$today' AND le.assigned_to = $exec_id
  ORDER BY l.follow_up_date ASC
";

              $result = $conn->query($query);
              $upcoming_followups = ($result) ? $result->num_rows : 0;
            }
            $result = $conn->query($query);
            $upcoming_followups = ($result) ? $result->num_rows : 0;

            // Overdue follow-ups (before today)
            if ($type == 1) {
              $query = "SELECT COUNT(*) as count FROM client_list WHERE DATE(follow_up_date) < CURDATE()";
            } else {
              $query = "SELECT COUNT(*) as count FROM client_list c INNER JOIN lead_list l ON c.lead_id = l.id WHERE l.assigned_to = '{$exec_id}' AND DATE(c.follow_up_date) < CURDATE()";
            }
            $result = $conn->query($query);
            $overdue_followups = ($result) ? $result->fetch_assoc()['count'] : 0;

            // Completion rate = converted opportunities / total leads * 100
            $completion_rate = ($total_leads > 0) ? round(($converted_opps / $total_leads) * 100, 2) : 0;

            // todays updated
            if ($type == 1) {
              // Admin - get all updated leads today
              $query = "
    SELECT COUNT(DISTINCT l.lead_id) AS count
    FROM log_list l
    INNER JOIN lead_list le ON le.id = l.lead_id
    WHERE DATE(l.date_created) = CURDATE()
  ";
            } else {
              // Executive-specific
              $query = "
    SELECT COUNT(DISTINCT l.lead_id) AS count
    FROM log_list l
    INNER JOIN lead_list le ON le.id = l.lead_id
    WHERE DATE(l.date_created) = CURDATE()
      AND le.assigned_to = $exec_id
  ";
            }
            $result = $conn->query($query);
            $todays_updated_leads = ($result) ? $result->fetch_assoc()['count'] : 0;

            // Cards data to display

            $cards = [
              // ["Assigned Leads", $assigned_leads, "primary", "fa-users"],
              // ["Opportunities", $assigned_opps, "success", "fa-bullseye"],
["Total Leads", $total_leads, "secondary", "fa-database", "all"],
                ["Todays Updated Leads", $todays_updated_leads, "success", "fa-exclamation-circle", "todays_updated"],
                ["Today's Follow-ups", $todays_followups, "info", "fa-calendar-day", "today"],
                ["Upcoming Follow-ups", $upcoming_followups, "warning", "fa-calendar-plus", "upcomming"],
                ["Overdue Follow-ups", $overdue_followups, "danger", "fa-exclamation-circle", "overdue"],
              // ["Todays Updated Leads", $overdue_followups, "success", "fa-exclamation-circle"],
              // ["Converted Opportunities", $converted_opps, "success", "fa-check-circle"],
              // ["Follow-up Completion Rate", $completion_rate . "%", "primary", "fa-percentage"]
            ];



            foreach ($cards as [$title, $value, $color, $icon,$filter1]) {

              echo <<<HTML
    <a href="?filter1={$filter1}" class="five-per-row float-left" style="text-decoration: none;">
        <div class="card border-left-$color shadow h-100 py-2">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-xs font-weight-bold text-$color text-uppercase mb-1">$title</div>
                    <div class="icon text-$color"><i class="fas $icon fa-2x"></i></div>
                </div>
                <div class="h5 mb-0 font-weight-bold text-gray-800">$value</div>
            </div>
        </div>
    </a>
HTML;
            }
          } else {
            // echo '<div class="col-12"><div class="alert alert-warning">You do not have permission to view this data.</div></div>';
          }
          ?>
        </div>
      <?php endif; ?>
      
      <script>
          document.addEventListener("DOMContentLoaded", function() {
            const urlParams = new URLSearchParams(window.location.search);
            const filter = urlParams.get('filter1');

            if (filter) {
              // Show the modal
              $('#cardModal').modal('show');

              // Remove the query parameter without reloading the page
              urlParams.delete('filter1'); // remove filter1
              const newUrl = window.location.pathname + (urlParams.toString() ? '?' + urlParams.toString() : '');
              window.history.replaceState({}, '', newUrl);
            }
          });
        </script>

      <?php if (in_array($_settings->userdata('type'), [2])): ?>

        <div class="row">
          <!-- ============================================================== -->
          <!-- basic table  -->

          <div class="col-12">
            <div class="card">
              <h5 class="card-header"> Assigned Leads</h5>
              <div class="card-body">
                <div class="table-responsive">
                  <?php


                  $uwhere = "";
                  if ($_settings->userdata('type') != 1)
                    $uwhere = " and assigned_to = '{$_settings->userdata('id')}' ";

                  $users = $conn->query("SELECT id,CONCAT(lastname,', ', firstname, '', COALESCE(middlename,'')) as fullname FROM `users` where id in (SELECT `user_id` FROM `lead_list` where in_opportunity = 0 {$uwhere}) OR id in (SELECT assigned_to FROM `lead_list` where in_opportunity = 0 {$uwhere})");
                  $user_arr = array_column($users->fetch_all(MYSQLI_ASSOC), 'fullname', 'id');

                  $uid = $_settings->userdata('id');
                  $role = $_settings->userdata('type');

                  $filter = $_GET['filter'] ?? 'all'; // default is today

                  switch ($filter) {
                    case 'week':
                      $where = "YEARWEEK(c.calling_date, 1) = YEARWEEK(CURDATE(), 1)";
                      break;
                    case 'month':
                      $where = "MONTH(c.calling_date) = MONTH(CURDATE()) AND YEAR(c.calling_date) = YEAR(CURDATE())";
                      break;
                    case 'custom':
                      $start_date = $_GET['start_date'] ?? null;
                      $end_date = $_GET['end_date'] ?? null;

                      if ($start_date && $end_date) {
                        $where = "DATE(c.calling_date) BETWEEN '{$start_date}' AND '{$end_date}'";
                      } else {
                        // If dates are missing, fallback or return all
                        $where = "1=1"; // or show an error
                      }
                      break;
                    case 'all':
                      $where = "1=1"; // show all data
                      break;
                      case 'todays_update':
                      $where = "DATE(lg.date_created) = CURDATE()";
                      break;
                      case 'overdue':
                        // New case: follow-up date before today
                        $where = "DATE(c.follow_up_date) < CURDATE()";
                        break;
                    case 'today':
                    default:
                      $where = "DATE(c.calling_date) = CURDATE()";
                      break;
                  }

                  $where .= " AND l.delete_flag = 0";

                  if ($role == 2) { // Executive
                    $where .= " AND l.assigned_to = '$uid'";
                  } elseif ($role == 3) { // Manager
                    // Replace with logic to fetch team IDs
                    $where .= " AND l.assigned_to = '$uid'";
                  }

                  $user_filter = "";
                  $selected_user = $_GET['user_id'] ?? 'all';
                  // call outcome
                  $call_outcome = $_GET['call_outcome'] ?? 'all';
                  // conversation outcome
                  $conversation_outcome = $_GET['conversation_outcome'] ?? 'all';
                  // attempt result
                  $attempt_result = $_GET['attempt_result'] ?? 'all';
                  // status
                  $status = $_GET['status'] ?? 'all';


                  $conversation_filter = '';
                  if ($conversation_outcome !== 'all') {
                    $conversation_filter = " AND lg.conversation_outcome = " . intval($conversation_outcome);
                  }

                  $attempt_filter = '';
                  if ($attempt_result !== 'all') {
                    $attempt_filter = " AND lg.attempt_result = " . intval($attempt_result);
                  }

                  $status_filter = '';
                  if ($status !== 'all') {
                    $status_filter = " AND l.status = '" . $conn->real_escape_string($status) . "'";
                  }

                  if ($role == 1) {
                    // Admin: get all users involved in leads
                    $user_qry = $conn->query("SELECT id, CONCAT(firstname, ' ', lastname) as name FROM users WHERE id IN (SELECT assigned_to FROM lead_list WHERE delete_flag = 0)");
                  } elseif ($role == 3) {
                    // Manager: get executives under this manager
                    $user_qry = $conn->query("SELECT id, CONCAT(firstname, ' ', lastname) as name FROM users WHERE id IN (SELECT assigned_to FROM lead_list WHERE delete_flag = 0)");
                  }

                  if ($selected_user !== 'all' && is_numeric($selected_user)) {
                    $user_filter = " AND l.assigned_to = '{$selected_user}'";
                  }
                  $qry = $conn->query("SELECT 
                        l.*, 
                        c.company_name AS client, 
                        c.website, c.city, c.state, c.country, 
                        c.follow_up_date, c.calling_date, 
                        u.firstname, u.lastname,
                        lg.call_outcome,
                        lg.conversation_outcome
                        FROM `lead_list` l 
                        INNER JOIN client_list c ON c.lead_id = l.id
                        LEFT JOIN users u ON l.assigned_to = u.id
                        LEFT JOIN (
                        SELECT lg1.*
                        FROM log_list lg1
                        INNER JOIN (
                            SELECT lead_id, MAX(date_created) AS latest_log
                            FROM log_list
                            GROUP BY lead_id
                        ) latest ON lg1.lead_id = latest.lead_id AND lg1.date_created = latest.latest_log
                    ) lg ON lg.lead_id = l.id
                        WHERE $where {$uwhere} {$user_filter}"
                                                . ($call_outcome !== 'all' ? " AND lg.call_outcome = " . intval($call_outcome) : "")
                                                . $conversation_filter
                                                . $attempt_filter
                                                . $status_filter . "
                        GROUP BY l.id 
                        ORDER BY l.status ASC, UNIX_TIMESTAMP(l.date_created) ASC");


                  $sn = 1;

                  ?>
                  <form method="get" class="mb-3">
                    <input type="hidden" name="tab" value="new">
                    <input type="hidden" name="start_date" value="<?= htmlspecialchars($_GET['start_date'] ?? '') ?>">
                    <input type="hidden" name="end_date" value="<?= htmlspecialchars($_GET['end_date'] ?? '') ?>">

                    <div class="container-fluid">
                      <!-- Headings Row -->
                      <div class="row font-weight-bold text-dark mb-1">
                        <div class="col-md-2 smooth-toggle" id="label-date-range">Date Range</div>
                        <!-- <div class="col-md-2 smooth-toggle" id="label-executive">Sales Executive</div> -->
                        <div class="col-md-2 smooth-toggle" id="label-call-outcome">Call Outcome</div>
                        <div class="col-md-2 smooth-toggle" id="label-conversation">Conversation Result</div>
                        <div class="col-md-2 smooth-toggle" id="label-attempt">Call Attempt Result</div>
                        <div class="col-md-2 smooth-toggle" id="label-status">Lead Status</div>
                      </div>

                      <!-- Dropdowns Row -->
                      <div class="row">
                        <!-- Filter By -->
                        <div class="col-md-2 smooth-toggle">
                          <select name="filter" id="filter" class="form-control form-control-sm">
                          
                          <option value="all" <?= ($_GET['filter'] ?? '') == 'all' ? 'selected' : '' ?>>All</option>

                            <option value="today" <?= ($_GET['filter'] ?? '') == 'today' ? 'selected' : '' ?>>Today</option>
                            <option value="week" <?= ($_GET['filter'] ?? '') == 'week' ? 'selected' : '' ?>>This Week</option>
                            <option value="month" <?= ($_GET['filter'] ?? '') == 'month' ? 'selected' : '' ?>>This Month</option>
                            <option value="custom" <?= ($_GET['filter'] ?? '') == 'custom' ? 'selected' : '' ?>>Custom</option>
                            
                          <option value="todays_update" <?= ($_GET['filter'] ?? '') == 'todays_update' ? 'selected' : '' ?>>Todays Updated Leads</option>
                           <option value="overdue" <?= ($_GET['filter'] ?? '') == 'overdue' ? 'selected' : '' ?>>Overdue Leads</option>

                          </select>
                        </div>



                        <!-- User
                        <div class="col-md-2 smooth-toggle">
                          <?php if ($role == 1 || $role == 3): ?>
                            <select name="user_id" id="user_id" class="form-control form-control-sm" onchange="this.form.submit()">
                              <option value="all">All</option>
                              <?php while ($u = $user_qry->fetch_assoc()): ?>
                                <option value="<?= $u['id'] ?>" <?= ($selected_user == $u['id']) ? 'selected' : '' ?>>
                                  <?= htmlspecialchars($u['name']) ?>
                                </option>
                              <?php endwhile; ?>
                            </select>
                          <?php endif; ?>
                        </div> -->

                        <!-- Call Outcome -->
                        <div class="col-md-2 smooth-toggle">
                          <select name="call_outcome" id="call_outcome" class="form-control form-control-sm" onchange="this.form.submit()">
                            <option value="all">All</option>
                            <option value="1" <?= ($_GET['call_outcome'] ?? '') == '1' ? 'selected' : '' ?>>Answered</option>
                            <option value="2" <?= ($_GET['call_outcome'] ?? '') == '2' ? 'selected' : '' ?>>Not Answered</option>
                            <!-- <option value="3" <?= ($_GET['call_outcome'] ?? '') == '3' ? 'selected' : '' ?>>Invalid Number</option> -->
                            <!-- <option value="4" <?= ($_GET['call_outcome'] ?? '') == '4' ? 'selected' : '' ?>>Not Interested</option> -->
                          </select>
                        </div>

                        <!-- Conversation Outcome -->
                        <div class="col-md-2 smooth-toggle">
                          <select name="conversation_outcome" class="form-control form-control-sm" onchange="this.form.submit()">
                            <option value="all" <?= $conversation_outcome === 'all' ? 'selected' : '' ?>>All</option>
                            <option value="1" <?= $conversation_outcome == '1' ? 'selected' : '' ?>>Interested</option>
                            <option value="2" <?= $conversation_outcome == '2' ? 'selected' : '' ?>>Call Back Later</option>
                            <option value="3" <?= $conversation_outcome == '3' ? 'selected' : '' ?>>Busy – Try Again</option>
                            <option value="4" <?= $conversation_outcome == '4' ? 'selected' : '' ?>>Needs More Info</option>
                            <option value="5" <?= $conversation_outcome == '5' ? 'selected' : '' ?>>Not Interested</option>
                            <option value="6" <?= $conversation_outcome == '6' ? 'selected' : '' ?>>Already Purchased</option>
                            <option value="7" <?= $conversation_outcome == '7' ? 'selected' : '' ?>>Wrong Contact</option>
                          </select>

                        </div>

                        <!-- Attempt Result -->
                        <div class="col-md-2 smooth-toggle">
                          <select name="attempt_result" id="attempt_result" class="form-control form-control-sm form-control-border" onchange="this.form.submit()">
                            <option value="all" <?= ($attempt_result ?? 'all') === 'all' ? 'selected' : '' ?>>All</option>
                            <option value="1" <?= $attempt_result == 1 ? 'selected' : '' ?>>Ringing, No Answer</option>
                            <option value="2" <?= $attempt_result == 2 ? 'selected' : '' ?>>Switched Off</option>
                            <option value="3" <?= $attempt_result == 3 ? 'selected' : '' ?>>Busy</option>
                            <option value="4" <?= $attempt_result == 4 ? 'selected' : '' ?>>Call Dropped</option>
                            <option value="5" <?= $attempt_result == 5 ? 'selected' : '' ?>>Invalid Number</option>
                          </select>

                        </div>

                        <!-- status outcome -->
                        <!-- Status -->
                        <div class="col-md-2 ">
                          <select name="status" id="status" class="form-control form-control-sm form-control-border" onchange="this.form.submit()">
                            <option value="all" <?= ($status ?? 'all') === 'all' ? 'selected' : '' ?>>All</option>
                            <option value="0" <?= $status === '0' ? 'selected' : '' ?>>Lead Uncontacted</option>
                            <option value="1" <?= $status === '1' ? 'selected' : '' ?>>Prospect Contact Made</option>
                            <option value="2" <?= $status === '2' ? 'selected' : '' ?>>Qualified Need Validated</option>
                            <option value="3" <?= $status === '3' ? 'selected' : '' ?>>Solution Fit Discovery</option>
                            <option value="4" <?= $status === '4' ? 'selected' : '' ?>>Proposal Value Proposition</option>
                            <option value="5" <?= $status === '5' ? 'selected' : '' ?>>Negotiation</option>
                            <option value="6" <?= $status === '6' ? 'selected' : '' ?>>Closed Won</option>
                            <option value="7" <?= $status === '7' ? 'selected' : '' ?>>Closed Lost</option>
                          </select>
                        </div>



                      </div>
                    </div>
                  </form>
                  <script>
                    document.addEventListener("DOMContentLoaded", function() {
                      const callOutcomeSelect = document.getElementById("call_outcome");
                      const conversationSelect = document.querySelector('select[name="conversation_outcome"]');
                      const attemptSelect = document.getElementById("attempt_result");

                      // Grab label divs
                      const labelConversation = document.getElementById("label-conversation");
                      const labelAttempt = document.getElementById("label-attempt");

                      // Grab parent .col-md-2 wrappers
                      const conversationWrapper = conversationSelect.closest(".col-md-2");
                      const attemptWrapper = attemptSelect.closest(".col-md-2");

                      function toggleFields() {
                        const selectedValue = callOutcomeSelect.value;

                        if (selectedValue === "1") {
                          // Answered: Show Conversation, Hide Attempt
                          conversationWrapper.style.display = "block";
                          labelConversation.style.display = "block";

                          attemptWrapper.style.display = "none";
                          labelAttempt.style.display = "none";
                        } else if (selectedValue === "2") {
                          // Not Answered: Show Attempt, Hide Conversation
                          conversationWrapper.style.display = "none";
                          labelConversation.style.display = "none";

                          attemptWrapper.style.display = "block";
                          labelAttempt.style.display = "block";
                        } else {
                          // All: Show both
                          conversationWrapper.style.display = "block";
                          labelConversation.style.display = "block";

                          attemptWrapper.style.display = "block";
                          labelAttempt.style.display = "block";
                        }
                      }

                      toggleFields(); // Run on page load
                      callOutcomeSelect.addEventListener("change", toggleFields);
                    });
                  </script>



                  <div class="modal fade" id="customDateModal" tabindex="-1" role="dialog" aria-labelledby="customDateModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                      <form method="get" action="?" class="modal-content">
                        <input type="hidden" name="tab" value="new">
                        <input type="hidden" name="filter" value="custom">


                        <div class="modal-header">
                          <h5 class="modal-title" id="customDateModalLabel">Select Custom Date Range</h5>
                          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span>&times;</span>
                          </button>
                        </div>
                        <div class="modal-body">
                          <label for="start_date">Start Date</label>
                          <input type="date" name="start_date" class="form-control" required>

                          <label for="end_date" class="mt-2">End Date</label>
                          <input type="date" name="end_date" class="form-control" required>
                        </div>
                        <div class="modal-footer">
                          <button type="submit" class="btn btn-primary">Apply Filter</button>
                        </div>
                      </form>
                    </div>
                  </div>

                  <script>
                    document.addEventListener('DOMContentLoaded', function() {
                      const filterDropdown = document.getElementById('filter');

                      filterDropdown.addEventListener('change', function() {
                        const selected = this.value;
                        if (selected === 'custom') {
                          $('#customDateModal').modal('show');
                        } else {
                          // Redirect using GET so filter applies
                          const url = new URL(window.location.href);
                          url.searchParams.set('filter', selected);
                          url.searchParams.delete('start_date');
                          url.searchParams.delete('end_date');
                          window.location.href = url.toString();
                        }
                      });

                      // Auto-open modal if custom is selected and start/end date missing
                      const urlParams = new URLSearchParams(window.location.search);
                      if (urlParams.get('filter') === 'custom') {
                        const startDate = urlParams.get('start_date');
                        const endDate = urlParams.get('end_date');

                        // Only open modal if both dates are missing (i.e. first time)
                        if (!startDate || !endDate) {
                          $('#customDateModal').modal('show');
                        }
                      }
                    });
                  </script>
                  <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
                  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

                  <!-- Row 1: 3 Charts -->
                  <div class="row" id="chartRow1">
                    <!-- Chart 1 -->
                    <div class="col-md-4">
                      <div class="card mt-4">
                        <div class="card-header bg-primary text-white">
                          <!-- Filtered Leads (by Executive) -->
                          <div>Executive-wise Lead Overview</div>

                          <small style="font-style: italic; color: #f8f9fa; font-size: 0.8rem;">Shows lead distribution across sales executives</small>
                        </div>
                        <div class="card-body">
                          <canvas id="execLeadChart" height="300"></canvas>
                        </div>
                      </div>
                    </div>

                    <!-- Chart 2 -->
                    <div class="col-md-4">
                      <div class="card mt-4">
                        <div class="card-header bg-success text-white">
                          <div>
                            Total Leads Summary
                          </div>
                          <small style="font-style: italic; color: #f8f9fa; font-size: 0.8rem;">Shows lead distribution across sales executives</small>
                          <!-- Filtered Leads (Total Count) -->
                        </div>
                        <div class="card-body">
                          <canvas id="totalLeadChart" height="300"></canvas>
                        </div>
                      </div>
                    </div>

                    <!-- Chart 3 (Pie Chart) -->
                    <div class="col-md-4">
                      <div class="card mt-4">
                        <div class="card-header bg-info text-white">
                          <div>
                            Lead Funnel by Status
                          </div>
                          <small style="font-style: italic; color: #f8f9fa; font-size: 0.8rem;">Visualizes lead journey through pipeline stages</small>
                          <!-- Leads Grouped by Status -->
                        </div>
                        <div class="card-body">
                          <canvas id="statusChart" height="220"></canvas>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Row 2: 2 Charts (Conversation & Attempt) -->
                  <div class="row" id="outcomeAttemptRow">
                    <!-- Chart 4: Conversation Outcome -->
                    <div id="conversationOutcomeChartContainer" class="col-md-6">
                      <div class="card mt-4">
                        <div class="card-header bg-warning text-dark">
                          <!-- Leads Grouped by Conversation Outcome -->
                          <div>
                            Conversation Outcome Breakdown (only when Call Outcome = "Answered")
                          </div>
                          <small style="font-style: italic; color: #f8f9fa; font-size: 0.8rem;">Breaks down conversations into Positive, Negative, etc.</small>
                        </div>
                        <div class="card-body">
                          <canvas id="conversationOutcomeChart" height="220"></canvas>
                        </div>
                      </div>
                    </div>

                    <!-- Chart 5: Attempt Result -->
                    <div id="attemptResultChartContainer" class="col-md-6">
                      <div class="card mt-4">
                        <div class="card-header bg-secondary text-white">
                          <!-- Leads Grouped by Attempt Result -->
                          <div>
                            Call Attempt Result Analysis (only when Call Outcome ≠ "Answered")
                          </div>
                          <small style="font-style: italic; color: #f8f9fa; font-size: 0.8rem;">Analyzes call attempts like No Answer, Busy, Not Reachable</small>
                        </div>
                        <div class="card-body">
                          <canvas id="attemptResultChart" height="220"></canvas>
                        </div>
                      </div>
                    </div>
                  </div>
 <!-- script for display charts -->
                <!--  <script>
  function toggleChartsBasedOnFilter() {
    const selectedFilter = document.getElementById("filter").value;

    if (selectedFilter === "todays_update") {
      document.getElementById("chartRow1").style.display = "none";
      document.getElementById("outcomeAttemptRow").style.display = "none";
    } else {
      document.getElementById("chartRow1").style.display = "";
      document.getElementById("outcomeAttemptRow").style.display = "";
    }
  }

  // Call it initially and also whenever filter changes
  document.addEventListener("DOMContentLoaded", toggleChartsBasedOnFilter);
  document.getElementById("yourFilterId").addEventListener("change", toggleChartsBasedOnFilter);
</script>
-->


                  <!-- script for  -->
                  <?php
                  // $start_date = date('Y-m-01');
                  // $end_date = date('Y-m-t');
                  $filter = $_GET['filter'] ?? 'all'; // default = this month

                  switch ($filter) {
                    case 'week':
                      $where = "YEARWEEK(c.calling_date, 1) = YEARWEEK(CURDATE(), 1)";
                      break;

                    case 'month':
                      $where = "MONTH(c.calling_date) = MONTH(CURDATE()) AND YEAR(c.calling_date) = YEAR(CURDATE())";
                      break;

                    case 'custom':
                      $start_date = $_GET['start_date'] ?? null;
                      $end_date = $_GET['end_date'] ?? null;

                      if ($start_date && $end_date) {
                        $where = "DATE(c.calling_date) BETWEEN '{$start_date}' AND '{$end_date}'";
                      } else {
                        $where = "1=1"; // fallback
                      }
                      break;
                      case 'todays_update':
                        $where = "DATE(lg.date_created) = CURDATE()";
                        break;
                        case 'overdue':
                        // Overdue based on latest follow-up in log_list
                        $where = "c.follow_up_date IS NOT NULL AND DATE(lg.follow_up_date) < CURDATE()";
                        break;

                    case 'all':
                      $where = "1=1"; // show all data
                      break;
                    case 'today':
                    default:
                      $where = "DATE(c.calling_date) = CURDATE()";
                      break;
                  }
                  $execLeadChartData = [];
                  $callOutcomeChartData = [];

                  // $selected_user = $_GET['user_id'] ?? 'all';
                  $selected_outcome = $_GET['call_outcome'] ?? 'all';
                  $conversation_outcome = $_GET['conversation_outcome'] ?? 'all';
                  $attempt_result = $_GET['attempt_result'] ?? 'all';
                  $status = $_GET['status'] ?? 'all';

                  // Base query
                  $sql = "
  SELECT 
    u.id,
    CONCAT(u.firstname, ' ', u.lastname) AS name,
    DATE(l.date_created) AS created_date,
    COUNT(DISTINCT l.id) AS total
  FROM lead_list l
  LEFT JOIN client_list c ON l.id = c.lead_id
  LEFT JOIN users u ON l.assigned_to = u.id
  LEFT JOIN (
        SELECT lg1.*
        FROM log_list lg1
        INNER JOIN (
            SELECT lead_id, MAX(id) AS max_id
            FROM log_list
            GROUP BY lead_id
        ) latest ON lg1.id = latest.max_id
    ) lg ON l.id = lg.lead_id
  WHERE $where
   AND l.assigned_to = {$exec_id}
";

                  // Apply filters dynamically

                  if ($selected_outcome !== 'all') {
                    $sql .= " AND lg.call_outcome = '" . $conn->real_escape_string($selected_outcome) . "'";
                  }

                  if ($conversation_outcome !== 'all') {
                    $sql .= " AND lg.conversation_outcome = '" . $conn->real_escape_string($conversation_outcome) . "'";
                  }

                  if ($attempt_result !== 'all') {
                    $sql .= " AND lg.attempt_result = '" . $conn->real_escape_string($attempt_result) . "'";
                  }
                  if ($status !== 'all') {
                    $sql .= " AND l.status = '" . $conn->real_escape_string($status) . "'";
                  }

                  // Group by executive
                  // $sql .= " GROUP BY l.assigned_to ORDER BY total DESC";
                  $sql .= " GROUP BY u.id, DATE(l.date_created) ORDER BY created_date ASC, total DESC";

                  $query = $conn->query($sql);

                  while ($row = $query->fetch_assoc()) {
                    $execLeadChartData[] = $row;
                  }
                  ?>



                  <!-- Chart Script -->
                  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                  <script>
                    const execLeadChartData = <?= json_encode($execLeadChartData); ?>;

                    const ctx = document.getElementById('execLeadChart').getContext('2d');

                    new Chart(ctx, {
                      type: 'bar',
                      data: {
                        // labels: execLeadChartData.map(item => item.name),
                        labels: execLeadChartData.map(item => `${item.created_date} - ${item.name}`),
                        datasets: [{
                          label: 'Leads',
                          data: execLeadChartData.map(item => item.total),
                          backgroundColor: 'rgba(54, 162, 235, 0.6)',
                          borderColor: 'rgba(54, 162, 235, 1)',
                          borderWidth: 1
                        }]
                      },
                      options: {
                        responsive: true,
                        plugins: {
                          legend: {
                            display: false
                          },
                          tooltip: {
                            enabled: true
                          }
                        },
                        scales: {
                          y: {
                            beginAtZero: true,
                            title: {
                              display: true,
                              text: 'Lead Count'
                            }
                          },
                          x: {
                            ticks: {
                              maxRotation: 90,
                              minRotation: 45
                            }
                          }
                        }
                      }
                    });
                  </script>
                  <!-- chart 2 -->
                  <?php
                  $totalLeadChartData = [];

                  // Clone your base SQL and reuse filters
                  $sql2 = "
  SELECT COUNT(DISTINCT l.id) AS total
  FROM lead_list l
  LEFT JOIN client_list c ON l.id = c.lead_id
  LEFT JOIN (
        SELECT lg1.*
        FROM log_list lg1
        INNER JOIN (
            SELECT lead_id, MAX(id) AS max_id
            FROM log_list
            GROUP BY lead_id
        ) latest ON lg1.id = latest.max_id
    ) lg ON l.id = lg.lead_id
  WHERE $where
  AND l.assigned_to = {$exec_id}
";

                  // Reapply same filters
                  if ($selected_outcome !== 'all') {
                    $sql2 .= " AND lg.call_outcome = '" . $conn->real_escape_string($selected_outcome) . "'";
                  }
                  if ($conversation_outcome !== 'all') {
                    $sql2 .= " AND lg.conversation_outcome = '" . $conn->real_escape_string($conversation_outcome) . "'";
                  }
                  if ($attempt_result !== 'all') {
                    $sql2 .= " AND lg.attempt_result = '" . $conn->real_escape_string($attempt_result) . "'";
                  }
                  if ($status !== 'all') {
                    $sql2 .= " AND l.status = '" . $conn->real_escape_string($status) . "'";
                  }

                  $query2 = $conn->query($sql2);
                  $totalCount = 0;
                  if ($query2 && $row2 = $query2->fetch_assoc()) {
                    $totalCount = $row2['total'];
                  }
                  ?>


                  <script>
                    const totalCount = <?= $totalCount ?>;
                    const ctxTotal = document.getElementById('totalLeadChart').getContext('2d');

                    const totalLeadChart = new Chart(ctxTotal, {
                      type: 'bar',
                      data: {
                        labels: ['Filtered Leads'],
                        datasets: [{
                          label: 'Total Count',
                          data: [totalCount],
                          backgroundColor: ['rgba(40, 167, 69, 0.7)'],
                          borderColor: ['rgba(40, 167, 69, 1)'],
                          borderWidth: 1,
                          borderRadius: 6
                        }]
                      },
                      options: {
                        plugins: {
                          tooltip: {
                            callbacks: {
                              label: function(context) {
                                const label = context.dataset.label || '';
                                const date = context.label;
                                const value = context.parsed.y;
                                return `${label} - ${value} leads on ${date}`;
                              }
                            }
                          },
                          legend: {
                            position: 'bottom'
                          }
                        },
                        animation: {
                          duration: 800,
                          easing: 'easeOutQuart'
                        },
                        scales: {
                          x: {
                            title: {
                              display: true,
                              text: 'Lead Date'
                            }
                          },
                          y: {
                            title: {
                              display: true,
                              text: 'Number of Leads'
                            },
                            beginAtZero: true
                          }
                        }
                      }

                    });
                  </script>

                  <!-- chart 3 -->
                  <?php
                  // Status Mapping
                  $statusLabels = [
                    '0' => 'Lead Uncontacted',
                    '1' => 'Prospect Contact Made',
                    '2' => 'Qualified Need Validated',
                    '3' => 'Solution Fit Discovery',
                    '4' => 'Proposal Value Proposition',
                    '5' => 'Negotiation',
                    '6' => 'Closed Won',
                    '7' => 'Closed Lost'
                  ];

                  // Sample query: Total leads grouped by status
                  $labels = [];
                  $data = [];

                  $sql = "
  SELECT l.status, COUNT(*) as total 
  FROM lead_list l 
";
if (in_array($filter, ['today', 'week', 'month', 'custom'])) {
                      $sql .= " INNER JOIN client_list c ON l.id = c.lead_id ";
                    }
// If today's update filter is applied, join with latest log
                    if ($filter == 'todays_update') {
                      $sql .= "
      LEFT JOIN (
          SELECT lg1.*
          FROM log_list lg1
          INNER JOIN (
              SELECT lead_id, MAX(id) AS max_id
              FROM log_list
              GROUP BY lead_id
          ) latest ON lg1.id = latest.max_id
      ) lg ON l.id = lg.lead_id
    ";
                    }
                    // If filter is overdue, join client_list to get follow_up_date
                    if ($filter == 'overdue') {
                      $sql .= " LEFT JOIN client_list c ON l.id = c.lead_id ";
                      $where = "c.follow_up_date IS NOT NULL AND DATE(c.follow_up_date) < CURDATE()";
                    }
                    $sql .= " WHERE $where AND l.assigned_to = {$exec_id}";


                  if ($status !== 'all') {
                    $sql .= " AND l.status = '$status'";
                  }
                  $sql .= " GROUP BY l.status ORDER BY l.status ASC";

                  $res = $conn->query($sql);
                  while ($row = $res->fetch_assoc()) {
                    $statusCode = $row['status'];
                    $labels[] = $statusLabels[$statusCode] ?? 'Unknown';
                    $data[] = $row['total'];
                  }
                  ?>



                  <script>
                    const statusCtx = document.getElementById('statusChart').getContext('2d');
                    const statusChart = new Chart(statusCtx, {
                      type: 'pie',
                      data: {
                        labels: <?= json_encode($labels) ?>,
                        datasets: [{
                          label: 'Lead Count',
                          data: <?= json_encode($data) ?>,
                          backgroundColor: [
                            '#007bff', '#28a745', '#ffc107', '#dc3545',
                            '#6f42c1', '#20c997', '#fd7e14', '#17a2b8'
                          ],
                          borderColor: '#fff',
                          borderWidth: 1
                        }]
                      },
                      options: {
                        responsive: true,
                        plugins: {
                          legend: {
                            position: 'right'
                          },
                          tooltip: {
                            callbacks: {
                              label: function(context) {
                                return `${context.label}: ${context.raw} leads`;
                              }
                            }
                          }
                        }
                      }
                    });
                  </script>


                  <!-- chart 4 -->
                  <?php
                  // Conversation Outcome Mapping
                  $conversationOutcomeLabels = [
                    '1' => 'Interested',
                    '2' => 'Call Back Later',
                    '3' => 'Busy – Try Again',
                    '4' => 'Needs More Info',
                    '5' => 'Not Interested',
                    '6' => 'Already Purchased',
                    '7' => 'Wrong Contact'
                  ];

                  $convLabels = [];
                  $convData = [];

                  if ($filter == 'overdue') {
                      // Separate SQL for overdue
                      $sql = "
    SELECT lg.conversation_outcome, COUNT(*) AS total
    FROM log_list lg
    INNER JOIN (
        SELECT lead_id, MAX(date_created) AS max_date
        FROM log_list
        GROUP BY lead_id
    ) latest ON lg.lead_id = latest.lead_id AND lg.date_created = latest.max_date
    LEFT JOIN lead_list l ON lg.lead_id = l.id
    WHERE l.id IN (
        SELECT lead_id 
        FROM client_list 
        WHERE follow_up_date IS NOT NULL 
          AND DATE(follow_up_date) < CURDATE()
    )
    AND l.assigned_to = {$exec_id}";

                      // Apply other filters
                      if ($selected_outcome !== 'all') {
                        $sql .= " AND lg.call_outcome = '" . $conn->real_escape_string($selected_outcome) . "'";
                      }
                      if ($attempt_result !== 'all') {
                        $sql .= " AND lg.attempt_result = '" . $conn->real_escape_string($attempt_result) . "'";
                      }
                      if ($status !== 'all') {
                        $sql .= " AND l.status = '" . $conn->real_escape_string($status) . "'";
                      }
                      if ($conversation_outcome !== 'all') {
                        $sql .= " AND lg.conversation_outcome = '" . $conn->real_escape_string($conversation_outcome) . "'";
                      }

                      $sql .= " GROUP BY lg.conversation_outcome ORDER BY lg.conversation_outcome ASC";
                    } else {
                    if (strpos($where, 'c.calling_date') !== false) {
                        $extraJoin = " INNER JOIN client_list c ON l.id = c.lead_id ";
                      } else {
                        $extraJoin = "";
                      }
                      // Your existing SQL for all other filters
                      $sql = "
    SELECT lg.conversation_outcome, COUNT(*) as total
    FROM log_list lg
    INNER JOIN (
        SELECT lead_id, MAX(date_created) as max_date
        FROM log_list
        GROUP BY lead_id
    ) latest ON lg.lead_id = latest.lead_id AND lg.date_created = latest.max_date
    LEFT JOIN lead_list l ON lg.lead_id = l.id
    $extraJoin
    WHERE $where
      AND l.assigned_to = {$exec_id}";

                      if ($selected_outcome !== 'all') {
                        $sql .= " AND lg.call_outcome = '" . $conn->real_escape_string($selected_outcome) . "'";
                      }
                      if ($attempt_result !== 'all') {
                        $sql .= " AND lg.attempt_result = '" . $conn->real_escape_string($attempt_result) . "'";
                      }
                      if ($status !== 'all') {
                        $sql .= " AND l.status = '" . $conn->real_escape_string($status) . "'";
                      }
                      if ($conversation_outcome !== 'all') {
                        $sql .= " AND lg.conversation_outcome = '" . $conn->real_escape_string($conversation_outcome) . "'";
                      }

                      $sql .= " GROUP BY lg.conversation_outcome ORDER BY lg.conversation_outcome ASC";
                    }


                  $result = $conn->query($sql);
                  while ($row = $result->fetch_assoc()) {
                    $code = $row['conversation_outcome'];
                    if (isset($conversationOutcomeLabels[$code])) {
                      $convLabels[] = $conversationOutcomeLabels[$code];
                      $convData[] = $row['total'];
                    }
                  }
                  ?>

                  <script>
                    const conversationOutcomeChart = new Chart(document.getElementById('conversationOutcomeChart'), {
                      type: 'bar',
                      data: {
                        labels: <?= json_encode($convLabels) ?>,
                        datasets: [{
                          label: 'Lead Count',
                          data: <?= json_encode($convData) ?>,
                          backgroundColor: 'rgba(255, 193, 7, 0.7)',
                          borderColor: 'rgba(255, 193, 7, 1)',
                          borderWidth: 1
                        }]
                      },
                      options: {
                        responsive: true,
                        plugins: {
                          legend: {
                            display: false
                          }
                        },
                        scales: {
                          y: {
                            beginAtZero: true,
                            title: {
                              display: true,
                              text: 'Total Leads'
                            }
                          },
                          x: {
                            title: {
                              display: true,
                              text: 'Conversation Outcome'
                            },
                            ticks: {
                              autoSkip: false
                            }
                          }
                        }
                      }
                    });
                  </script>

                  <?php
                  // Attempt Result Labels
                  $attemptResultLabels = [
                    '1' => 'Ringing, No Answer',
                    '2' => 'Switched Off',
                    '3' => 'Busy',
                    '4' => 'Call Dropped',
                    '5' => 'Invalid Number'
                  ];

                  $attemptLabels = [];
                  $attemptData = [];

                  if ($filter == 'overdue') {
                      // Separate query for overdue leads
                      $sql = "
    SELECT lg.attempt_result, COUNT(*) as total
    FROM log_list lg
    INNER JOIN (
        SELECT lead_id, MAX(date_created) as max_date
        FROM log_list
        GROUP BY lead_id
    ) latest ON lg.lead_id = latest.lead_id AND lg.date_created = latest.max_date
    LEFT JOIN lead_list l ON lg.lead_id = l.id
    WHERE l.id IN (
        SELECT lead_id 
        FROM client_list 
        WHERE follow_up_date IS NOT NULL 
          AND DATE(follow_up_date) < CURDATE()
    )
    AND l.assigned_to = {$exec_id}";

                      // Apply filters
                      if ($selected_user !== 'all') {
                        $sql .= " AND l.assigned_to = " . intval($selected_user);
                      }
                      if ($selected_outcome !== 'all') {
                        $sql .= " AND lg.call_outcome = '" . $conn->real_escape_string($selected_outcome) . "'";
                      }
                      if ($conversation_outcome !== 'all') {
                        $sql .= " AND lg.conversation_outcome = '" . $conn->real_escape_string($conversation_outcome) . "'";
                      }
                      if ($status !== 'all') {
                        $sql .= " AND l.status = '" . $conn->real_escape_string($status) . "'";
                      }
                      if ($attempt_result !== 'all') {
                        $sql .= " AND lg.attempt_result = '" . $conn->real_escape_string($attempt_result) . "'";
                      }

                      $sql .= " GROUP BY lg.attempt_result ORDER BY lg.attempt_result ASC";
                    } else {
                    if (strpos($where, 'c.calling_date') !== false) {
                        $extraJoin = " INNER JOIN client_list c ON l.id = c.lead_id ";
                      } else {
                        $extraJoin = "";
                      }
                      // Existing query for all other filters
                      $sql = "
    SELECT lg.attempt_result, COUNT(*) as total
    FROM log_list lg
    INNER JOIN (
        SELECT lead_id, MAX(date_created) as max_date
        FROM log_list
        GROUP BY lead_id
    ) latest ON lg.lead_id = latest.lead_id AND lg.date_created = latest.max_date
    LEFT JOIN lead_list l ON lg.lead_id = l.id
    $extraJoin
    WHERE $where
      AND l.assigned_to = {$exec_id}";

                      // Apply filters
                      if ($selected_user !== 'all') {
                        $sql .= " AND l.assigned_to = " . intval($selected_user);
                      }
                      if ($selected_outcome !== 'all') {
                        $sql .= " AND lg.call_outcome = '" . $conn->real_escape_string($selected_outcome) . "'";
                      }
                      if ($conversation_outcome !== 'all') {
                        $sql .= " AND lg.conversation_outcome = '" . $conn->real_escape_string($conversation_outcome) . "'";
                      }
                      if ($status !== 'all') {
                        $sql .= " AND l.status = '" . $conn->real_escape_string($status) . "'";
                      }
                      if ($attempt_result !== 'all') {
                        $sql .= " AND lg.attempt_result = '" . $conn->real_escape_string($attempt_result) . "'";
                      }

                      $sql .= " GROUP BY lg.attempt_result ORDER BY lg.attempt_result ASC";
                    }

                  $result = $conn->query($sql);
                  while ($row = $result->fetch_assoc()) {
                    $code = $row['attempt_result'];
                    if (isset($attemptResultLabels[$code])) {
                      $attemptLabels[] = $attemptResultLabels[$code];
                      $attemptData[] = $row['total'];
                    }
                  }
                  ?>



                  <!-- Chart Script -->
                  <script>
                    const attemptResultChart = new Chart(document.getElementById('attemptResultChart'), {
                      type: 'bar',
                      data: {
                        labels: <?= json_encode($attemptLabels) ?>,
                        datasets: [{
                          label: 'Lead Count',
                          data: <?= json_encode($attemptData) ?>,
                          backgroundColor: 'rgba(23, 162, 184, 0.7)',
                          borderColor: 'rgba(23, 162, 184, 1)',
                          borderWidth: 1
                        }]
                      },
                      options: {
                        responsive: true,
                        plugins: {
                          legend: {
                            display: false
                          }
                        },
                        scales: {
                          y: {
                            beginAtZero: true,
                            title: {
                              display: true,
                              text: 'Total Leads'
                            }
                          },
                          x: {
                            title: {
                              display: true,
                              text: 'Attempt Result'
                            },
                            ticks: {
                              autoSkip: false
                            }
                          }
                        }
                      }
                    });
                  </script>

                  <!-- toggle script -->
                  <script>
                    document.addEventListener("DOMContentLoaded", function() {
                      const callOutcome = document.getElementById("call_outcome");

                      const conversationFilter = document.querySelector('select[name="conversation_outcome"]').closest(".col-md-2");
                      const attemptFilter = document.getElementById("attempt_result").closest(".col-md-2");

                      const conversationChart = document.getElementById("conversationOutcomeChartContainer");
                      const attemptChart = document.getElementById("attemptResultChartContainer");

                      function toggleFiltersAndCharts() {
                        const val = callOutcome.value;

                        if (val === "all") {
                          // Show both filters and both charts
                          conversationFilter.style.display = "";
                          attemptFilter.style.display = "";
                          conversationChart.style.display = "";
                          attemptChart.style.display = "";
                        } else if (val === "1") {
                          // Answered: show only conversation
                          conversationFilter.style.display = "";
                          attemptFilter.style.display = "none";
                          conversationChart.style.display = "";
                          attemptChart.style.display = "none";
                        } else if (val === "2") {
                          // Not Answered: show only attempt
                          conversationFilter.style.display = "none";
                          attemptFilter.style.display = "";
                          conversationChart.style.display = "none";
                          attemptChart.style.display = "";
                        }
                      }

                      // Run on load
                      toggleFiltersAndCharts();

                      // Run on change
                      callOutcome.addEventListener("change", toggleFiltersAndCharts);
                    });
                  </script>

                  <!-- end of toggle script -->






                  <table class="table table-striped table-bordered second">
                    <thead class="bg-light">
                      <tr class="border-0">
                        <th class="border-0">#</th>
                        <th class="border-0">Ref Code</th>
                        <th class="border-0">Company</th>
                        <th class="border-0">Contacts</th>
                        <th class="border-0">Country</th>
                        <th class="border-0">Assigned To</th>
                        <th class="border-0">Call Date</th>
                        <th class="border-0">Follow-up</th>
                        <th class="border-0">Status</th>
                        <th class="border-0">Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php while ($row = $qry->fetch_assoc()):
                        // $contacts = json_decode($row['contact'], true);
                        $contacts = [];
                        $raw_contact = $row['contact'];

                        // Check if it's a valid JSON array
                        if (is_string($raw_contact) && is_array(json_decode($raw_contact, true))) {
                          $contacts = json_decode($raw_contact, true);
                        }
                        $contact_html = "";
                        if (!is_array($contacts)) {
                          error_log("Invalid contact format for lead ID: " . $row['id']);
                        }

                        if (!empty($contacts)) {
                          foreach (array_slice($contacts, 0, 2) as $c) {
                            $contact_html .= '<div><strong>' . htmlspecialchars($c['name']) . '</strong>';
                            if (!empty($c['email'])) {
                              $contact_html .= '<i class="fa fa-envelope text-primary ml-2" title="' . htmlspecialchars($c['email']) . '"></i>';
                            }
                            if (!empty($c['phone'])) {
                              $contact_html .= '<i class="fa fa-phone text-success ml-2" title="' . htmlspecialchars($c['phone']) . '"></i>';
                            }
                            $contact_html .= '</div>';
                          }
                          if (count($contacts) > 2) {
                            $contact_html .= '<span class="badge badge-info">+' . (count($contacts) - 2) . ' more</span>';
                          }
                        } else {
                          $contact_html = '<span class="text-muted">No contacts</span>';
                        }
                      ?>
                        <tr>
                          <td>
                            <?= $sn++ ?>
                          </td>
                          <td>
                            <?= htmlspecialchars($row['id']) ?>
                          </td>
                          <td>
                            <?= htmlspecialchars($row['client']) ?>
                          </td>
                          <td>
                            <?= $contact_html ?>
                          </td>
                          <td>
                            <?= htmlspecialchars($row['country']) ?>
                          </td>
                          <td>
                            <?= ucwords($row['firstname'] . ' ' . $row['lastname']) ?>
                          </td>
                          <td>
                            <?= $row['calling_date']
                              ? date('d-m-Y H:i', strtotime($row['calling_date']))
                              : '<span class="text-muted">N/A</span>' ?>
                          </td>
                          <td>
                            <?= $row['follow_up_date'] ? date('d-m-Y', strtotime($row['follow_up_date'])) : '<span class="text-muted">N/A</span>' ?>
                          </td>
                          <td>
                            <span
                              class="badge badge-<?= $row['in_opportunity'] ? 'success' : 'warning' ?>">
                              <?= $row['in_opportunity'] ? 'Opportunity' : 'Lead' ?>
                            </span>
                          </td>
                          <td>
                              <?php if (in_array($_settings->userdata('type'), [2])): ?>
                                <a class="btn btn-sm btn-info border"
                                  href="./?page=Sharks_portal&company_search=<?= urlencode($row['client']) ?>&date_search="
                                  title="Search this Company">
                                  <i class="fa fa-search"></i>
                                </a>
                              <?php endif; ?>
                            </td>
                        </tr>
                      <?php endwhile; ?>
                      <?php if ($sn === 1): ?>
                        <tr>
                          <td colspan="9" class="text-center text-muted">No leads assigned today.</td>
                        </tr>
                      <?php endif; ?>
                    </tbody>
                  </table>

                </div>
              </div>
            </div>
          </div>

          <?php
          // Define updated status labels
          $statusMap = [
            0 => "Lead – Uncontacted",
            1 => "Prospect – Contact Made",
            2 => "Qualified – Need Validated",
            3 => "Solution Fit / Discovery",
            4 => "Proposal / Value Proposition",
            5 => "Negotiation",
            6 => "Closed – Won",
            7 => "Closed – Lost"
          ];

          // Build role-based condition
          $where = "1=1";
          if ($role == 2) { // Executive
            $where .= " AND assigned_to = '$uid'";
          } elseif ($role == 3) { // Manager
            $where .= " AND assigned_to = '$uid'";
          }
          // Admin sees all leads, no filter

          // Query with role filter
          $statusData = $conn->query("
                    SELECT status, COUNT(*) as count 
                    FROM lead_list 
                    WHERE $where
                    GROUP BY status
                ");

          $labels = $data = [];
          while ($row = $statusData->fetch_assoc()) {
            $labels[] = isset($statusMap[$row['status']]) ? $statusMap[$row['status']] : "Status " . $row['status'];
            $data[] = $row['count'];
          }
          ?>

        </div>
      <?php endif; ?>

    </div>
  </div>
  </div>

  </div>

  <!-- Optional JavaScript -->
 <script src="../assets/vendor/jquery/jquery-3.3.1.min.js"></script>
    <script src="../assets/vendor/bootstrap/js/bootstrap.bundle.js"></script>
    <script src="../assets/vendor/slimscroll/jquery.slimscroll.js"></script>
    <script src="../assets/vendor/multi-select/js/jquery.multi-select.js"></script>
    <script src="../assets/libs/js/main-js.js"></script>
    <script src="https://cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js"></script>
    <script src="../assets/vendor/datatables/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.5.2/js/dataTables.buttons.min.js"></script>
    <script src="../assets/vendor/datatables/js/buttons.bootstrap4.min.js"></script>
    <script src="../assets/vendor/datatables/js/data-table.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.5.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.5.2/js/buttons.print.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.5.2/js/buttons.colVis.min.js"></script>
    <script src="https://cdn.datatables.net/rowgroup/1.0.4/js/dataTables.rowGroup.min.js"></script>
    <script src="https://cdn.datatables.net/select/1.2.7/js/dataTables.select.min.js"></script>
    <script src="https://cdn.datatables.net/fixedheader/3.1.5/js/dataTables.fixedHeader.min.js"></script>
    
</div> <!-- end wrapper -->
<?php require_once('inc/footer.php'); ?>
</body>

</html>
<!-- Added remark and updates work -->