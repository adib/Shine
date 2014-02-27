<?PHP
	require_once 'aws-sdk-for-php/sdk.class.php';
	require 'includes/master.inc.php';
	
	$Auth->requireAdmin('login.php');
	$nav = 'applications';
	
	$app = new Application($_GET['id']);
	if(!$app->ok()) redirect('index.php');

	if(isset($_POST['btnCreateVersion']))
	{
		$Error->blank($_POST['version_number'], 'Version Number');
		$Error->blank($_POST['human_version'], 'Human Readable Version Number');
		$Error->upload($_FILES['file'], 'file');
	
		if($Error->ok())
		{
			$v = new Version();
			$v->app_id         = $app->id;
			$v->version_number = $_POST['version_number'];
			$v->human_version  = $_POST['human_version'];
			$v->release_notes  = $_POST['release_notes'];
			$v->dt             = dater();
			$v->downloads      = 0;
			$v->filesize       = filesize($_FILES['file']['tmp_name']);
			$v->signature      = sign_file($_FILES['file']['tmp_name'], $app->sparkle_pkey);
			$v->status         = !empty($_POST['version_status']) ? $_POST['version_status'] : VERSION_STATUS_PRODUCTION;
			if (!empty($_POST['alternate_fname'])) $v->alternate_fname = $_POST['alternate_fname'];
			
			$object = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $app->name)) . "_" . $v->version_number . "." . pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
			$v->url = $object;
			chmod($_FILES['file']['tmp_name'], 0755);

			$alternate_fname = $v->alternate_fname;
			$uploadS3result = true;
			switch ($app->storage)
			{
				case 1:
					setCFconfig($app);
					$cdn = new AmazonCloudFront();
					$response = $cdn->create_invalidation($app->s3distribution, 'alternate_fname' . time(), $alternate_fname);
				case 0:
					setCFconfig($app);
				/*
					# Amazon S3 file upload
					$s3 = new S3($app->s3key, $app->s3pkey);
					$uploadS3result = $s3->uploadFile($app->s3bucket, $object, $_FILES['file']['tmp_name'], true);
				*/
					$s3 = new AmazonS3();
					$file_resource = fopen($_FILES['file']['tmp_name'], 'r');

	                $opt = array(
	                        'fileUpload'	=> $file_resource,
	                        'acl'			=> AmazonS3::ACL_PUBLIC
	                );
	                
	                $path = str_replace('//', '/', $app->s3path.'/'.$object);
	                $response = $s3->create_object($app->s3bucket, $path, $opt);

	                $uploadS3result = $response->isOK();
					
					if (!empty($alternate_fname))
					{
						$file_resource = fopen($_FILES['file']['tmp_name'], 'r');
	
		                $opt = array(
		                        'fileUpload'	=> $file_resource,
		                        'acl'			=> AmazonS3::ACL_PUBLIC
		                );
		                
		                $path = str_replace('//', '/', $app->s3path.'/'.$object);
		                $response = $s3->create_object($app->s3bucket, $path, $opt);

		                $uploadS3result &= $response->isOK();
					}				
				case 2:
					LocalUpload::uploadFile($_FILES['file']['tmp_name'], $object);
					if (!empty($alternate_fname))
					{
						copy(LOCAL_UPLOAD_PATH.'/'.$object, LOCAL_UPLOAD_PATH.'/'.$alternate_fname);
					}
				break;
			}

			$v->insert();
			
			if (!$uploadS3result){
				$Error->add('uploadS3', 'Could not upload file(s) to AmazonS3!');
				$Error->alert();
				
				$version_number = $_POST['version_number'];
				$human_version  = $_POST['human_version'];
				$release_notes  = $_POST['release_notes'];
				$alternate_fname  = $_POST['alternate_fname'];
			}else {
				redirect('versions.php?id=' . $app->id);
			}
		}
		else
		{
			$version_number = $_POST['version_number'];
			$human_version  = $_POST['human_version'];
			$release_notes  = $_POST['release_notes'];
			$alternate_fname  = $_POST['alternate_fname'];
		}
	}
	else
	{
		$version_number = '';
		$human_version  = '';
		$release_notes  = '';
		$alternate_fname = '';
	}
		
	// It would be better to use PHP's native OpenSSL extension
	// but it's PHP 5.3+ only. Too early to force that requirement
	// upon users.
    function sign_file($filename, $keydata)
    {
        $binary_hash = shell_exec('/usr/bin/openssl dgst -sha1 -binary < ' . $filename);
        $hash_tmp_file = tempnam('/tmp', 'foo');
        file_put_contents($hash_tmp_file, $binary_hash);

        $key_tmp_file = tempnam('/tmp', 'bar');
        if(strpos($keydata, '-----BEGIN DSA PRIVATE KEY-----') === false)
            $keydata = "-----BEGIN DSA PRIVATE KEY-----\n" . $keydata . "\n-----END DSA PRIVATE KEY-----\n";        
        file_put_contents($key_tmp_file, $keydata);

        $signed_data = shell_exec("/usr/bin/openssl dgst -dss1 -sign $key_tmp_file < $hash_tmp_file");

        return base64_encode($signed_data);     
    }
?>
<?PHP include('inc/header.inc.php'); ?>

        <div id="bd">
            <div id="yui-main">
                <div class="yui-b"><div class="yui-g">

                    <div class="block tabs spaces">
						<?PHP echo $Error; ?>
                        <div class="hd">
                            <h2>Applications</h2>
							<ul>
								<li><a href="application.php?id=<?PHP echo $app->id; ?>"><?PHP echo $app->name; ?></a></li>
								<li><a href="license-types.php?id=<?PHP echo $app->id; ?>">License types</a></li>
								<li><a href="versions.php?id=<?PHP echo $app->id; ?>">Versions</a></li>
								<li class="active"><a href="version-new.php?id=<?PHP echo $app->id; ?>">Release New Version</a></li>
							</ul>
							<div class="clear"></div>
                        </div>
                        <div class="bd">
							<form action="version-new.php?id=<?PHP echo $app->id; ?>" method="post" enctype="multipart/form-data">
								<p><label for="version_status">Version Status</label>
									<select name="version_status" id="version_status">
										<option value="<?php echo VERSION_STATUS_PRODUCTION; ?>"> Production
										<option value="<?php echo VERSION_STATUS_BETA; ?>"> Beta
										<option value="<?php echo VERSION_STATUS_TEST; ?>"> Test
									</select>
								</p>
								<p><label for="alternate_fname">Alternate Filename (optional)</label> <input type="text" name="alternate_fname" id="alternate_fname" value="<?PHP echo $alternate_fname;?>" class="text"><span class="info">ex.: yourappname.dmg</span></p>
								<p><label for="version_number">Sparkle Version Number</label> <input type="text" name="version_number" id="version_number" value="<?PHP echo $version_number;?>" class="text"></p>
								<p><label for="human_version">Human Readable Version Number</label> <input type="text" name="human_version" id="human_version" value="<?PHP echo $human_version;?>" class="text"></p>
								<p><label for="release_notes">Release Notes</label> <textarea class="text" name="release_notes" id="release_notes"><?PHP echo $release_notes; ?></textarea></p>
								<p><label for="file">Application Archive</label> <input type="file" name="file" id="file"></p>
								<p><input type="submit" name="btnCreateVersion" value="Create Version" id="btnCreateVersion"></p>
							</form>
						</div>
					</div>
              
                </div></div>
            </div>
            <div id="sidebar" class="yui-b">

            </div>
        </div>

<?PHP include('inc/footer.inc.php'); ?>
