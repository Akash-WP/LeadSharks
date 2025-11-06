<?php
// Optional: include DB and session auth if needed
require_once('../config.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Lead Overview</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/vendor/bootstrap/css/bootstrap.min.css">
    <link href="../assets/vendor/fonts/circular-std/style.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/libs/css/style.css">
    <link rel="stylesheet" href="../assets/vendor/fonts/fontawesome/css/fontawesome-all.css">
    <link rel="stylesheet" type="text/css" href="../assets/vendor/datatables/css/dataTables.bootstrap4.css">
    <link rel="stylesheet" type="text/css" href="../assets/vendor/datatables/css/buttons.bootstrap4.css">
    <link rel="stylesheet" type="text/css" href="../assets/vendor/datatables/css/select.bootstrap4.css">
    <link rel="stylesheet" type="text/css" href="../assets/vendor/datatables/css/fixedHeader.bootstrap4.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Include jQuery & DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
</head>
<body>

<div class="container py-4">
  <h2 class="mb-4">Lead Overview</h2>

  <!-- Nav Tabs -->
  <ul class="nav nav-tabs" id="leadTabs" role="tablist">
    <li class="nav-item" role="presentation">
      <a class="nav-link active" id="tab-total-tab" data-bs-toggle="tab" href="#tab-total" role="tab">Total Leads</a>
    </li>
    <li class="nav-item" role="presentation">
      <a class="nav-link" id="tab-today-tab" data-bs-toggle="tab" href="#tab-today" role="tab">Today's Follow-ups</a>
    </li>
    <li class="nav-item" role="presentation">
      <a class="nav-link" id="tab-upcoming-tab" data-bs-toggle="tab" href="#tab-upcoming" role="tab">Upcoming Follow-ups</a>
    </li>
    <li class="nav-item" role="presentation">
      <a class="nav-link" id="tab-overdue-tab" data-bs-toggle="tab" href="#tab-overdue" role="tab">Overdue Follow-ups</a>
    </li>
  </ul>

  <!-- Tab Content -->
  <div class="tab-content border p-3 mt-2" id="leadTabsContent">
    <div class="tab-pane fade show active" id="tab-total" role="tabpanel">
      <h5>Total Leads</h5>
      <table class="table table-bordered table-hover table-striped" id="leads-table">
                    <thead class="thead-dark">
                        <tr>
                            <!-- <?php if (in_array($_settings->userdata('type'), [1, 3])): ?>
                                <th class="no-sort"><input type="checkbox" id="select-all"></th> 
                            <?php endif ?> -->
                            <th>Ref. Code</th>
                            <th>Company</th>
                            <th>Country</th>
                            <th style="display: none;">State</th>
                            <th style="display: none;">City</th>
                            <th>Interested In</th>
                            <th>Contacts</th> 
                            <th>Assigned To</th>
                            <th>Status</th>
                            <th>Calling</th>
                            <th>Follow-Up</th>
                            <th>Created By</th>
                            <th style="display: none;">Date Created</th>
                            <!-- <th>Actions</th> -->
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $uwhere = "";
                        if ($_settings->userdata('type') != 1) {
                            $userId = $_settings->userdata('id');
                            $uwhere = " and (assigned_to = '{$userId}' OR user_id = '{$userId}') ";
                        }

                        $users = $conn->query("SELECT id,CONCAT(lastname,', ', firstname, '', COALESCE(middlename,'')) as fullname FROM `users` where id in (SELECT `user_id` FROM `lead_list` where in_opportunity = 0 {$uwhere}) OR id in (SELECT assigned_to FROM `lead_list` where in_opportunity = 0 {$uwhere})");
                        $user_arr = array_column($users->fetch_all(MYSQLI_ASSOC), 'fullname', 'id');

                        $leads = $conn->query("SELECT l.*, c.company_name as client, c.website, c.city, c.state, c.country, c.follow_up_date, c.calling_date FROM `lead_list` l 
                            INNER JOIN client_list c ON c.lead_id = l.id 
                            WHERE l.in_opportunity = 0 {$uwhere} 
                            ORDER BY UNIX_TIMESTAMP(l.date_created) DESC, l.`status` DESC");


                        while ($row = $leads->fetch_assoc()):

                            $contacts = [];
                            $stmt = $conn->prepare("SELECT * FROM contact_persons WHERE lead_id = ?");
                            $stmt->bind_param("i", $row['id']);
                            $stmt->execute();
                            $result_contacts = $stmt->get_result();
                            while ($contact_row = $result_contacts->fetch_assoc()) {
                                $contacts[] = $contact_row;
                            }
                            $stmt->close();

                            // Now use $contacts in your HTML for display
                            ?>
                            <tr class="list-item" data-status="<?= $row['status'] ?>">
                                <!-- <?php if (in_array($_settings->userdata('type'), [1, 3])): ?>
                                    <td><input type="checkbox" class="lead-checkbox" name="selected_leads[]"
                                            value="<?= $row['id'] ?>"></td> 
                                <?php endif ?> -->
                                <td>
                                    <?= $row['code'] ?>
                                </td>
                                <td>
                                    <?= ucwords($row['client']) ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($row['country']) ?>
                                </td>
                                <td style="display: none;">
                                    <?= htmlspecialchars($row['state']) ?>
                                </td>
                                <td style="display: none;">
                                    <?= htmlspecialchars($row['city']) ?>
                                </td>
                                <!-- <td><a href="<?= $row['website'] ?>" target="_blank">
                                    <?= $row['website'] ?>
                                </a></td> -->
                                <td>
                                    <?= $row['interested_in'] ?>
                                </td>
                                <td>
                                    <?php if (!empty($contacts)): ?>
                                        <ul class="list-unstyled mb-0">
                                            <?php foreach ($contacts as $c): ?>
                                                <!-- <li>
                                        <strong>
                                            <?= htmlspecialchars($c['name']) ?>
                                        </strong><br>
                                        <?= htmlspecialchars($c['contact']) ?><br>
                                        <small>
                                            <?= htmlspecialchars($c['email']) ?><br>
                                            <?= htmlspecialchars($c['designation']) ?>
                                        </small>
                                    </li> -->
                                                <li>
                                                    <strong>
                                                        <?= htmlspecialchars($c['name']) ?>
                                                        <?php if ($c['is_lead_contact'] == 1): ?>
                                                            <span class="badge ml-1">⭐</span>
                                                        <?php endif; ?>
                                                    </strong><br>
                                                    <?= htmlspecialchars($c['contact']) ?><br>
                                                    <small>
                                                        <?= htmlspecialchars($c['email']) ?><br>
                                                        <?= htmlspecialchars($c['designation']) ?>
                                                    </small>
                                                </li>

                                                <hr class="my-1">
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <span class="text-muted">No Contacts</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= isset($user_arr[$row['assigned_to']]) ? ucwords($user_arr[$row['assigned_to']]) : "Not Assigned Yet." ?>
                                </td>
                                <td>
                                    <?php
                                    switch ($row['status']) {
                                        case 0:
                                            echo '<span class="badge badge-primary">Lead – Uncontacted</span>';
                                            break;
                                        case 1:
                                            echo '<span class="badge badge-info">Prospect – Contact Made</span>';
                                            break;
                                        case 2:
                                            echo '<span class="badge badge-warning">Qualified – Need Validated</span>';
                                            break;
                                        case 3:
                                            echo '<span class="badge badge-secondary">Solution Fit / Discovery</span>';
                                            break;
                                        case 4:
                                            echo '<span class="badge badge-primary">Proposal / Value Proposition</span>';
                                            break;
                                        case 5:
                                            echo '<span class="badge badge-info">Negotiation</span>';
                                            break;
                                        case 6:
                                            echo '<span class="badge badge-success">Closed – Won</span>';
                                            break;
                                        case 7:
                                            echo '<span class="badge badge-danger">Closed – Lost</span>';
                                            break;
                                        default:
                                            echo '<span class="badge badge-light border">N/A</span>';
                                            break;
                                    }
                                    ?>
                                </td>

                                <td>
                                    <?php if (!empty($row['calling_date'])): ?>
                                        <span class="badge badge-warning">
                                            <?= date("M d, Y h:i A", strtotime($row['calling_date'])) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                    <?php endif; ?>

                                </td>
                                <td>
                                    <?php if (!empty($row['follow_up_date'])): ?>
                                        <span class="badge badge-warning">
                                            <?= date("M d, Y h:i A", strtotime($row['follow_up_date'])) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                    <?php endif; ?>

                                </td>
                                <td>
                                    <?= isset($user_arr[$row['user_id']]) ? ucwords($user_arr[$row['user_id']]) : "N/A" ?>
                                </td>
                                <td style="display: none;">
                                    <?= date("D M d, Y h:i A", strtotime($row['date_created'])) ?>
                                </td>
                                <!-- <td>
                                    <a class="btn btn-sm btn-light border" href="./?page=view_lead&id=<?= $row['id'] ?>"><i
                                            class="fa fa-eye"></i></a>
                                    <?php if (in_array($_settings->userdata('type'), [1, 3])): ?>
                                        <a class="btn btn-sm btn-primary"
                                            href="./?page=leads/manage_lead&id=<?= $row['id'] ?>"><i class="fa fa-edit"></i></a>
                                        <button class="btn btn-sm btn-danger delete_data" data-id="<?= $row['id'] ?>"><i
                                                class="fa fa-trash"></i></button>
                                    <?php endif; ?>
                                </td> -->
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
    </div>
    <div class="tab-pane fade" id="tab-today" role="tabpanel">
      <h5>Today's Follow-ups</h5>
      <?php
$result = $conn->query("
    SELECT 
        l.code AS lead_code,
        c.company_name,
        c.city,
        c.state,
        c.country,
        c.follow_up_date
    FROM client_list c
    JOIN lead_list l ON c.lead_id = l.id
    WHERE DATE(c.follow_up_date) = CURDATE()
    AND l.delete_flag = 0
    ORDER BY c.follow_up_date ASC
");

if ($result && $result->num_rows > 0): ?>
    <div class="table-responsive">
        <table class="table table-bordered table-hover table-sm">
            <thead class="table-primary">
                <tr>
                    <th>#</th>
                    <th>Lead Code</th>
                    <th>Company</th>
                    <th>City</th>
                    <th>State</th>
                    <th>Country</th>
                    <th>Follow-up Date</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= htmlspecialchars($row['lead_code']) ?></td>
                    <td><?= htmlspecialchars($row['company_name']) ?></td>
                    <td><?= htmlspecialchars($row['city']) ?></td>
                    <td><?= htmlspecialchars($row['state']) ?></td>
                    <td><?= htmlspecialchars($row['country']) ?></td>
                    <td><?= date('d M Y, h:i A', strtotime($row['follow_up_date'])) ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <div class="alert alert-info">No leads found with a follow-up scheduled for today.</div>
<?php endif; ?>

    </div>
    <div class="tab-pane fade" id="tab-upcoming" role="tabpanel">
      <h5>Upcoming Follow-ups</h5>
      <?php
$result = $conn->query("
    SELECT 
        l.code AS lead_code,
        c.company_name,
        c.city,
        c.state,
        c.country,
        c.follow_up_date
    FROM client_list c
    JOIN lead_list l ON c.lead_id = l.id
    WHERE DATE(c.follow_up_date) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    AND l.delete_flag = 0
    ORDER BY c.follow_up_date ASC
");

if ($result && $result->num_rows > 0): ?>
    <div class="table-responsive">
        <table class="table table-bordered table-hover table-sm">
            <thead class="table-warning">
                <tr>
                    <th>#</th>
                    <th>Lead Code</th>
                    <th>Company</th>
                    <th>City</th>
                    <th>State</th>
                    <th>Country</th>
                    <th>Follow-up Date</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= htmlspecialchars($row['lead_code']) ?></td>
                    <td><?= htmlspecialchars($row['company_name']) ?></td>
                    <td><?= htmlspecialchars($row['city']) ?></td>
                    <td><?= htmlspecialchars($row['state']) ?></td>
                    <td><?= htmlspecialchars($row['country']) ?></td>
                    <td><?= date('d M Y, h:i A', strtotime($row['follow_up_date'])) ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <div class="alert alert-info">No upcoming follow-ups found in the next 7 days.</div>
<?php endif; ?>

    </div>
    <div class="tab-pane fade" id="tab-overdue" role="tabpanel">
      <h5>Overdue Follow-ups</h5>
      <?php
$result = $conn->query("
    SELECT 
        l.code AS lead_code,
        c.company_name,
        c.city,
        c.state,
        c.country,
        c.follow_up_date
    FROM client_list c
    JOIN lead_list l ON c.lead_id = l.id
    WHERE DATE(c.follow_up_date) < CURDATE()
    AND NOT EXISTS (
        SELECT 1 FROM log_list lg 
        WHERE lg.lead_id = l.id 
        AND DATE(lg.date_created) >= DATE(c.follow_up_date)
    )
    AND l.delete_flag = 0
    ORDER BY c.follow_up_date ASC
");

if ($result && $result->num_rows > 0): ?>
    <div class="table-responsive">
        <table class="table table-bordered table-hover table-sm">
            <thead class="table-danger">
                <tr>
                    <th>#</th>
                    <th>Lead Code</th>
                    <th>Company</th>
                    <th>City</th>
                    <th>State</th>
                    <th>Country</th>
                    <th>Follow-up Date</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= htmlspecialchars($row['lead_code']) ?></td>
                    <td><?= htmlspecialchars($row['company_name']) ?></td>
                    <td><?= htmlspecialchars($row['city']) ?></td>
                    <td><?= htmlspecialchars($row['state']) ?></td>
                    <td><?= htmlspecialchars($row['country']) ?></td>
                    <td><?= date('d M Y, h:i A', strtotime($row['follow_up_date'])) ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <div class="alert alert-success">No overdue leads without logged action found.</div>
<?php endif; ?>

    </div>
  </div>
</div>

<!-- JS: Activate tab from anchor -->
<script>
document.addEventListener("DOMContentLoaded", function () {
  const hash = window.location.hash;
  if (hash) {
    const triggerTab = document.querySelector(`a[href="${hash}"]`);
    if (triggerTab) {
      new bootstrap.Tab(triggerTab).show();
    }
  }
});
</script>

<script>
$(document).ready(function(){
    $('table').DataTable({
        "pageLength": 10,
        "order": [[6, 'asc']]
    });
});
</script>

</body>
</html>
