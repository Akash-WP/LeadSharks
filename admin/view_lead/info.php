<?php
$lead_id = isset($_GET['id']) ? $_GET['id'] : 0;

// 🔹 Get lead info from lead_list
$lead = $conn->query("SELECT * FROM lead_list WHERE id = '$lead_id'")->fetch_assoc();
if ($lead) {
    $code = $lead['code'];
    $status = $lead['status'];
    $interested_in = $lead['interested_in'];
    $source = $lead['source_id'];
    $assigned_to = $lead['assigned_to'];
    $user_id = $lead['user_id'];
    $remarks = $lead['remarks'];
    $date_created = $lead['date_created'];
    $date_updated = $lead['date_updated'];
    $signal_hire = isset($lead['signal_hire']) ? (int)$lead['signal_hire'] : 0;

}

// 🔹 Get company info from client_list
$client = $conn->query("SELECT * FROM client_list WHERE lead_id = '$lead_id'")->fetch_assoc();
if ($client) {
    $company_name = $client['company_name'];
    $company_type = $client['company_type'];
    $website = $client['website'];
    $address = $client['address'];
    $city = $client['city'];
    $state = $client['state'];
    $country = $client['country'];
    $pincode = $client['pincode'];
    $other_info = $client['other_info'];
}
?>
<?php
$user_arr = [];
$users = $conn->query("SELECT id, firstname, middlename, lastname FROM users");
while ($row = $users->fetch_assoc()) {
    $full_name = $row['lastname'] . ', ' . $row['firstname'];
    if (!empty($row['middlename'])) {
        $full_name .= ' ' . $row['middlename'];
    }
    $user_arr[$row['id']] = $full_name;
}
?>


<div class="container-fluid">
    <table class="table table-bordered">
        <tbody>
            <tr>
                <th width="30%">Ref. Code</th>
                <td><?= isset($code) ? $code : "" ?></td>
            </tr>

            <tr class="table-secondary text-center">
                <th colspan="2">Company Information</th>
            </tr>
            <tr>
                <th>Name</th>
                <td><?= isset($company_name) ? $company_name : "" ?></td>
            </tr>
            <tr>
                <th>Type</th>
                <td><?= isset($company_type) ? $company_type : "" ?></td>
            </tr>
            <tr>
                <th>Website </th>
                <td><?= isset($website) ? $website : "" ?></td>
            </tr>
            <tr>
                <th>Address</th>
                <td><?= isset($address) ? $address : "" ?></td>
            </tr>
            <tr>
                <th>City</th>
                <td><?= isset($city) ? $city : "" ?></td>
            </tr>
            <tr>
                <th>State</th>
                <td><?= isset($state) ? $state : "" ?></td>
            </tr>
            <tr>
                <th>Country</th>
                <td><?= isset($country) ? $country : "" ?></td>
            </tr>
            <tr>
                <th>Pin Code</th>
                <td><?= isset($pincode) ? $pincode : "" ?></td>
            </tr>
            <tr>
                <th>Other Information</th>
                <td><?= isset($other_info) ? $other_info : "" ?></td>
            </tr>

            <tr class="table-secondary text-center">
                <th colspan="2">Lead Information</th>
            </tr>
            <tr>
                <th>Interested In</th>
                <td><?= isset($interested_in) ? $interested_in : "" ?></td>
            </tr>
            <tr>
                <th>Lead Source</th>
                <td><?= isset($source) ? $source : "" ?></td>
            </tr>
            <tr>
                <th>Assigned To</th>
                <td><?= isset($user_arr[$assigned_to]) ? ucwords($user_arr[$assigned_to]) : "Not Assigned yet." ?></td>
            </tr>
            <tr>
                <th>Remarks</th>
                <td><?= isset($remarks) ? $remarks : "" ?></td>
            </tr>
            <tr>
                <th>Created By</th>
                <td><?= isset($user_arr[$user_id]) ? ucwords($user_arr[$user_id]) : "" ?></td>

            </tr>
            <tr>
                <th>Date Created</th>
                <td><?= isset($date_created) ? date("M d, Y h:i A", strtotime($date_created)) : "" ?></td>
            </tr>
            <tr>
                <th>Last Update</th>
                <td><?= isset($date_updated) ? date("M d, Y h:i A", strtotime($date_updated)) : "" ?></td>
            </tr>
            <tr>
                <th>Status</th>
                <td>
                    <?php
                    $status = isset($status) ? $status : '';
                    switch ($status) {
                        case 0:
                            echo '<span class="badge badge-secondary bg-gradient-secondary px-3 rounded-pill">Lead – Uncontacted</span>';
                            break;
                        case 1:
                            echo '<span class="badge badge-primary bg-gradient-primary px-3 rounded-pill">Prospect – Contact Made</span>';
                            break;
                        case 2:
                            echo '<span class="badge badge-info bg-gradient-info px-3 rounded-pill">Qualified – Need Validated</span>';
                            break;
                        case 3:
                            echo '<span class="badge badge-warning bg-gradient-warning px-3 rounded-pill">Solution Fit / Discovery</span>';
                            break;
                        case 4:
                            echo '<span class="badge badge-primary bg-gradient-primary px-3 rounded-pill">Proposal / Value Proposition</span>';
                            break;
                        case 5:
                            echo '<span class="badge badge-warning bg-gradient-warning px-3 rounded-pill">Negotiation</span>';
                            break;
                        case 6:
                            echo '<span class="badge badge-success bg-gradient-success px-3 rounded-pill">Closed – Won</span>';
                            break;
                        case 7:
                            echo '<span class="badge badge-danger bg-gradient-danger px-3 rounded-pill">Closed – Lost</span>';
                            break;
                        default:
                            echo '<span class="badge badge-light bg-gradient-light border px-3 rounded-pill">N/A</span>';
                            break;
                    }
                    ?>
                    <!-- <span class="ml-3">
                        <a href="javascript:void(0)" id="update_lead_status">Update Status</a>
                    </span> -->
                </td>
            </tr>
            <tr>
                <th>Is SignalHire Used?</th>
                <td><?= isset($signal_hire) && $signal_hire == 1 ? 'Yes' : 'No' ?></td>
            </tr>
        </tbody>
    </table>
</div>

<script>
    $(function () {
        $('#update_lead_status').click(function () {
            uni_modal("Update Lead's Status", "view_lead/update_lead_status.php?id=<?= isset($id) ? $id : '' ?>")
        })
    })
</script>