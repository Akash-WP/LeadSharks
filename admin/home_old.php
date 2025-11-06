
<hr class="border-primary">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<?php if(in_array($_settings->userdata('type'), [1, 3])): ?>
<!-- Admin or Manager -->
<div class="row">
    <!-- Total Lead Sources -->
    <div class="col-md-3 col-sm-6">
        <div class="info-box bg-gradient-light shadow">
            <span class="info-box-icon bg-gradient-primary elevation-1"><i class="fas fa-th-list"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Total Lead Sources</span>
                <span class="info-box-number text-right">
                    <?php echo $conn->query("SELECT COUNT(*) FROM `source_list` WHERE delete_flag = 0")->fetch_row()[0]; ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Total Leads -->
    <div class="col-md-3 col-sm-6">
        <div class="info-box bg-gradient-light shadow">
            <span class="info-box-icon bg-gradient-teal elevation-1"><i class="fas fa-stream"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Total Leads</span>
                <span class="info-box-number text-right">
                    <?php echo $conn->query("SELECT COUNT(*) FROM `lead_list` WHERE `in_opportunity` = 0")->fetch_row()[0]; ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Total Opportunities -->
    <div class="col-md-3 col-sm-6">
        <div class="info-box bg-gradient-light shadow">
            <span class="info-box-icon bg-gradient-maroon elevation-1"><i class="fas fa-bullseye"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Total Opportunities</span>
                <span class="info-box-number text-right">
                    <?php echo $conn->query("SELECT COUNT(*) FROM `lead_list` WHERE `in_opportunity` = 1")->fetch_row()[0]; ?>
                </span>
            </div>
        </div>
    </div>

    <!-- System Users -->
    <div class="col-md-3 col-sm-6">
        <div class="info-box bg-gradient-light shadow">
            <span class="info-box-icon bg-gradient-primary elevation-1"><i class="fas fa-users-cog"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">System Users</span>
                <span class="info-box-number text-right">
                    <?php echo $conn->query("SELECT COUNT(*) FROM `users`")->fetch_row()[0]; ?>
                </span>
            </div>
        </div>
    </div>
</div>

