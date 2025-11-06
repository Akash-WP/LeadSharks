<?php
require_once('../config.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/phpmailer/src/Exception.php';
require_once __DIR__ . '/../vendor/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/src/SMTP.php';

Class Master extends DBConnection {
	private $settings;
	public function __construct(){
		global $_settings;
		$this->settings = $_settings;
		parent::__construct();
	}
	public function __destruct(){
		parent::__destruct();
	}
	function capture_err(){
		if(!$this->conn->error)
			return false;
		else{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
			return json_encode($resp);
			exit;
		}
	}

	public function send_email($to, $subject, $body, $attachment = null, $cc = '', $bcc = '') {
		$mail = new PHPMailer(true);
		try {
			// Server settings
			$mail->isSMTP();
			$mail->Host = 'smtp.zoho.com'; // e.g., smtp.zoho.com
			$mail->SMTPAuth = true;
			$mail->Username = 'saspartner@woodpeckerind.com';
			$mail->Password = 'W@@dPecker@2025';
			$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
			$mail->Port = 587;

			// Recipients
			$mail->setFrom('saspartner@woodpeckerind.com');
			$mail->addAddress($to);
			if (!empty($cc)) $mail->addCC($cc);
			if (!empty($bcc)) $mail->addBCC($bcc);

			// Content
			$mail->isHTML(true);
			$mail->Subject = $subject;
			$mail->Body    = $body;

			if ($attachment && file_exists($attachment)) {
            $mail->addAttachment($attachment);
        	}

			$mail->send();
			return true;
		} catch (Exception $e) {
			error_log("Email sending failed: {$mail->ErrorInfo}");
			return false;
		}
	}

	function save_source(){
		extract($_POST);
		$data = "";
		foreach($_POST as $k =>$v){
			if(!in_array($k,array('id'))){
				if(!is_numeric($v))
					$v = $this->conn->real_escape_string($v);
				if(!empty($data)) $data .=",";
				$data .= " `{$k}`='{$this->conn->real_escape_string($v)}' ";
			}
		}
		if(empty($id)){
			$sql = "INSERT INTO `source_list` set {$data} ";
		}else{
			$sql = "UPDATE `source_list` set {$data} where id = '{$id}' ";
		}
		$check = $this->conn->query("SELECT * FROM `source_list` where `name` = '{$name}' ".(is_numeric($id) && $id > 0 ? " and id != '{$id}'" : "")." ")->num_rows;
		if($check > 0){
			$resp['status'] = 'failed';
			$resp['msg'] = ' Source Name already exists.';
			
		}else{
			$save = $this->conn->query($sql);
			if($save){
				$rid = !empty($id) ? $id : $this->conn->insert_id;
				$resp['id'] = $rid;
				$resp['status'] = 'success';
				if(empty($id))
					$resp['msg'] = " Source has successfully added.";
				else
					$resp['msg'] = " Source details has been updated successfully.";
			}else{
				$resp['status'] = 'failed';
				$resp['msg'] = "An error occured.";
				$resp['err'] = $this->conn->error."[{$sql}]";
			}
		}
		if($resp['status'] =='success')
			$this->settings->set_flashdata('success',$resp['msg']);
		return json_encode($resp);
	}
	function delete_source(){
		extract($_POST);
		$del = $this->conn->query("UPDATE `source_list` set delete_flag = 1 where id = '{$id}'");
		if($del){
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success'," Source has been deleted successfully.");
		}else{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);
	}
	
	function save_lead(){
	
        $_POST['user_id'] = $this->settings->userdata('id');	
    if(empty($_POST['id'])){
        $prefix = date("Ym-");
        $code = sprintf("%'.05d",1);
        while(true){
            $check = $this->conn->query("SELECT * FROM `lead_list` where code = '{$prefix}{$code}'")->num_rows;
            if($check > 0){
                $code = sprintf("%'.05d",ceil($code) + 1);
            }else{
                break;
            }
        }
        $_POST['code'] = $prefix.$code;
    }

    $lead_allowed_field = ['code', 'source_id', 'interested_in', 'remarks', 'assigned_to', 'user_id', 'status', 'in_opportunity', 'delete_flag', 'date_updated', 'signal_hire'];
	//$client_allowed_field = ['lead_id', 'company_name', 'company_type', 'website', 'contact', 'address', 'other_info'];
	$client_allowed_field = ['lead_id', 'company_name', 'company_type', 'website', 'contact', 'address', 'city', 'state', 'country', 'pincode', 'other_info'];

    extract($_POST);
    $data = "";
    foreach($_POST as $k => $v){
        if(in_array($k, $lead_allowed_field) && !is_array($v)){
            if(!is_numeric($v))
                $v = $this->conn->real_escape_string($v);
            if(!empty($data)) $data .=",";
            $data .= " `{$k}`='{$v}' ";
        }
    }

    if(empty($id)){
        $sql = "INSERT INTO `lead_list` set {$data}";
    } else {
        $sql = "UPDATE `lead_list` set {$data} where id = '{$id}'";
    }

    $save = $this->conn->query($sql);
    if($save){
        $lid = !empty($id) ? $id : $this->conn->insert_id;
        $resp['id'] = $lid;

        $data = "";
        foreach($_POST as $k => $v){
            if(in_array($k, $client_allowed_field) && !is_array($v)){
                if(!is_numeric($v))
                    $v = $this->conn->real_escape_string($v);
                if(!empty($data)) $data .=",";
                $data .= " `{$k}`='{$v}' ";
            }
        }

        if(!empty($data)){
            if(empty($id)){
                $data .= ", `lead_id`='{$lid}' ";
                $sql2 = "INSERT INTO `client_list` set {$data}";
            } else {
                $sql2 = "UPDATE `client_list` set {$data} where `lead_id` = '{$lid}'";
            }
            $save2 = $this->conn->query($sql2);

            if($save2){
                /** Handle Contact Persons Section */
                $this->conn->query("DELETE FROM `contact_persons` WHERE lead_id = '{$lid}'"); // clear old data if update

                if(isset($_POST['contact_person_name']) && is_array($_POST['contact_person_name'])){
                    foreach($_POST['contact_person_name'] as $index => $name){
                        $person_name = $this->conn->real_escape_string($name);
                        $person_contact = $this->conn->real_escape_string($_POST['contact_person_contact'][$index]);
                        $person_email = $this->conn->real_escape_string($_POST['contact_person_email'][$index]);
                        $designation = $this->conn->real_escape_string($_POST['contact_person_designation'][$index]);
						

						$is_lead_contact = (isset($_POST['lead_contact'][0]) && $_POST['lead_contact'][0] == $_POST['contact_person_id'][$index]) ? 1 : 0;
                        $sql3 = "INSERT INTO `contact_persons` (`lead_id`, `name`, `contact`, `email`, `designation`, `is_lead_contact`) 
                                VALUES ('{$lid}', '{$person_name}', '{$person_contact}', '{$person_email}', '{$designation}', '{$is_lead_contact}')";
                        $this->conn->query($sql3);
                    }
                }

				// Fetch assigned user's email if new lead or changed assigned user
				if (!empty($_POST['assigned_to'])) {
					$assigned_to_id = $_POST['assigned_to'];
					$q = $this->conn->query("SELECT email, firstname FROM users WHERE id = '{$assigned_to_id}'");
					if ($q->num_rows > 0) {
						$u = $q->fetch_assoc();
						$to = $u['email'];
						$subject = "New Lead Assigned";
						 $code = $_POST['code'] ?? '';  // ✅ Ensure $code is set
						$body = "Hi {$u['firstname']},<br><br>
								A new lead has been assigned to you.<br>
								Lead Code: <strong>{$code}</strong><br>
								Please log in to view and take action.<br><br>
								Regards,<br>Lead Management System";
						$this->send_email($to, $subject, $body);
					}
				}

                $resp['status'] = 'success';
                $resp['msg'] = empty($id) ? "Lead has been successfully added." : "Lead details have been updated successfully.";
            } else {
                $resp['error'] = $this->conn->error;
                $resp['sql'] = $sql2;
                $resp['status'] = 'failed';
                $resp['msg'] = empty($id) ? "Failed to save lead information." : "Failed to update lead information.";
                if(empty($id)) $this->conn->query("DELETE FROM `lead_list` where id = '{$lid}'");
            }
        } else {
            $resp['status'] = 'failed';
            $resp['msg'] = "Client Information is empty.";
            if(empty($id)) $this->conn->query("DELETE FROM `lead_list` where id = '{$lid}'");
        }

    } else {
        $resp['status'] = 'failed';
        $resp['msg'] = "An error occurred.";
        $resp['err'] = $this->conn->error."[{$sql}]";
    }

    if($resp['status'] == 'success')
        $this->settings->set_flashdata('success', $resp['msg']);
    return json_encode($resp);
}

	function delete_lead(){
		extract($_POST);
		$del = $this->conn->query("DELETE FROM `lead_list` where id = '{$id}'");
		if($del){
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success'," Lead has been deleted successfully.");
		}else{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);
	}
	function save_log(){
		if(empty($_POST['id'])){
			$_POST['user_id'] = $this->settings->userdata('id');
		}
		extract($_POST);
		$_POST['is_updated'] = 1;  // Mark the log as updated
		$get_lead = $this->conn->query("SELECT * FROM `lead_list` where id = '{$lead_id}'");
		$lead_res = $get_lead->fetch_array();
		if(isset($lead_res['status'])){
			$status = $lead_res['status'] < 2 ? 2 : $lead_res['status'];
		}
		$data = "";
		// foreach($_POST as $k =>$v){
		// 	if(!in_array($k,array('id', 'lead_status'))){
		// 		if(!is_numeric($v))
		// 			$v = $this->conn->real_escape_string($v);
		// 		if(!empty($data)) $data .=",";
		// 		$data .= " `{$k}`='{$this->conn->real_escape_string($v)}' ";
		// 	}
		// }
		foreach ($_POST as $k => $v) {
    if (!in_array($k, ['id', 'lead_status'])) {
        if (is_array($v)) continue; // Prevents crash on array

        $v = trim($v);

        // Check for NULL values for optional fields
        $v_escaped = $this->conn->real_escape_string($v);
        if ($v === '') {
            $val = "NULL";
        } else {
            $val = "'$v_escaped'";
        }

        if (!empty($data)) $data .= ",";
        $data .= "`$k` = $val";
    }
}

		if(empty($id)){
			$sql = "INSERT INTO `log_list` set {$data} ";
		}else{
			$sql = "UPDATE `log_list` set {$data} where id = '{$id}' ";
		}
		$save = $this->conn->query($sql);
		if($save){
        // Update lead status if 'lead_status' is provided and not empty
        if(isset($lead_status) && $lead_status !== ""){
            // Fetch old status before updating
            $get_current = $this->conn->query("SELECT status FROM `lead_list` WHERE id = '{$lead_id}'");
            $current_status_row = $get_current->fetch_array();
            $old_status = $current_status_row['status'];

            // Determine in_opportunity flag (1 if status == 6 else 0)
            $in_opportunity = ($lead_status == 6) ? 1 : 0;

            // Update lead_list status and in_opportunity
            $update = $this->conn->query("UPDATE `lead_list` 
                                          SET status = '{$lead_status}', in_opportunity = '{$in_opportunity}' 
                                          WHERE id = '{$lead_id}'");

            // Insert a record into lead_status_history table for audit trail
            $user_id = $this->settings->userdata('id');
            // $this->conn->query("INSERT INTO `lead_status_history` 
            //                    (lead_id, old_status, new_status, changed_by) 
            //                    VALUES ('{$lead_id}', '{$old_status}', '{$lead_status}', '{$user_id}')");

			if ($lead_status != $old_status) {
    // Only insert history if status has changed
    $this->conn->query("INSERT INTO `lead_status_history` 
       (lead_id, old_status, new_status, changed_by) 
       VALUES ('{$lead_id}', '{$old_status}', '{$lead_status}', '{$user_id}')");
}
else {
    // Just update the edited timestamp if log is edited without changing status
    $this->conn->query("UPDATE `lead_status_history` 
        SET date_updated = NOW() 
        WHERE lead_id = '{$lead_id}' 
        ORDER BY changed_at DESC LIMIT 1");
}

        } else {
            // If no new lead_status given, just keep the original or adjusted status
            $this->conn->query("UPDATE `lead_list` SET status = '{$status}' WHERE id = '{$lead_id}'");
        }

        $resp['status'] = 'success';
        if(empty($id))
            $resp['msg'] = "Log has successfully been added.";
        else
            $resp['msg'] = "Log details have been updated successfully.";
    }else{
			$resp['status'] = 'failed';
			$resp['msg'] = "An error occured.";
			$resp['err'] = $this->conn->error."[{$sql}]";
		}
		if($resp['status'] =='success')
			$this->settings->set_flashdata('success',$resp['msg']);
		return json_encode($resp);
	}
	function delete_log(){
		extract($_POST);
		$del = $this->conn->query("DELETE FROM `log_list` where id = '{$id}'");
		if($del){
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success'," Log has been deleted successfully.");
		}else{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);
	}
	function save_note(){
		if(empty($_POST['id'])){
			$_POST['user_id'] = $this->settings->userdata('id');
		}
		extract($_POST);
		$get_lead = $this->conn->query("SELECT * FROM `lead_list` where id = '{$lead_id}'");
		$lead_res = $get_lead->fetch_array();
		if(isset($lead_res['status'])){
			$status = $lead_res['status'] < 2 ? 2 : $lead_res['status'];
		}
		$data = "";
		foreach($_POST as $k =>$v){
			if(!in_array($k,array('id'))){
				if(!is_numeric($v))
					$v = $this->conn->real_escape_string($v);
				if(!empty($data)) $data .=",";
				$data .= " `{$k}`='{$this->conn->real_escape_string($v)}' ";
			}
		}
		if(empty($id)){
			$sql = "INSERT INTO `note_list` set {$data} ";
		}else{
			$sql = "UPDATE `note_list` set {$data} where id = '{$id}' ";
		}
		$save = $this->conn->query($sql);
		if($save){
			$resp['status'] = 'success';
			if(empty($id))
				$resp['msg'] = " Note has successfully added.";
			else
				$resp['msg'] = " Note details has been updated successfully.";
			$this->conn->query("UPDATE `lead_list` set `status` = '{$status}' where id = '{$lead_id}' ");
		}else{
			$resp['status'] = 'failed';
			$resp['msg'] = "An error occured.";
			$resp['err'] = $this->conn->error."[{$sql}]";
		}
		if($resp['status'] =='success')
			$this->settings->set_flashdata('success',$resp['msg']);
		return json_encode($resp);
	}
	function delete_note(){
		extract($_POST);
		$del = $this->conn->query("DELETE FROM `note_list` where id = '{$id}'");
		if($del){
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success'," Note has been deleted successfully.");
		}else{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);
	}
	function update_lead_status(){
    $id = $_POST['id'] ?? null;
    $status = $_POST['lead_status'] ?? null; // 'lead_status' is the form field name
    $in_opportunity = $_POST['in_opportunity'] ?? 0;

    if ($status === null || $id === null) {
        echo json_encode(['status' => 'failed', 'msg' => 'Missing required fields']);
        exit;
    }

    $in_opportunity = ($status == 6 || $in_opportunity == 1) ? 1 : 0;

    $update = $this->conn->query("UPDATE `lead_list` SET status = '{$status}', in_opportunity = '{$in_opportunity}' WHERE id = '{$id}'");

    if($update){
        $resp['status'] = 'success';
        $this->settings->set_flashdata('success', "Lead's Status has been updated successfully.");
    } else {
        $resp['status'] = 'failed';
        $resp['error'] = $this->conn->error;
    }

    echo json_encode($resp);
    exit;
}

function reassign_lead() {
    $lead_ids = isset($_POST['lead_ids']) ? $this->conn->real_escape_string($_POST['lead_ids']) : '';
    $assigned_to = isset($_POST['assigned_to']) ? $this->conn->real_escape_string($_POST['assigned_to']) : '';
    $calling_date = isset($_POST['calling_date']) ? $this->conn->real_escape_string($_POST['calling_date']) : '';

    if (empty($lead_ids) || empty($assigned_to) || empty($calling_date)) {
        echo json_encode([
            'status' => 'failed',
            'msg' => 'Lead IDs, assigned user, and calling date & time are required.'
        ]);
        return;
    }

    // Format calling_date
    $calling_date = date('Y-m-d H:i:s', strtotime($calling_date));

    // Explode the lead_ids string into an array of integers
    $lead_ids_arr = array_filter(array_map('intval', explode(',', $lead_ids)));
    if (empty($lead_ids_arr)) {
        echo json_encode([
            'status' => 'failed',
            'msg' => 'Invalid Lead IDs.'
        ]);
        return;
    }

    $this->conn->begin_transaction();

    try {
        // Prepare statements for update to prevent SQL injection and improve efficiency
        $stmt_update_lead = $this->conn->prepare("UPDATE `lead_list` SET `assigned_to` = ? WHERE `id` = ?");
        $stmt_check_client = $this->conn->prepare("SELECT id FROM `client_list` WHERE `lead_id` = ?");
        $stmt_update_client = $this->conn->prepare("UPDATE `client_list` SET `calling_date` = ? WHERE `lead_id` = ?");
        $stmt_insert_client = $this->conn->prepare("INSERT INTO `client_list` (`lead_id`, `company_name`, `company_type`, `contact`, `address`, `city`, `state`, `country`, `calling_date`) VALUES (?, '', '', '', '', '', '', '', ?)");

        foreach ($lead_ids_arr as $lead_id) {
            // Update lead_list assigned_to
            $stmt_update_lead->bind_param('ii', $assigned_to, $lead_id);
            $stmt_update_lead->execute();
            
            // ✅ Reset is_updated for latest log
            $this->conn->query("UPDATE log_list 
                              SET is_updated = 0 
                              WHERE id = (
                                  SELECT id FROM (
                                  SELECT id FROM log_list 
                                  WHERE lead_id = '{$lead_id}' AND is_updated = 1
                                  ORDER BY id DESC 
                                  LIMIT 1
                                  ) AS latest
            )");

            // Check if client_list entry exists
            $stmt_check_client->bind_param('i', $lead_id);
            $stmt_check_client->execute();
            $stmt_check_client->store_result();

            if ($stmt_check_client->num_rows > 0) {
                // Update calling_date
                $stmt_update_client->bind_param('si', $calling_date, $lead_id);
                $stmt_update_client->execute();
            } else {
                // Insert new client_list row
                $stmt_insert_client->bind_param('is', $lead_id, $calling_date);
                $stmt_insert_client->execute();
            }
        }

		$leadInfoText = "";
		$to = "";
		$firstName = "";

		// After $this->conn->commit();
		foreach ($lead_ids_arr as $lead_id) {
			$q = $this->conn->query("SELECT u.email, u.firstname, l.code FROM lead_list l 
				JOIN users u ON l.assigned_to = u.id 
				WHERE l.id = '{$lead_id}'");

			if ($q->num_rows > 0) {
				$row = $q->fetch_assoc();

				// Set recipient details only once (assuming all leads assigned to same user)
				if (empty($to)) {
					$to = $row['email'];
					$firstName = $row['firstname'];
				}

				// Append lead info to the text string
				$leadInfoText .= "Lead Code: {$row['code']}\n";
			}
		}

		// Proceed only if we have at least one lead
		if (!empty($to) && !empty($leadInfoText)) {
			// Save to text file
			$filename = 'reassigned_leads_' . date('Ymd_His') . '.txt';
			$filepath = sys_get_temp_dir() . '/' . $filename;
			file_put_contents($filepath, $leadInfoText);

			// Prepare email content
			$subject = "Reassigned Leads Summary";
			$body = "Hi {$firstName},<br><br>
					Please find the attached file containing the list of leads reassigned to you.<br>
					Log in to the system for more details.<br><br>
					Thanks,<br>Leads Management System";

			// Send email with attachment
			$this->send_email($to, $subject, $body, $filepath);

			// Optional: Delete the file after sending
			unlink($filepath);
		}


        // Close statements
        $stmt_update_lead->close();
        $stmt_check_client->close();
        $stmt_update_client->close();
        $stmt_insert_client->close();

        $this->conn->commit();

        echo json_encode([
            'status' => 'success',
            'msg' => 'Leads reassigned and calling date & time updated.'
        ]);
        $this->settings->set_flashdata('success', 'Leads reassigned successfully.');
    } catch (Exception $e) {
        $this->conn->rollback();
        echo json_encode([
            'status' => 'failed',
            'msg' => 'Failed to update leads. Error: ' . $e->getMessage()
        ]);
    }
}
}

$Master = new Master();
$action = !isset($_GET['f']) ? 'none' : strtolower($_GET['f']);
$sysset = new SystemSettings();
switch ($action) {
	case 'save_source':
		echo $Master->save_source();
	break;
	case 'delete_source':
		echo $Master->delete_source();
	break;
	case 'save_lead':
		echo $Master->save_lead();
	break;
	case 'delete_lead':
		echo $Master->delete_lead();
	break;
	case 'save_log':
		echo $Master->save_log();
	break;
	case 'delete_log':
		echo $Master->delete_log();
	break;
	case 'save_note':
		echo $Master->save_note();
	break;
	case 'delete_note':
		echo $Master->delete_note();
	break;
	case 'update_lead_status':
		echo $Master->update_lead_status();
	break;
	case 'reassign_lead':
    echo $Master->reassign_lead();
    break;
	default:
		// echo $sysset->index();
		break;
}