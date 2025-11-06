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
<body>
    <?php
    require_once('../config.php');
            
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
                        0 => 'Lead &#45; Uncontacted',
                        1 => 'Prospect &#45; Contact Made',
                        2 => 'Qualified &#45; Need Validated',
                        3 => 'Solution Fit &#47; Discovery',
                        4 => 'Proposal &#47; Value Proposition',
                        5 => 'Negotiation',
                        6 => 'Closed &#45; Won',
                        7 => 'Closed &#45; Lost',
                    ];
                    return $status_labels[$status] ?? 'N/A';
                }

                $completed_count = count(array_filter($today_leads, fn($l) => $l['is_completed']));
                $not_completed_count = $total_leads - $completed_count;
            ?>
            
            <!-- for follow-up counts -->
    <?php
    // NAV BAR FOLLOW-UP COUNT
    $today = date('Y-m-d');
    $user_id = $_settings->userdata('id');

    // Use a new variable: $nav_followup_count
    $nav_followup_query = "
    SELECT COUNT(*) as followup_count
    FROM log_list l
    INNER JOIN (
        SELECT lead_id, MAX(id) as latest_id
        FROM log_list
        WHERE follow_up_date IS NOT NULL
        GROUP BY lead_id
    ) latest_log ON latest_log.lead_id = l.lead_id AND latest_log.latest_id = l.id
    JOIN lead_list le ON le.id = l.lead_id
    WHERE DATE(l.follow_up_date) = ?
    AND le.assigned_to = ?
