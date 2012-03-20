<?php
require 'includes/master.inc.php';

$response = array(
	'result' => false,
	'errorCode' => 1,
	'errorMessage' => 'Incorrect request data'
);

if (!empty($_POST['data']) && !empty($_POST['bundle_id'])) {
	$app = new Application();
	$app->select($_POST['bundle_id'], 'bundle_id');
	if ($app->ok()) {
		$data = $app->decodeRequestData($_POST['data']);
		if (!empty($data) && !empty($data['activationCode']) && !empty($data['hardwareId'])) {
			$serial = $data['activationCode'];
			$hwid = $data['hardwareId'];
			
			$a = new Activation();
			$params = array(
				'serial_number' => $serial,
				'app_id' => $app->id,
				'hwid' => $hwid
			);
			$a->selectMultiple($params);
			
			# If not found
			if (!$a->ok()) {
				# Check activations count
				$db = Database::getDatabase();
				$sql = "SELECT o.id, o.quantity, COUNT(*) AS count
					FROM shine_activations AS a 
					LEFT JOIN shine_orders AS o ON o.id = a.order_id
					WHERE o.app_id = ".((int)$app->id)." AND o.serial_number = '".$db->escape($serial)."'";
				if ($db->query($sql) && $db->hasRows()) {
					$row = $db->getRow();
					if ($row['quantity'] > 0 && $row['quantity'] > $row['count']) {
						$a = new Activation();
						$a->app_id = $app->id;
						$a->hwid = $hwid;
						$a->serial_number = $serial;
						$a->dt = dater();
						$a->ip = $_SERVER['REMOTE_ADDR'];
						$a->order_id = $row['id'];
						$a->insert();
					}
					else if ($row['quantity'] <= 0) {
						$a->id = null;
						$response['errorCode'] = 6;
						$response['errorMessage'] = 'Wrong serial number';
					}
					else {
						$a->id = null;
						$response['errorCode'] = 5;
						$response['errorMessage'] = 'Maximum users for this serial number reached.';
					}
				}
				else {
					$a->id = null;
					$response['errorCode'] = 4;
					$response['errorMessage'] = 'Database error';
				}
			}
			
			if ($a->ok()) {
				# Generate license and respond
				$license = $a->generateLicenseOnline($a->hwid);
				
				$response = array(
					'result' => true,
					'licence' => base64_encode($license)
				);
			}
		}
		else {
			$response['errorCode'] = 3;
			$response['errorMessage'] = 'Incorrect data';
		}
	}
	else {
		$response['errorCode'] = 2;
		$response['errorMessage'] = 'Application not found';
	}
}

echo json_encode($response);

/*
$post = trim(file_get_contents('php://input'));
$post = base64_decode($post);
$dict = json_decode($post);

$a = new Activation();
$a->app_id        = $dict->app_id;
$a->name          = $dict->email;
$a->serial_number = $dict->serial;
$a->dt            = dater();
$a->ip            = $_SERVER['REMOTE_ADDR'];
$a->insert();

$app = new Application($a->app_id);
if(!$app->ok()) die('serial');

$o = new Order();
$o->select($a->serial_number, 'serial_number');
if(!$o->ok()) die('serial');

// Because we die before the activation is updated with the found order id,
// this has the added benefit of highlighting the activation as "fraudulent"
// in the activations list. It's not fraudulent obviously, but it does let
// us quickly see if deactivated licenses are still being used.
if($o->deactivated == 1) die('serial');

$a->order_id = $o->id;
$a->update();

$o->downloadLicense();
