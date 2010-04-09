<?PHP
	require 'includes/master.inc.php';
	$Auth->requireAdmin('login.php');
	$nav = 'applications';
	
	$v = new Version($_GET['id']);
	if(!$v->ok()) redirect('index.php');
	
	$app = new Application($v->app_id);
	if(!$app->ok()) redirect('index.php');
	
	// BEGIN adib 7-Apr-2010 18:53
	// handle version updates
	if(isset($_POST['btnUpdate']))
	{
		$hasData = false;
		if(!empty($_POST['version_number'])) {
			$v->version_number = $_POST['version_number'];
			$hasData = true;
		}
		if(!empty($_POST['human_version'])) {
			$v->human_version = $_POST['human_version'];
			$hasData = true;
		}
		if(!empty($_POST['release_notes'])) {
			$v->release_notes  = $_POST['release_notes'];
			$hasData = true;
		}
		if(!empty($_POST['url'])) {
			$v->url  = $_POST['url'];
			$hasData = true;
		}
		if(!empty($_POST['signature'])) {
			$v->signature  = $_POST['signature'];
			$hasData = true;
		}
		if(!empty($_POST['filesize'])) {
			$v->filesize  = $_POST['filesize'];	
			$hasData = true;
		}
		if($hasData) {
			$v->update();
		}
		redirect('version-edit.php?id=' . $v->id);
	}
	// END adib 7-Apr-2010 18:53
	
	if(isset($_POST['btnDelete']))
	{
		$v->delete();
		redirect('versions.php?id=' . $app->id);
	}

	$version_number = $v->version_number;
	$human_version  = $v->human_version;
	$release_notes  = $v->release_notes;
	$url            = $v->url;
	$signature      = $v->signature;
	$filesize       = $v->filesize;	
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
								<li class="active"><a href="versions.php?id=<?PHP echo $app->id; ?>">Versions</a></li>
								<li><a href="pirates.php?id=<?PHP echo $app->id; ?>">Pirates</a></li>
								<li><a href="version-new.php?id=<?PHP echo $app->id; ?>">Release New Version</a></li>
							</ul>
							<div class="clear"></div>
                        </div>
                        <div class="bd">
							<form action="version-edit.php?id=<?PHP echo $v->id; ?>" method="post">
								<p><label for="version_number">Version Number</label> <input type="text" name="version_number" id="version_number" value="<?PHP echo $version_number;?>" class="text"></p>
								<p><label for="human_version">Human Readable Version Number</label> <input type="text" name="human_version" id="human_version" value="<?PHP echo $human_version;?>" class="text"></p>
								<p><label for="url">Download URL</label> <input type="text" name="url" id="url" value="<?PHP echo $url;?>" class="text"></p>
								<p><label for="release_notes">Release Notes</label> <textarea class="text" name="release_notes" id="release_notes"><?PHP echo $release_notes; ?></textarea></p>
								<p><label for="filesize">Filesize</label> <input type="text" name="filesize" id="filesize" value="<?PHP echo $filesize; ?>" class="text"></p>
								<p><label for="signature">Sparkle Signature</label> <input type="text" name="signature" id="signature" value="<?PHP echo $signature; ?>" class="text"></p>
								<p><input type="submit" name="btnDelete" value="Delete Version" id="btnDelete" onclick="return confirm('Are you sure?');">
								<!-- BEGIN adib 7-Apr-2010 18:52 -->
								&nbsp;
								<input type="submit" name="btnUpdate" value="Update Version Data" id="btnUpdate" onclick="return confirm('Update version data?');">
								<!-- END adib 7-Apr-2010 18:52 -->
								</p>
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