";

    $nav_followup_stmt = $conn->prepare($nav_followup_query);
    $nav_followup_stmt->bind_param("si", $today, $user_id);
    $nav_followup_stmt->execute();
    $result = $nav_followup_stmt->get_result();
    $nav_followup_count = 0;
    if ($row = $result->fetch_assoc()) {
        $nav_followup_count = $row['followup_count'];
    }
    $nav_followup_stmt->close();
    ?>
    <!-- end of follow up count -->

                <!-- nav bar -->
                <ul class="nav nav-tabs mb-3">
                    <li class="nav-item">
                        <a class="nav-link active" id="allLeadsTab" href="#" onclick="showSection('all')">All Leads</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="todayLeadsTab" href="#" onclick="showSection('today')">Todays Leads</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="followUpLeadsTab" href="#" onclick="showSection('follow-ups')">Follow ups <span id="followUpCount">(<?= $nav_followup_count ?>)</span></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="bulkUploadLeadsTab" href="#" onclick="showSection('bulk-upload')">Bulk Upload</a>
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
                                                    <form method="get" class="form-inline" action="">
                                                    <input type="hidden" name="page" value="Sharks_portal">
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
                                                        <a href="?page=Sharks_portal" class="btn btn-secondary mb-2">
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
                                                    $client_id = $all_client['id'] ?? null;


                                                    $follow_up = null;
                                                    if ($lead_id && $client_id) {
                                                        $log = $conn->query("SELECT follow_up_date FROM log_list WHERE lead_id = '{$lead_id}' ORDER BY date_created DESC LIMIT 1");
                                                        $follow_up = ($log && $log->num_rows > 0) ? $log->fetch_assoc()['follow_up_date'] : null;
                                                        // 2. Only update client_list if follow-up exists and is different
                                            if (!empty($follow_up)) {
                                                $update = $conn->query("
            UPDATE client_list 
            SET follow_up_date = '{$follow_up}' 
            WHERE id = '{$client_id}' 
              AND (follow_up_date IS NULL OR follow_up_date != '{$follow_up}')
        ");

                                                if (!$update) {
                                                    error_log("Failed to update follow_up_date for client ID {$client_id}: " . $conn->error);
                                                }
                                            }
                                                        
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
                                                                        <p><strong>Company:</strong> <?= htmlspecialchars($all_client['company_name'] ?? 'N/A') ?></p>
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
                                                            <p><strong>Company:</strong> <?= htmlspecialchars($client['company_name'] ?? 'N/A') ?></p>
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

                <!-- start of bulkUpload section -->
<div id="bulkUploadSection" class="p-6 bg-gray-50 rounded-2xl shadow-md max-w-4xl mx-auto mt-8">
  <h1 class="text-2xl font-semibold mb-4 text-gray-800 flex items-center gap-2">
    📤 Bulk Upload
  </h1>

  <div class="flex items-center gap-3">
    <button id="uploadBtn" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg shadow transition-all">
      📁 Upload Excel File
    </button>
    <a href="excel/bulk_upload_template.xlsx" class="btn btn-sm btn-success" download>
                        <i class="fa fa-download"></i> Download Template
                    </a>
    <input type="file" id="excelInput" accept=".xlsx,.xls" class="hidden">
    <p id="fileName" class="text-gray-600 text-sm"></p>
  </div>

  <div id="uploadResult" class="mt-6 text-gray-700"></div>
</div>

<script>
  const uploadBtn = document.getElementById("uploadBtn");
  const excelInput = document.getElementById("excelInput");
  const fileNameDisplay = document.getElementById("fileName");
  const uploadResult = document.getElementById("uploadResult");
  
  uploadBtn.addEventListener("click", () => excelInput.click());
  excelInput.addEventListener("change", async () => {
    const file = excelInput.files[0];
    if (!file) return;

    fileNameDisplay.textContent = ` ${file.name}`;
    uploadResult.innerHTML = `<div class="text-blue-600 animate-pulse">Uploading  verifying...</div>`;

    const formData = new FormData();
    formData.append("excel_file", file);

    try {
      const res = await fetch("bulk_upload.php", { method: "POST", body: formData,credentials: "include" });
      const rawText = await res.text();
      console.log("Raw response from PHP:", rawText);

      let data;
      try { data = JSON.parse(rawText); } 
      catch (e) {
        uploadResult.innerHTML = `<div class="text-red-600 font-medium">❌ Invalid server response (not JSON)</div>`;
        return;
      }

      if (data.status === "verify") {
        let foundRows = "";
        let missingRows = "";

        data.missing_list = data.missing_list || [];

        if (data.found_list && data.found_list.length > 0) {
  foundRows = data.found_list.map(c => `
    <tr>
      <td class="px-4 py-2 border text-gray-800">${c}</td>
      <td class="px-4 py-2 border text-green-600">✅ Found</td>
    </tr>
  `).join("");
} 

        if (data.missing_list.length > 0)
          missingRows = data.missing_list.map(c => `
            <tr>
              <td class="px-4 py-2 border text-gray-800">${c}</td>
              <td class="px-4 py-2 border text-red-600">❌ Not Found</td>
            </tr>
          `).join("");

        uploadResult.innerHTML = `
          <div class="bg-white rounded-lg shadow p-4">
            <h2 class="text-lg font-semibold mb-2 text-gray-800">Verification Summary</h2>
            <table class="w-full border text-sm border-gray-300 rounded-lg overflow-hidden">
              <thead class="bg-gray-100">
                <tr>
                  <th class="px-4 py-2 border text-left text-gray-700">Company Name</th>
                  <th class="px-4 py-2 border text-left text-gray-700">Status</th>
                </tr>
              </thead>
              <tbody>
                ${foundRows}
                ${missingRows || `<tr><td colspan="2" class="px-4 py-2 text-green-600">✅ All companies found in database.</td></tr>`}
              </tbody>
            </table>
            <div class="mt-4 text-right">
              <button id="proceedBtn" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg shadow transition-all">
                🚀 Proceed with Upload
              </button>
            </div>
          </div>
        `;

        document.getElementById("proceedBtn").addEventListener("click", async () => {
          const confirmData = new FormData();
          confirmData.append("excel_file", file);
          confirmData.append("confirm", "true");

          uploadResult.innerHTML = `<div class="text-blue-600 animate-pulse font-medium">⚙️ Uploading data to database...</div>`;

          const confirmRes = await fetch("bulk_upload.php", { method: "POST", body: confirmData });
          const confirmRaw = await confirmRes.text();
          console.log("Raw confirm response:", confirmRaw);

          let confirmResult;
          try { confirmResult = JSON.parse(confirmRaw); } 
          catch (e) {
            uploadResult.innerHTML = `<div class="text-red-600 font-medium">❌ Invalid JSON in confirm step.</div>`;
            return;
          }

          if (confirmResult.status === "success") {
            uploadResult.innerHTML = `
              <div class="bg-green-50 border border-green-400 rounded-lg p-4">
                <h2 class="text-lg font-semibold text-green-700">✅ Upload Completed</h2>
                <p class="mt-2 text-green-800">Successfully inserted 
                  <span class="font-bold">${confirmResult.inserted}</span> out of 
                  <span class="font-bold">${confirmResult.total}</span> records.
                </p>
                <p class="mt-2 text-sm text-gray-600">Check leads for verification.</p>
              </div>
            `;
          } else {
            uploadResult.innerHTML = `<div class="text-red-600 font-medium mt-2">❌ Error: ${confirmResult.message}</div>`;
          }
        });
      } else {
        uploadResult.innerHTML = `<div class="text-red-600 font-medium">❌ ${data.message || "Unknown error occurred."}</div>`;
      }
    } catch (err) {
      console.error("Fetch error:", err);
      uploadResult.innerHTML = `<div class="text-red-600 font-medium">❌ Upload failed. Check console for details.</div>`;
    }
  });
</script>
                <!-- end of bulkupload section -->



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
            // <a class="nav-link" id="bulkUploadLeadsTab" href="#" onclick="showSection('bulk-upload')">Bulk Upload</a>
            const all = document.getElementById("allLeadsSection");
            const today = document.getElementById("todayLeadsSection");
            const follow = document.getElementById("followUpLeadsSection");
            const bulkUpload = document.getElementById("bulkUploadSection");

            const allTab = document.getElementById("allLeadsTab");
            const todayTab = document.getElementById("todayLeadsTab");
            const followTab = document.getElementById("followUpLeadsTab");
            const bulkUploadTab = document.getElementById("bulkUploadLeadsTab");

            all.style.display = section === 'all' ? 'block' : 'none';
            today.style.display = section === 'today' ? 'block' : 'none';
            follow.style.display = section === 'follow-ups' ? 'block' : 'none';
            bulkUpload.style.display = section === 'bulk-upload' ? 'block' : 'none';

            allTab.classList.toggle('active', section === 'all');
            todayTab.classList.toggle('active', section === 'today');
            followTab.classList.toggle('active', section === 'follow-ups');
            bulkUploadTab.classList.toggle('active', section === 'bulk-upload');
            
            // toggle follow-up count visibility
        const followUpCount = document.getElementById('followUpCount'); // <span> inside tab
        if (followUpCount) {
            followUpCount.style.display = (section === 'follow-ups') ? 'none' : 'inline';
        }
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
    <script src="https://cdn.tailwindcss.com"></script>

</body>

</html>