<?php
require 'includes/master.inc.php';

$app = new Application();
$app->select($_POST['item_number']); // custom
if (!$app->ok()) {
	error_log("Application {$_POST['item_name']} {$_POST['item_number']} not found!");
	exit;
}

# Security data collect
$check_data = '';
ksort($_REQUEST);
foreach ($_REQUEST as $key => $val) {
	if ($key != 'security_request_hash') $check_data .= $val;
}

// FastSpring security check...
if (md5($check_data . $app->fs_license_key) != $_REQUEST['security_request_hash'])
	die('Security check failed.');

# Insert Order
$o = new Order();
$o->txn_id = $_POST['reference'];
$o->payer_email = $_POST['email'];
$o->quantity = $_POST['quantity'];
$o->serial_number = $o->generateSerial();

# Getting name
$name = $_POST['name'];
if (!empty($name)) {
	$name = explode(' ', $name, 2);
	if (!empty($name[1])) {
		$o->first_name = $name[0];
		$o->last_name = $name[1];
	}
	else $o->last_name = $name[0];
}

$o->app_id = $app->id;
$o->dt = dater();
$o->type = 'FastSpring';
$id = $o->insert();

# Return serial number
if ($id > 0) echo $o->serial_number;
else 'Order already exists. Security violation';