<?php
require 'includes/master.inc.php';

$app = new Application();
$app->select('FOCUS', 'abbreviation'); // custom
if (!$app->ok()) {
	error_log("Application FOCUS not found!");
	die("Application FOCUS not found!");
}

########## License or default ##########
$lt = new LicenseType();
$lt->select($app->default_license_abbr, 'abbreviation');
if (!$lt->ok()) {
	error_log("License type not found!");
	die("License type not found!");
}

if (empty($_POST['cust_email'])) {
	error_log("Email is not set!");
	die("Email is not set!");
}

# FIXME: security? nah, haven't heard of... (Paddle.com have no security check)

//# Security data collect
//$check_data = '';
//ksort($_REQUEST);
//foreach ($_REQUEST as $key => $val) {
//	if ($key != 'security_request_hash') $check_data .= $val;
//}
//
//// FastSpring security check...
//if (md5($check_data . $app->fs_license_key) != $_REQUEST['security_request_hash'])
//	die('Security check failed.');

$o = new Order();
$params = array(
	'type' => 'Paddle',
	'app_id' => $app->id,
	'payer_email' => $_POST['cust_email']
);
$o->selectMultiple($params);

# Found
if ($o->ok()) echo $o->serial_number;
else {
	# Insert Order
	$o = new Order();
	$o->app_id = $app->id;
	$o->payer_email = $_POST['cust_email'];
	$o->quantity = $lt->quantity;
	$ed = $lt->expiration_days;
	if (!empty($ed)) $o->expiration_date = date('Y-m-d', time()+$ed*86400);
	$o->license_type_id = $lt->id;
	$o->dt = dater();
	$o->type = 'Paddle';
	$o->generateSerial(); # generates serial into $o->serial_number
	
	# Getting name
	if (!empty($_POST['cust_name'])) {
		$name = explode(' ', $_POST['cust_name'], 2);
		if (!empty($name[1])) {
			$o->first_name = $name[0];
			$o->last_name = $name[1];
		}
		else $o->last_name = $name[0];
	}
	
	$id = $o->insert();
	
	# Return serial number
	if ($id > 0) {
		$instruction = 'Please, follow these 3 easy steps below to activate your version of Focus:'.PHP_EOL.
				'- Download and install free version of Focus from our site. (http://coppertino.com/focus/download)'.PHP_EOL.
				'- Launch Focus, click application icon in Menu bar and select "Activate" item'.PHP_EOL.
				'- Enter your email and Activation Code into the fields and click "Activate"';
		echo 'Your activation code - '.$o->serial_number.PHP_EOL.PHP_EOL.$instruction;
	}
	else 'Order already exists. Security violation';
}