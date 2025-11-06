<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);


$user_arr = [];
// Get all distinct assigned_to user IDs from lead_list
$assigned_ids_result = $conn->query("SELECT DISTINCT assigned_to FROM lead_list");
$assigned_ids = [];

while ($row = $assigned_ids_result->fetch_assoc()) {
    $assigned_ids[] = $row['assigned_to'];
}

// Find unlisted assigned_to IDs (users not in $user_arr)
$other_users_exist = false;
foreach ($assigned_ids as $id) {
    if (!array_key_exists($id, $user_arr)) {
        $other_users_exist = true;
        break;
    }
}

$qry = $conn->query("
    SELECT 
    u.id, 
    CONCAT(u.lastname, ', ', u.firstname, '', COALESCE(u.middlename, '')) AS full_name,
    COUNT(DISTINCT l1.id) AS created_leads,
    COUNT(DISTINCT l2.id) AS assigned_leads
FROM users u
LEFT JOIN lead_list l1 ON l1.user_id = u.id
LEFT JOIN lead_list l2 ON l2.assigned_to = u.id
GROUP BY u.id
ORDER BY full_name
");

while ($row = $qry->fetch_assoc()) {
    $user_arr[$row['id']] = $row['full_name'];
}

?>

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="../assets/vendor/bootstrap/css/bootstrap.min.css">
    <link href="../assets/vendor/fonts/circular-std/style.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/libs/css/style.css">
    <link rel="stylesheet" href="../assets/vendor/fonts/fontawesome/css/fontawesome-all.css">
    <link rel="stylesheet" type="text/css" href="../assets/vendor/datatables/css/dataTables.bootstrap4.css">
    <link rel="stylesheet" type="text/css" href="../assets/vendor/datatables/css/buttons.bootstrap4.css">
    <link rel="stylesheet" type="text/css" href="../assets/vendor/datatables/css/select.bootstrap4.css">
    <link rel="stylesheet" type="text/css" href="../assets/vendor/datatables/css/fixedHeader.bootstrap4.css">
    <script src="../assets/vendor/datatables/js/dataTables.buttons.min.js"></script>
    <script src="../assets/vendor/datatables/js/buttons.bootstrap4.min.js"></script>
    <script src="../assets/vendor/datatables/js/jszip.min.js"></script>
    <script src="../assets/vendor/datatables/js/buttons.html5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

</head>

<style>
    .img-thumb-path {
        height: 100px;
        width: 80px;
        object-fit: scale-down;
        object-position: center center;
    }
</style>
<div class="card card-outline card-primary rounded-0 shadow">
    <div class="card-header">
        <h3 class="card-title">List of Leads</h3>
        <!-- <? // if ($_settings->userdata('type') == 1): 
                ?> -->
        <?php if (in_array($_settings->userdata('type'), [1, 3])): ?>
            <div class="card-tools">
                <a href="./?page=leads/manage_lead" class="btn btn-flat btn-sm btn-primary"><span
                        class="fas fa-plus"></span> Add New Leads</a>
            </div>
        <?php endif ?>
        <!-- <? // endif; 
                ?> -->
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
                    <!-- <input type="text" id="filter-country" class="form-control form-control-sm"
                        placeholder="Filter Country"> -->
                    <select id="filter-country" class="form-control form-control-sm">
                        <!-- <option value="India">India</option> -->
                    </select>
                </div>
                <div class="col-md-2 col-sm-6">
                    <select id="statusFilter" class="form-control form-control-sm">
                        <option value="all">All Status</option>
                        <option value="0">Lead – Uncontacted</option>
                        <option value="1">Prospect – Contact Made</option>
                        <option value="2">Qualified – Need Validated</option>
                        <option value="3">Solution Fit / Discovery</option>
                        <option value="4">Proposal / Value Proposition</option>
                        <option value="5">Negotiation</option>
                        <option value="6">Closed – Won</option>
                        <option value="7">Closed – Lost</option>
                    </select>
                </div>

                <?php if (in_array($_settings->userdata('type'), [1, 3])): ?>

                    <div class="col-md-2 col-sm-6">
                        <select id="filter-assigned" class="form-control form-control-sm">
                            <option value="">Assigned To (All)</option>
                            <?php foreach ($user_arr as $uid => $uname): ?>
                                <option value="<?= ucwords($uname) ?>">
                                    <?= ucwords($uname) ?>
                                </option>
                            <?php endforeach; ?>
                            <?php if ($other_users_exist): ?>
                                <option value="other">Other (Not in Current List)</option>
                            <?php endif; ?>
                        </select>
                    </div>
                <?php endif ?>
                <div class="col-md-2 col-sm-6">
                    <select id="filter-created" class="form-control form-control-sm">
                        <option value="">Created By (All)</option>
                        <?php foreach ($user_arr as $uid => $uname): ?>
                            <option value="<?= ucwords($uname) ?>">
                                <?= ucwords($uname) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 col-sm-6">
                    <select id="filter-company-type" class="form-control form-control-sm">
                        <option value="">Company Type (All)</option>
                        <!-- Options will be populated dynamically by DataTables -->
                    </select>
                </div>
            </div>

            <div class="card-tools" style="margin-bottom: 5px;">
                <?php if (in_array($_settings->userdata('type'), [1, 3])): ?>

                    <a href="excel/lead_template.xlsx" class="btn btn-sm btn-success" download>
                        <i class="fa fa-download"></i> Download Excel
                    </a>

                    <form id="importForm" method="post" enctype="multipart/form-data" style="display:inline-block;">
                        <input type="file" name="import_file" id="import_file" accept=".xlsx, .xls" style="display:none;">
                        <button type="button" class="btn btn-sm btn-warning"
                            onclick="document.getElementById('import_file').click();">
                            <i class="fa fa-upload"></i> Excel
                        </button>
                    </form>
                <?php endif ?>
                <button id="export-excel" class="btn btn-success btn-sm">
                    <i class="fa fa-file-excel"></i> Export Excel
                </button>
                <?php if (in_array($_settings->userdata('type'), [1, 3])): ?>
                    <button id="reassign-selected" class="btn btn-info btn-sm" disabled>Reassign Selected</button>
                <?php endif ?>
            </div>


            <div class="table-responsive">
                <table class="table table-bordered table-hover table-striped" id="leads-table">
                    <thead class="thead-dark">
                        <tr>
                            <?php if (in_array($_settings->userdata('type'), [1, 3])): ?>
                                <th class="no-sort"><input type="checkbox" id="select-all"></th>
                            <?php endif ?>
                            <th>Ref. Code</th>
                            <th>Company</th>
                            <th>Company Type</th>
                            <th>Country</th>
                            <th style="display: none;">State</th>
                            <th style="display: none;">City</th>

                            <!-- <th>Website</th> -->
                            <th>Interested In</th>
                            <th>Contacts</th>
                            <th>Assigned To</th>
                            <th>Status</th>
                            <th>Calling</th>
                            <th>Follow-Up</th>
                            <th>Created By</th>
                            <th>Date Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $uwhere = "";
                        if ($_settings->userdata('type') != 1 && $_settings->userdata('type') != 3)
                            $uwhere = " and assigned_to = '{$_settings->userdata('id')}' ";

                        $users = $conn->query("SELECT id,CONCAT(lastname,', ', firstname, ' ', COALESCE(middlename,'')) as fullname FROM `users` where id in (SELECT `user_id` FROM `lead_list` where in_opportunity = 0 {$uwhere}) OR id in (SELECT assigned_to FROM `lead_list` where in_opportunity = 0 {$uwhere})");
                        $user_arr = array_column($users->fetch_all(MYSQLI_ASSOC), 'fullname', 'id');

                        $leads = $conn->query("SELECT l.*, c.company_name as client, c.website, c.city, c.state, c.country, c.follow_up_date, c.calling_date, c.company_type FROM `lead_list` l 
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
                                <?php if (in_array($_settings->userdata('type'), [1, 3])): ?>
                                    <td><input type="checkbox" class="lead-checkbox" name="selected_leads[]"
                                            value="<?= $row['id'] ?>"></td>
                                <?php endif ?>
                                <td>
                                    <?= $row['code'] ?>
                                </td>
                                <td>
                                    <?= ucwords($row['client']) ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($row['company_type']) ?>
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
                                <td>
                                    <?= date("D M d, Y h:i A", strtotime($row['date_created'])) ?>
                                </td>
                                <td>
                                    <a class="btn btn-sm btn-light border" href="./?page=view_lead&id=<?= $row['id'] ?>"><i
                                            class="fa fa-eye"></i></a>
                                    <?php if (in_array($_settings->userdata('type'), [2])): ?>
                                        <a class="btn btn-sm btn-info border"
                                            href="./?page=Sharks_portal&company_search=<?= urlencode($row['client']) ?>&date_search="
                                            title="Search this Company">
                                            <i class="fa fa-search"></i>
                                        </a>
                                    <?php endif; ?>

                                    <?php if (in_array($_settings->userdata('type'), [1, 3])): ?>
                                        <a class="btn btn-sm btn-primary"
                                            href="./?page=leads/manage_lead&id=<?= $row['id'] ?>"><i class="fa fa-edit"></i></a>
                                        <button class="btn btn-sm btn-danger delete_data" data-id="<?= $row['id'] ?>"><i
                                                class="fa fa-trash"></i></button>
                                        <!-- <button class="btn btn-sm btn-info reassign-btn" data-id="<?= $row['id'] ?>">Reassign</button>  New button
         -->
                                    <?php endif; ?>

                                </td>

                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

                <div class="text-center d-none" id="noData">
                    <center>No result.</center>
                </div>
            </div>
        </div>
    </div>
</div>


<script>
    $(document).ready(function() {
        $('.delete_data').click(function() {
            _conf("Are you sure to delete this Lead Information permanently?", "delete_lead", [$(this).attr('data-id')]);
        });

        $('#search').on('input', function() {
            var _search = $(this).val().toLowerCase();
            var visibleCount = 0;
            $('#leads-table tbody tr').each(function() {
                var rowText = $(this).text().toLowerCase();
                var match = rowText.includes(_search);
                $(this).toggle(match);
                if (match) visibleCount++;
            });
            $('#noData').toggleClass('d-none', visibleCount > 0);
        });
        $('#export-excel').click(function() {
            var table = $('#leads-table').DataTable();

            var rows = [];

            // Add "Sr." column header
            var headers = ["Sr."];
            $('#leads-table thead th').each(function() {
                var headerText = $(this).text().trim();
                if ($(this).is(':visible') && headerText !== "Actions") {
                    headers.push(headerText);
                }
            });
            rows.push(headers);

            // Get ALL row data from DataTables (ignores pagination)
            var allData = table.rows({
                search: 'applied'
            }).data().toArray();

            allData.forEach(function(rowData, index) {
                var row = [index + 1]; // Sr. number starts from 1
                rowData.forEach(function(cell, i) {
                    var colHeader = $('#leads-table thead th').eq(i).text().trim();

                    // Skip Actions + hidden cols
                    if (colHeader !== "Actions" && $('#leads-table thead th').eq(i).is(':visible')) {
                        // Convert HTML cell -> plain text
                        var text = $('<div>').html(cell).text().trim().replace(/\s+/g, " ");
                        row.push(text);
                    }
                });
                rows.push(row);
            });

            // Convert to Excel
            var wb = XLSX.utils.book_new();
            var ws = XLSX.utils.aoa_to_sheet(rows);
            XLSX.utils.book_append_sheet(wb, ws, "Leads");
            XLSX.writeFile(wb, "leads_export.xlsx");
        });


    });

    function delete_lead($id) {
        start_loader();
        $.ajax({
            url: _base_url_ + "classes/Master.php?f=delete_lead",
            method: "POST",
            data: {
                id: $id
            },
            dataType: "json",
            error: err => {
                console.log(err);
                alert_toast("An error occurred.", 'error');
                end_loader();
            },
            success: function(resp) {
                if (typeof resp == 'object' && resp.status == 'success') {
                    location.reload();
                } else {
                    alert_toast("An error occurred.", 'error');
                    end_loader();
                }
            }
        });
    }
</script>

<script>
    $(document).ready(function() {
        $('#import_file').on('change', function() {
            if (this.files.length > 0) {
                $('#importForm').submit();
            }
        });

        $('#importForm').on('submit', function(e) {
            e.preventDefault();

            var formData = new FormData(this);

            $.ajax({
                url: 'excel/import_leads.php', // your import script path
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function(response) {
                    console.log('Raw response:', response); // <-- Debug log to see response in browser console

                    let data = null;
                    try {
                        data = JSON.parse(response); // Try to parse JSON
                    } catch (e) {
                        // Not JSON, treat as plain string
                    }

                    if (data && data.status === 'needs_assignment') {
                        alert(data.message); // Show JSON error message
                        console.log('Row data needing assignment:', data.row_data); // Optional detailed info
                    } else {
                        alert(response); // Show normal response text
                        location.reload(); // Reload after success (optional)
                    }
                },
                error: function() {
                    alert('Import failed, please try again.');
                }
            });
        });
    });
</script>

<script>
    $(document).ready(function() {
        var table = $('#leads-table').DataTable({
            stateSave: true,
            "order": [
                [11, "desc"]
            ], // Corrected column index for 'Date Created'
            "columnDefs": [{
                    "orderable": false,
                    "targets": [0, 6, 11]
                } // disable sort for checkbox, contacts, actions
            ]
        });

        // ===== FILTERS =====
        function updateCountryFilter() {
            const countries = [];
            table.column(4, {
                search: 'applied'
            }).nodes().each(function(cell) {
                const country = $(cell).text().trim();
                if (country && !countries.includes(country)) {
                    countries.push(country);
                }
            });
            countries.sort();
            const dropdown = $('#filter-country');
            const currentValue = dropdown.val();
            dropdown.empty().append('<option value="">All Countries</option>');
            countries.forEach(country => dropdown.append(`<option value="${country}">${country}</option>`));
            if (currentValue && countries.includes(currentValue)) dropdown.val(currentValue);
        }

        updateCountryFilter();
        table.on('draw', function() {
            updateCountryFilter();
            updateReassignButtonState(); // Needed to re-evaluate checkboxes on new page
        });

        $('#filter-country').on('change', function() {
            table.column(4).search(this.value).draw();
        });

        <?php if (in_array($_settings->userdata('type'), [1, 3])): ?>

            $('#statusFilter').on('change', function() {
                const val = this.options[this.selectedIndex].text;
                table.column(10).search(val === "All Status" ? "" : '^' + val + '$', true, false).draw();
            });

            $('#filter-assigned').on('change', function() {
                table.column(9).search(this.value).draw();
            });

            $('#filter-created').on('change', function() {
                table.column(13).search(this.value).draw();
            });
        <?php endif ?>
        <?php if (in_array($_settings->userdata('type'), [2])): ?>

            $('#statusFilter').on('change', function() {
                const val = this.options[this.selectedIndex].text;
                table.column(9).search(val === "All Status" ? "" : '^' + val + '$', true, false).draw();
            });

            $('#filter-assigned').on('change', function() {
                table.column(8).search(this.value).draw();
            });

            $('#filter-created').on('change', function() {
                table.column(12).search(this.value).draw();
            });
        <?php endif ?>

        // ===== CHECKBOX + SELECT ALL =====
        const $selectAll = $('#select-all');
        const $reassignBtn = $('#reassign-selected');

        function getCurrentPageCheckboxes() {
            return table.rows({
                page: 'current'
            }).nodes().to$().find('.lead-checkbox');
        }

        function updateReassignButtonState() {
            const anyChecked = $('.lead-checkbox:checked').length > 0;
            $reassignBtn.prop('disabled', !anyChecked);
        }

        $selectAll.on('change', function() {
            const isChecked = this.checked;
            getCurrentPageCheckboxes().prop('checked', isChecked);
            updateReassignButtonState();
        });

        $('#leads-table').on('change', '.lead-checkbox', function() {
            const $currentPage = getCurrentPageCheckboxes();
            const allChecked = $currentPage.length === $currentPage.filter(':checked').length;
            $selectAll.prop('checked', allChecked);
            updateReassignButtonState();
        });

        // ===== REASSIGN BUTTON =====
        $reassignBtn.on('click', function() {
            const selectedIds = $('.lead-checkbox:checked').map(function() {
                return $(this).val();
            }).get();

            if (selectedIds.length === 0) {
                alert("Please select at least one lead.");
                return;
            }

            const idsStr = selectedIds.join(',');
            uni_modal('Reassign Lead', 'leads/reassign_form.php?ids=' + encodeURIComponent(idsStr));
        });

        updateReassignButtonState();
    });
</script>
<script>
    // ===== COMPANY TYPE FILTER =====
    function updateCompanyTypeFilter() {
        const companyTypes = [];
        table.column(3 + 1, {
            search: 'applied'
        }).nodes().each(function(cell) {
            const type = $(cell).text().trim();
            if (type && !companyTypes.includes(type)) {
                companyTypes.push(type);
            }
        });
        companyTypes.sort();
        const dropdown = $('#filter-company-type');
        const currentValue = dropdown.val();
        dropdown.empty().append('<option value="">Company Type (All)</option>');
        companyTypes.forEach(type => dropdown.append(`<option value="${type}">${type}</option>`));
        if (currentValue && companyTypes.includes(currentValue)) dropdown.val(currentValue);
    }

    updateCompanyTypeFilter();
    table.on('draw', function() {
        updateCompanyTypeFilter();
    });

    $('#filter-company-type').on('change', function() {
        table.column(4).search(this.value).draw(); // Adjust column index properly
    });
</script>