<?php
require_once '../config.php';
require_once 'Master.php';
class Login extends DBConnection {
	private $settings;
	public function __construct(){
		global $_settings;
		$this->settings = $_settings;

		parent::__construct();
		ini_set('display_error', 1);
	}
	public function __destruct(){
		parent::__destruct();
	}
	public function index(){
		echo "<h1>Access Denied</h1> <a href='".base_url."'>Go Back.</a>";
	}
	public function login(){
		extract($_POST);
		$stmt = $this->conn->prepare("SELECT * from users where username = ? and password = ? ");
		$pw = md5($password);
		$stmt->bind_param('ss',$username,$pw);
		$stmt->execute();
		$qry = $stmt->get_result();
		if($qry->num_rows > 0){
			$res = $qry->fetch_array();
			if($res['status'] != 1){
				return json_encode(array('status'=>'notverified'));
			}
			foreach($res as $k => $v){
				if(!is_numeric($k) && $k != 'password'){
					$this->settings->set_userdata($k,$v);
				}
			}
			$this->settings->set_userdata('login_type',1);
		return json_encode(array('status'=>'success'));
		}else{
		return json_encode(array('status'=>'incorrect','error'=>$this->conn->error));
		}
	}
	public function logout(){
		if($this->settings->sess_des()){
			redirect('admin/login.php');
		}
	}
	function employee_login(){
		extract($_POST);
		$stmt = $this->conn->prepare("SELECT *,concat(lastname,', ',firstname,' ',middlename) as fullname from employee_list where email = ? and `password` = ? ");
		$pw = md5($password);
		$stmt->bind_param('ss',$email,$pw);
		$stmt->execute();
		$qry = $stmt->get_result();
		if($this->conn->error){
			$resp['status'] = 'failed';
			$resp['msg'] = "An error occurred while fetching data. Error:". $this->conn->error;
		}else{
			if($qry->num_rows > 0){
				$res = $qry->fetch_array();
				if($res['status'] == 1){
					foreach($res as $k => $v){
						$this->settings->set_userdata($k,$v);
					}
					$this->settings->set_userdata('login_type',2);
					$resp['status'] = 'success';
				}else{
					$resp['status'] = 'failed';
					$resp['msg'] = "Your Account is Inactive. Please Contact the Management to verify your account.";
				}
			}else{
				$resp['status'] = 'failed';
				$resp['msg'] = "Invalid email or password.";
			}
		}
		return json_encode($resp);
	}
	public function employee_logout(){
		if($this->settings->sess_des()){
			redirect('./login.php');
		}
	}

	public function forgot() {
    extract($_POST);
    $email = trim($email);
    $qry = $this->conn->query("SELECT * FROM users WHERE email = '{$email}'");

    if ($qry->num_rows > 0) {
        $token = md5(uniqid(rand(), true));
        $this->conn->query("UPDATE users SET reset_token = '{$token}', token_expiry = NOW() + INTERVAL 1 HOUR WHERE email = '{$email}'");

        $link = base_url . "admin/reset_password.php?token={$token}";
        $subject = "Reset Your Password";
        $body = "
            <p>Hello,</p>
            <p>You requested to reset your password. Click the link below to reset it:</p>
            <p><a href='{$link}'>Reset Password</a></p>
            <p>This link will expire in 1 hour.</p>
            <br><p>Regards,<br>Admin Team</p>
        ";

        // ✅ Use send_email() from Master
        $mailer = new Master();
        $sent = $mailer->send_email($email, $subject, $body);

        if ($sent) {
            return json_encode(['status' => 'success']);
        } else {
            return json_encode(['status' => 'failed', 'msg' => 'Failed to send reset email.']);
        }
    } else {
        return json_encode(['status' => 'failed', 'msg' => 'Email not found.']);
    }
}


}
$action = !isset($_GET['f']) ? 'none' : strtolower($_GET['f']);
$auth = new Login();
switch ($action) {
	case 'login':
		echo $auth->login();
		break;
	case 'logout':
		echo $auth->logout();
		break;
	case 'elogin':
		echo $auth->employee_login();
		break;
	case 'elogout':
		echo $auth->employee_logout();
		break;
	case 'forgot':
    echo $auth->forgot();
    break;	
	default:
		echo $auth->index();
		break;
}