<?php else: ?>
<div class="row">
    <?php 
    $exec_id = $conn->real_escape_string($_settings->userdata('id'));

    // Assigned Leads
    $assigned_leads = $conn->query("SELECT COUNT(*) FROM `lead_list` WHERE `in_opportunity` = 0 AND `assigned_to` = '{$exec_id}'")->fetch_row()[0];

    // Assigned Opportunities
    $assigned_opps = $conn->query("SELECT COUNT(*) FROM `lead_list` WHERE `in_opportunity` = 1 AND `assigned_to` = '{$exec_id}'")->fetch_row()[0];

    // Today's Follow-Ups
    $todays_followups = $conn->query("SELECT COUNT(*) FROM `lead_list` WHERE `assigned_to` = '{$exec_id}' AND DATE(`follow_up_date`) = CURDATE()")->fetch_row()[0];

    // Leads by Status (only assigned leads, not opportunities)
    $leads_status = [];
    $status_labels = [
        0 => 'New', 1 => 'Open', 2 => 'Working', 3 => 'Not a Target', 
        4 => 'Disqualified', 5 => 'Nurture', 6 => 'Opportunity Created', 
        7 => 'Opportunity Lost', 8 => 'Inactive'
    ];
    $result = $conn->query("SELECT status, COUNT(*) as cnt FROM `lead_list` WHERE `assigned_to` = '{$exec_id}' AND `in_opportunity` = 0 GROUP BY status");
    while($row = $result->fetch_assoc()){
        $leads_status[$row['status']] = $row['cnt'];
    }

    // Upcoming Follow-Ups (next 7 days)
    $upcoming_followups = $conn->query("SELECT COUNT(*) FROM `lead_list` WHERE `assigned_to` = '{$exec_id}' AND `follow_up_date` BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)")->fetch_row()[0];

    // Overdue Follow-Ups (before today)
    $overdue_followups = $conn->query("SELECT COUNT(*) FROM `lead_list` WHERE `assigned_to` = '{$exec_id}' AND `follow_up_date` < CURDATE()")->fetch_row()[0];
    ?>

    <div class="row text-center">

    <!-- Assigned Leads -->
    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-3">
        <div class="info-box bg-gradient-light shadow rounded-3 p-2">
            <span class="info-box-icon bg-gradient-teal elevation-1 rounded-circle"><i class="fas fa-tasks"></i></span>
            <div class="info-box-content mt-2">
                <div class="info-box-text small text-muted">Assigned Leads</div>
                <div class="info-box-number h5 mb-0"><?php echo $assigned_leads ?></div>
            </div>
        </div>
    </div>

    <!-- Assigned Opportunities -->
    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-3">
        <div class="info-box bg-gradient-light shadow rounded-3 p-2">
            <span class="info-box-icon bg-gradient-maroon elevation-1 rounded-circle"><i class="fas fa-lightbulb"></i></span>
            <div class="info-box-content mt-2">
                <div class="info-box-text small text-muted">Assigned Opportunities</div>
                <div class="info-box-number h5 mb-0"><?php echo $assigned_opps ?></div>
            </div>
        </div>
    </div>

    <!-- Today's Follow-Ups -->
    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-3">
        <div class="info-box bg-gradient-light shadow rounded-3 p-2">
            <span class="info-box-icon bg-gradient-orange elevation-1 rounded-circle"><i class="fas fa-calendar-day"></i></span>
            <div class="info-box-content mt-2">
                <div class="info-box-text small text-muted">Today's Follow-Ups</div>
                <div class="info-box-number h5 mb-0"><?php echo $todays_followups ?></div>
            </div>
        </div>
    </div>

    <!-- Upcoming Follow-Ups -->
    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-3">
        <div class="info-box bg-gradient-light shadow rounded-3 p-2">
            <span class="info-box-icon bg-gradient-info elevation-1 rounded-circle"><i class="fas fa-calendar-plus"></i></span>
            <div class="info-box-content mt-2">
                <div class="info-box-text small text-muted">Upcoming Follow-Ups</div>
                <div class="info-box-number h5 mb-0"><?php echo $upcoming_followups ?></div>
            </div>
        </div>
    </div>

    <!-- Overdue Follow-Ups -->
    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-3">
        <div class="info-box bg-gradient-light shadow rounded-3 p-2">
            <span class="info-box-icon bg-gradient-danger elevation-1 rounded-circle"><i class="fas fa-calendar-times"></i></span>
            <div class="info-box-content mt-2">
                <div class="info-box-text small text-muted">Overdue Follow-Ups</div>
                <div class="info-box-number h5 mb-0"><?php echo $overdue_followups ?></div>
            </div>
        </div>
    </div>

    <div class="col-xl-9 col-lg-12 col-md-6 col-sm-12 col-12">
                                <div class="card">
                                    <h5 class="card-header">Today's Assigned Leads</h5>
                                    <div class="card-body p-0">
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-striped" id="leads-table">
        <thead>
          <tr>
            <th>Ref. Code</th>
            <th>Company</th>
            <th>Country</th>
            <th>Contacts</th>
            <th>Assigned To</th>
            <th>Call Date</th>
            <th>Follow Up Date</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php 
                            $uwhere = "";
                            if($_settings->userdata('type') != 1)
                                    $uwhere = " and assigned_to = '{$_settings->userdata('id')}' ";

                            $users = $conn->query("SELECT id,CONCAT(lastname,', ', firstname, '', COALESCE(middlename,'')) as fullname FROM `users` where id in (SELECT `user_id` FROM `lead_list` where in_opportunity = 0 {$uwhere}) OR id in (SELECT assigned_to FROM `lead_list` where in_opportunity = 0 {$uwhere})");
                            $user_arr = array_column($users->fetch_all(MYSQLI_ASSOC), 'fullname', 'id');

                            $leads = $conn->query("SELECT l.*, c.company_name as client, c.website, c.city, c.state, c.country, c.follow_up_date, c.calling_date FROM `lead_list` l 
                            INNER JOIN client_list c ON c.lead_id = l.id 
                            WHERE l.in_opportunity = 0 {$uwhere} 
                            ORDER BY l.`status` ASC, UNIX_TIMESTAMP(l.date_created) ASC");


                                while($row = $leads->fetch_assoc()):
                                    
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
                            <td>
                                <?= $row['code'] ?>
                            </td>
                            <td>
                                <?= ucwords($row['client']) ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($row['country']) ?>
                            </td>
                            
                            <td>
                                <?php if (!empty($contacts)): ?>
                                <ul class="list-unstyled mb-0">
                                    <?php foreach($contacts as $c): ?>
                                    <li>
                                        <strong>
                                            <?= htmlspecialchars($c['name']) ?>
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
                                <?php 
                                    switch($row['status']){
                                        case 0: echo '<span class="badge badge-primary">New/Prospect</span>'; break;
                                        case 1: echo '<span class="badge badge-light border">Open</span>'; break;
                                        case 2: echo '<span class="badge badge-primary">Working</span>'; break;
                                        case 3: echo '<span class="badge badge-danger">Not a Target</span>'; break;
                                        case 4: echo '<span class="badge badge-danger">Disqualified</span>'; break;
                                        case 5: echo '<span class="badge badge-info">Nurture</span>'; break;
                                        case 6: echo '<span class="badge badge-success">Opportunity Created</span>'; break;
                                        case 7: echo '<span class="badge badge-danger">Opportunity Lost</span>'; break;
                                        case 8: echo '<span class="badge badge-danger">Inactive</span>'; break;
                                        default: echo '<span class="badge badge-light border">N/A</span>'; break;
                                    }
                                ?>
                            </td>                            
                        </tr>
                        <?php endwhile; ?>
        </tbody>
      </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

</div>

    <div class="col-xl-9 col-lg-12 col-md-6 col-sm-12 col-12">
                                <div class="card">
                                    <h5 class="card-header">Recent Orders</h5>
                                    <div class="card-body p-0">
                                        <div class="table-responsive">
                                            <table class="table">
                                                <thead class="bg-light">
                                                    <tr class="border-0">
                                                        <th class="border-0">#</th>
                                                        <th class="border-0">Image</th>
                                                        <th class="border-0">Product Name</th>
                                                        <th class="border-0">Product Id</th>
                                                        <th class="border-0">Quantity</th>
                                                        <th class="border-0">Price</th>
                                                        <th class="border-0">Order Time</th>
                                                        <th class="border-0">Customer</th>
                                                        <th class="border-0">Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td>1</td>
                                                        <td>
                                                            <div class="m-r-10"><img src="assets/images/product-pic.jpg" alt="user" class="rounded" width="45"></div>
                                                        </td>
                                                        <td>Product #1 </td>
                                                        <td>id000001 </td>
                                                        <td>20</td>
                                                        <td>$80.00</td>
                                                        <td>27-08-2018 01:22:12</td>
                                                        <td>Patricia J. King </td>
                                                        <td><span class="badge-dot badge-brand mr-1"></span>InTransit </td>
                                                    </tr>
                                                    <tr>
                                                        <td>2</td>
                                                        <td>
                                                            <div class="m-r-10"><img src="assets/images/product-pic-2.jpg" alt="user" class="rounded" width="45"></div>
                                                        </td>
                                                        <td>Product #2 </td>
                                                        <td>id000002 </td>
                                                        <td>12</td>
                                                        <td>$180.00</td>
                                                        <td>25-08-2018 21:12:56</td>
                                                        <td>Rachel J. Wicker </td>
                                                        <td><span class="badge-dot badge-success mr-1"></span>Delivered </td>
                                                    </tr>
                                                    <tr>
                                                        <td>3</td>
                                                        <td>
                                                            <div class="m-r-10"><img src="assets/images/product-pic-3.jpg" alt="user" class="rounded" width="45"></div>
                                                        </td>
                                                        <td>Product #3 </td>
                                                        <td>id000003 </td>
                                                        <td>23</td>
                                                        <td>$820.00</td>
                                                        <td>24-08-2018 14:12:77</td>
                                                        <td>Michael K. Ledford </td>
                                                        <td><span class="badge-dot badge-success mr-1"></span>Delivered </td>
                                                    </tr>
                                                    <tr>
                                                        <td>4</td>
                                                        <td>
                                                            <div class="m-r-10"><img src="assets/images/product-pic-4.jpg" alt="user" class="rounded" width="45"></div>
                                                        </td>
                                                        <td>Product #4 </td>
                                                        <td>id000004 </td>
                                                        <td>34</td>
                                                        <td>$340.00</td>
                                                        <td>23-08-2018 09:12:35</td>
                                                        <td>Michael K. Ledford </td>
                                                        <td><span class="badge-dot badge-success mr-1"></span>Delivered </td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="9"><a href="#" class="btn btn-outline-light float-right">View Details</a></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

</div>

    <!-- Toggle Button -->
<div class="col-12 mt-3 text-center">
    
</div>

<!-- Leads by Status (Collapsible Section) -->
<div class="col-12 mt-3">
    <div class="collapse" id="leadsStatusBreakdown">
        <div class="card shadow">
            <div class="card-header bg-gradient-primary text-white">
                <h5>Leads Status Breakdown</h5>
            </div>
            <div class="card-body">
                <table class="table table-bordered table-sm">
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        foreach($status_labels as $status_id => $status_name):
                            $count = $leads_status[$status_id] ?? 0;
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($status_name) ?></td>
                            <td><?php echo $count ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


    <!-- Quick Action Buttons -->
     
    <div class="col-12 mt-3 d-flex gap-2">
        <a href="./?page=leads/manage_lead" class="btn btn-primary"><i class="fas fa-plus"></i> Add New Lead</a>
        <a href="./?page=leads/my_leads" class="btn btn-info"><i class="fas fa-list"></i> View My Leads</a>
        <a href="./?page=opportunities/my_opportunities" class="btn btn-success"><i class="fas fa-lightbulb"></i> View My Opportunities</a>
        <!-- <button class="btn btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#leadsStatusBreakdown" aria-expanded="false" aria-controls="leadsStatusBreakdown">
        Show Leads Status Breakdown
    </button> -->
    </div>
    
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
