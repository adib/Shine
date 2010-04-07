<?PHP
	require 'includes/master.inc.php';
	$Auth->requireAdmin('login.php');
	$nav = 'applications';
	
	// BEGIN adib 7-Apr-2010 12:44
 	$Config = Config::getConfig();
	// END adib 7-Apr-2010 12:44
	
	$app = new Application($_GET['id']);
	if(!$app->ok()) redirect('index.php');

	if(isset($_POST['btnCreateVersion']))
	{
		// BEGIN adib 7-Apr-2010 10:54
		// replace $_FILES['file'] with $uploadedFile
		$uploadedFile = $_FILES['file'];
		// END adib 7-Apr-2010 10:54
		
		$Error->blank($_POST['version_number'], 'Version Number');
		$Error->blank($_POST['human_version'], 'Human Readable Version Number');

		// BEGIN adib 7-Apr-2010 10:57
		//$Error->upload($_FILES['file'], 'file');
		if(empty($uploadedFile['tmp_name'])) {
			$uploadFolder = $Config->uploadFolder;
			if(!empty($_POST['existingUploadedFile']) && !empty($uploadFolder)) {
				$uploadedFile['name'] = $_POST['existingUploadedFile'];
				$uploadedFile['tmp_name'] = $uploadFolder . '/' . $_POST['existingUploadedFile'];
			}
			$Error->valid_file($uploadedFile['tmp_name'],'file');
		} else {
			$Error->upload($uploadedFile, 'file');
		}
		// END adib 7-Apr-2010 10:57
		
		if($Error->ok())
		{
			$v = new Version();
			$v->app_id         = $app->id;
			$v->version_number = $_POST['version_number'];
			$v->human_version  = $_POST['human_version'];
			$v->release_notes  = $_POST['release_notes'];
			$v->dt             = dater();
			$v->downloads      = 0;
			
			// BEGIN adib 7-Apr-2010 10:58
			//$v->filesize       = filesize($_FILES['file']['tmp_name']);
			//$v->signature      = sign_file($_FILES['file']['tmp_name'], $app->sparkle_pkey);
			//$object = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $app->name)) . "_" . $v->version_number . "." . substr($_FILES['file']['name'], -3);
			$v->filesize       = filesize($uploadedFile['tmp_name']);
			$v->signature      = sign_file($uploadedFile['tmp_name'], $app->sparkle_pkey);
			$object = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $app->name)) . "_" . $v->version_number . "." . substr($uploadedFile['name'], -3);
			// END adib 7-Apr-2010 10:58
			
			// BEGIN adib 7-Apr-2010 12:36
			// upload to S3 only if its configured.
			if(!empty($app->s3path)) {
			// END adib 7-Apr-2010 12:36
			
				$v->url = slash($app->s3path) . $object;
				$info   = parse_url($app->s3path);
				$object = slash($info['path']) . $object;
				
				// BEGIN adib 7-Apr-2010 10:59
				//chmod($_FILES['file']['tmp_name'], 0755);
				if(is_uploaded_file($uploadedFile['tmp_name'])) {
					chmod($uploadedFile['tmp_name'], 0755);
				}
				// END adib 7-Apr-2010 10:59
				
				$s3 = new S3($app->s3key, $app->s3pkey);
				
				// BEGIN adib 7-Apr-2010 11:00
				//$s3->uploadFile($app->s3bucket, $object, $_FILES['file']['tmp_name'], true);
				$s3->uploadFile($app->s3bucket, $object, $uploadedFile['tmp_name'], true);
				// END adib 7-Apr-2010 11:00
				
			// BEGIN adib 7-Apr-2010 12:37
			} else if (!empty($Config->downloadBaseFolder)) {
				// upload into a folder local to the web server.
				$downloadFolder = realpath($Config->downloadBaseFolder) . '/' . $app->id;
				if(!is_dir($downloadFolder)) {
					if(!mkdir($downloadFolder, 0755, true)) {
						die('Could not create download folder ' . $downloadFolder);
					} 
				}
				
				$destinationFile = $downloadFolder . '/' . $object;
				
				if(is_uploaded_file($uploadedFile['tmp_name'])) {
					move_uploaded_file($uploadedFile['tmp_name'], $destinationFile);
				} else {
					// just copy the file
					copy($uploadedFile['tmp_name'],$destinationFile);
				}
				chmod($destinationFile,0644);
				
				$v->url = $Config->downloadBaseURL . '/' . $app->id . '/' . $object;
			} // !empty($app->s3path)
			// END adib 7-Apr-2010 12:37
			
			$v->insert();

			redirect('versions.php?id=' . $app->id);
		}
		else
		{
			$version_number = $_POST['version_number'];
			$human_version  = $_POST['human_version'];
			$release_notes  = $_POST['release_notes'];
		}
	}
	else
	{
		$version_number = '';
		$human_version  = '';
		$release_notes  = '';
	}
	
	// It would be better to use PHP's native OpenSSL extension
	// but it's PHP 5.3+ only. Too early to force that requirement
	// upon users.
    function sign_file($filename, $keydata)
    {
        $binary_hash = shell_exec('openssl dgst -sha1 -binary < ' . $filename);
        $hash_tmp_file = tempnam('/tmp', 'foo');
        file_put_contents($hash_tmp_file, $binary_hash);

        $key_tmp_file = tempnam('/tmp', 'bar');
        if(strpos($keydata, '-----BEGIN DSA PRIVATE KEY-----') === false)
            $keydata = "-----BEGIN DSA PRIVATE KEY-----\n" . $keydata . "\n-----END DSA PRIVATE KEY-----\n";        
        file_put_contents($key_tmp_file, $keydata);

        $signed_data = shell_exec("openssl dgst -dss1 -sign $key_tmp_file < $hash_tmp_file");
		
		// BEGIN adib 7-Apr-2010 12:47
		// delete the key and hash file since leaving it around may be a security issue in shared web hosting environments.
		unlink($key_tmp_file);
		unlink($hash_tmp_file);
		// END adib 7-Apr-2010 12:47

        return base64_encode($signed_data);     
    }
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
 "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
    <title>Shine</title>
    <meta http-equiv="Content-Type" content="text/html;charset=utf-8" >
    <link rel="stylesheet" href="http://yui.yahooapis.com/2.7.0/build/reset-fonts-grids/reset-fonts-grids.css" type="text/css">
    <link rel="stylesheet" href="css/yuiapp.css" type="text/css">
</head>
<body class="rounded">
    <div id="doc3" class="yui-t0">

        <div id="hd">
            <?PHP include('inc/header.inc.php'); ?>
        </div>

        <div id="bd">
            <div id="yui-main">
                <div class="yui-b"><div class="yui-g">

                    <div class="block tabs spaces">
						<?PHP echo $Error; ?>
                        <div class="hd">
                            <h2>Applications</h2>
							<ul>
								<li><a href="application.php?id=<?PHP echo $app->id; ?>"><?PHP echo $app->name; ?></a></li>
								<li><a href="versions.php?id=<?PHP echo $app->id; ?>">Versions</a></li>
								<li><a href="pirates.php?id=<?PHP echo $app->id; ?>">Pirates</a></li>
								<li class="active"><a href="version-new.php?id=<?PHP echo $app->id; ?>">Release New Version</a></li>
							</ul>
							<div class="clear"></div>
                        </div>
                        <div class="bd">
							<form action="version-new.php?id=<?PHP echo $app->id; ?>" method="post" enctype="multipart/form-data">
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

        <div id="ft"></div>
    </div>
</body>
</html>
