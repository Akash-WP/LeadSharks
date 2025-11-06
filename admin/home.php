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
    #loadingOverlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(255, 255, 255, 0.8);
        z-index: 9999;
        justify-content: center;
        align-items: center;
    }

    #loadingOverlay .spinner {
        width: 3rem;
        height: 3rem;
        border: 4px solid #ccc;
        border-top-color: #007bff;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }
</style>
<div id="loadingOverlay">
    <div class="spinner"></div>
</div>

<body>
    <!-- ============================================================== -->
    <!-- main wrapper -->
    <!-- ============================================================== -->

    <!-- ============================================================== -->
    <!-- wrapper  -->
    <!-- ============================================================== -->
    <div class="dashboard-wrapper">
        <div class="container-fluid" style="margin-left: -250px;">
            <!-- ============================================================== -->
            <!-- pageheader -->
            <!-- ============================================================== -->
            <!-- <div class="row">
                <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
                    <div class="page-header">
                        <div class="page-breadcrumb">
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="#" class="breadcrumb-link">Dashboard</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">Dashboard Template</li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                </div>
            </div> -->
            <!-- ============================================================== -->
            <!-- end pageheader -->
            <!-- ============================================================== -->
            <?php if (in_array($_settings->userdata('type'), [1, 3])): ?>
                <?php if (in_array($_settings->userdata('type'), [1, 3])): ?>
                    <div class="row">
                        <?php
                        // Only run if user type is 1 or 3 (e.g. admins or managers)
                        if (in_array($_settings->userdata('type'), [1, 3])) {
                            $exec_id = $conn->real_escape_string($_settings->userdata('id'));

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

                            if ($type == 1) {
                                // Admin: Show all leads
                                $result = $conn->query("SELECT COUNT(*) as count FROM `lead_list` WHERE delete_flag = 0");
                            } elseif ($type == 3) {
                                // Manager: Show leads assigned to self or team
                                // $team_ids = [$exec_id]; // Later replace with actual team IDs
                                // $team_ids_str = implode(',', array_map('intval', $team_ids));
                                // $result = $conn->query("SELECT COUNT(*) as count FROM `lead_list` WHERE assigned_to IN ($team_ids_str) AND delete_flag = 0");
                                $result = $conn->query("SELECT COUNT(*) as count FROM `lead_list` WHERE delete_flag = 0");
                            } else {
                                // Default fallback (executives)
                                $result = $conn->query("SELECT COUNT(*) as count FROM `lead_list` WHERE assigned_to = '{$exec_id}' AND delete_flag = 0");
                            }
                            if ($result) {
                                $row = $result->fetch_assoc();
                                $total_leads = $row['count'];
                            }

                            // Today's follow-ups
                            if ($type == 1 || $type ==3) {
                                $query = "SELECT COUNT(*) as count FROM client_list WHERE DATE(follow_up_date) = CURDATE()";
                            } else {
                                $query = "SELECT COUNT(*) as count FROM client_list c INNER JOIN lead_list l ON c.lead_id = l.id WHERE l.assigned_to = '{$exec_id}' AND DATE(c.follow_up_date) = CURDATE()";
                            }
                            $result = $conn->query($query);
                            $todays_followups = ($result) ? $result->fetch_assoc()['count'] : 0;

                            // Upcoming follow-ups (7 days)
                            if ($type == 1 || $type ==3) {
                                $query = "SELECT COUNT(*) as count FROM client_list WHERE DATE(follow_up_date) > CURDATE()";
                            } else {
                                $query = "SELECT COUNT(*) as count FROM client_list c INNER JOIN lead_list l ON c.lead_id = l.id WHERE l.assigned_to = '{$exec_id}' AND c.follow_up_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
                            }
                            $result = $conn->query($query);
                            $upcoming_followups = ($result) ? $result->fetch_assoc()['count'] : 0;

                            // Overdue follow-ups (before today)
                            if ($type == 1 || $type ==3) {
                                $query = "SELECT COUNT(*) as count FROM client_list WHERE DATE(follow_up_date) < CURDATE()";
                            } else {
                                $query = "SELECT COUNT(*) as count FROM client_list c INNER JOIN lead_list l ON c.lead_id = l.id WHERE l.assigned_to = '{$exec_id}' AND DATE(c.follow_up_date) < CURDATE()";
                            }
                            $result = $conn->query($query);
                            $overdue_followups = ($result) ? $result->fetch_assoc()['count'] : 0;

                            // Completion rate = converted opportunities / total leads * 100
                            $completion_rate = ($total_leads > 0) ? round(($converted_opps / $total_leads) * 100, 2) : 0;

                            // todays updated
                            if ($type == 1 || $type ==3) {
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
                <script>
document.addEventListener("DOMContentLoaded", function() {
  const overlay = document.getElementById("loadingOverlay");

  // Show loader immediately when a card is clicked
  document.querySelectorAll(".five-per-row").forEach(link => {
    link.addEventListener("click", function(e) {
      overlay.style.display = "flex";  // show spinner
      // no e.preventDefault ? allow normal navigation
      // browser will replace page, loader stays visible until then
    });
  });


});
</script>
                
                <?php if (in_array($_settings->userdata('type'), [1, 3])): ?>

                    <div class="row">
                        <!-- ============================================================== -->
                        <!-- basic table  -->

                        <div class="col-12">
                            <div class="card">
                                <div class="d-flex justify-content-between align-items-center px-3 mt-2">
                                    <h5 class="mb-0">Assigned Leads</h5>
                                    <button id="resetBtn" class="btn btn-sm btn-secondary ms-auto">Reset Filter</button>
                                </div>
                                <script>
                                    document.getElementById('resetBtn').addEventListener('click', function() {
                                        // Go to the base URL without query parameters
                                        window.location.href = window.location.origin + '/lms/admin/';
                                    });
                                </script>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <?php


                                        $uwhere = "";
                                        if ($_settings->userdata('type') == 2)
                                            $uwhere = " and assigned_to = '{$_settings->userdata('id')}' ";

                                        $users = $conn->query("SELECT id,CONCAT(lastname,', ', firstname, '', COALESCE(middlename,'')) as fullname FROM `users` where id in (SELECT `user_id` FROM `lead_list` where in_opportunity = 0 {$uwhere}) OR id in (SELECT assigned_to FROM `lead_list` where in_opportunity = 0 {$uwhere})");
                                        $user_arr = array_column($users->fetch_all(MYSQLI_ASSOC), 'fullname', 'id');

                                        $uid = $_settings->userdata('id');
                                        $role = $_settings->userdata('type');

                                        $filter = $_GET['filter'] ?? 'all'; // default is all

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
                                        } //elseif ($role == 3) { // Manager
                                            // Replace with logic to fetch team IDs
                                            //$where .= " AND l.assigned_to = '$uid'";
                                        //}

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
                                            <!-- <input type="hidden" name="tab" value="new"> -->
                                            <input type="hidden" name="start_date" value="<?= htmlspecialchars($_GET['start_date'] ?? '') ?>">
                                            <input type="hidden" name="end_date" value="<?= htmlspecialchars($_GET['end_date'] ?? '') ?>">

                                            <div class="container-fluid">
                                                <!-- Headings Row -->
                                                <div class="row font-weight-bold text-dark mb-1">
                                                    <div class="col-md-2 smooth-toggle" id="label-date-range">Date Range</div>
                                                    <div class="col-md-2 smooth-toggle" id="label-executive">Sales Executive</div>
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
                                                            <option value="month" <?= ($_GET['filter'] ?? '') == 'month' ? 'selected' : '' ?>>This Month</option>
                                                            <option value="today" <?= ($_GET['filter'] ?? '') == 'today' ? 'selected' : '' ?>>Today</option>
                                                            <option value="week" <?= ($_GET['filter'] ?? '') == 'week' ? 'selected' : '' ?>>This Week</option>
                                                            <option value="custom" <?= ($_GET['filter'] ?? '') == 'custom' ? 'selected' : '' ?>>Custom</option>
                                                            
                                                            <option value="todays_update" <?= ($_GET['filter'] ?? '') == 'todays_update' ? 'selected' : '' ?>>Todays Updated Leads</option>
                                                            <option value="overdue" <?= ($_GET['filter'] ?? '') == 'overdue' ? 'selected' : '' ?>>Overdue Leads</option>

                                                        </select>
                                                    </div>



                                                    <!-- User -->
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
                                                    </div>

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
                                                            <option value="3" <?= $conversation_outcome == '3' ? 'selected' : '' ?>>Busy Try Again</option>
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
                                                    <!-- <input type="hidden" name="tab" value="new"> -->
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
                                                            Call Attempt Result Analysis (only when Call Outcome ? "Answered")
                                                        </div>
                                                        <small style="font-style: italic; color: #f8f9fa; font-size: 0.8rem;">Analyzes call attempts like No Answer, Busy, Not Reachable</small>
                                                    </div>
                                                    <div class="card-body">
                                                        <canvas id="attemptResultChart" height="220"></canvas>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- <script>
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
</script> -->


                                        <!-- script for  -->
                                        <?php
                                        // $start_date = date('Y-m-01');
                                        // $end_date = date('Y-m-t');
                                        $filter = $_GET['filter'] ?? 'all'; // default all

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
                                        $execLeadChartData = [];
                                        $callOutcomeChartData = [];

                                        $selected_user = $_GET['user_id'] ?? 'all';
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
                                        ";

                                        // Apply filters dynamically
                                        if ($selected_user !== 'all') {
                                            $sql .= " AND l.assigned_to = " . intval($selected_user);
                                        }

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
                                        <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
                                        <script>
                                            const execLeadChartData = <?= json_encode($execLeadChartData); ?>;

                                            const ctx1 = document.getElementById('execLeadChart').getContext('2d');

                                            new Chart(ctx1, {
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
                                                        },
                                                        datalabels: { // <-- This plugin shows labels on the chart
                                                            anchor: 'end',
                                                            align: 'end',
                                                            color: '#000',
                                                            font: {
                                                                weight: 'bold',
                                                                size: '15',
                                                            },
                                                            formatter: (value) => value // shows the number
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
                                                },
                                                plugins: [ChartDataLabels] // <-- Register the plugin
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
                                      ";

                                        // Reapply same filters
                                        if ($selected_user !== 'all') {
                                            $sql2 .= " AND l.assigned_to = " . intval($selected_user);
                                        }
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
                                                    responsive: true,
                                                    plugins: {
                                                        datalabels: { // <-- show count on top of bar
                                                            anchor: 'center', // inside the bar
                                                            align: 'center',
                                                            color: '#000',
                                                            font: {
                                                                weight: 'bold',
                                                                size: 15
                                                            },
                                                            formatter: (value) => value // just show the number
                                                        },
                                                        tooltip: {
                                                            callbacks: {
                                                                label: function(context) {
                                                                    const label = context.dataset.label || '';
                                                                    const value = context.parsed.y;
                                                                    return `${label} - ${value} leads`;
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
                                                },
                                                plugins: [ChartDataLabels] // register the plugin
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

                                        $sql .= " WHERE $where";

                                        if ($selected_user !== 'all') {
                                            $sql .= " AND l.assigned_to = " . intval($selected_user);
                                        }
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
                                                        datalabels: { // <-- display count on slices
                                                            color: 'black',
                                                            font: {
                                                                weight: 'bold',
                                                                size: 14
                                                            },
                                                            formatter: (value, context) => {
                                                                return `${value}`; // just show the number
                                                            }
                                                        },
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
                                                },
                                                plugins: [ChartDataLabels] // register the plugin
                                            });
                                        </script>


                                        <!-- chart 4 -->
                                        <?php
                                        // Conversation Outcome Mapping
                                        $conversationOutcomeLabels = [
                                            '1' => 'Interested',
                                            '2' => 'Call Back Later',
                                            '3' => 'Busy  Try Again',
                                            '4' => 'Needs More Info',
                                            '5' => 'Not Interested',
                                            '6' => 'Already Purchased',
                                            '7' => 'Wrong Contact'
                                        ];

                                        $convLabels = [];
                                        $convData = [];

                                         if ($filter == 'overdue') {
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
    )";
                                            // Apply filters
                                            if ($selected_user !== 'all') {
                                                $sql .= " AND l.assigned_to = " . intval($selected_user);
                                            }
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
                                        // Ensure client_list is joined if $where references c.calling_date
                                            if (strpos($where, 'c.calling_date') !== false) {
                                                $extraJoin = " INNER JOIN client_list c ON l.id = c.lead_id ";
                                            } else {
                                                $extraJoin = "";
                                            }
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
  
";

                                            // Apply filters
                                            if ($selected_user !== 'all') {
                                                $sql .= " AND l.assigned_to = " . intval($selected_user);
                                            }
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
                                                        datalabels: { // <-- display count on slices
                                                            color: 'black',
                                                            font: {
                                                                weight: 'bold',
                                                                size: 15
                                                            },
                                                            formatter: (value, context) => {
                                                                return `${value}`; // just show the number
                                                            }
                                                        },
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
                                                },
                                                plugins: [ChartDataLabels] // register the plugin
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
    )";

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
                                        // Ensure client_list is joined if $where references c.calling_date
                                            if (strpos($where, 'c.calling_date') !== false) {
                                                $extraJoin = " INNER JOIN client_list c ON l.id = c.lead_id ";
                                            } else {
                                                $extraJoin = "";
                                            }
                                            // SQL Query
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
 
