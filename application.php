<?PHP
	require 'includes/master.inc.php';
	
	define('LOCAL_UPLOAD_PATH', DOC_ROOT.'/shine_uploads');
	
	$Auth->requireAdmin('login.php');
	$nav = 'applications';
	
	$app = new Application($_GET['id']);
	if(!$app->ok()) redirect('index.php');
	
	function syncVersions($app)
	{
		$allAppsVersionsList = scandir(LOCAL_UPLOAD_PATH);
		
		$versions = $app->versions();
		$versionsList = array();
		foreach ($versions as $v)
		{
			$versionsList[] = $v->url;
			$altVersionsList[] = $v->alternate_fname;	
		}
		$versionsList = array_unique($versionsList);
		$altVersionsList = array_unique($altVersionsList);
		
		$s3 = new S3($app->s3key, $app->s3pkey);
		$s3VersionsList = array_keys($s3->getBucketContents($app->s3bucket));
		
		$intersect = array_intersect($versionsList, $allAppsVersionsList);
		$diff = array_diff($intersect, $s3VersionsList);
		$intersectAlt = array_intersect($altVersionsList, $allAppsVersionsList);
		$diffList = array_merge($diff, $intersectAlt);
		
		foreach ($diffList as $v)
		{
			$s3->uploadFile($app->s3bucket, $v, LOCAL_UPLOAD_PATH . "/" . $v, true);
		}
	}
	
	if (isset($_POST['btnSync']))
	{
		syncVersions($app);
	}

	if(isset($_POST['btnSaveApp']))
	{
		$Error->blank($_POST['name'], 'Application Name');

		if($Error->ok())
		{
			$app                    = new Application($_GET['id']);
			$app->abbreviation      = $_POST['abbreviation'];
			$app->name              = $_POST['name'];
			$app->link              = $_POST['link'];
			$app->bundle_name       = $_POST['bundle_name'];
			$app->bundle_id         = $_POST['bundle_id'];
			$app->i_use_this_key    = $_POST['i_use_this_key'];
			$app->s3key             = $_POST['s3key'];
			$app->s3pkey            = $_POST['s3pkey'];
			$app->s3bucket          = $_POST['s3bucket'];
			$app->s3path            = $_POST['s3path'];
			$app->s3domain          = $_POST['s3domain'];
			$app->s3distribution    = $_POST['s3distribution'];
			$app->sparkle_key       = $_POST['sparkle_key'];
			$app->sparkle_pkey      = $_POST['sparkle_pkey'];
			$app->activation_online = $_POST['activation_online'];
			$app->activation_online_class = $_POST['activation_online_class'];
			$app->ap_key            = $_POST['ap_key'];
			$app->ap_pkey           = $_POST['ap_pkey'];
			$app->cf_key            = $_POST['cf_key'];
			$app->cf_pkey           = $_POST['cf_pkey'];
			$app->rsa_key           = $_POST['rsa_key'];
			$app->rsa_pkey          = $_POST['rsa_pkey'];
			$app->custom_salt       = $_POST['custom_salt'];
			$app->from_email        = $_POST['from_email'];
			$app->email_subject     = $_POST['email_subject'];
			$app->email_body        = $_POST['email_body'];
			$app->license_filename  = $_POST['license_filename'];
			$app->return_url        = $_POST['return_url'];
			$app->fs_license_key    = $_POST['fs_license_key'];
			$app->fs_security_key   = $_POST['fs_security_key'];
			$app->mu_license_key    = $_POST['mu_license_key'];
			$app->tweet_terms       = $_POST['tweet_terms'];
			$app->upgrade_app_id    = $_POST['upgrade_app_id'];
			$app->engine_class_name = $_POST['engine_class_name'];
			$app->direct_download   = $_POST['direct_download'];
			$app->storage  		 	= $_POST['storage'];
			$app->is_ssl  		 	= $_POST['is_ssl'];
			$app->use_ga            = $_POST['use_ga'];
			$app->ga_key            = $_POST['ga_key'];
			$app->ga_domain         = $_POST['ga_domain'];
			$app->ga_country        = $_POST['ga_country'];
			$app->getdealy_name     = $_POST['getdealy_name'];
			$app->default_license_abbr = $_POST['default_license_abbr'];
			$app->getdealy_price    = $_POST['getdealy_price'];
			$app->use_postmark      = $_POST['use_postmark'];
			$app->update();
			redirect('application.php?id=' . $app->id);
		}
		else
		{
			$abbreviation      = $_POST['abbreviation'];
			$name              = $_POST['name'];
			$link              = $_POST['link'];
			$bundle_name       = $_POST['bundle_name'];
			$bundle_id         = $_POST['bundle_id'];
			$i_use_this_key    = $_POST['i_use_this_key'];
			$s3key             = $_POST['s3key'];
			$s3pkey            = $_POST['s3pkey'];
			$s3bucket          = $_POST['s3bucket'];
			$s3path            = $_POST['s3path'];
			$s3domain          = $_POST['s3domain'];
			$s3distribution    = $_POST['s3distribution'];
			$sparkle_key       = $_POST['sparkle_key'];
			$sparkle_pkey      = $_POST['sparkle_pkey'];
			$activation_online = $_POST['activation_online'];
			$activation_online_class = $_POST['activation_online_class'];
			$ap_key            = $_POST['ap_key'];
			$ap_pkey           = $_POST['ap_pkey'];
			$cf_key            = $_POST['cf_key'];
			$cf_pkey           = $_POST['cf_pkey'];
			$rsa_key           = $_POST['rsa_key'];
			$rsa_pkey          = $_POST['rsa_pkey'];
			$custom_salt       = $_POST['custom_salt'];
			$from_email        = $_POST['from_email'];
			$email_subject     = $_POST['email_subject'];
			$email_body        = $_POST['email_body'];
			$license_filename  = $_POST['license_filename'];
			$return_url        = $_POST['return_url'];
			$fs_license_key    = $_POST['fs_license_key'];
			$fs_security_key   = $_POST['fs_security_key'];
			$mu_license_key    = $_POST['mu_license_key'];
			$tweet_terms       = $_POST['tweet_terms'];
			$upgrade_app_id    = $_POST['upgrade_app_id'];
			$engine_class_name = $_POST['engine_class_name'];
			$direct_download   = $_POST['direct_download'];
			$storage		   = $_POST['storage'];
			$is_ssl  		   = $_POST['is_ssl'];
			$use_ga            = $_POST['use_ga'];
			$ga_key            = $_POST['ga_key'];
			$ga_domain         = $_POST['ga_domain'];
			$ga_country        = $_POST['ga_country'];
			$getdealy_name     = $_POST['getdealy_name'];
			$default_license_abbr = $_POST['default_license_abbr'];
			$getdealy_price    = $_POST['getdealy_price'];
			$use_postmark      = $_POST['use_postmark'];
		}
	}
	else
	{	
		$abbreviation      = $app->abbreviation;
		$name              = $app->name;
		$link              = $app->link;
		$bundle_name       = $app->bundle_name;
		$bundle_id         = $app->bundle_id;
		$i_use_this_key    = $app->i_use_this_key;
		$s3key             = $app->s3key;
		$s3pkey            = $app->s3pkey;
		$s3bucket          = $app->s3bucket;
		$s3path            = $app->s3path;
		$s3domain          = $app->s3domain;
		$s3distribution    = $app->s3distribution;
		$sparkle_key       = $app->sparkle_key;
		$sparkle_pkey      = $app->sparkle_pkey;
		$activation_online = $app->activation_online;
		$activation_online_class = $app->activation_online_class;
		$ap_key            = $app->ap_key;
		$ap_pkey           = $app->ap_pkey;
		$cf_key            = $app->cf_key;
		$cf_pkey           = $app->cf_pkey;
		$rsa_key           = $app->rsa_key;
		$rsa_pkey          = $app->rsa_pkey;
		$custom_salt       = $app->custom_salt;
		$from_email        = $app->from_email;
		$email_subject     = $app->email_subject;
		$email_body        = $app->email_body;
		$license_filename  = $app->license_filename;
		$return_url        = $app->return_url;
		$fs_license_key    = $app->fs_license_key;
		$fs_security_key   = $app->fs_security_key;
		$mu_license_key    = $app->mu_license_key;
		$tweet_terms       = $app->tweet_terms;
		$upgrade_app_id    = $app->upgrade_app_id;
		$engine_class_name = $app->engine_class_name;
		$direct_download   = $app->direct_download;
		$storage 		   = $app->storage;
		$is_ssl			   = $app->is_ssl;
		$use_ga            = $app->use_ga;
		$ga_key            = $app->ga_key;
		$ga_domain         = $app->ga_domain;
		$ga_country        = $app->ga_country;
		$getdealy_name     = $app->getdealy_name;
		$default_license_abbr = $app->default_license_abbr;
		$getdealy_price    = $app->getdealy_price;
		$use_postmark      = $app->use_postmark;
	}

	$upgrade_apps = DBObject::glob('Application', "SELECT * FROM shine_applications WHERE id <> '{$app->id}' ORDER BY name");

	$includes_path = DOC_ROOT . '/includes/';
	$files = scandir($includes_path);
	$available_engines = array();
	foreach($files as $fn)
	{
		$engine_name = match('/^class\.engine(..*?)\.php/', $fn, 1);
		if($engine_name !== false)
		{
			$available_engines[] = $engine_name;
		} 
	}
	$available_engines = implode(', ', $available_engines);
	
	
	$available_online_engines = array();
	foreach($files as $fn)
	{
		$engine_name = match('/^class\.engineonline(..*?)\.php/', $fn, 1);
		if($engine_name !== false)
		{
			$available_online_engines[] = $engine_name;
		} 
	}
	$available_online_engines = implode(', ', $available_online_engines);
	
	$license_types = $app->license_types();
	$lt_exists = false;
	
	if ((0 == $storage || 1 == $storage) && isset($_POST['btnSaveApp']))
	{
		syncVersions($app);
	}
