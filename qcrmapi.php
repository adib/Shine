<?php
require 'includes/master.inc.php';
$db = Database::getDatabase();

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

$sql = "SELECT a.id, a.name, a.serial_number, a.hwid, a.dt, a.ip, o.payer_email, app.abbreviation, app.name as appname FROM shine_activations a LEFT JOIN shine_orders o ON a.order_id=o.id LEFT JOIN shine_applications app ON a.app_id=app.id WHERE payer_email='".$email."' AND sent_to_qcrm=0";

$rows = $db->getRows($sql);

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

echo base64_encode(json_encode($response_data));
?>
