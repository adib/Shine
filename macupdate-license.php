<?php
require 'includes/master.inc.php';

$app = new Application();
$app->select($_POST['item_number']); // custom
if (!$app->ok()) {
	error_log("Application {$_POST['item_name']} {$_POST['item_number']} not found!");
	die("Application {$_POST['item_name']} {$_POST['item_number']} not found!");
}

$lt = new LicenseType();
$lt->select($_POST['license'], 'abbreviation'); // custom
if (!$lt->ok()) {
	error_log("License type {$_POST['license_type']} not found!");
	die("License type {$_POST['license_type']} not found!");
}

# Check required params
if (empty($_POST['email']) || empty($_POST['transaction_id']) || !isset($_POST['payment_gross'])) {
	error_log("Not enough params for a license request");
	die("Not enough params for a license request");
}

# Security data collect
$check_data = '';
ksort($_POST);
foreach ($_POST as $key => $val) {
	if ($key != 'security_request_hash') $check_data .= $val;
}

// Security check...
if (md5($check_data . $app->mu_license_key) != $_POST['security_request_hash'])
	die('Security check failed.');

# Insert Order
$o = new Order();
$o->app_id = $app->id;
$o->txn_id = $_POST['transaction_id'];
$o->payer_email = $_POST['email'];
$o->payment_gross = $_POST['payment_gross'];
$o->quantity = $lt->quantity;
$ed = $lt->expiration_days;
if (!empty($ed)) $o->expiration_date = date('Y-m-d', time()+$ed*86400);
$o->license_type_id = $lt->id;
$o->dt = dater();
$o->type = 'MacUpdate';
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
else {
	if (!empty($_POST['first_name'])) $o->first_name = $_POST['first_name'];
	if (!empty($_POST['last_name'])) $o->last_name = $_POST['last_name'];
}

if (!empty($_POST['item_name'])) $o->item_name = $_POST['item_name'];
if (!empty($_POST['country'])) $o->residence_country = $_POST['country'];
if (!empty($_POST['currency'])) $o->mc_currency = $_POST['currency'];
if (!empty($_POST['payment_type'])) $o->payment_type = $_POST['payment_type'];
if (!empty($_POST['tax'])) $o->tax = $_POST['tax'];
if (!empty($_POST['payer_id'])) $o->payer_id = $_POST['payer_id'];
if (!empty($_POST['payment_fee'])) $o->payment_fee = $_POST['payment_fee'];
if (!empty($_POST['notes'])) $o->notes = $_POST['notes'];

$id = $o->insert();

# Return serial number
if ($id > 0) echo $o->serial_number;
else 'Order already exists. Security violation';