?>
<?PHP include('inc/header.inc.php'); ?>

        <div id="bd">
            <div id="yui-main">
                <div class="yui-b"><div class="yui-g">

                    <div class="block tabs spaces">
                        <div class="hd">
                            <h2>Applications</h2>
							<ul>
								<li class="active"><a href="application.php?id=<?PHP echo $app->id; ?>"><?PHP echo $app->name; ?></a></li>
								<li><a href="license-types.php?id=<?PHP echo $app->id; ?>">License types</a></li>
								<li><a href="versions.php?id=<?PHP echo $app->id; ?>">Versions</a></li>
								<li><a href="version-new.php?id=<?PHP echo $app->id; ?>">Release New Version</a></li>
							</ul>
							<div class="clear"></div>
                        </div>
                        <div class="bd">
							<?PHP echo $Error; ?>
							<form action="application.php?id=<?PHP echo $app->id; ?>" method="post">
								<h3>Basic Stuff</h3>
                                <p>
									<label for="name">Application Abbreviation</label>
                                    <input type="text" class="text" name="abbreviation" id="abbreviation" value="<?PHP echo $abbreviation; ?>">
                                    <span class="info">Ex: FOC, COP, WPW</span>
                                </p>
                                <p>
                                    <label for="default_license_abbr">Default license abbreviation</label><br/>
                                    <select name="default_license_abbr" id="default_license_abbr">
                                    		<option value="">-- none --</option>
                                  		<?PHP foreach($license_types as $lt) : ?>
						<option <?PHP if($default_license_abbr == $lt->abbreviation) { echo 'selected="selected"'; $lt_exists = true; } ?> value="<?PHP echo $lt->abbreviation; ?>"><?PHP echo $lt->abbreviation; ?></option>
						<?PHP endforeach; ?>
						
						<?php if ($default_license_abbr != '' && $lt_exists == false): ?>
						<option selected="selected" value="<?PHP echo $default_license_abbr; ?>"><?php echo $default_license_abbr.' (DOES NOT EXIST)'; ?></option>
						<?php endif; ?>
						
				    </select><br/>
                                    <span class="info">(Used by GetDealy)</span>
                                </p> 
				<p>
					<label for="storage">Choose Storage Type</label><br>
					<select name="storage" id="storage">
						<option value="0" <?PHP if ($storage == '0') echo 'selected="selected"'; ?>>Amazon S3</option>
						<option value="1" <?PHP if ($storage == '1') echo 'selected="selected"'; ?>>CloudFront</option>
						<option value="2" <?PHP if ($storage == '2') echo 'selected="selected"'; ?>>Direct Download</option>
					</select>
					<input type="submit" name="btnSync" value="Sync" id="btnSync" />
					<br/>
				</p>
				<p>
					<input type="checkbox" name="is_ssl" id="is_ssl" value="1" <?PHP if (1 == $is_ssl) echo 'checked="checked"'; ?> >Use SSL</input><br/>
				</p>	
				<p>
					<label for="direct_download">Direct Download Type</label><br>
					<select name="direct_download" id="direct_download">
						<option value="0" <?PHP if ($direct_download == '0') echo 'selected="selected"'; ?>>PHP readfile() function</option>
						<option value="1" <?PHP if ($direct_download == '1') echo 'selected="selected"'; ?>>Webserver file download</option>
					</select><br/>
					<span class="info">When chosen 'PHP readfile() function', download.php will return the file itself with readfile(), while 'Webserver file download' option means, that the webserver itself will handle file downloads. To use webserver file downloads, you need to turn them on in webserver's settings, look for 'X-Sendfile setting' in your webserver (supports Apache, Nginx and Lighttpd webservers)</span>
                                </p>
                                <p>
									<label for="name">Application Name</label>
                                    <input type="text" class="text" name="name" id="name" value="<?PHP echo $name; ?>">
                                </p>
                                <p>
									<label for="link">Info URL</label>
                                    <input type="text" class="text" name="link" id="link" value="<?PHP echo $link; ?>">
									<span class="info">Your application's product page</span>
                                </p>
                                <p>
                                    <label for="url">Bundle Name</label>
                                    <input type="text" class="text" name="bundle_name" id="bundle_name" value="<?PHP echo $bundle_name; ?>">
                                    <span class="info">Ex: MyApplication.app</span>
                                </p>
                                <p>
                                    <label for="url">(MacAppstore) Bundle Id</label>
                                    <input type="text" class="text" name="bundle_id" id="bundle_id" value="<?PHP echo $bundle_id; ?>">
                                    <span class="info">Bundle id for an application like com.mycompany.myproduct</span>
                                </p>
                                <p>
                                    <label for="url">i use this URL Key Slug</label>
                                    <input type="text" class="text" name="i_use_this_key" id="i_use_this_key" value="<?PHP echo $i_use_this_key; ?>">
                                    <span class="info">Ex: http://osx.iusethis.com/app/<strong>virtualhostx</strong></span>
                                </p>
                                <p>
                                    <label for="url">Twitter keywords to search for</label>
                                    <input type="text" class="text" name="tweet_terms" id="tweet_terms" value="<?PHP echo htmlspecialchars($tweet_terms); ?>">
                                    <span class="info">Seperate with commas</span>
                                </p>
								<p>
									<label for="upgrade_app_id">Upgrade App</label><br>
                                    <select name="upgrade_app_id" id="upgrade_app_id">
										<option value="">-- None --</option>
										<?PHP foreach($upgrade_apps as $a) : ?>
										<option <?PHP if($upgrade_app_id == $a->id) echo 'selected="selected"'; ?> value="<?PHP echo $a->id; ?>"><?PHP echo $a->name; ?></option>
										<?PHP endforeach; ?>
									</select><br/>
									<span class="info">Choosing an app here will provide a one-click option to upgrade existing orders to the selected app.</span>
                                </p>
                                
				<hr>
				<h3>Google Analytics</h3>
				<p>
					<input type="checkbox" name="use_ga" id="use_ga" value="1" <?PHP echo $use_ga == 1 ? 'checked="checked"' : ''; ?>>
					<label for="use_ga">Use GA</label>
					<span class="info">If checked, downloads, updates and online activations will be tracked (with _trackEvent) to your GA account</span>
				</p>
				<p>
					<label for="ga_key">GA key (account id)</label>
					<input type="text" class="text" name="ga_key" id="ga_key" value="<?PHP echo $ga_key; ?>">
					<span class="info">Example: UA-123456-78</span>
				</p>
				<p>
					<label for="ga_domain">GA domain (your site)</label>
					<input type="text" class="text" name="ga_domain" id="ga_domain" value="<?PHP echo $ga_domain; ?>">
					<span class="info">Example: example.com, yourdomain.net</span>
				</p>
				<p>
					<input type="checkbox" name="ga_country" id="ga_country" value="1" <?PHP echo $ga_country == 1 ? 'checked="checked"' : ''; ?>>
					<label for="ga_country">Track countries</label>
					<span class="info">If checked, countries will be added to GA tracks like 'label' parameter</span>
				</p>


								<hr>
								
								<h3>Amazon S3</h3>
                                <p>
									<label for="s3key">Amazon S3 Key</label>
                                    <input type="text" class="text" name="s3key" id="s3key" value="<?PHP echo $s3key; ?>">
                                </p>
                                <p>
									<label for="s3key">Amazon S3 Private Key</label>
                                    <input type="text" class="text" name="s3pkey" id="s3pkey" value="<?PHP echo $s3pkey; ?>">
                                </p>
                                <p>
									<label for="s3key">Amazon S3 Bucket Name</label>
                                    <input type="text" class="text" name="s3bucket" id="s3bucket" value="<?PHP echo $s3bucket; ?>">
                                </p>
                                <p>
									<label for="url">Amazon S3 Path</label>
                                    <input type="text" class="text" name="s3path" id="s3path" value="<?PHP echo $s3path; ?>">
									<span class="info">The directory in your bucket where you downloads will be stored</span>
                                </p>
                                <p>
									<label for="s3domain">Amazon S3 Domain</label>
                                    <input type="text" class="text" name="s3domain" id="s3domain" value="<?PHP echo $s3domain; ?>">
                                </p>
                                <p>
									<label for="s3distribution">Amazon S3 Distribution ID</label>
                                    <input type="text" class="text" name="s3distribution" id="s3distribution" value="<?PHP echo $s3distribution; ?>">
                                </p>

								<hr>

								<h3>Sparkle</h3>
                                <p>
									<label for="sparkle_key">Sparkle Public Key</label>
                                    <textarea name="sparkle_key" id="sparkle_key" class="text"><?PHP echo $sparkle_key ?></textarea>
                                </p>
                                <p>
									<label for="sparkle_pkey">Sparkle Private Key</label>
                                    <textarea name="sparkle_pkey" id="sparkle_pkey" class="text"><?PHP echo $sparkle_pkey ?></textarea>
                                </p>

								<hr>

								<h3>Licensing Engine</h3>
								<p>
									<input type="checkbox" name="activation_online" id="activation_online" value="1" <?PHP echo $activation_online == 1 ? 'checked="checked"' : ''; ?>>
									<label for="engine_class_name">Use online activations</label>
									<span class="info">If checked, activation will result in generating activation key. 
										This key will be given to the user, who should enter it in the application itself. 
										The application then should make online-activation request, which will 
										return generated license for an activation key.</span>
								</p>
								
								<p>
									<label for="engine_class_name">Online Activations Key Generation Class Name</label><br>
                                    					<input type="text" class="text" name="activation_online_class" id="activation_online_class" value="<?PHP echo $activation_online_class; ?>">
									<span class="info">The PHP class name of online activations key generation engine. Available engines are: <?PHP echo $available_online_engines; ?></span>
                                				</p>


				                                <p>
									<label for="rsa_key">RSA Public Key for online activations</label>
				                                    <textarea name="rsa_key" id="rsa_key" class="text"><?PHP echo $rsa_key ?></textarea>
				                                </p>
				                                <p>
									<label for="rsa_pkey">RSA Private Key for online activations</label>
				                                    <textarea name="rsa_pkey" id="rsa_pkey" class="text"><?PHP echo $rsa_pkey ?></textarea>
				                                </p>
                                
								
								<p>
									<label for="engine_class_name">License Engine Class Name</label><br>
                                    <input type="text" class="text" name="engine_class_name" id="engine_class_name" value="<?PHP echo $engine_class_name; ?>">
									<span class="info">The PHP class name of your licensing engine. Available engines are: <?PHP echo $available_engines; ?></span>
                                </p>

                                <p>
									<label for="ap_key">Aquatic Prime Public Key</label>
                                    <textarea name="ap_key" id="ap_key" class="text"><?PHP echo $ap_key ?></textarea>
                                </p>
                                <p>
									<label for="ap_pkey">Aquatic Prime Private Key</label>
                                    <textarea name="ap_pkey" id="ap_pkey" class="text"><?PHP echo $ap_pkey ?></textarea>
                                </p>


                                <p>
									<label for="cf_key">CocoaFob DSA Public Key</label>
                                    <textarea name="cf_key" id="cf_key" class="text"><?PHP echo $cf_key ?></textarea>
                                </p>
                                <p>
									<label for="cf_pkey">CocoaFob DSA Private Key</label>
                                    <textarea name="cf_pkey" id="cf_pkey" class="text"><?PHP echo $cf_pkey ?></textarea>
                                </p>

								<p>
									<label for="custom_salt">Custom License Salt (if not using Aquatic Prime)</label>
                                    <textarea name="custom_salt" id="custom_salt" class="text"><?PHP echo $custom_salt ?></textarea>
                                </p>

								<hr>
								
                                <h3>PayPal</h3>
                                <p>
                                    <label for="return_url">PayPal Thanks URL</label>
                                    <input type="text" class="text" name="return_url" value="<?PHP echo $return_url; ?>" id="return_url">
                                </p>                                

                                <hr>

                                <h3>FastSpring</h3>
                                <p>
                                    <label for="return_url">License Request (Fulfillment) Security Key</label>
                                    <input type="text" class="text" name="fs_license_key" value="<?PHP echo $fs_license_key; ?>" id="fs_license_key">
                                </p>  
                                <p>
                                    <label for="return_url">Item Notification Security Key</label>
                                    <input type="text" class="text" name="fs_security_key" value="<?PHP echo $fs_security_key; ?>" id="fs_security_key">
                                </p>                                

                                <hr>

                                <h3>MacUpdate</h3>
                                <p>
                                    <label for="mu_license_key">License Request (Fulfillment) Security Key</label>
                                    <input type="text" class="text" name="mu_license_key" value="<?PHP echo $mu_license_key; ?>" id="mu_license_key">
                                </p>  

                                <hr>

                                <h3>GetDealy</h3>
                                <p>
                                    <label for="getdealy_name">GetDealy application name</label>
                                    <input type="text" class="text" name="getdealy_name" value="<?PHP echo $getdealy_name; ?>" id="getdealy_name">
                                    <span class="info">'app' parameter for license requests</span>
                                </p>
                                <p>
                                    <label for="getdealy_price">Price</label>
                                    <input type="text" class="text" name="getdealy_price" value="<?PHP echo $getdealy_price; ?>" id="getdealy_price">
                                    <span class="info">Getdealy price, ex: 9.95</span>
                                </p>

                                <hr>
								
								<h3>Thank-you Email</h3>
								<p>
									<input type="checkbox" name="use_postmark" id="use_postmark" value="1" <?PHP echo $use_postmark == 1 ? 'checked="checked"' : ''; ?>>
									<label for="use_postmark">Use Postmark service (GetDealy only)</label>
									<span class="info">If checked, emails will be sent with Postmark, otherwise standard 'mail' function will be used.</span>
								</p>
								<p>
									<label for="from_email">From Email</label>
									<input type="text" class="text" name="from_email" value="<?PHP echo $from_email; ?>" id="from_email">
								</p>
								<p>
									<label for="email_subject">Email Subject</label>
									<input type="text" class="text" name="email_subject" value="<?PHP echo $email_subject; ?>" id="email_subject">
								</p>
                                <p>
									<label for="email_body">Email Body</label>
                                    <textarea name="email_body" id="email_body" class="text"><?PHP echo $email_body ?></textarea><br>
									<span class="info"><strong>Available Substitutions</strong>: {first_name}, {last_name}, {payer_email}, {license}, {serial_number}, {1daylink}, {3daylink}, {1weeklink}, {foreverlink}. Add your own in includes/class.objects.php getBody().</span>
                                </p>

								<p>
									<label for="license_filename">License Filename</label>
									<input type="text" class="text" name="license_filename" value="<?PHP echo $license_filename; ?>" id="license_filename">
								</p>

								<p><input type="submit" name="btnSaveApp" value="Save Application" id="btnSaveApp"></p>
							</form>
						</div>
					</div>
              
                </div></div>
            </div>
            <div id="sidebar" class="yui-b">
            </div>
        </div>

<?PHP include('inc/footer.inc.php'); ?>
