<?php
require 'includes/master.inc.php';

# Check required params
if (empty($_REQUEST['app']) || empty($_REQUEST['email']) || empty($_REQUEST['order'])) {
	error_log("Not enough params for a license request");
	die("Not enough params for a license request");
}

########## APP ###########
$app = new Application();
$app->select($_REQUEST['app'], 'getdealy_name'); // custom
if (!$app->ok()) {
	error_log("Application {$_REQUEST['app']} not found!");
	die("Application {$_REQUEST['app']} not found!");
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
$o->txn_id = $_REQUEST['order'];
$o->payer_email = $_REQUEST['email'];
$o->payment_gross = $app->getdealy_price;
$o->quantity = $lt->quantity;
$ed = $lt->expiration_days;
if (!empty($ed)) $o->expiration_date = date('Y-m-d', time()+$ed*86400);
$o->license_type_id = $lt->id;
$o->dt = dater();
$o->type = 'GetDealy';
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
else {
	if (!empty($_REQUEST['first_name'])) $o->first_name = $_REQUEST['first_name'];
	if (!empty($_REQUEST['last_name'])) $o->last_name = $_REQUEST['last_name'];
}

if (!empty($_REQUEST['item_name'])) $o->item_name = $_REQUEST['item_name'];
if (!empty($_REQUEST['country'])) $o->residence_country = $_REQUEST['country'];
if (!empty($_REQUEST['currency'])) $o->mc_currency = $_REQUEST['currency'];
if (!empty($_REQUEST['payment_type'])) $o->payment_type = $_REQUEST['payment_type'];
if (!empty($_REQUEST['tax'])) $o->tax = $_REQUEST['tax'];
if (!empty($_REQUEST['payer_id'])) $o->payer_id = $_REQUEST['payer_id'];
if (!empty($_REQUEST['payment_fee'])) $o->payment_fee = $_REQUEST['payment_fee'];
if (!empty($_REQUEST['notes'])) $o->notes = $_REQUEST['notes'];

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