<?php
if (empty($_REQUEST['name'])) {
	die("Name is not set!");
}

if (empty($_REQUEST['email'])) {
	die("Email is not set!");
}

if (empty($_REQUEST['reference'])) {
	die("Order reference is not set!");
}

$hashparam = 'security_request_hash';

if (empty($_REQUEST[$hashparam])) {
	die("Security hash is not set!");
}

$wpwActions = array('MightyDealsApril2013', 'HackStoreDecember2013', '2dealDecember2013');

$name = $_REQUEST['name'];
$email = $_REQUEST['email'];
$orderID = $_REQUEST['orderid'];
$reference = $_REQUEST['reference'].$orderID;
$security_hash = $_REQUEST[$hashparam];

ksort($_REQUEST);
$data = '';
$privatekey = md5('WPW2013LICENSES');

foreach ($_REQUEST as $key => $val) {
	if ($key != $hashparam) {
		$data .= stripslashes($val);
	}
}

if (!in_array($_REQUEST['reference'], $wpwActions) || (md5($data . $privatekey) != $_REQUEST[$hashparam])) {
	die('Security check failed');
}

$security_request_hash = md5($email.$name.$reference.'719279c718955653ca17889cc6f7629d');

$postRequest = "name=" . $name . "&email=" . $email . "&reference=" . $reference . "&security_request_hash=" . $security_request_hash;

if( $curl = curl_init() ) {
    curl_setopt($curl, CURLOPT_URL, 'http://wallwiz.com/secure/fastspringReceiver.php');
    curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $postRequest);
    $response = curl_exec($curl);
    curl_close($curl);
  }
else {
	$response = 'Connection failed';
}


echo $response;