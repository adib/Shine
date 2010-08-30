<?php

    // This was code used to integrate with MUPromo.com
	//error_log('MU Promo not active');
	//exit;
	
	/*
	How to test:
	http://stackoverflow.com/questions/1087185/http-testing-tool-easily-send-post-get-put
	Use CURL for POST test:
	
	curl -d "first_name=John&last_name=Doe&payment_gross=19.95&email=john.doe@example.com&transaction_id=test" http://intra.basil-salad.com/shine/mupromo.php
	
	
	*/
	
    require 'includes/master.inc.php';
    
    $remoteIP = $_SERVER['REMOTE_ADDR'];
    
    /*
    if(!empty($_GET['_ip_override'])) {
    	$remoteIP = $_GET['_ip_override'];
    } else if(!empty($_POST['_ip_override'])) {
    	$remoteIP = $_POST['_ip_override'];
    }*/
    
    
    if($remoteIP != '67.19.42.50') {
    	// ensure the remote server is MU.
    	header("HTTP/1.1 403 Invalid client");
    	error_log("Invalid IP address: " . $remoteIP);
    	exit;
    }
    
    
    if(empty($_POST['email'])) {
     	header("HTTP/1.1 400 Missing parameter");
     	error_log("POST variable 'email' is empty – exiting.");
     	exit;
    }

	if(empty($_POST['last_name']) || empty($_POST['first_name']) || empty($_POST['payment_gross']) || empty($_POST['transaction_id'])) {
     	header("HTTP/1.1 400 Missing parameter");
		error_log("Incomplete POST variables – exiting.");
		exit;
	}
     
    // 
     $app = new Application();
     $app->select(7);
     if(!$app->ok())
     {
     	header("HTTP/1.1 400 Missing parameter");
         error_log("Application not found!");
         exit;
     }
     
    // 
     $o              = new Order();
     $o->app_id      = $app->id;
     $o->item_name   = $app->name;
     $o->dt          = dater();
     $o->type        = 'MUPromo';
     $o->first_name  = $_POST['first_name'];
     $o->last_name   = $_POST['last_name'];
     $o->payer_email = $_POST['email'];
     $o->txn_id      = $_POST['transaction_id'];
     
     $o->payment_gross     = preg_replace('/[^0-9.]/', '', $_POST['payment_gross']); // custom
     
     $o->insert();
          
     $o->generateLicense();
     //$o->emailLicense();
     
     // return the URL
		 header('Content-type: text/plain', TRUE);
     echo "http://updates.basil-salad.com/shine/order-retrieve.php?order_id=" . urlencode($o->id) . "&email=" .urlencode($o->payer_email) . "\n";	     
?>
