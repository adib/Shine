<?PHP
    // Stick your DBOjbect subclasses in here (to help keep things tidy).

    class User extends DBObject
    {
        public function __construct($id = null)
        {
            parent::__construct('shine_users', array('username', 'password', 'level', 'email', 'twitter'), $id);
        }

        public function setPassword($password)
        {
            $Config = Config::getConfig();

            if($Config->useHashedPasswords === true)
                $this->password = sha1($password . $Config->authSalt);
            else
                $this->password = $password;
        }

		public function avatar()
		{		
			if(strlen($this->twitter) > 0)
				return "http://api.twitter.com/1/users/profile_image/{$this->twitter}.xml?size=normal";
			else
				return "http://l.yimg.com/us.yimg.com/i/identity/nopic_48.gif";
		}
    }

    class Application extends DBObject
    {
        public function __construct($id = null)
        {
            parent::__construct('shine_applications', array('name', 'link', 'bundle_name', 'upgrade_app_id', 's3key', 's3pkey', 's3bucket', 's3path', 'sparkle_key', 'sparkle_pkey', 'ap_key', 'ap_pkey', 'from_email', 'email_subject', 'email_body', 'license_filename', 'custom_salt', 'license_type', 'return_url', 'fs_security_key', 'i_use_this_key', 'tweet_terms', 'hidden', 'engine_class_name'), $id);
        }

		public function engine()
		{
			$class_name = 'Engine' . $this->engine_class_name;
			$engine = new $class_name();
			$engine->application = $this;
			return $engine;
		}

		public function versions()
		{
			return DBObject::glob('Version', "SELECT * FROM shine_versions WHERE app_id = '{$this->id}' ORDER BY dt DESC");
		}

		public function strCurrentVersion()
		{
			$db = Database::getDatabase();
			return $db->getValue("SELECT version_number FROM shine_versions WHERE app_id = '{$this->id}' ORDER BY dt DESC LIMIT 1");
		}
		
		public function strLastReleaseDate()
		{
			$db = Database::getDatabase();
			$dt = $db->getValue("SELECT dt FROM shine_versions WHERE app_id = '{$this->id}' ORDER BY dt DESC LIMIT 1");
			return time2str($dt);
		}
		
        public function totalDownloads()
        {
            $db = Database::getDatabase();
            return $db->getValue("SELECT SUM(downloads) FROM shine_versions WHERE app_id = '{$this->id}'");
        }

        public function totalUpdates()
        {
            $db = Database::getDatabase();
            return $db->getValue("SELECT SUM(updates) FROM shine_versions WHERE app_id = '{$this->id}'");
        }

		public function numSupportQuestions()
		{
			$db = Database::getDatabase();
			return $db->getValue("SELECT COUNT(*) FROM shine_feedback WHERE appname = '{$this->name}' AND `type` = 'support' AND new = 1");
		}
		
		public function numBugReports()
		{
			$db = Database::getDatabase();
			return $db->getValue("SELECT COUNT(*) FROM shine_feedback WHERE appname = '{$this->name}' AND `type` = 'bug' AND new = 1");
		}
		
		public function numFeatureRequests()
		{
			$db = Database::getDatabase();
			return $db->getValue("SELECT COUNT(*) FROM shine_feedback WHERE appname = '{$this->name}' AND `type` = 'feature' AND new = 1");
		}

		public function getBody($order)
		{
			return str_replace(array('{first_name}', '{last_name}', '{payer_email}', '{license}', '{1daylink}', '{3daylink}', '{1weeklink}', '{foreverlink}', '{serial_number}'),
			array($order->first_name, $order->last_name, $order->payer_email, $order->license, $order->getDownloadLink(86400), $order->getDownloadLink(86400*3), $order->getDownloadLink(86400*7), $order->getDownloadLink(0), $order->serial_number),
			$this->email_body);
		}
		
		public function ordersPerMonth()
		{
			$db = Database::getDatabase();			

			$orders = $db->getRows("SELECT DATE_FORMAT(dt, '%Y-%m') as dtstr, COUNT(*) FROM shine_orders WHERE type = 'PayPal' AND app_id = '{$this->id}' GROUP BY CONCAT(YEAR(dt), '-', MONTH(dt)) ORDER BY YEAR(dt) ASC, MONTH(dt) ASC");
			$keys = gimme($orders, 'dtstr');
			$values = gimme($orders, 'COUNT(*)');
			$orders = array();
			for($i = 0; $i < count($keys); $i++)
				$orders[$keys[$i]] = $values[$i];
				
			$first_order_date = $db->getValue("SELECT dt FROM shine_orders ORDER BY dt ASC LIMIT 1");
			list($year, $month) = explode('-', dater($first_order_date, 'Y-n'));

			do
			{
				$month = str_pad($month, 2, '0', STR_PAD_LEFT);
				if(!isset($orders["$year-$month"]))
					$orders["$year-$month"] = 0;
				
				$month = intval($month) + 1;
				if($month == 13)
				{
					$month = 1;
					$year++;
				}
			}
			while($year <> date('Y') && $month <> date('m'));
			
			ksort($orders);
			return $orders;
		}
		
		public function iUseThisHTML()
		{
		    $html = file_get_contents("http://osx.iusethis.com/app/include/{$this->i_use_this_key}/2");
		    $count = preg_replace('/[^0-9]/', '', strip_tags($html));
		    $result = "<div style=\"width: 160px;background: no-repeat url(http://osx.iusethis.com/static/badges/ucb2.png); height: 43px; cursor: pointer;\"><a href='http://osx.iusethis.com/app/{$this->i_use_this_key}'><div style=\"color: #383838; font: 14px Geneva, Arial, Helvetica, sans-serif; position: relative; top: 14px;    left: 45px; font-weight: bold; text-align: left;\">$count<span style=\"color:#7a7a7a; font:12px;\">usethis</span></div></a></div>";
		    return $result;
	    }
	
		public function numNewTickets()
		{
			$db = Database::getDatabase();
			return $db->getValue("SELECT COUNT(*) FROM shine_tickets WHERE status = 'new' AND app_id = '{$this->id}'");
		}

		public function numOpenTickets()
		{
			$db = Database::getDatabase();
			return $db->getValue("SELECT COUNT(*) FROM shine_tickets WHERE status = 'open' AND app_id = '{$this->id}'");
		}

		public function nextMilestone()
		{
			$db = Database::getDatabase();
			$row = $db->getRow("SELECT * FROM shine_milestones WHERE status = 'open' AND app_id = '{$this->id}'ORDER BY dt_due ASC");
			if($row !== false)
			{
				$m = new Milestone();
				$m->load($row);
				return $m;
			}
			return null;			
		}
		
		public function strNextMilestone()
		{
			$m = $this->nextMilestone();
			if(is_null($m))
				return '';
			else
				return "<a href='tickets-milestone.php?id={$m->id}'>{$m->title}</a>";
		}
    }

    class Activation extends DBObject
    {
        public function __construct($id = null)
        {
            parent::__construct('shine_activations', array('app_id', 'name', 'serial_number', 'guid', 'dt', 'ip', 'order_id'), $id);
        }

		public function applicationName()
		{
			static $cache;
			if(!is_array($cache)) $cache = array();

			if(!isset($cache[$this->app_id]))
			{
				$app = new Application($this->app_id);
				$cache[$this->app_id] = $app->name;
			}
			
			return $cache[$this->app_id];
		}
	}

    class Order extends DBObject
    {
        public function __construct($id = null)
        {
            parent::__construct('shine_orders', array('app_id', 'dt', 'txn_type', 'first_name', 'last_name', 'residence_country', 'item_name', 'payment_gross', 'mc_currency', 'business', 'payment_type', 'verify_sign', 'payer_status', 'tax', 'payer_email', 'txn_id', 'quantity', 'receiver_email', 'payer_id', 'receiver_id', 'item_number', 'payment_status', 'payment_fee', 'mc_fee', 'shipping', 'mc_gross', 'custom', 'license', 'type', 'deleted', 'hash', 'claimed', 'serial_number', 'notes', 'upgrade_coupon', 'deactivated'), $id);
        }

		public function activationCount()
		{
			$db = Database::getDatabase();
			return $db->getValue("SELECT COUNT(*) FROM shine_activations WHERE order_id = '{$this->id}'");
		}

		public function applicationName()
		{
			static $cache;
			if(!is_array($cache)) $cache = array();

			if(!isset($cache[$this->app_id]))
			{
				$app = new Application($this->app_id);
				$cache[$this->app_id] = $app->name;
			}
			
			return $cache[$this->app_id];
		}

		public function generateLicense()
		{
			$app = new Application($this->app_id);
			$engine = $app->engine();
			$engine->order = $this;
			$engine->generateLicense();
		}

		public function emailLicense()
		{
			$app = new Application($this->app_id);
			$engine = $app->engine();
			$engine->order = $this;
            // only send an e-mail if the "from" address is configured.
            if(strlen(trim($app->from_email)) > 0) {
                $engine->emailLicense();
            }
			$engine->emailLicense();
		}

		public function upgradeLicense()
		{
			$app = new Application($this->app_id);
			$engine = $app->engine();
			$engine->order = $this;
			return $engine->upgradeLicense();
		}

		public function downloadLicense()
		{
			$app = new Application($this->app_id);
			$engine = $app->engine();
			$engine->order = $this;
			$engine->downloadLicense();
			exit;
		}
		
		public function getDownloadLink($expires = 0) // Number of seconds until link expires, 0 = never expires
		{
			if($expires > 0) $expires += time();
			$hash = md5($this->id . $expires . Config::get('authSalt'));
			$link = 'http://' . $_SERVER['HTTP_HOST'] . WEB_ROOT . 'license.php?id=' . $this->id . '&x=' . $expires . '&h=' . $hash;
			return $link;
		}
		
		public function intlAmount()
		{
			$currencies = array('USD' => '$', 'GBP' =>'£', 'EUR' => '€', 'CAD' => '$', 'JPY' => '¥');
			
			if($this->mc_currency == '') return '';
			
			return $currencies[$this->mc_currency] . number_format($this->mc_gross, 2);
		}
		
		public static function totalOrders($id = null)
		{
			$db = Database::getDatabase();
			if(is_null($id))
				return $db->getValue("SELECT COUNT(*) FROM shine_orders");
			else
				return $db->getValue("SELECT COUNT(*) FROM shine_orders WHERE app_id = " . intval($id));
		}
    }

    class Version extends DBObject
    {
        public function __construct($id = null)
        {
            parent::__construct('shine_versions', array('app_id', 'human_version', 'version_number', 'dt', 'release_notes', 'filesize', 'url', 'downloads', 'updates', 'signature'), $id);
        }
    }

	class Feedback extends DBObject
	{
		public function __construct($id = null)
		{
			parent::__construct('shine_feedback', array('appname', 'appversion', 'systemversion', 'email', 'reply', 'type', 'message', 'importance', 'critical', 'dt', 'ip', 'new', 'starred', 'reguser', 'regmail', 'notes'), $id);
		}
	}

    class Tweet extends DBObject
    {
        public function __construct($id = null)
        {
            parent::__construct('shine_tweets', array('tweet_id', 'app_id', 'username', 'dt', 'body', 'profile_img', 'new', 'replied_to', 'reply_date', 'deleted'), $id);
        }
    }

    class Ticket extends DBObject
    {
        public function __construct($id = null)
        {
            parent::__construct('shine_tickets', array('app_id', 'title', 'description', 'created_by', 'assigned_to', 'milestone_id', 'status', 'dt_created', 'dt_last_state'), $id);
        }
    }

    class Milestone extends DBObject
    {
        function __construct($id = null)
        {
            parent::__construct('shine_milestones', array('app_id', 'title', 'dt_due', 'description', 'status'), $id);
        }

		function percent()
		{
			$db = Database::getDatabase();
			$complete = $db->getValue("SELECT COUNT(*) FROM shine_tickets WHERE status IN ('resolved', 'invalid') AND milestone_id = '{$this->id}'");
			$total = $db->getValue("SELECT COUNT(*) FROM shine_tickets WHERE status <> 'hold' AND milestone_id = '{$this->id}'");
			if($total == 0)
				return 0;
			else
				return round($complete / $total * 100);
		}
    }

    class TicketHistory extends DBObject
    {
        function __construct($id = null)
        {
            parent::__construct('shine_ticket_history', array('dt', 'ticket_id', 'app_id', 'user_id', 'user_from', 'user_to', 'status_from', 'status_to', 'milestone_from_id', 'milestone_to_id', 'comment'), $id);
        }

		function changes()
		{
			$users = DBObject::glob('user', 'SELECT * FROM shine_users');
			$milestones = DBObject::glob('milestone', "SELECT * FROM shine_milestones WHERE app_id = '{$this->app_id}'");

			$changes = array();
			if($this->user_from != $this->user_to)
			{
				$from = isset($users[$this->user_from]) ? $users[$this->user_from]->username : null;
				$to = isset($users[$this->user_to]) ? $users[$this->user_to]->username : null;

				if($from && $to)
					$changes[] = "Reassigned to <span class='noun'>$to</span> from <span class='noun'>$from</span>";
				elseif($to)
					$changes[] = "Assigned to <span class='noun'>$to</span>";
				elseif($from)
					$changes[] = "No longer assigned to <span class='noun'>$from</span>";
			}


			if($this->milestone_from_id != $this->milestone_to_id)
			{
				$from = isset($milestones[$this->milestone_from_id]) ? $milestones[$this->milestone_from_id]->title : null;
				$to = isset($milestones[$this->milestone_to_id]) ? $milestones[$this->milestone_to_id]->title : null;

				if($from && $to)
					$changes[] = "Milestone changed from <span class='noun'>$from</span> to <span class='noun'>$to</span>";
				elseif($to)
					$changes[] = "Milestone changed to <span class='noun'>$to</span>";
				elseif($from)
					$changes[] = "Removed from the <span class='noun'>$from</span> milestone";
			}

			if($this->status_from != $this->status_to)
			{
				$changes[] = "Status changed from <span class='noun'>" . ucwords($this->status_from) . "</span> to <span class='noun'>" . ucwords($this->status_to) . "</span>";
			}
			
			return $changes;
		}
    }
    
    // BEGIN adib 5-Jun-2010 10:13
    /*
    CREATE TABLE `customer_registration` (
      `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Primary key',
      `app_id` int(11) DEFAULT NULL COMMENT 'Shine (Mac) application ID',
      `customer_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Customer real name',
      `email_user` varchar(64) COLLATE utf8_unicode_ci NOT NULL COMMENT 'The left part of the @ sign in the e-mail',
      `email_domain` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'The right part of the @ sign in the e-mail',
      `email_confirmed` ENUM('YES','NO') NOT NULL DEFAULT 'NO' COMMENT 'Whether the user have confirmed the e-mail.',
      `dt_created` datetime DEFAULT NULL COMMENT 'Created timestamp',
      `dt_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Updated timestamp',
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 
    */
    
    class CustomerRegistration extends DBObject 
    {
    	public $customerEmail;
    	
    	function __construct($id = null)
    	{
    		parent::__construct('customer_registration',array(
    				'app_id',
    				'customer_name',
    				'email_user',
    				'email_domain',
    				'dt_created',
    				'dt_updated',
    				'email_confirmed'
    			),
    			$id
    		);
    	}
    	public function select($id, $column = null) {
    		parent::select($id,$column);
    		$this->customerEmail = $this->email_user . '@' . $this->email_domain;
    	}
    	
    	
    	function insertOrUpdate() {
    		$mailParts = explode('@',$this->customerEmail,2);
    		// the user part of an e-mail address is case _sensitive_
    		// while the domain part is case insensitive.
    		$this->email_user = $mailParts[0];
    		$this->email_domain = strtolower($mailParts[1]);
    		// TODO
    		
    		$db = Database::getDatabase();			
    		$result = $db->query("SELECT id FROM {$this->tableName} WHERE email_user = :user AND email_domain = :domain LIMIT 1",array( 'user' => $this->email_user , 'domain' => $this->email_domain));
    		$existingID = $db->getValue();
    		if($existingID === FALSE) {
    			// no existing ID, insert
    			$this->dt_created = date ("Y-m-d H:i:s");
    			$this->insert();
    		} else {
    			$this->id = $existingID;
    			$this->dt_updated = date ("Y-m-d H:i:s");
    			$this->update();
    		}
    		return $this->id;
    	}
    	
	        public function update()
	        {
	            if(is_null($this->id)) return false;
	
	            $db = Database::getDatabase();
	
	            if(count($this->columns) == 0) return;
	
	            $sql = "UPDATE {$this->tableName} SET ";
	            foreach($this->columns as $k => $v) {
	            	if($k == 'dt_created') {
	            		// don't update created date.
	            		continue;
	            	}
	                $sql .= "`$k`=" . $db->quote($v) . ',';
	            }
	            $sql[strlen($sql) - 1] = ' ';
	
	            $sql .= "WHERE `{$this->idColumnName}` = " . $db->quote($this->id);
	            $db->query($sql);
	
	            return $db->affectedRows();
	        }
    	
    	
    	function sendConfirmationMail() {
    		// TODO
    		$downloadScriptName = 'download-confirm.php?id=' . $this->id . '&customerEmail=' . urlencode($this->customerEmail) . '&app_id=' . $this->app_id;
    		
    		// TODO
    		$downloadLink = 'http://'.$_SERVER['HTTP_HOST'] . WEB_ROOT . '/' . $downloadScriptName;

			$app = new Application($this->app_id);
			if(!$app->ok()) {
				die('Invalid app ID: ' . $this->app_id);
			}
    		$body 	 = "Hello {$this->customer_name}\n\n";
    		$body	.= "You have requested to download {$app->name}. Please click on the link below to download the application:\n\n";
    		$body	.= "\t{$downloadLink}\n\n";
    		$body	.= "If your e-mail program does not display the above text as a link, please copy the line above and paste it in your web browser.\n\n";
    		$body	.= "Thank you for your interest in {$app->name}.";
    		
    		
    		/*
    					// Create a random boundary
    					$boundary = base64_encode(md5(rand()));
    		
    					$headers  = "From: {$app->from_email}\n";
    					$headers .= "X-Mailer: PHP/" . phpversion() . "\n";
    					$headers .= "MIME-Version: 1.0\n";
    					$headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\n";
    					$headers .= "Content-Transfer-Encoding: 7bit\n\n";
    					$headers .= "This is a MIME encoded message.\n\n";
    		
    					$headers .= "--$boundary\n";
    		
    					$headers .= "Content-Type: text/plain; charset=\"iso-8859-1\"\n";
    					$headers .= "Content-Transfer-Encoding: 7bit\n\n";
    					$headers .= $app->getBody($this) . "\n\n\n";
    		
    					$headers .= "--$boundary\n";
    		
    					$headers .= "Content-Type: application/octet-stream; name=\"{$app->license_filename}\"\n";
    					$headers .= "Content-Transfer-Encoding: base64\n";
    					$headers .= "Content-Disposition: attachment\n\n";
    		
    				    $headers .= chunk_split(base64_encode($this->license))."\n";
    		
    				    $headers .= "--$boundary--";
    		
    		*/
    		
			// Create a random boundary
			$boundary = base64_encode(md5(rand()));

			$headers  = "From: {$app->from_email}\n";
			$headers .= "X-Mailer: PHP/" . phpversion() . "\n";
			$headers .= "MIME-Version: 1.0\n";
			$headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\n";
			$headers .= "Content-Transfer-Encoding: 7bit\n\n";
			$headers .= "This is a MIME encoded message.\n\n";

			$headers .= "--$boundary\n";

			$headers .= "Content-Type: text/plain; charset=\"iso-8859-1\"\n";
			$headers .= "Content-Transfer-Encoding: 7bit\n\n";
			$headers .= $body . "\n";

		    $headers .= "--$boundary--";
    		

		    
		    return mail($this->customerEmail, $app->name . ' download link','', utf8_encode($headers));
    		
    	}
    }
    // END adib 5-Jun-2010 10:13 