";

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
                                                        datalabels: { // <-- display count on slices
                                                            color: 'black',
                                                            font: {
                                                                weight: 'bold',
                                                                size: 15
                                                            },
                                                            formatter: (value, context) => {
                                                                return `${value}`; // just show the number
                                                            }
                                                        },
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
                                                },
                                                plugins: [ChartDataLabels] // register the plugin
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
                                                            <?php if (in_array($_settings->userdata('type'), [1, 3])): ?>
                                                                <a class="btn btn-sm btn-light border" href="./?page=view_lead&id=<?= $row['id'] ?>"><i
                                                                        class="fa fa-search"></i></a>
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
                            0 => "Lead ? Uncontacted",
                            1 => "Prospect ? Contact Made",
                            2 => "Qualified ? Need Validated",
                            3 => "Solution Fit / Discovery",
                            4 => "Proposal / Value Proposition",
                            5 => "Negotiation",
                            6 => "Closed ? Won",
                            7 => "Closed ? Lost"
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
            <?php endif; ?>
            
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const tbody = document.querySelector('tbody');
                    const rows = tbody.querySelectorAll('tr');

                    rows.forEach((row) => {
                        row.addEventListener('click', function(e) {
                            // Avoid triggering when clicking an actual link/button inside the row
                            if (e.target.tagName.toLowerCase() === 'a' || e.target.closest('a')) return;

                            // Find the <a> inside this row
                            const link = row.querySelector('td a');
                            if (link) {
                                // Navigate to the link
                              //  window.location.href = link.href;
                              window.open(link.href,'_blank');
                            }
                        });
                    });
                });
            </script>
            <style>
                #cardModal .modal-dialog {
                    max-width: 95%;
                    /* adjust to 100% if you want full */
                    width: 95%;
                }
            </style>
            <?php


            $uwhere = "";
            if ($_settings->userdata('type') == 2)
                $uwhere = " and assigned_to = '{$_settings->userdata('id')}' ";

            $users = $conn->query("SELECT id,CONCAT(lastname,', ', firstname, '', COALESCE(middlename,'')) as fullname FROM `users` where id in (SELECT `user_id` FROM `lead_list` where in_opportunity = 0 {$uwhere}) OR id in (SELECT assigned_to FROM `lead_list` where in_opportunity = 0 {$uwhere})");
            $user_arr = array_column($users->fetch_all(MYSQLI_ASSOC), 'fullname', 'id');

            $uid = $_settings->userdata('id');
            $role = $_settings->userdata('type');

            $filter1 = $_GET['filter1'] ?? 'today';
            echo $filter1;

            switch ($filter1) {
                case 'all':
                    $where = "1=1";
                    $modal_title = "All Leads";
                    break;
                case 'todays_updated':
                    $where = "DATE(lg.date_created) = CURDATE()";
                    $modal_title = "Today's Updated Leads";
                    break;
                case 'today':
                    $where = "DATE(c.follow_up_date) = CURDATE()";
                    $modal_title = "Today's Follow-ups";
                    break;
                case 'upcomming':
                    $where = "DATE(c.follow_up_date) > CURDATE()";
                    $modal_title = "Upcoming Follow-ups";
                    break;
                case 'overdue':
                    $where = "DATE(c.follow_up_date) < CURDATE()";
                    $modal_title = "Overdue Follow-ups";
                    break;
                default:
                    $where = "1=1";
                    $modal_title = "Leads";
                    break;
            }

            $where .= " AND l.delete_flag = 0";

            if ($role == 2) { // Executive
                $where .= " AND l.assigned_to = '$uid'";
            }

            $user_filter = "";
            $selected_user = $_GET['user_id'] ?? 'all';
            // call outcome

            if ($role == 1) {
                // Admin: get all users involved in leads
                $user_qry = $conn->query("SELECT id, CONCAT(firstname, ' ', lastname) as name FROM users WHERE id IN (SELECT assigned_to FROM lead_list WHERE delete_flag = 0)");
            } elseif ($role == 3) {
                // Manager: get executives under this manager
                $user_qry = $conn->query("SELECT id, CONCAT(firstname, ' ', lastname) as name FROM users WHERE id IN (SELECT assigned_to FROM lead_list WHERE delete_flag = 0)");
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
                        WHERE $where 
                        GROUP BY l.id 
                        ORDER BY l.status ASC, UNIX_TIMESTAMP(l.date_created) ASC");


            $sn = 1;

            ?>
            
            <div class="modal fade" id="cardModal" tabindex="-1" role="dialog" aria-labelledby="cardModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-3xl" role="document"> <!-- modal-xl for wide table -->
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="cardModalLabel"><?= htmlspecialchars($modal_title) ?></h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        
                        <div class="info-cards row justify-content-center">
                            <?php
                            if (in_array($_settings->userdata('type'), [1, 3])) {

                                switch ($filter1) {
                                    case "all":
                                        // Define the status labels for better readability on the cards
                                        $statusMap = [
                                            0 => "Uncontacted Leads",
                                            1 => "Prospects",
                                            2 => "Qualified",
                                            3 => "Solution Fit",
                                            4 => "Proposal Stage",
                                            5 => "Negotiation",
                                            6 => "Closed Won",
                                            7 => "Closed Lost"
                                        ];

                                        // Query to get all sales executives (type = 2)
                                        $executives_qry = $conn->query("SELECT id, CONCAT(firstname, ' ', lastname) as name FROM users WHERE type = 2 ORDER BY firstname ASC");

                                        // Check if any executives were found in the database
                                        if ($executives_qry->num_rows > 0) {

                                            // Loop through each executive
                                            while ($executive = $executives_qry->fetch_assoc()) {
                                                $executive_id = $executive['id'];

                                                // Query to get lead counts for this specific executive
                                                $stmt = $conn->prepare("SELECT status, COUNT(*) as count FROM lead_list WHERE assigned_to = ? GROUP BY status");
                                                $stmt->bind_param("i", $executive_id);
                                                $stmt->execute();
                                                $result = $stmt->get_result();

                                                // Store the counts in an array
                                                $lead_counts = [];
                                                while ($row = $result->fetch_assoc()) {
                                                    $lead_counts[$row['status']] = $row['count'];
                                                }
                                                $stmt->close();

                                                // Calculate the total assigned leads
                                                $total_leads = array_sum($lead_counts);

                                                // --- NEW: Only display the card IF the executive has more than zero leads ---
                                                if ($total_leads > 0) {

                                                    // --- Generate the HTML for the card ---
                                                    echo '
                                        
            <div class="col-xl-2 col-lg-3 col-md-6 col-sm-12 mb-4">
                <div class="card border-left-primary shadow h-100 ">
                    <div class="card-header bg-light py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary" style= "font-size:0.9rem">' . htmlspecialchars($executive['name']) . '</h6>
                        
                        <span class="badge badge-primary" style="font-size: 0.8rem;">
                            Total Assigned: ' . $total_leads . '
                        </span>
                    </div>
                    <div class="card-body" style="font-size: 0.9rem;">
                        <h6 class="text-xs font-weight-bold text-secondary text-uppercase mb-2">Status Breakdown</h6>';

                                                    // Loop through the status map and display the count for each status
                                                    foreach ($statusMap as $status_code => $status_label) {
                                                        $count = $lead_counts[$status_code] ?? 0;

                                                        // We only need to show statuses with counts > 0
                                                        if ($count > 0) {
                                                            echo '
                        <div class="d-flex justify-content-between mb-1">
                            <span>' . htmlspecialchars($status_label) . ':</span>
                            <span class="badge badge-info badge-pill">' . $count . '</span>
                        </div>';
                                                        }
                                                    }

                                                    // --- Close the card HTML ---
                                                    echo '
                    </div>
                </div>
            </div>
            ';
                                                } // --- End of the if ($total_leads > 0) condition ---
                                            }
                                        } else {
                                            // This message only shows if NO users with type = 2 exist at all
                                            echo '<div class="col-12"><p class="text-muted">No sales executives found.</p></div>';
                                        }

                                        break;
                                    case "todays_updated":
                                        $statusMap = [
                                            0 => "Uncontacted Leads",
                                            1 => "Prospects",
                                            2 => "Qualified",
                                            3 => "Solution Fit",
                                            4 => "Proposal Stage",
                                            5 => "Negotiation",
                                            6 => "Closed Won",
                                            7 => "Closed Lost"
                                        ];

                                        // Fetch executives
                                        $executives_qry = $conn->query("SELECT id, CONCAT(firstname, ' ', lastname) as name 
                                        FROM users 
                                        WHERE type = 2 
                                        ORDER BY firstname ASC");

                                        if ($executives_qry->num_rows > 0) {
                                            while ($executive = $executives_qry->fetch_assoc()) {
                                                $executive_id = $executive['id'];

                                                // ? Only count leads updated today
                                                $stmt = $conn->prepare("
                    SELECT l.status, COUNT(*) as count 
                    FROM lead_list l
                    INNER JOIN log_list lg ON lg.lead_id = l.id
                    WHERE l.assigned_to = ? 
                      AND DATE(lg.date_created) = CURDATE()
                    GROUP BY l.status
                ");
                                                $stmt->bind_param("i", $executive_id);
                                                $stmt->execute();
                                                $result = $stmt->get_result();

                                                $lead_counts = [];
                                                while ($row = $result->fetch_assoc()) {
                                                    $lead_counts[$row['status']] = $row['count'];
                                                }
                                                $stmt->close();

                                                $total_leads = array_sum($lead_counts);

                                                if ($total_leads > 0) {
                                                    echo '
                    <div class="col-xl-2 col-lg-3 col-md-6 col-sm-12 mb-4">
                        <div class="card border-left-success shadow h-100">
                            <div class="card-header bg-light py-3 d-flex justify-content-between align-items-center">
                                <h6 class="m-0 font-weight-bold text-success" style= "font-size:0.9rem">' . htmlspecialchars($executive['name']) . '</h6>
                                <span class="badge badge-success" style="font-size: 0.8rem;">
                                    Updated Today: ' . $total_leads . '
                                </span>
                            </div>
                            <div class="card-body" style="font-size: 0.9rem;">
                                <h6 class="text-xs font-weight-bold text-secondary text-uppercase mb-2">Status Breakdown</h6>';

                                                    foreach ($statusMap as $status_code => $status_label) {
                                                        $count = $lead_counts[$status_code] ?? 0;
                                                        if ($count > 0) {
                                                            echo '
                                <div class="d-flex justify-content-between mb-1">
                                    <span>' . htmlspecialchars($status_label) . ':</span>
                                    <span class="badge badge-info badge-pill">' . $count . '</span>
                                </div>';
                                                        }
                                                    }

                                                    echo '      </div>
                        </div>
                    </div>';
                                                }
                                            }
                                        } else {
                                            echo '<div class="col-12"><p class="text-muted">No sales executives found.</p></div>';
                                        }
                                        break;
                                    case "today":
                                        $statusMap = [
                                            0 => "Uncontacted Leads",
                                            1 => "Prospects",
                                            2 => "Qualified",
                                            3 => "Solution Fit",
                                            4 => "Proposal Stage",
                                            5 => "Negotiation",
                                            6 => "Closed Won",
                                            7 => "Closed Lost"
                                        ];

                                        // Fetch executives
                                        $executives_qry = $conn->query("SELECT id, CONCAT(firstname, ' ', lastname) as name 
                                    FROM users 
                                    WHERE type = 2 
                                    ORDER BY firstname ASC");

                                        if ($executives_qry->num_rows > 0) {
                                            while ($executive = $executives_qry->fetch_assoc()) {
                                                $executive_id = $executive['id'];

                                                // ? Only count leads with follow-up today
                                                $stmt = $conn->prepare("
                SELECT l.status, COUNT(*) as count 
                FROM lead_list l
                INNER JOIN client_list c ON c.lead_id = l.id
                WHERE l.assigned_to = ? 
                  AND DATE(c.follow_up_date) = CURDATE()
                GROUP BY l.status
            ");
                                                $stmt->bind_param("i", $executive_id);
                                                $stmt->execute();
                                                $result = $stmt->get_result();

                                                $lead_counts = [];
                                                while ($row = $result->fetch_assoc()) {
                                                    $lead_counts[$row['status']] = $row['count'];
                                                }
                                                $stmt->close();

                                                $total_leads = array_sum($lead_counts);

                                                if ($total_leads > 0) {
                                                    echo '
                <div class="col-xl-2 col-lg-3 col-md-6 col-sm-12 mb-4">
                    <div class="card border-left-warning shadow h-100">
                        <div class="card-header bg-light py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-warning"> style= "font-size:0.9rem">' . htmlspecialchars($executive['name']) . '</h6>
                            <span class="badge badge-warning" style="font-size: 0.8rem;">
                                Today\'s Follow-ups: ' . $total_leads . '
                            </span>
                        </div>
                        <div class="card-body" style="font-size: 0.9rem;">
                            <h6 class="text-xs font-weight-bold text-secondary text-uppercase mb-2">Status Breakdown</h6>';

                                                    foreach ($statusMap as $status_code => $status_label) {
                                                        $count = $lead_counts[$status_code] ?? 0;
                                                        if ($count > 0) {
                                                            echo '
                            <div class="d-flex justify-content-between mb-1">
                                <span>' . htmlspecialchars($status_label) . ':</span>
                                <span class="badge badge-info badge-pill">' . $count . '</span>
                            </div>';
                                                        }
                                                    }

                                                    echo '      </div>
                    </div>
                </div>';
                                                }
                                            }
                                        } else {
                                            echo '<div class="col-12"><p class="text-muted">No sales executives found.</p></div>';
                                        }
                                        break;
                                    case "upcomming":
                                        $statusMap = [
                                            0 => "Uncontacted Leads",
                                            1 => "Prospects",
                                            2 => "Qualified",
                                            3 => "Solution Fit",
                                            4 => "Proposal Stage",
                                            5 => "Negotiation",
                                            6 => "Closed Won",
                                            7 => "Closed Lost"
                                        ];

                                        $executives_qry = $conn->query("SELECT id, CONCAT(firstname, ' ', lastname) as name 
                                    FROM users 
                                    WHERE type = 2 
                                    ORDER BY firstname ASC");

                                        if ($executives_qry->num_rows > 0) {
                                            while ($executive = $executives_qry->fetch_assoc()) {
                                                $executive_id = $executive['id'];

                                                $stmt = $conn->prepare("
                SELECT l.status, COUNT(*) as count 
                FROM lead_list l
                INNER JOIN client_list c ON c.lead_id = l.id
                WHERE l.assigned_to = ? 
                  AND DATE(c.follow_up_date) > CURDATE()
                GROUP BY l.status
            ");
                                                $stmt->bind_param("i", $executive_id);
                                                $stmt->execute();
                                                $result = $stmt->get_result();

                                                $lead_counts = [];
                                                while ($row = $result->fetch_assoc()) {
                                                    $lead_counts[$row['status']] = $row['count'];
                                                }
                                                $stmt->close();

                                                $total_leads = array_sum($lead_counts);

                                                if ($total_leads > 0) {
                                                    echo '
                <div class="col-xl-2 col-lg-3 col-md-6 col-sm-12 mb-4">
                    <div class="card border-left-info shadow h-100">
                        <div class="card-header bg-light py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-info" style= "font-size:0.9rem">' . htmlspecialchars($executive['name']) . '</h6>
                            <span class="badge badge-info" style="font-size: 0.8rem;">
                                Upcoming Follow-ups: ' . $total_leads . '
                            </span>
                        </div>
                        <div class="card-body" style="font-size: 0.9rem;">
                            <h6 class="text-xs font-weight-bold text-secondary text-uppercase mb-2">Status Breakdown</h6>';

                                                    foreach ($statusMap as $status_code => $status_label) {
                                                        $count = $lead_counts[$status_code] ?? 0;
                                                        if ($count > 0) {
                                                            echo '
                            <div class="d-flex justify-content-between mb-1">
                                <span>' . htmlspecialchars($status_label) . ':</span>
                                <span class="badge badge-info badge-pill">' . $count . '</span>
                            </div>';
                                                        }
                                                    }

                                                    echo '      </div>
                    </div>
                </div>';
                                                }
                                            }
                                        } else {
                                            echo '<div class="col-12"><p class="text-muted">No sales executives found.</p></div>';
                                        }
                                        break;
                                    case "overdue":
                                        $statusMap = [
                                            0 => "Uncontacted Leads",
                                            1 => "Prospects",
                                            2 => "Qualified",
                                            3 => "Solution Fit",
                                            4 => "Proposal Stage",
                                            5 => "Negotiation",
                                            6 => "Closed Won",
                                            7 => "Closed Lost"
                                        ];

                                        $executives_qry = $conn->query("SELECT id, CONCAT(firstname, ' ', lastname) as name 
                                    FROM users 
                                    WHERE type = 2 
                                    ORDER BY firstname ASC");

                                        if ($executives_qry->num_rows > 0) {
                                            while ($executive = $executives_qry->fetch_assoc()) {
                                                $executive_id = $executive['id'];

                                                $stmt = $conn->prepare("
                SELECT l.status, COUNT(*) as count 
                FROM lead_list l
                INNER JOIN client_list c ON c.lead_id = l.id
                WHERE l.assigned_to = ? 
                  AND DATE(c.follow_up_date) < CURDATE()
                GROUP BY l.status
            ");
                                                $stmt->bind_param("i", $executive_id);
                                                $stmt->execute();
                                                $result = $stmt->get_result();

                                                $lead_counts = [];
                                                while ($row = $result->fetch_assoc()) {
                                                    $lead_counts[$row['status']] = $row['count'];
                                                }
                                                $stmt->close();

                                                $total_leads = array_sum($lead_counts);

                                                if ($total_leads > 0) {
                                                    echo '
                <div class="col-xl-2 col-lg-3 col-md-6 col-sm-12 mb-4">
                    <div class="card border-left-danger shadow h-100">
                        <div class="card-header bg-light py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-danger" style= "font-size:0.9rem">' . htmlspecialchars($executive['name']) . '</h6>
                            <span class="badge badge-danger" style="font-size: 0.8rem;">
                                Overdue Follow-ups: ' . $total_leads . '
                            </span>
                        </div>
                        <div class="card-body" style="font-size: 0.9rem;">
                            <h6 class="text-xs font-weight-bold text-secondary text-uppercase mb-2">Status Breakdown</h6>';

                                                    foreach ($statusMap as $status_code => $status_label) {
                                                        $count = $lead_counts[$status_code] ?? 0;
                                                        if ($count > 0) {
                                                            echo '
                            <div class="d-flex justify-content-between mb-1">
                                <span>' . htmlspecialchars($status_label) . ':</span>
                                <span class="badge badge-info badge-pill">' . $count . '</span>
                            </div>';
                                                        }
                                                    }

                                                    echo '      </div>
                    </div>
                </div>';
                                                }
                                            }
                                        } else {
                                            echo '<div class="col-12"><p class="text-muted">No sales executives found.</p></div>';
                                        }
                                        break;
                                }
                            }
                            ?>


                        </div>

                        
                        <div class="modal-body">
                            <!-- Your table goes here -->
                            <table class="table table-striped table-bordered second">
                                <thead class="bg-light">
                                    <tr class="border-0">
                                        <th class="border-0"></th>
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
                                <tbody id="modal_table">
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
                                                <span class="badge badge-info">
                                                    <?= $statusMap[$row['status']] ?? 'Unknown' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if (in_array($_settings->userdata('type'), [1, 3])): ?>
                                                    <a class="btn btn-sm btn-light border" href="./?page=view_lead&id=<?= $row['id'] ?>"><i
                                                            class="fa fa-search"></i></a>
                                                <?php endif; ?>
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

                            <!-- OR directly paste the <table> code -->
                        </div>
                    </div>
                </div>
            </div>
            
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const tbody = document.querySelector('#modal_table'); // fixed ID selector
                    const rows = tbody.querySelectorAll('tr');

                    rows.forEach((row) => {
                        row.addEventListener('click', function(e) {
                            // Don't trigger if clicking an <a> or inside <a>
                            if (e.target.tagName.toLowerCase() === 'a' || e.target.closest('a')) return;

                            // Find the first <a> inside this row
                            const link = row.querySelector('td a');
                            if (link) {
                                window.open(link.href, '_blank'); // open in new tab
                            }
                        });
                    });
                });
            </script>

            <!-- <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> -->
            <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.6.2/js/bootstrap.bundle.min.js"></script>
            
            <!-- chatbot -->
            <div id="chatbot-container"></div>



            <script>
                // Dynamically load chatbot HTML
                fetch("chatbot/chatbot.php")
                    .then(response => response.text())
                    .then(html => {
                        const container = document.getElementById("chatbot-container");
                        container.innerHTML = html;

                        // Re-run any <script> tags inside the loaded HTML
                        const scripts = container.querySelectorAll("script");
                        scripts.forEach(oldScript => {
                            const newScript = document.createElement("script");
                            if (oldScript.src) {
                                // If the script has a src attribute, re-load it
                                newScript.src = oldScript.src;
                            } else {
                                // Inline script: copy its content
                                newScript.textContent = oldScript.textContent;
                            }
                            document.body.appendChild(newScript);
                        });
                    })
                    .catch(err => console.error("Failed to load chatbot:", err));
            </script>

            



            <?php
            if ($_settings->userdata('type') == 2):

                // maintainance

                include 'dashboard_new.php';
                exit;
                // end of it
                $user_id = $conn->real_escape_string($_settings->userdata('id'));
                $today = date('Y-m-d');




                // ---------- TODAY'S LEADS ----------
                $today_leads = [];
                // $stmt = $conn->prepare("
                //     SELECT l.* 
                //     FROM lead_list l
                //     JOIN client_list cl ON cl.lead_id = l.id
                //     WHERE l.assigned_to = ? 
                //     AND DATE(cl.calling_date) = CURDATE()
                //     ORDER BY l.id ASC
                // ");
                $stmt = $conn->prepare("
                        SELECT l.* 
                        FROM lead_list l
                        JOIN client_list cl ON cl.lead_id = l.id
                        WHERE l.assigned_to = ? 
                        AND DATE(cl.calling_date) = CURDATE()
                        AND l.id NOT IN (
                            SELECT lead_id FROM log_list WHERE is_updated = 1
                        )
                        ORDER BY l.id ASC
                    ");

                $stmt->bind_param('i', $user_id);
                $stmt->execute();
                $result = $stmt->get_result();

                while ($row = $result->fetch_assoc()) {
                    $row['is_completed'] = in_array($row['status'], [1, 2, 3, 4, 5, 6, 7]);
                    $today_leads[] = $row;
                }
                $stmt->close();

                $total_leads = count($today_leads);
                $current_index = isset($_GET['lead_index']) ? intval($_GET['lead_index']) : 0;
                if ($current_index < 0) $current_index = 0;
                if ($current_index >= $total_leads) $current_index = max(0, $total_leads - 1);

                $lead = $today_leads[$current_index] ?? null;

                if ($lead) {
                    $lead_id = $lead['id'];

                    // Check if lead has updated log
                    $is_updated = 0;
                    $check_update_q = $conn->query("SELECT COUNT(*) as cnt FROM log_list WHERE lead_id = '{$lead_id}' AND is_updated = 1");
                    if ($check_update_q) {
                        $is_updated = $check_update_q->fetch_assoc()['cnt'] > 0 ? 1 : 0;
                    }

                    // Client Info
                    $stmt = $conn->prepare("SELECT * FROM client_list WHERE lead_id = ?");
                    $stmt->bind_param('i', $lead_id);
                    $stmt->execute();
                    $client = $stmt->get_result()->fetch_assoc();
                    $stmt->close();

                    // Contacts
                    $contacts = [];
                    $stmt = $conn->prepare("SELECT * FROM contact_persons WHERE lead_id = ?");
                    $stmt->bind_param('i', $lead_id);
                    $stmt->execute();
                    $contacts_result = $stmt->get_result();
                    while ($row = $contacts_result->fetch_assoc()) {
                        $contacts[] = $row;
                    }
                    $stmt->close();
                }

                // ---------- USER NAME MAP ----------
                $user_arr = [];
                $users = $conn->query("SELECT id, firstname, middlename, lastname FROM users");
                while ($row = $users->fetch_assoc()) {
                    $full_name = $row['lastname'] . ', ' . $row['firstname'];
                    if (!empty($row['middlename'])) {
                        $full_name .= ' ' . $row['middlename'];
                    }
                    $user_arr[$row['id']] = $full_name;
                }

                // ---------- OLD FOLLOW-UPS ----------
                $old_followups = [];
                $stmt = $conn->prepare("
                    SELECT 
                        l.*, 
                        c.company_name,
                        cp.name AS contact_name,
                        cp.contact AS contact_phone,
                        cp.email AS contact_email,
                        cp.designation AS contact_designation
                    FROM lead_list l
                    LEFT JOIN client_list c ON c.lead_id = l.id
                    LEFT JOIN contact_persons cp ON cp.lead_id = l.id
                    WHERE l.assigned_to = ? 
                    AND DATE(l.date_created) < ? 
                    AND l.status NOT IN (6, 7)
                    ORDER BY l.date_created DESC 
                    LIMIT 5;

                    ");
                $stmt->bind_param('is', $user_id, $today);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $old_followups[] = $row;
                }
                $stmt->close();

                // ---------- STATUS LABEL FUNCTION ----------
                function get_status_label($status)
                {
                    $status_labels = [
                        0 => 'Lead ? Uncontacted',
                        1 => 'Prospect ? Contact Made',
                        2 => 'Qualified ? Need Validated',
                        3 => 'Solution Fit / Discovery',
                        4 => 'Proposal / Value Proposition',
                        5 => 'Negotiation',
                        6 => 'Closed ? Won',
                        7 => 'Closed ? Lost',
                    ];
                    return $status_labels[$status] ?? 'N/A';
                }

                $completed_count = count(array_filter($today_leads, fn($l) => $l['is_completed']));
                $not_completed_count = $total_leads - $completed_count;
            ?>

                <!-- nav bar -->
                <ul class="nav nav-tabs mb-3">
                    <li class="nav-item">
                        <a class="nav-link active" id="allLeadsTab" href="#" onclick="showSection('all')">All Leads</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="todayLeadsTab" href="#" onclick="showSection('today')">Todays Leads</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="followUpLeadsTab" href="#" onclick="showSection('follow-ups')">Follow ups</a>
                    </li>
                </ul>

                <!-- end of nav bar -->

                <?php
                $active_tab = $_GET['tab'] ?? 'all';
                ?>

                <!-- section of todays leads section -->
                <div id="todayLeadsSection" style="display: <?= $active_tab === 'today' ? 'block' : 'none' ?>;">
                    <div class="row">
                        <!-- LEFT COLUMN: Todays Lead Details -->
                        <style>
                            .lead-info-label {
                                font-weight: 600;
                                color: #555;
                            }

                            .lead-info-value {
                                color: #222;
                            }

                            .lead-section-title {
                                font-size: 1rem;
                                font-weight: 600;
                                border-bottom: 1px solid #ddd;
                                margin-bottom: 0.5rem;
                                padding-bottom: 0.3rem;
                                color: #0d6efd;
                            }

                            .lead-details p {
                                margin-bottom: 0.4rem;
                            }

                            .disabled {
                                pointer-events: none;
                                opacity: 0.6;
                            }
                        </style>

                        <div class="col-md-8">
                            <div class="card shadow-sm mb-4" style="height:auto">
                                <div class="card-header bg-primary text-white text-center">
                                    <h5 class="mb-0" style="color: #f9f9f9;">Todays Assigned Lead (<?= $current_index + 1 ?> / <?= $total_leads ?>)</h5>
                                </div>

                                <div class="card-body lead-details">
                                    <?php if ($lead): ?>
                                        <div class="row">
                                            <!-- Company Info -->
                                            <div class="col-md-6">
                                                <div class="lead-section-title">Company Information</div>
                                                <p><span class="lead-info-label">Name:</span> <span class="lead-info-value"><?= htmlspecialchars($client['company_name'] ?? 'N/A') ?></span></p>
                                                <p><span class="lead-info-label">Type:</span> <span class="lead-info-value"><?= htmlspecialchars($client['company_type'] ?? 'N/A') ?></span></p>
                                                <p><span class="lead-info-label">Country:</span> <span class="lead-info-value"><?= htmlspecialchars($client['country'] ?? 'N/A') ?></span></p>
                                            </div>

                                            <!-- Lead Info -->
                                            <div class="col-md-6">
                                                <div class="lead-section-title">Lead Information</div>
                                                <!-- <p><span class="lead-info-label">Status:</span> <span class="lead-info-value"><?= get_status_label($lead['status']) ?></span></p> -->
                                                <p>
                                                    <span class="lead-info-label">Status:</span>
                                                    <span class="lead-info-value"><?= get_status_label($lead['status']) ?></span>
                                                    <i title="<?= $is_updated ? 'Log updated' : 'No recent update' ?>" class="fas <?= $is_updated ? 'fa-check-circle text-success' : 'fa-times-circle text-danger' ?>"></i>
                                                </p>
                                                <p><span class="lead-info-label">Remarks:</span> <span class="lead-info-value"><?= nl2br(htmlspecialchars($lead['remarks'] ?? 'N/A')) ?></span></p>
                                            </div>

                                            <!-- Contacts -->
                                            <?php if (!empty($contacts)) : ?>
                                                <div class="col-md-12">
                                                    <div class="lead-section-title">Contact Information</div>
                                                    <div class="row">
                                                        <?php foreach ($contacts as $index => $contact): ?>
                                                            <div class="col-md-6 col-lg-4 mb-3">
                                                                <div class="border rounded p-3 h-100 shadow-sm bg-light">
                                                                    <p><span class="lead-info-label">Name:</span> <span class="lead-info-value"><?= htmlspecialchars($contact['name'] ?? 'N/A') ?></span></p>
                                                                    <p><span class="lead-info-label">Designation:</span> <span class="lead-info-value"><?= htmlspecialchars($contact['designation'] ?? 'N/A') ?></span></p>
                                                                    <p><span class="lead-info-label">Email:</span> <span class="lead-info-value"><?= htmlspecialchars($contact['email'] ?? 'N/A') ?></span></p>
                                                                    <p><span class="lead-info-label">Phone:</span> <span class="lead-info-value"><?= htmlspecialchars($contact['contact'] ?? 'N/A') ?></span></p>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Buttons Section -->
                                        <button id="prev-btn" class="btn btn-secondary" <?= $current_index <= 0 ? 'disabled' : '' ?>>
                                            <i class="fas fa-arrow-left"></i> Previous
                                        </button>

                                        <!-- <button id="btn-update" class="btn btn-success">
                                        <i class="fas fa-save"></i> Update
                                    </button> -->
                                        <button class="btn btn-success update-today-btn" data-id="<?= $lead['id'] ?>">
                                            <i class="fas fa-save"></i> Update
                                        </button>

                                        <?php if ($current_index < $total_leads - 1): ?>
                                            <a id="next-btn" href="?lead_index=<?= $current_index + 1 ?>" class="btn btn-primary disabled" aria-disabled="true">
                                                Next <i class="fas fa-arrow-right"></i>
                                            </a>
                                        <?php else: ?>
                                            <button id="next-btn" class="btn btn-primary" disabled>
                                                Next <i class="fas fa-arrow-right"></i>
                                            </button>
                                        <?php endif; ?>

                                    <?php else: ?>
                                        <div class="alert alert-info">No leads available.</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- RIGHT COLUMN: Lead Summary with Graph -->
                        <style>
                            .summary-card .card-title {
                                font-size: 1.2rem;
                                font-weight: 600;
                            }

                            .summary-card p {
                                margin-bottom: 0.5rem;
                                font-size: 0.95rem;
                            }

                            .progress {
                                font-size: 0.85rem;
                                height: 24px;
                            }

                            .progress-bar {
                                line-height: 24px;
                                padding-left: 6px;
                            }

                            .progress-bar.bg-warning {
                                color: #212529;
                            }
                        </style>

                        <style>
                            .bg-light-primary {
                                background-color: rgba(67, 97, 238, 0.1);
                            }

                            .bg-light-success {
                                background-color: rgba(40, 167, 69, 0.1);
                            }

                            .bg-light-warning {
                                background-color: rgba(255, 193, 7, 0.1);
                            }
                        </style>
                        <div class="col-md-4">
                            <div class="card border-0 shadow-sm mb-4">
                                <div class="card-header bg-primary text-white py-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0">
                                            <i class="fas fa-calendar-day me-2"></i>
                                            <span class="ms-2 text-white">Todays Summary</span> <!-- Added ms-2 (margin-start) -->
                                        </h5>
                                        <span class="badge bg-light text-primary">
                                            <?= date('M j, Y') ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="card-body p-4">
                                    <?php
                                    // Get total assigned leads for today (including completed ones)
                                    $summary_stmt = $conn->prepare("
                SELECT l.id 
                FROM lead_list l
                JOIN client_list cl ON cl.lead_id = l.id
                WHERE l.assigned_to = ? 
                AND DATE(cl.calling_date) = CURDATE()
            ");
                                    $summary_stmt->bind_param('i', $user_id);
                                    $summary_stmt->execute();
                                    $summary_result = $summary_stmt->get_result();

                                    $total_leads = 0;
                                    $completed_count = 0;

                                    while ($lead_row = $summary_result->fetch_assoc()) {
                                        $total_leads++;
                                        $lead_id = $lead_row['id'];

                                        $check = $conn->query("SELECT COUNT(*) as cnt FROM log_list WHERE lead_id = {$lead_id} AND is_updated = 1");
                                        $updated = $check && $check->num_rows ? (int)$check->fetch_assoc()['cnt'] : 0;
                                        if ($updated > 0) {
                                            $completed_count++;
                                        }
                                    }

                                    $summary_stmt->close();
                                    $not_completed_count = $total_leads - $completed_count;
                                    $completed_percent = $total_leads > 0 ? ($completed_count / $total_leads) * 100 : 0;
                                    $not_completed_percent = 100 - $completed_percent;
                                    ?>

                                    <div class="row g-2 mb-4">
                                        <div class="col-4 text-center">
                                            <div class="p-3 bg-light-primary rounded-3">
                                                <h3 class="mb-0 text-primary"><?= $total_leads ?></h3>
                                                <small class="text-muted">Total</small>
                                            </div>
                                        </div>
                                        <div class="col-4 text-center">
                                            <div class="p-3 bg-light-success rounded-3">
                                                <h3 class="mb-0 text-success"><?= $completed_count ?></h3>
                                                <small class="text-muted">Called</small>
                                            </div>
                                        </div>
                                        <div class="col-4 text-center">
                                            <div class="p-3 bg-light-warning rounded-3">
                                                <h3 class="mb-0 text-warning"><?= $not_completed_count ?></h3>
                                                <small class="text-muted" style="white-space: nowrap;">Pending</small>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Completion Progress Bar -->
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="text-muted small">Completion Progress</span>
                                            <span class="fw-bold small" id="progress-percent">0%</span>
                                        </div>
                                        <div class="progress" style="height: 8px;">
                                            <div class="progress-bar bg-success progress-bar-striped progress-bar-animated"
                                                id="lead-progress-bar"
                                                data-final="<?= round($completed_percent) ?>"
                                                style="width: 0%">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center mt-4">
                                        <div class="text-center">
                                            <i class="fas fa-check-circle text-success fs-4 mb-2"></i>
                                            <p class="mb-0 small"><?= $completed_count ?> Completed</p>
                                        </div>
                                        <div class="text-center">
                                            <i class="fas fa-hourglass-half text-warning fs-4 mb-2"></i>
                                            <p class="mb-0 small"><?= $not_completed_count ?> Pending</p>
                                        </div>
                                        <div class="text-center">
                                            <i class="fas fa-tasks text-primary fs-4 mb-2"></i>
                                            <p class="mb-0 small"><?= $total_leads ?> Total</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>


                <style>
                    .card-header button {
                        font-weight: 500;
                    }

                    .card-body p {
                        margin-bottom: 0.5rem;
                    }
                </style>

                <!-- div for all leads section -->
                <div id="allLeadsSection" style="display: <?= $active_tab === 'all' ? 'block' : 'none' ?>;">
                    <!-- modified code for all leads section -->
                    <?php if ($_settings->userdata('type') == 2): ?>
                        <!-- Add this section after the Callback Summary section -->

                        <div class="row">
                            <div class="col-md-8">
                                <div class="row">
                                    <div class="col-12">
                                        <div class="card shadow-sm">
                                            <div class="card-header bg-primary text-white">
                                                <h5 class="mb-0">All Leads Status</h5>
                                            </div>
                                            <div class="card-body">
                                                <!-- Search and Filter Controls -->
                                                <div class="card-header bg-light">
                                                    <form method="get" class="form-inline">
                                                        <!-- Company Name Filter -->
                                                        <div class="input-group mr-2 mb-2">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text"><i class="fas fa-building"></i></span>
                                                            </div>
                                                            <input
                                                                type="text"
                                                                name="company_search"
                                                                class="form-control"
                                                                placeholder="Company name"
                                                                value="<?= htmlspecialchars($_GET['company_search'] ?? '') ?>">
                                                        </div>

                                                        <!-- Date Filter -->
                                                        <div class="input-group mr-2 mb-2">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                                                            </div>
                                                            <input
                                                                type="date"
                                                                name="date_search"
                                                                class="form-control"
                                                                value="<?= htmlspecialchars($_GET['date_search'] ?? '') ?>">
                                                        </div>

                                                        <!-- Filter Button -->
                                                        <button type="submit" class="btn btn-primary mr-1 mb-2">
                                                            <i class="fas fa-filter"></i> Filter
                                                        </button>

                                                        <!-- Reset Button -->
                                                        <a href="<?= strtok($_SERVER['REQUEST_URI'], '?') ?>" class="btn btn-secondary mb-2">
                                                            <i class="fas fa-undo"></i> Reset
                                                        </a>
                                                    </form>
                                                </div>
                                                <?php
                                                // Fetch all leads assigned to this executive (with filters)
                                                $all_leads = [];

                                                $query = "
                                                SELECT l.*, cl.company_name
                                                FROM lead_list l
                                                JOIN client_list cl ON cl.lead_id = l.id
                                                WHERE l.assigned_to = ?
                                                AND l.delete_flag = 0
                                            ";

                                                $params = [$user_id];
                                                $types = "i";

                                                // Apply company search filter if present
                                                if (!empty($_GET['company_search'])) {
                                                    $query .= " AND cl.company_name LIKE ?";
                                                    $params[] = '%' . $_GET['company_search'] . '%';
                                                    $types .= "s";
                                                }

                                                // Apply date search filter if present
                                                if (!empty($_GET['date_search'])) {
                                                    $query .= " AND DATE(l.date_created) = ?";
                                                    $params[] = $_GET['date_search'];
                                                    $types .= "s";
                                                }

                                                $query .= " ORDER BY l.date_created DESC";

                                                // Prepare, bind, execute
                                                $all_stmt = $conn->prepare($query);
                                                $all_stmt->bind_param($types, ...$params);
                                                $all_stmt->execute();
                                                $all_result = $all_stmt->get_result();
                                                while ($row = $all_result->fetch_assoc()) {
                                                    $all_leads[] = $row;
                                                }
                                                $all_stmt->close();

                                                // Total assigned leads to this executive
                                                $total_leads = count($all_leads);

                                                // Index navigation logic
                                                $current_all_index = isset($_GET['all_lead_index']) ? intval($_GET['all_lead_index']) : 0;
                                                if ($current_all_index < 0) $current_all_index = 0;
                                                if ($current_all_index >= $total_leads) $current_all_index = max(0, $total_leads - 1);

                                                $all_lead = $all_leads[$current_all_index] ?? null;

                                                if ($all_lead) {
                                                    $all_lead_id = $all_lead['id'];

                                                    // Client Info
                                                    $all_client = [];
                                                    $stmt = $conn->prepare("SELECT * FROM client_list WHERE lead_id = ?");
                                                    $stmt->bind_param('i', $all_lead_id);
                                                    $stmt->execute();
                                                    $all_client = $stmt->get_result()->fetch_assoc();
                                                    $stmt->close();

                                                    // Contacts
                                                    $all_contacts = [];
                                                    $stmt = $conn->prepare("SELECT * FROM contact_persons WHERE lead_id = ?");
                                                    $stmt->bind_param('i', $all_lead_id);
                                                    $stmt->execute();
                                                    $contacts_result = $stmt->get_result();
                                                    while ($row = $contacts_result->fetch_assoc()) {
                                                        $all_contacts[] = $row;
                                                    }
                                                    $stmt->close();

                                                    // Lead history
                                                    $history = [];
                                                    $stmt = $conn->prepare("
                                                    SELECT l.*, u.firstname, u.lastname 
                                                    FROM log_list l
                                                    LEFT JOIN users u ON l.user_id = u.id
                                                    WHERE l.lead_id = ?
                                                    ORDER BY l.date_created DESC
                                                ");
                                                    $stmt->bind_param('i', $all_lead_id);
                                                    $stmt->execute();
                                                    $history_result = $stmt->get_result();
                                                    while ($row = $history_result->fetch_assoc()) {
                                                        $history[] = $row;
                                                    }
                                                    $stmt->close();
                                                ?>

                                                    <?php
                                                    $lead_id = $all_lead['id'] ?? null;

                                                    $follow_up = null;
                                                    if ($lead_id) {
                                                        $log = $conn->query("SELECT follow_up_date FROM log_list WHERE lead_id = '{$lead_id}' ORDER BY date_created DESC LIMIT 1");
                                                        $follow_up = ($log && $log->num_rows > 0) ? $log->fetch_assoc()['follow_up_date'] : null;
                                                    }
                                                    ?>

                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <h6>Lead Information</h6>
                                                            <p><strong>Company:</strong> <?= htmlspecialchars($all_client['company_name'] ?? 'N/A') ?></p>
                                                            <p><strong>Status:</strong> <?= get_status_label($all_lead['status']) ?></p>
                                                            <p><strong>Assigned Date:</strong> <?= date('d-m-Y', strtotime($all_lead['date_created'])) ?></p>
                                                            <p><strong>Follow-up Date:</strong>
                                                                <?= !empty($follow_up) ? date('d-m-Y', strtotime($follow_up)) : 'N/A' ?>
                                                            </p>

                                                            <h6 class="mt-4">Contacts</h6>
                                                            <?php if (!empty($all_contacts)): ?>
                                                                <ul class="list-group">
                                                                    <?php foreach ($all_contacts as $contact): ?>
                                                                        <li class="list-group-item">
                                                                            <strong><?= htmlspecialchars($contact['name'] ?? 'N/A') ?></strong><br>
                                                                            <?= htmlspecialchars($contact['designation'] ?? '') ?><br>
                                                                            <i class="fa fa-phone text-success"></i> <?= htmlspecialchars($contact['contact'] ?? '') ?><br>
                                                                            <i class="fa fa-envelope text-primary"></i> <?= htmlspecialchars($contact['email'] ?? '') ?>
                                                                        </li>
                                                                    <?php endforeach; ?>
                                                                </ul>
                                                            <?php else: ?>
                                                                <p class="text-muted">No contacts available</p>
                                                            <?php endif; ?>
                                                        </div>
                                                        <!-- from here -->
                                                        <div class="col-md-6">
                                                            <h6>Lead History</h6>
                                                            <?php
                                                            // Fetch status history only
                                                            $status_history = [];
                                                            $stmt = $conn->prepare("
                                                            SELECT h.old_status, h.new_status, h.changed_at AS timestamp, u.firstname, u.lastname 
                                                            FROM lead_status_history h
                                                            LEFT JOIN users u ON h.changed_by = u.id
                                                            WHERE h.lead_id = ?
                                                            ORDER BY h.changed_at DESC
                                                        ");
                                                            $stmt->bind_param('i', $all_lead_id);
                                                            $stmt->execute();
                                                            $result = $stmt->get_result();

                                                            // Preload all logs once
                                                            $log_stmt = $conn->prepare("
                                                            SELECT id, remarks, date_created 
                                                            FROM log_list 
                                                            WHERE lead_id = ? 
                                                            ORDER BY date_created DESC
                                                        ");
                                                            $log_stmt->bind_param('i', $all_lead_id);
                                                            $log_stmt->execute();
                                                            $log_result = $log_stmt->get_result();
                                                            $logs = $log_result->fetch_all(MYSQLI_ASSOC);
                                                            $log_stmt->close();

                                                            while ($row = $result->fetch_assoc()) {
                                                                // Attach latest remark at or before this timestamp
                                                                foreach ($logs as $log) {
                                                                    if (strtotime($log['date_created']) <= strtotime($row['timestamp'])) {
                                                                        $row['remarks'] = $log['remarks'];
                                                                        break;
                                                                    }
                                                                }
                                                                $status_history[] = $row;
                                                            }
                                                            $stmt->close();
                                                            ?>

                                                            <?php if (!empty($status_history)): ?>
                                                                <div class="d-flex justify-content-end"> <!-- Align whole block to the right -->
                                                                    <div class="timeline" style="max-height:300px; overflow-y:auto; padding-right:10px; width: 100%; max-width: 600px;"> <!-- Optional width -->
                                                                        <?php foreach ($status_history as $item): ?>
                                                                            <div class="timeline-item">
                                                                                <div class="timeline-point bg-info"></div>
                                                                                <div class="timeline-content">
                                                                                    <div class="d-flex justify-content-between align-items-center">
                                                                                        <h6 class="mb-1">Status Changed</h6>
                                                                                        <small class="text-muted"><?= date('d-m-Y H:i', strtotime($item['timestamp'])) ?></small>
                                                                                    </div>
                                                                                    <div class="mt-2">
                                                                                        <div>From: <strong class="text-danger"><?= get_status_label($item['old_status']) ?></strong></div>
                                                                                        <div>To: <strong class="text-success"><?= get_status_label($item['new_status']) ?></strong></div>

                                                                                        <?php if (!empty($item['remarks'])): ?>
                                                                                            <div class="mt-1">Remark: <em><?= nl2br(htmlspecialchars($item['remarks'])) ?></em></div>
                                                                                        <?php endif; ?>

                                                                                        <div class="mt-1">By: <strong><?= htmlspecialchars(($item['firstname'] ?? '') . ' ' . ($item['lastname'] ?? '')) ?></strong></div>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        <?php endforeach; ?>
                                                                    </div>
                                                                </div>
                                                            <?php else: ?>
                                                                <p class="text-muted text-end">No timeline history available</p>
                                                            <?php endif; ?>
                                                        </div>


                                                        <!-- till hear -->
                                                    </div>

                                                    <div class="row mt-4">
                                                        <div class="col-12 text-center">
                                                            <?php
                                                            $base_url = strtok($_SERVER['REQUEST_URI'], '?');
                                                            $query_params = $_GET;
                                                            ?>

                                                            <!-- Previous Button -->
                                                            <?php if ($current_all_index > 0): ?>
                                                                <?php $query_params['all_lead_index'] = $current_all_index - 1; ?>
                                                                <a href="<?= $base_url . '?' . http_build_query($query_params) ?>" class="btn btn-secondary">
                                                                    <i class="fas fa-arrow-left"></i> Previous
                                                                </a>
                                                            <?php else: ?>
                                                                <span class="btn btn-secondary disabled" style="background-color: #ff407b; border-color: #ff407b;">
                                                                    <i class="fas fa-arrow-left"></i> Previous
                                                                </span>
                                                            <?php endif; ?>

                                                            <!-- Update Button -->
                                                            <button class="btn btn-success update-all-btn" data-id="<?= $all_lead_id ?>">
                                                                <i class="fas fa-save"></i> Update
                                                            </button>

                                                            <!-- Next Button -->
                                                            <?php if ($current_all_index < $total_leads - 1): ?>
                                                                <?php $query_params['all_lead_index'] = $current_all_index + 1; ?>
                                                                <a href="<?= $base_url . '?' . http_build_query($query_params) ?>" class="btn btn-primary">
                                                                    Next <i class="fas fa-arrow-right"></i>
                                                                </a>
                                                            <?php else: ?>
                                                                <a class="btn btn-primary disabled">
                                                                    Next <i class="fas fa-arrow-right"></i>
                                                                </a>
                                                            <?php endif; ?>

                                                            <p class="mt-2">Lead <?= $current_all_index + 1 ?> of <?= $total_leads ?></p>
                                                        </div>
                                                    </div>
                                                <?php } else { ?>
                                                    <div class="alert alert-info">No leads available</div>
                                                <?php } ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!--end of  all lead section  -->

                            </div>
                            <div class="col-md-4">
                                <!-- summary card -->
                                <div class="card shadow-sm summary-card">
                                    <div class="card-header bg-info text-white py-3">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h5 class="mb-0">
                                                <i class="fas fa-chart-pie me-2"></i>
                                                <span class="ms-2 text-white">All Leads Summary</span>
                                            </h5>
                                            <span class="badge bg-light text-info">Overall</span>
                                        </div>
                                    </div>
                                    <div class="card-body p-4">
                                        <?php
                                        // Get status counts for all leads (assuming $all_leads is already populated from the previous section)
                                        $status_counts = [];
                                        foreach ($all_leads as $lead) {
                                            $status = $lead['status'];
                                            $status_counts[$status] = ($status_counts[$status] ?? 0) + 1;
                                        }

                                        // Calculate total leads assigned (already available as $total_leads)
                                        // $total_leads = count($all_leads); // This line is commented out as $total_leads is already defined earlier
                                        ?>

                                        <div class="row g-2 mb-4">
                                            <div class="col-12 text-center">
                                                <div class="p-3 bg-light-info rounded-3">
                                                    <h3 class="mb-0 text-info"><?= $total_leads ?></h3>
                                                    <small class="text-muted">Total Assigned Leads</small>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mt-3">
                                            <h6 class="text-center mb-3">Status Breakdown</h6>
                                            <div class="row row-cols-2 g-2">
                                                <?php
                                                // Define some light background colors for badges if not already defined globally
                                                // These are just examples, you might want to map specific status IDs to specific colors
                                                $status_colors = [
                                                    '0' => 'primary',    // Example: Pending
                                                    '1' => 'warning',    // Example: Follow-up
                                                    '2' => 'danger',     // Example: Not Interested
                                                    '3' => 'success',    // Example: Converted
                                                    '4' => 'secondary',  // Example: No Answer
                                                    // Add more mappings as needed
                                                ];
                                                ?>
                                                <?php foreach ($status_counts as $status => $count): ?>
                                                    <div class="col mb-2">
                                                        <div class="d-flex justify-content-between align-items-center border rounded p-2 bg-light-<?= $status_colors[$status] ?? 'secondary' ?>">
                                                            <small class="text-muted" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                                <?= get_status_label($status) ?>
                                                            </small>
                                                            <span class="badge bg-<?= $status_colors[$status] ?? 'secondary' ?> rounded-pill ms-2"><?= $count ?></span>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <style>
                                    /* Add this to your existing style block if not already present, or ensure these classes are defined */
                                    .bg-light-info {
                                        background-color: rgba(23, 162, 184, 0.1);
                                        /* Bootstrap info color with transparency */
                                    }

                                    .bg-light-primary {
                                        background-color: rgba(13, 110, 253, 0.1);
                                    }

                                    .bg-light-warning {
                                        background-color: rgba(255, 193, 7, 0.1);
                                    }

                                    .bg-light-danger {
                                        background-color: rgba(220, 53, 69, 0.1);
                                    }

                                    .bg-light-success {
                                        background-color: rgba(25, 135, 84, 0.1);
                                    }

                                    .bg-light-secondary {
                                        background-color: rgba(108, 117, 125, 0.1);
                                    }
                                </style>
                                <!-- end of summary card -->

                                <!-- Follow-up Cards -->
                                <?php
                                $today = date('Y-m-d');

                                // Today's follow-ups
                                $today_followups = $conn->query("
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
                                WHERE DATE(l.follow_up_date) = '$today' AND le.assigned_to = $user_id
                                ORDER BY l.follow_up_date ASC
                            ");

                                // Upcoming follow-ups
                                $upcoming_followups = $conn->query("
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
                                WHERE DATE(l.follow_up_date) > '$today' AND le.assigned_to = $user_id
                                ORDER BY l.follow_up_date ASC
                            ");
                                ?>


                                <div class="card shadow-sm mt-4">
                                    <div class="card-header bg-warning text-white py-3">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h5 class="mb-0">
                                                <i class="fas fa-bell me-2"></i> Follow-Ups
                                            </h5>
                                            <ul class="nav nav-tabs card-header-tabs" id="followupTabs" role="tablist">
                                                <li class="nav-item">
                                                    <a class="nav-link active" id="today-tab" data-toggle="tab" href="#today" role="tab">Todays</a>
                                                </li>
                                                <li class="nav-item">
                                                    <a class="nav-link" id="upcoming-tab" data-toggle="tab" href="#upcoming" role="tab">Upcoming</a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>


                                    <div class="card-body tab-content" id="followupTabsContent">
                                        <!-- Today -->
                                        <div class="tab-pane fade show active" id="today" role="tabpanel">
                                            <?php if ($today_followups && $today_followups->num_rows > 0): ?>
                                                <h6 class="text-muted mb-2 d-flex justify-content-between px-2">
                                                    <span>Date</span>
                                                    <span style="margin-right: 20%;">Company Name</span>
                                                </h6>
                                                <ul class="list-group">
                                                    <?php while ($row = $today_followups->fetch_assoc()): ?>

                                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                                            <?= date('d-m-Y', strtotime($row['follow_up_date'])) ?>
                                                            <span class="fw-bold"><?= htmlspecialchars($row['company_name'] ?? 'N/A') ?></span>
                                                        </li>
                                                    <?php endwhile; ?>
                                                </ul>
                                            <?php else: ?>
                                                <p class="text-muted">No follow-ups for today.</p>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Upcoming -->
                                        <div class="tab-pane fade" id="upcoming" role="tabpanel">
                                            <?php if ($upcoming_followups && $upcoming_followups->num_rows > 0): ?>
                                                <h6 class="text-muted mb-2 d-flex justify-content-between px-2">
                                                    <span>Date</span>
                                                    <span style="margin-right: 5%;">Company Name</span>
                                                </h6>
                                                <ul class="list-group">
                                                    <?php while ($row = $upcoming_followups->fetch_assoc()): ?>

                                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                                            <?= date('d-m-Y', strtotime($row['follow_up_date'])) ?>
                                                            <span class="fw-bold"><?= htmlspecialchars($row['company_name'] ?? 'N/A') ?></span>
                                                        </li>
                                                    <?php endwhile; ?>
                                                </ul>
                                            <?php else: ?>
                                                <p class="text-muted">No upcoming follow-ups.</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- End of follow-up Cards -->
                            </div>
                        </div>
                </div>

                <!-- start of follow-ups section -->
                <?php
                        $today = date('Y-m-d');
                        $user_id = $_settings->userdata('id');
                        $followup_leads = [];

                        $followup_query = "
                        SELECT le.*, cl.company_name, cl.company_type, cl.country, l.follow_up_date
                        FROM log_list l
                        INNER JOIN (
                            SELECT lead_id, MAX(id) as latest_id
                            FROM log_list
                            WHERE follow_up_date IS NOT NULL
                            GROUP BY lead_id
                        ) latest_log ON latest_log.lead_id = l.lead_id AND latest_log.latest_id = l.id
                        JOIN lead_list le ON le.id = l.lead_id
                        JOIN client_list cl ON cl.lead_id = le.id
                        WHERE DATE(l.follow_up_date) = ?
                        AND le.assigned_to = ?
                        ORDER BY l.follow_up_date ASC
                    ";

                        $followup_stmt = $conn->prepare($followup_query);
                        $followup_stmt->bind_param("si", $today, $user_id);
                        $followup_stmt->execute();
                        $result = $followup_stmt->get_result();
                        while ($row = $result->fetch_assoc()) {
                            $followup_leads[] = $row;
                        }
                        $followup_stmt->close();

                        // pagination
                        $current_index = isset($_GET['lead_index']) ? intval($_GET['lead_index']) : 0;
                        $total_leads = count($followup_leads);
                        if ($current_index < 0) $current_index = 0;
                        if ($current_index >= $total_leads) $current_index = max(0, $total_leads - 1);

                        $current_lead = $followup_leads[$current_index] ?? null;
                ?>

                <div id="followUpLeadsSection" style="display: <?= $active_tab === 'followups' ? 'block' : 'none' ?>;">

                    <div class="row">
                        <div class="col-8">
                            <div class="card shadow-sm">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0">Follow-ups Status</h5>
                                </div>
                                <div class="card-body">
                                    <?php if ($current_lead): ?>
                                        <?php
                                        $lead_id = $current_lead['id'];
                                        $client = $conn->query("SELECT * FROM client_list WHERE lead_id = {$lead_id}")->fetch_assoc();
                                        $contacts = [];
                                        $contact_q = $conn->query("SELECT * FROM contact_persons WHERE lead_id = {$lead_id}");
                                        while ($c = $contact_q->fetch_assoc()) {
                                            $contacts[] = $c;
                                        }

                                        // history
                                        $history = [];
                                        $stmt = $conn->prepare("
                                SELECT l.*, u.firstname, u.lastname 
                                FROM log_list l
                                LEFT JOIN users u ON l.user_id = u.id
                                WHERE l.lead_id = ?
                                ORDER BY l.date_created DESC
                            ");
                                        $stmt->bind_param("i", $lead_id);
                                        $stmt->execute();
                                        $res = $stmt->get_result();
                                        while ($row = $res->fetch_assoc()) {
                                            $history[] = $row;
                                        }
                                        $stmt->close();

                                        // timeline
                                        $status_history = [];
                                        $stmt = $conn->prepare("
                                SELECT h.old_status, h.new_status, h.changed_at AS timestamp, u.firstname, u.lastname 
                                FROM lead_status_history h
                                LEFT JOIN users u ON h.changed_by = u.id
                                WHERE h.lead_id = ?
                                ORDER BY h.changed_at DESC
                            ");
                                        $stmt->bind_param("i", $lead_id);
                                        $stmt->execute();
                                        $res = $stmt->get_result();

                                        // preload logs for remark mapping
                                        $log_stmt = $conn->prepare("
                                SELECT id, remarks, date_created 
                                FROM log_list 
                                WHERE lead_id = ? 
                                ORDER BY date_created DESC
                            ");
                                        $log_stmt->bind_param("i", $lead_id);
                                        $log_stmt->execute();
                                        $logs = $log_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                                        $log_stmt->close();

                                        while ($row = $res->fetch_assoc()) {
                                            foreach ($logs as $log) {
                                                if (strtotime($log['date_created']) <= strtotime($row['timestamp'])) {
                                                    $row['remarks'] = $log['remarks'];
                                                    break;
                                                }
                                            }
                                            $status_history[] = $row;
                                        }
                                        $stmt->close();

                                        $follow_up = $current_lead['follow_up_date'] ?? null;
                                        ?>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6>Lead Information</h6>
                                                <p><strong>Company:</strong> <?= htmlspecialchars($client['company_name'] ?? 'N/A') ?></p>
                                                <p><strong>Status:</strong> <?= get_status_label($current_lead['status']) ?></p>
                                                <p><strong>Assigned Date:</strong> <?= date('d-m-Y', strtotime($current_lead['date_created'])) ?></p>
                                                <p><strong>Follow-up Date:</strong>
                                                    <?= !empty($follow_up) ? date('d-m-Y', strtotime($follow_up)) : 'N/A' ?>
                                                </p>

                                                <h6 class="mt-4">Contacts</h6>
                                                <?php if (!empty($contacts)): ?>
                                                    <ul class="list-group">
                                                        <?php foreach ($contacts as $contact): ?>
                                                            <li class="list-group-item">
                                                                <strong><?= htmlspecialchars($contact['name'] ?? '') ?></strong><br>
                                                                <?= htmlspecialchars($contact['designation'] ?? '') ?><br>
                                                                <i class="fa fa-phone text-success"></i> <?= htmlspecialchars($contact['contact'] ?? '') ?><br>
                                                                <i class="fa fa-envelope text-primary"></i> <?= htmlspecialchars($contact['email'] ?? '') ?>
                                                            </li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                <?php else: ?>
                                                    <p class="text-muted">No contacts available</p>
                                                <?php endif; ?>
                                            </div>

                                            <div class="col-md-6">
                                                <h6>Lead History</h6>
                                                <?php if (!empty($status_history)): ?>
                                                    <div class="d-flex justify-content-end">
                                                        <div class="timeline" style="max-height:300px; overflow-y:auto; padding-right:10px; width: 100%; max-width: 600px;">
                                                            <?php foreach ($status_history as $item): ?>
                                                                <div class="timeline-item">
                                                                    <div class="timeline-point bg-info"></div>
                                                                    <div class="timeline-content">
                                                                        <div class="d-flex justify-content-between align-items-center">
                                                                            <h6 class="mb-1">Status Changed</h6>
                                                                            <small class="text-muted"><?= date('d-m-Y H:i', strtotime($item['timestamp'])) ?></small>
                                                                        </div>
                                                                        <div class="mt-2">
                                                                            <div>From: <strong class="text-danger"><?= get_status_label($item['old_status']) ?></strong></div>
                                                                            <div>To: <strong class="text-success"><?= get_status_label($item['new_status']) ?></strong></div>
                                                                            <?php if (!empty($item['remarks'])): ?>
                                                                                <div class="mt-1">Remark: <em><?= nl2br(htmlspecialchars($item['remarks'])) ?></em></div>
                                                                            <?php endif; ?>
                                                                            <div class="mt-1">By: <strong><?= htmlspecialchars(($item['firstname'] ?? '') . ' ' . ($item['lastname'] ?? '')) ?></strong></div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <p class="text-muted text-end">No timeline history available</p>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <div class="row mt-4">
                                            <div class="col-12 text-center">
                                                <?php
                                                $base_url = strtok($_SERVER['REQUEST_URI'], '?');
                                                $query_params = $_GET;
                                                $query_params['tab'] = 'followups'; // force tab on every link
                                                ?>

                                                <!-- Previous Button -->
                                                <?php if ($current_index > 0): ?>
                                                    <?php $query_params['lead_index'] = $current_index - 1; ?>
                                                    <a href="<?= $base_url . '?' . http_build_query($query_params) ?>#follow-ups" class="btn btn-primary">

                                                        <i class="fas fa-arrow-left"></i> Previous
                                                    </a>
                                                <?php else: ?>
                                                    <span class="btn btn-secondary disabled" style="background-color: #ff407b;">
                                                        <i class="fas fa-arrow-left"></i> Previous
                                                    </span>
                                                <?php endif; ?>

                                                <!-- Update Button -->
                                                <button class="btn btn-success update-all-btn" data-id="<?= $lead_id ?>">
                                                    <i class="fas fa-save"></i> Update
                                                </button>

                                                <!-- Next Button -->
                                                <?php if ($current_index < $total_leads - 1): ?>
                                                    <?php $query_params['lead_index'] = $current_index + 1; ?>
                                                    <a href="<?= $base_url . '?' . http_build_query($query_params) ?>#follow-ups" class="btn btn-primary">

                                                        Next <i class="fas fa-arrow-right"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <a class="btn btn-primary disabled">Next <i class="fas fa-arrow-right"></i></a>
                                                <?php endif; ?>


                                                <p class="mt-2">Lead <?= $current_index + 1 ?> of <?= $total_leads ?></p>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-info">No follow-up leads for today.</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <!-- Follow-ups Summary Card -->
                        <div class="col-4">
                            <div class="card shadow-sm summary-card ">
                                <div class="card-header bg-success text-white py-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0">
                                            <i class="fas fa-calendar-check me-2"></i>
                                            <span class="ms-2 text-white">Todays Follow-ups Summary</span>
                                        </h5>
                                        <span class="badge bg-light text-success"><?= date('d M Y') ?></span>
                                    </div>
                                </div>
                                <div class="card-body p-4">
                                    <?php
                                    $followup_status_counts = [];
                                    foreach ($followup_leads as $flead) {
                                        $status = $flead['status'];
                                        $followup_status_counts[$status] = ($followup_status_counts[$status] ?? 0) + 1;
                                    }

                                    $total_followups = count($followup_leads);
                                    ?>

                                    <div class="row g-2 mb-4">
                                        <div class="col-12 text-center">
                                            <div class="p-3 bg-light-success rounded-3">
                                                <h3 class="mb-0 text-success"><?= $total_followups ?></h3>
                                                <small class="text-muted">Total Follow-up Leads</small>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-3">
                                        <h6 class="text-center mb-3">Status Breakdown</h6>
                                        <div class="row row-cols-2 g-2">
                                            <?php
                                            $status_colors = [
                                                '0' => 'primary',
                                                '1' => 'warning',
                                                '2' => 'danger',
                                                '3' => 'success',
                                                '4' => 'secondary',
                                            ];
                                            ?>
                                            <?php foreach ($followup_status_counts as $status => $count): ?>
                                                <div class="col mb-2">
                                                    <div class="d-flex justify-content-between align-items-center border rounded p-2 bg-light-<?= $status_colors[$status] ?? 'secondary' ?>">
                                                        <small class="text-muted" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                            <?= get_status_label($status) ?>
                                                        </small>
                                                        <span class="badge bg-<?= $status_colors[$status] ?? 'secondary' ?> rounded-pill ms-2"><?= $count ?></span>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>



                    </div>

                </div>

                <!-- end of follow-ups section -->



        </div>


        <style>
            .timeline {
                position: relative;
                padding-left: 20px;
            }

            .timeline-item {
                position: relative;
                padding-bottom: 15px;
            }

            .timeline-point {
                position: absolute;
                left: -10px;
                top: 5px;
                width: 20px;
                height: 20px;
                border-radius: 50%;
                background-color: #0d6efd;
                z-index: 2;
            }

            .timeline-content {
                position: relative;
                padding: 10px 15px;
                background-color: #f8f9fa;
                border-radius: 5px;
                margin-left: 20px;
                border: 1px solid #dee2e6;
            }

            .timeline-item:after {
                content: '';
                position: absolute;
                left: 0;
                top: 5px;
                height: 100%;
                width: 2px;
                background-color: #0d6efd;
                z-index: 1;
            }
        </style>

        <script>
            // $(document).ready(function() {
            //     $('.update-all-btn').click(function() {
            //         const leadId = $(this).data('id');
            //         uni_modal("Update Lead Status", "view_lead/manage_log.php?lid=" + leadId, function() {
            //             alert('Lead updated successfully!');
            //             location.reload();
            //         });
            //     });
            // });
            $(document).ready(function() {
                $('.update-all-btn, .update-today-btn').click(function() {
                    const leadId = $(this).data('id');
                    uni_modal("Update Lead's Status", "view_lead/manage_log.php?lid=" + leadId, function() {
                        // After modal closes
                        const nextBtn = document.getElementById('next-btn');
                        if (nextBtn) {
                            nextBtn.classList.remove('disabled');
                            nextBtn.removeAttribute('aria-disabled');
                            nextBtn.removeAttribute('disabled');
                        }
                        alert('Lead log updated! You can now proceed to the next lead.');
                        location.reload(); // optional, only if needed
                    });
                });
            });
        </script>
    <?php endif; ?>

    <!-- end of modified code -->




    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const statusField = document.getElementById('lead_status');
            const outcomeField = document.getElementById('call_outcome');
            const remarksField = document.getElementById('remarks');
            const followUpField = document.getElementById('follow_up_date');
            const nextBtn = document.getElementById('next-btn');
            const updateBtn = document.getElementById('btn-update');

            const fields = [statusField, outcomeField, remarksField, followUpField];

            function checkValidityAndToggleNext() {
                const status = statusField?.value;
                const outcome = outcomeField?.value;
                const remarks = remarksField?.value.trim();
                const followUp = followUpField?.value;

                const requireFollowUpStatus = ["0", "1", "2"];
                const requireFollowUpOutcome = ["2", "3"];

                const followUpRequired = requireFollowUpStatus.includes(status) || requireFollowUpOutcome.includes(outcome);
                const followUpValid = !followUpRequired || (followUp && followUp.length > 0);

                //nextBtn.disabled = !(status && outcome && remarks.length > 0 && followUpValid);
            }

            fields.forEach(el => {
                if (el) {
                    el.addEventListener('input', checkValidityAndToggleNext);
                    el.addEventListener('change', checkValidityAndToggleNext);
                }
            });

            if (nextBtn) checkValidityAndToggleNext();

            document.querySelectorAll('.btn-old-update').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.dataset.id;
                    const notes = prompt('Enter new notes/remarks:');
                    if (notes !== null) {
                        updateLead(id, notes);
                    }
                });
            });

            function updateLead(id, notes) {
                fetch('lead_update.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            id: id,
                            remarks: notes
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        alert(data.message);
                        if (data.success) location.reload();
                    });
            }

            function updateLeadStatus(id, status) {
                fetch('lead_update.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            id: id,
                            status: status
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        alert(data.message);
                        if (data.success) location.reload();
                    });
            }

            document.getElementById('btn-update').addEventListener('click', function() {
                uni_modal("Update Lead's Status", "view_lead/manage_log.php?lid=<?= $lead['id'] ?>", function() {
                    // After modal closes (means user updated log), enable next button
                    const nextBtn = document.getElementById('next-btn');
                    if (nextBtn) {
                        nextBtn.classList.remove('disabled');
                        nextBtn.removeAttribute('aria-disabled');
                        nextBtn.removeAttribute('disabled');
                    }
                    alert('Lead log updated! You can now proceed to the next lead.');
                });
            });

        });
    </script>

<?php endif; ?>
    </div>
    <!-- ============================================================== -->
    <!-- end basic table  -->
    <!-- ============================================================== -->
    </div>

    </div>
    <!-- ============================================================== -->

    </div>

    <!-- ============================================================== -->
    <!-- end main wrapper -->
    <!-- ============================================================== -->

    <!-- Chart Script -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctx = document.getElementById('leadStatusChart').getContext('2d');
        const leadStatusChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($labels); ?>,
                datasets: [{
                    label: 'Leads',
                    data: <?php echo json_encode($data); ?>,
                    backgroundColor: [
                        '#f9c74f', '#90be6d', '#577590', '#f9844a', '#43aa8b', '#f94144', '#277da1', '#adb5bd'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.label}: ${context.raw}`;
                            }
                        }
                    }
                }
            }
        });
    </script>
    <script>
        function showSection(section) {
            const all = document.getElementById("allLeadsSection");
            const today = document.getElementById("todayLeadsSection");
            const follow = document.getElementById("followUpLeadsSection");

            const allTab = document.getElementById("allLeadsTab");
            const todayTab = document.getElementById("todayLeadsTab");
            const followTab = document.getElementById("followUpLeadsTab");

            all.style.display = section === 'all' ? 'block' : 'none';
            today.style.display = section === 'today' ? 'block' : 'none';
            follow.style.display = section === 'follow-ups' ? 'block' : 'none';

            allTab.classList.toggle('active', section === 'all');
            todayTab.classList.toggle('active', section === 'today');
            followTab.classList.toggle('active', section === 'follow-ups');
        }

        // On page load, check if there's a hash
        document.addEventListener('DOMContentLoaded', function() {
            if (window.location.hash === '#follow-ups') {
                showSection('follow-ups');
            } else if (window.location.hash === '#today') {
                showSection('today');
            } else {
                showSection('all');
            }
        });
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const bar = document.getElementById('lead-progress-bar');
            const percentSpan = document.getElementById('progress-percent');
            const finalPercent = parseInt(bar.getAttribute('data-final')) || 0;

            let current = 0;
            const interval = setInterval(() => {
                if (current >= finalPercent) {
                    clearInterval(interval);
                } else {
                    current += 1;
                    bar.style.width = current + '%';
                    percentSpan.textContent = current + '%';
                }
            }, 15);
        });
    </script>


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
</body>

</html>
<!-- Added remark and updates work -->