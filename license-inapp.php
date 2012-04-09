<?php
require 'includes/master.inc.php';
use UnitedPrototype\GoogleAnalytics;

$response = array(
	'result' => 0,
	'errorCode' => 1,
	'errorMessage' => 'Empty request data'
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
			
			if ($app->use_ga == 1) {
				$uuid_ga = abs(crc32($hwid)); # unsigned crc32
				// Initilize GA Tracker
				$tracker = new GoogleAnalytics\Tracker($app->ga_key, $app->ga_domain);
				
				// Assemble Visitor information
				// (could also get unserialized from database)
				$visitor = new GoogleAnalytics\Visitor();
				$visitor->setUniqueId($uuid_ga);
				$visitor->setIpAddress($_SERVER['REMOTE_ADDR']);
				$visitor->setUserAgent($_SERVER['HTTP_USER_AGENT']);
				$visitor->setScreenResolution('1024x768');
				$ga_country = null;
				if ($app->ga_country == 1 && function_exists('geoip_country_code_by_name')) {
					$ga_country = geoip_country_code_by_name($_SERVER['REMOTE_ADDR']);
					if ($ga_country == '') $ga_country = 'XX';
				}
				
				// Assemble Session information
				// (could also get unserialized from PHP session)
				$session = new GoogleAnalytics\Session();
			}
			
			# If not found
			if (!$a->ok()) {
				# Check activations count
				$db = Database::getDatabase();
				$sql = "SELECT o.id, o.deactivated, o.quantity, COUNT(a.id) AS count
					FROM shine_orders AS o 
					LEFT JOIN shine_activations AS a ON o.id = a.order_id
					WHERE o.app_id = ".((int)$app->id)." AND o.serial_number = '".$db->escape($serial)."'";
				if ($db->query($sql) && $db->hasRows()) {
					$row = $db->getRow();
					if ($row['quantity'] > 0 && $row['deactivated'] == 0 && $row['quantity'] > $row['count']) {
						$a = new Activation();
						$a->app_id = $app->id;
						$a->hwid = $hwid;
						$a->serial_number = $serial;
						$a->dt = dater();
						$a->ip = $_SERVER['REMOTE_ADDR'];
						$a->order_id = $row['id'];
						$a->insert();
						
						if ($app->use_ga == 1) {
							$ga_action = 'New Activation';
							$ga_added = $ga_last = $ga_current = new DateTime($a->dt);
						}
					}
					else if ($row['quantity'] <= 0) {
						$a->id = null;
						$response['errorCode'] = 7;
						$response['errorMessage'] = 'Wrong serial number';
					}
					else if ($row['deactivated'] > 0) {
						$a->id = null;
						$response['errorCode'] = 6;
						$response['errorMessage'] = 'License was deactivated';
					}
					else {
						$a->id = null;
						$response['errorCode'] = 5;
						$response['errorMessage'] = 'Maximum users for this serial number reached';
					}
				}
				else {
					$a->id = null;
					$response['errorCode'] = 4;
					$response['errorMessage'] = 'Database error';
				}
			}
			# Found, re-generate
			else if ($app->use_ga == 1) {
				$ga_action = 'ReActivation';
				$ga_added = $ga_last = $ga_current = new DateTime($a->dt);
			}
			
			if ($a->ok()) {
				if ($app->use_ga == 1) {
					$visitor->setFirstVisitTime($ga_added);
					$visitor->setPreviousVisitTime($ga_last);
					$visitor->setCurrentVisitTime($ga_current);
					// Assemble Event information
					$event = new GoogleAnalytics\Event($app->name, $ga_action, $ga_country, null, true);
					// Track event
					$tracker->trackEvent($event, $session, $visitor);
				}
				
				# Generate license and respond
				$license = $a->generateLicenseOnline($a->hwid);
				
				$response = array(
					'result' => 1,
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
*/