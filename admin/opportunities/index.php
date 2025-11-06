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
    .img-thumb-path{
        height:100px;
        width:80px;
        object-fit:scale-down;
        object-position:center center;
    }
</style>
<div class="card card-outline card-primary rounded-0 shadow">
    <div class="card-header">
        <h3 class="card-title">List of Opportunities</h3>
        <?php //if($_settings->userdata('type') == 1): ?>
        <div class="card-tools">
            <a href="./?page=opportunities/manage_opportunity" class="btn btn-flat btn-sm btn-primary"><span class="fas fa-plus"></span> Add New Leads</a>
        </div>
        <?php //endif; ?>
    </div>
    <div class="card-body">
        <div class="container-fluid">
            <div class="row justify-content-center mb-3">
                <div class="col-lg-5 col-md-6 col-sm-12">
                    <div class="input-group input-group-sm">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Search</span>
                        </div>
                        <input type="search" id="search" class="form-control">
                        <div class="input-group-append">
                            <span class="input-group-text"><i class="fa fa-search"></i></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add filter inputs above the table -->
            <div class="row mb-3">
                <div class="col-md-2 col-sm-6">
                    <input type="text" id="filter-country" class="form-control form-control-sm"
                        placeholder="Filter Country">
                </div>
                <div class="col-md-2 col-sm-6">
                    <input type="text" id="filter-state" class="form-control form-control-sm"
                        placeholder="Filter State">
                </div>
                <div class="col-md-2 col-sm-6">
                    <input type="text" id="filter-city" class="form-control form-control-sm" placeholder="Filter City">
                </div>
                <div class="col-md-2 col-sm-6">
                        <select id="statusFilter" class="form-control form-control-sm w-auto d-inline-block">
                            <option value="all">All</option>
                            <option value="0">New/Prospect</option>
                            <option value="1">Open</option>
                            <option value="2">Working</option>
                            <option value="3">Not a Target</option>
                            <option value="4">Disqualified</option>
                            <option value="5">Nurture</option>
                            <option value="6">Opportunity Created</option>
                            <option value="7">Opportunity Lost</option>
                            <option value="8">Inactive</option>
                        </select>                
                    </div>
                </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover table-striped" id="lead-table">
                    <thead class="thead-dark">
                        <tr class="bg-primary text-light">
                            <th>Ref. Code</th>
                            <th>Remarks</th>
                            <th>Client</th>
                            <th>City</th>
                            <th>State</th>
                            <th>Country</th>
                            <th>Contacts</th> <!-- New column -->
                            <th>Interested In</th>
                            <th>Assigned To</th>
                            <th>Status</th>
                            <th>Created By</th>
                            <th>Created Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="lead-list">
                        <?php 
                        $uwhere = "";
                        if($_settings->userdata('type') != 1)
                            $uwhere = " and assigned_to = '{$_settings->userdata('id')}' ";
                        $users = $conn->query("SELECT id,CONCAT(lastname,', ', firstname, '', COALESCE(middlename,'')) as fullname FROM `users` where id in (SELECT `user_id` FROM `lead_list` where in_opportunity = 1 {$uwhere}) OR id in (SELECT assigned_to FROM `lead_list` where in_opportunity = 1 {$uwhere})");
                        $user_arr = array_column($users->fetch_all(MYSQLI_ASSOC),'fullname','id');
                        $leads = $conn->query("SELECT l.*, c.company_name as client, c.website, c.city, c.state, c.country FROM `lead_list` l 
    INNER JOIN client_list c ON c.lead_id = l.id 
    WHERE l.in_opportunity = 1 {$uwhere} 
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
                        ?>
                        <tr class="list-item" data-status="<?= $row['status'] ?>">
                            <td><b><?= $row['code'] ?></b></td>
                            <td><?= $row['remarks'] ?></td>
                            <td><?= ucwords($row['client']) ?></td>
                            <td>
                                <?= htmlspecialchars($row['city']) ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($row['state']) ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($row['country']) ?>
                            </td>
                            <td>
                <?php if (!empty($contacts)): ?>
                    <ul class="list-unstyled mb-0">
                        <?php foreach($contacts as $c): ?>
                            <li>
                                <strong><?= htmlspecialchars($c['name']) ?></strong><br>
                                <?= htmlspecialchars($c['contact']) ?><br>
                                <small><?= htmlspecialchars($c['email']) ?><br><?= htmlspecialchars($c['designation']) ?></small>
                            </li>
                            <hr class="my-1">
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <span class="text-muted">No Contacts</span>
                <?php endif; ?>
            </td>
                            <td><?= $row['interested_in'] ?></td>
                            <td><?= isset($user_arr[$row['assigned_to']]) ? ucwords($user_arr[$row['assigned_to']]) : "Not Assigned Yet." ?></td>
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
                            <td><?= isset($user_arr[$row['user_id']]) ? ucwords($user_arr[$row['user_id']]) : "N/A" ?></td>
                            <td><?= date("D M d, Y h:i A", strtotime($row['date_created'])) ?></td>
                            <td>
                                <a class="btn btn-sm btn-light border" href="./?page=view_lead&id=<?= $row['id'] ?>"><i class="fa fa-eye"></i></a>
                                <?php if (in_array($_settings->userdata('type'), [1, 3])): ?>
                                    <a class="btn btn-sm btn-primary" href="./?page=opportunities/manage_opportunity&id=<?= $row['id'] ?>"><i class="fa fa-edit"></i></a>
                                    <button class="btn btn-sm btn-danger delete_data" data-id="<?= $row['id'] ?>"><i class="fa fa-trash"></i></button>
                                <?php endif; ?>
                            </td>

                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <div class="text-center d-none" id="noData"><center>No result.</center></div>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function(){
        $('.delete_data').click(function(){
            _conf("Are you sure to delete this Lead Information permanently?", "delete_lead", [$(this).attr('data-id')])
        })
        $('#search').on('input', function(){
            var _search = $(this).val().toLowerCase();
            $('#lead-list .list-item').each(function(){
                var txt = $(this).text().toLowerCase();
                $(this).toggle(txt.includes(_search));
            })
            $('#noData').toggle($('#lead-list .list-item:visible').length === 0);
        })
    })

    function delete_lead($id){
        start_loader();
        $.ajax({
            url: _base_url_ + "classes/Master.php?f=delete_lead",
            method: "POST",
            data: { id: $id },
            dataType: "json",
            error: err => {
                console.log(err)
                alert_toast("An error occurred.", 'error');
                end_loader();
            },
            success: function(resp){
                if(typeof resp == 'object' && resp.status == 'success'){
                    location.reload();
                } else {
                    alert_toast("An error occurred.", 'error');
                    end_loader();
                }
            }
        })
    }

    function filterTable() {
    var globalSearch = $('#search').val().toLowerCase();
    var cityFilter = $('#filter-city').val().toLowerCase();
    var stateFilter = $('#filter-state').val().toLowerCase();
    var countryFilter = $('#filter-country').val().toLowerCase();
    var statusFilter = $('#statusFilter').val(); // status is numeric string or 'all'

    var visibleCount = 0;

    $('#lead-table tbody tr').each(function () {
        var $row = $(this);
        var rowText = $row.text().toLowerCase();

        var cityText = $row.find('td').eq(3).text().toLowerCase();     // Adjusted column index
        var stateText = $row.find('td').eq(4).text().toLowerCase();    // Adjusted column index
        var countryText = $row.find('td').eq(5).text().toLowerCase();  // Adjusted column index

        var rowStatus = $row.data('status');

        var matchesCity = cityFilter === "" || cityText.includes(cityFilter);
        var matchesState = stateFilter === "" || stateText.includes(stateFilter);
        var matchesCountry = countryFilter === "" || countryText.includes(countryFilter);
        var matchesStatus = statusFilter === "all" || rowStatus == statusFilter;
        var matchesGlobal = globalSearch === "" || rowText.includes(globalSearch);

        if (matchesCity && matchesState && matchesCountry && matchesStatus && matchesGlobal) {
            $row.show();
            visibleCount++;
        } else {
            $row.hide();
        }
    });

    $('#noData').toggleClass('d-none', visibleCount > 0);
}


// Bind all filters to run the filter function
$('#search, #filter-city, #filter-state, #filter-country, #statusFilter').on('input change', filterTable);


</script>
