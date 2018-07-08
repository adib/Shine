<?php
if (empty($_REQUEST['name'])) {
	error_log("Name is not set!");
	die("Name is not set!");
}

if (empty($_REQUEST['email'])) {
	error_log("Email is not set!");
	die("Email is not set!");
}

if (empty($_REQUEST['orderID'])) {
	error_log("OrderID is not set!");
	die("Order ID is not set!");
}

$name = $_REQUEST['name'];
$email = $_REQUEST['email'];
$orderID = $_REQUEST['orderID'];

$security_request_hash = md5($email.$name.$orderID.'719279c718955653ca17889cc6f7629d');

$postRequest = "name=" . $name . "&email=" . $email . "&reference=" . $orderID . "&security_request_hash=" . $security_request_hash;

if( $curl = curl_init() ) {
    curl_setopt($curl, CURLOPT_URL, 'http://wallwiz.com/secure/fastspringReceiver.php');
    curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $postRequest);
    $out = curl_exec($curl);
    echo $out;
    curl_close($curl);
  }
else {
	$response = 'Connection failed';
}


echo $response;