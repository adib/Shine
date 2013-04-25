<?php
require 'includes/master.inc.php';

define('SECURITY_KEY', 'WINSTICKPCBUNDLER04252013');

$abbreviations_list = array('PSW');

$app = new Application();
$app->select('PSW', 'abbreviation'); // custom
if (!$app->ok()) {
	error_log("Application PSW not found!");
	die("Application PSW not found!");
}

$lt = new LicenseType();
$lt->select('WINSTICK-PCBUNDLER', 'abbreviation'); // custom
if (!$lt->ok()) {
	error_log("License type WINSTICK-PCBUNDLER not found!");
	die("License type WINSTICK-PCBUNDLER not found!");
}

if (empty($_REQUEST['email'])) {
	error_log("Email is not set!");
	die("Email is not set!");
}

if (empty($_REQUEST['security_request_hash'])) {
	error_log("Security check failed!");
	die("Security check failed!");
}

# Security data collect
$check_data = '';
ksort($_REQUEST);
foreach ($_REQUEST as $key => $val) {
	if ($key != 'security_request_hash') $check_data .= $val;
}

// FastSpring security check...
if (md5($check_data . SECURITY_KEY) != $_REQUEST['security_request_hash'])
	die('Security check failed.');

$o = new Order();
$params = array(
	'type' => 'WINSTICK-PCBUNDLER',
	'app_id' => $app->id,
	'payer_email' => $_REQUEST['email']
);
$o->selectMultiple($params);

# Found
if ($o->ok()) $response = $o->serial_number;
else {
	# Insert Order
	$o = new Order();
	$o->app_id = $app->id;
	$o->payer_email = $_REQUEST['email'];
	$o->quantity = $lt->quantity;
	$ed = $lt->expiration_days;
	if (!empty($ed)) $o->expiration_date = date('Y-m-d', time()+$ed*86400);
	$o->license_type_id = $lt->id;
	$o->dt = dater();
	$o->type = 'WINSTICK-PCBUNDLER';
	$o->generateSerial(); # generates serial into $o->serial_number
	
	# Getting name
	if (!empty($_REQUEST['name'])) {
		$name = explode(' ', $_REQUEST['name'], 2);
		if (!empty($name[1])) {
			$o->first_name = $name[0];
			$o->last_name = $name[1];
		}
		else $o->last_name = $name[0];
	}
	
	$id = $o->insert();

	# Return serial number
	$response = ($id > 0) ? $o->serial_number : 'Order already exists. Security violation';
}

echo $response;