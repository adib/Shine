<?php
require 'includes/master.inc.php';

# Check required params
if (empty($_POST['app']) || empty($_POST['email']) || empty($_POST['order'])) {
	error_log("Not enough params for a license request");
	die("Not enough params for a license request");
}

########## APP ###########
$app = new Application();
$app->select($_POST['app'], 'getdealy_name'); // custom
if (!$app->ok()) {
	error_log("Application {$_POST['app']} not found!");
	die("Application {$_POST['app']} not found!");
}

########## License or default ##########
$lt = new LicenseType();
$lt->select($app->default_license_abbr, 'abbreviation');
if (!$lt->ok()) {
	error_log("License type not found!");
	die("License type not found!");
}

# Insert Order
$o = new Order();
$o->app_id = $app->id;
$o->txn_id = $_POST['order'];
$o->payer_email = $_POST['email'];
$o->payment_gross = $app->getdealy_price;
$o->quantity = $lt->quantity;
$ed = $lt->expiration_days;
if (!empty($ed)) $o->expiration_date = date('Y-m-d', time()+$ed*86400);
$o->license_type_id = $lt->id;
$o->dt = dater();
$o->type = 'GetDealy';
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
if ($id > 0) {
	if ($app->use_postmark) {
		Mail_Postmark::compose()
			    ->addTo($o->payer_email)
			    ->subject($app->email_subject)
			    ->messagePlain($app->getBody($o))
			    ->send();
	}
	else {
		$headers = 'FROM: ' . $app->from_email;
		$headers .= PHP_EOL.'Content-type: text/plain; charset=UTF-8';
		$message = $app->getBody($o);
		mail($o->payer_email, $app->email_subject, $message, $headers);
	}
	
	echo 'OK';
}
else 'Order already exists. Security violation';