<?php
	require 'includes/master.inc.php';
    $Config = Config::getConfig();
   	$registration = new CustomerRegistration($_REQUEST['id']);
   	if(!$registration->ok()) {
   		die('Invalid customer registration');
   	}
   	if(strtolower($registration->customerEmail) == strtolower($_REQUEST['customerEmail'])) {
   		// verified
   		$registration->email_confirmed = 'YES';
   		$registration->update();
   		$scriptName = 'download.php?id=' . $registration->app_id;
   		$downloadLink = 'http://'.$_SERVER['HTTP_HOST'] . WEB_ROOT . '/' . $scriptName;
   		header("Location: $downloadLink");
   		exit;
   		
   	}
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head><title>Invalid parameter</title>
</head>
<body>
You have specified an incorrect download link.  
<?php
	$appID = !empty($_REQUEST['app_id']) ? $_REQUEST['app_id'] : $registration->app_id;
	if(!empty($appID)) {
?>
	<br />Please <a href="download-register.php?id=<?php echo $appID ?>">register here</a>.
<?php
	} // if(!empty($appID))
?>
</body>
</html>