<?php
require 'includes/master.inc.php';

$app = new Application();
$app->select($_POST['item_number']); // custom
if (!$app->ok()) {
	error_log("Application {$_POST['item_name']} {$_POST['item_number']} not found!");
	die("Application {$_POST['item_name']} {$_POST['item_number']} not found!");
}

$lt = new LicenseType();
$lt->select($_POST['license_type'], 'abbreviation'); // custom
if (!$lt->ok()) {
	error_log("License type {$_POST['license_type']} not found!");
	die("License type {$_POST['license_type']} not found!");
}

# Security data collect
$check_data = '';
ksort($_REQUEST);
foreach ($_REQUEST as $key => $val) {
	if ($key != 'security_request_hash') $check_data .= $val;
}

// FastSpring security check...
//if (md5($check_data . $app->fs_license_key) != $_REQUEST['security_request_hash'])
//	die('Security check failed.');

# Insert Order
$o = new Order();
$o->app_id = $app->id;
$o->txn_id = $_POST['reference'];
$o->payer_email = $_POST['email'];
$o->quantity = $lt->quantity * $lt->serials_quantity;
$ed = $lt->expiration_days;
if (!empty($ed)) $o->expiration_date = date('Y-m-d', time()+$ed*86400);
$o->license_type_id = $lt->id;
$o->dt = dater();
$o->type = 'FastSpring';

# Getting name
if (!empty($_POST['name'])) {
	$name = explode(' ', $_POST['name'], 2);
	if (!empty($name[1])) {
		$o->first_name = $name[0];
		$o->last_name = $name[1];
	}
	else $o->last_name = $name[0];
}

$error = '';
$serials = array();

for ($i = 0; $i < $lt->serials_quantity; $i++) {
	$o->generateSerial(); # generates serial into $o->serial_number	
	$serials[] = $o->serial_number;
}

$o->load(array('serial_number' => implode(',', $serials)));
$id = $o->save();

# Return serial number
	if ($id > 0) echo implode('\n', $serials);
	else echo 'Order already exists. Security violation';
