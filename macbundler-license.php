<?php
require 'includes/master.inc.php';

$abbreviations_list = array('FOCUS');

if (empty($_POST['abbr']) || !in_array($_POST['abbr'], $abbreviations_list)) {
	error_log("Application {$_POST['abbr']} not found!");
	die("Application {$_POST['abbr']} not found!");
}
$app = new Application();
$app->select($_POST['abbr'], 'abbreviation'); // custom
if (!$app->ok()) {
	error_log("Application {$_POST['abbr']} not found!");
	die("Application {$_POST['abbr']} not found!");
}

$lt = new LicenseType();
$lt->select($_POST['license_type'], 'abbreviation'); // custom
if (!$lt->ok()) {
	error_log("License type {$_POST['license_type']} not found!");
	die("License type {$_POST['license_type']} not found!");
}

if (empty($_REQUEST['email'])) {
	error_log("Email is not set!");
	die("Email is not set!");
}

# FIXME: security? nah, haven't heard of... (MacBundler have no security check)

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
	'type' => 'MacBundler',
	'app_id' => $app->id,
	'payer_email' => $_REQUEST['email']
);
$o->selectMultiple($params);

# Found
if ($o->ok()) echo $o->serial_number;
else {
	# Insert Order
	$o = new Order();
	$o->app_id = $app->id;
	$o->payer_email = $_POST['email'];
	$o->quantity = $lt->quantity;
	$ed = $lt->expiration_days;
	if (!empty($ed)) $o->expiration_date = date('Y-m-d', time()+$ed*86400);
	$o->license_type_id = $lt->id;
	$o->dt = dater();
	$o->type = 'MacBundler';
	$o->generateSerial(); # generates serial into $o->serial_number
	
	# Getting name
	if (!empty($_POST['name'])) {
		$name = explode(' ', $_POST['name'], 2);
		if (!empty($name[1])) {
			$o->first_name = $name[0];
			$o->last_name = $name[1];
		}
		else $o->last_name = $name[0];
	}
	
	$id = $o->insert();
	
	# Return serial number
	if ($id > 0) echo $o->serial_number;
	else 'Order already exists. Security violation';
}