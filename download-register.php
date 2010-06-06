<?php
	require 'includes/master.inc.php';
    $Config = Config::getConfig();

	if(!empty($_REQUEST['id'])) {
		$app = new Application($_GET['id']);
		if(!$app->ok()) {
			die('Invalid app ID: ' . $appID);
		}
		
		$customerName = '';
		$customerEmail = '';
	}
	else {
		die('Unknown app ID');
	}
	$mailOK = FALSE;
	
	if($_SERVER['REQUEST_METHOD'] == 'POST') {
		$errorMessage = '';
		if(!empty($_POST['customerName'])) {
			$customerName = $_POST['customerName'];
		} else {
			$errorMessage .= 'Please enter your name.';
		}
		if(!empty($_POST['customerEmail'])) {
			$customerEmail = $_POST['customerEmail'];
			if(!eregi("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$", $customerEmail)) { 
				if(!empty($errorMessage)) {
					$errorMessage .= '<br />';
				}	
				$errorMessage .= 'Please enter a valid e-mail address.';
			} 
		} else {
			if(!empty($errorMessage)) {
				$errorMessage .= '<br />';
			}	
			$errorMessage .= 'Please enter your e-mail address.';
		}
		
		if(empty($errorMessage)) {
			// process data.
			$registration = new CustomerRegistration();
			$registration->customer_name = $customerName;
			$registration->customerEmail = $customerEmail;
			$registration->app_id = $app->id;
			$registration->insertOrUpdate();
			
			if($registration->ok()) {
				if(!$registration->sendConfirmationMail()) {
					if(!empty($errorMessage)) {
						$errorMessage .= '<br />';
					}
					$errorMessage .= 'There was a problem in sending the registration e-mail. Please contact <a href="mailto:' . $Config->supportEmail . '?Subject=Download%20Registration%20Mail">' . $Config->supportEmail . '</a> and describe the issue.';
				} else {
					$mailOK = TRUE;
				}
			} else {
				if(!empty($errorMessage)) {
					$errorMessage .= '<br />';
				}	
				$errorMessage .= 'There was a problem registering your data. Please contact <a href="mailto:' . $Config->supportEmail . '?Subject=Download%20Registration">' . $Config->supportEmail . '</a> and describe the issue.';
			
			}
		}
	}
	
	/*
	$customerName = !empty($_POST['customerName']) ? $_POST['customerName'] : '';
	$customerEmail = !empty($_POST['customerEmail']) ? $_POST['customerEmail'] : '';
	*/
	
	
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title>Register to download <?php echo $app->name; ?></title>
</head>
<body>
<?php
	if($_SERVER['REQUEST_METHOD'] == 'POST' && $mailOK) {
		// Process Data
?>
Thank you for registering.  The download link have been sent to your e-mail address. <br />
<?php

	} else {
?>
Please enter your name and e-mail address to download  <?php echo $app->name; ?>.  We will send the download link to your e-mail address. <br />
<br />
<?php
	} // if($_SERVER['REQUEST_METHOD'] == 'POST')
?>
<?php
	if(!empty($errorMessage)) {
?>
<font color="red">Error: <?php echo $errorMessage; ?></font><br />
<?php
	} //if(!empty($errorMessage))
?>
<form method="POST"  >
<input type="hidden" name="id" value="<?php echo $app->id; ?>" />
<table border="0" align="center" valign="middle">
<tr>
	<td><b>name</b></td>
	<td><input type="text" name="customerName" value="<?php echo $customerName; ?>" size="40" /></td>
</tr>
<tr>
	<td><b>e-mail</b></td>
	<td><input type="text" name="customerEmail" value="<?php echo $customerEmail; ?>" size="40" /></td>
</tr>
<tr>
<td align="right" colspan="2"><input type="Submit" name="" value="Submit" /></td>
</tr>
</table>
</body>
</html>