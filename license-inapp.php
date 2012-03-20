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
		$data = explode('|', base64_decode($_POST['data']));
		if (!empty($data) && count($data) == 3) {
			$serial = $data[0];
			$hwid = $data[1];
			
			$a = new Activation();
			$params = array(
				'serial_number' => $serial,
				'app_id' => $app->id
			);
			$a->selectMultiple($params);
			
			# If not found
			if (!$a->ok()) {
				# FIXME: check activations count
				
				$a = new Activation();
				$a->app_id = $app->id;
				$a->hwid = $hwid;
				$a->serial_number = $serial;
				$a->dt = dater();
				$a->ip = $_SERVER['REMOTE_ADDR'];
				
				$o = new Order();
				$o->select($a->serial_number, 'serial_number');
				
				if ($o->ok()) {
					$a->order_id = $o->id;
					$a->insert();
				}
				else {
					$a->id = null;
					$response['errorCode'] = 4;
					$response['errorMessage'] = 'Wrong serial number';
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
