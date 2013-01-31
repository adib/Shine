<?php
require 'includes/master.inc.php';
$db = Database::getDatabase();
    
function generateSalt($length = 8){
	$chars = 'abdefhiknrstyzABDEFGHKNQRSTYZ23456789';
	$numChars = strlen($chars);
	$string = '';
	for ($i = 0; $i < $length; $i++) {
		$string .= substr($chars, rand(1, $numChars) - 1, 1);
	}
	return $string;
}

if (!isset($_POST['email']) || empty($_POST['email'])) {
	echo json_encode(array('error' => 'Customer email is not specified'));
	die();
}

if (!isset($_POST['sk']) || empty($_POST['sk'])) {
	echo json_encode(array('error' => 'Secret key is not specified'));
	die();
}

$email = $_POST['email'];
$sk = md5($email.'support_secret_key');

if ($sk != $_POST['sk']) {
	echo json_encode(array('error' => 'Secret key is invalid'));
	die();
}

$sql = "SELECT a.id, a.name, a.serial_number, a.hwid, a.dt, a.ip, o.payer_email, app.abbreviation, app.name as appname FROM shine_activations a LEFT JOIN shine_orders o ON a.order_id=o.id LEFT JOIN shine_applications app ON a.app_id=app.id WHERE payer_email='".$email."' AND sent_to_qcrm=0 AND a.app_id=4";

$rows = $db->getRows($sql);

$private_key = 'temp123';
$salt = generateSalt(40);
$signature = md5($private_key.$salt);

$response = array(
	'qcrm_api' => array(
		'signature' => $signature,
		'salt' =>	$salt,
		'system' => '100005',
		'method' => 'import',
		'response_data' => ''
	)
);

$response_data = array();

foreach ($rows as $row) {
	$response_data[] = array(
		'type' => 'custom_ticket',
		'product_abbreviation' => $row['abbreviation'],
		'customer_email' => $email,
		'title' => $row['appname'].' activation',
		'additional' => array(
			'hwid' => $row['hwid'],
			'date' => $row['dt'],
			'serial' => $row['serial_number'],
			'ip' => $row['ip']
		)
	);
	
	$db->query("UPDATE shine_activations SET sent_to_qcrm=1 WHERE id='".$row['id']."' LIMIT 1");
}

$response['response_data'] = base64_encode($response_data);
echo json_encode($response);
?>
