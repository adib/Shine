<?PHP
// This is just a helper script you can use on your website to track
// downloads of each version of your app. Set the $app_id variable below,
// and this will automatically redirect the user to download the most
// recent version of your app. The downloads will be counted and reported
// in Shine.

require 'includes/master.inc.php';
require_once 'includes/class.localupload.php';
use UnitedPrototype\GoogleAnalytics;

// disable GZIP compression (if it enabled)
// for correctly detect attachment size in webkit browsers (chrome, safari)
@ini_set('zlib.output_compression', 0);
@ini_set('implicit_flush', 1);


if (isset($_GET['id'])) {
	$a = new Application($_GET['id']);
}
else if (isset($_GET['abbr'])) {
	// get by abbreviation
	$a = new Application();
	$a->select($_GET['abbr'], 'abbreviation');
}

if ($a->ok()) {
	$status = VERSION_STATUS_PRODUCTION;
	if (!empty($_GET['status'])) {
		switch ($_GET['status']) {
			case 'test':
				$status = VERSION_STATUS_TEST;
				break;
			case 'beta':
				$status = VERSION_STATUS_BETA;
				break;
		}
	}
	
	$version = !empty($_GET['version']) ? mysql_escape_string($_GET['version']) : '';
	
	$v = DBObject::glob('Version', "SELECT * 
					FROM shine_versions 
					WHERE app_id = ".$a->id." AND 
						status = ".$status." " .
						(!empty($version) ? "AND version_number = '".$version."'" : "ORDER BY dt DESC")." LIMIT 1");
	$v = array_pop($v);
	if (is_object($v) && get_class($v) == 'Version') {
		$v->downloads++;
		$v->update();
	
		Download::track('', $a->id);
		# Google Analytics
		if ($a->use_ga == 1) {
			$uuid_ga = abs(crc32(dater())); # unsigned crc32
			// Initilize GA Tracker
			$tracker = new GoogleAnalytics\Tracker($a->ga_key, $a->ga_domain);
			
			// Assemble Visitor information
			// (could also get unserialized from database)
			$visitor = new GoogleAnalytics\Visitor();
			$visitor->setUniqueId($uuid_ga);
			$visitor->setIpAddress($_SERVER['REMOTE_ADDR']);
			$visitor->setUserAgent($_SERVER['HTTP_USER_AGENT']);
			$visitor->setScreenResolution('1024x768');
			
			$ga_country = null;
			if ($a->ga_country == 1 && function_exists('geoip_country_code_by_name')) {
				$ga_country = geoip_country_code_by_name($ip);
				if ($ga_country == '') $ga_country = 'XX';
			}
			
			// Assemble Session information
			// (could also get unserialized from PHP session)
			$session = new GoogleAnalytics\Session();
			// Assemble Event information
			$event = new GoogleAnalytics\Event($a->name, 'Update', $ga_country, null, true);
			// Track event
			$tracker->trackEvent($event, $session, $visitor);
		}
		
		$fname = $v->alternate_fname;
		
		if ($a->storage == 2) {
			header('Content-Description: File Transfer');
			header("Content-Disposition: attachment; filename=\"".basename((!empty($fname) ? $fname : $v->url))."\"");
			header("Content-Type: application/octet-stream");
			header('Content-Transfer-Encoding: binary');
			
			# Webserver file download
			if ($a->direct_download == '1') {
				$server = 'lighttpd';
				if (!empty($_SERVER['SERVER_SOFTWARE']) && stripos($_SERVER['SERVER_SOFTWARE'], 'apache') !== false) $server = 'apache';
				else if (!empty($_SERVER['SERVER_SOFTWARE']) && stripos($_SERVER['SERVER_SOFTWARE'], 'nginx') !== false) $server = 'nginx';
				
				switch ($server) {
					case 'lighttpd':
						header("X-LIGHTTPD-send-file: " . LOCAL_UPLOAD_PATH . '/' . $v->url);
						break;
					case 'apache':
						header("X-Sendfile: " . LOCAL_UPLOAD_PATH . '/' . $v->url);
						break;
					case 'nginx':
						header("X-Accel-Redirect: " . LOCAL_UPLOAD_PATH . '/' . $v->url);
						break;
				}
			}
			# Simple readfile
			else {
				header('Content-Length: ' . (string)filesize(LOCAL_UPLOAD_PATH . '/' . $v->url));
				readfile(LOCAL_UPLOAD_PATH . '/' . $v->url);
			}
		}
		else header('Location: http'.($a->is_ssl == 1 ? 's' : '').'://'.$a->s3domain.'/'.$a->s3path.$v->url);
		exit;
	}
}

# No file
header("HTTP/1.0 404 File Not Found");
echo '<html><head><title>404 - Not Found</title></head><body><h1>404 - Not Found</h1></body></html>';