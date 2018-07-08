<?php
$_SERVER['HTTP_HOST'] = 'admin.softorino.com';
require realpath(dirname(__FILE__).'/../includes/master.inc.php');
$db = Database::getDatabase();
$downloads = $db->getRows("SELECT url FROM shine_downloads WHERE app_id=0 GROUP BY url");
$apps = array();

$abbrs = $db->getRows("SELECT id, abbreviation FROM shine_applications WHERE abbreviation != ''");
$abbrs_matches = array();
foreach ($abbrs as $abbr) {
	$abbrs_matches[$abbr['id']] = strtolower($abbr['abbreviation']);
}

$regexp = "/\/(".implode('|', $abbrs_matches).")\//i";
foreach ($downloads as $download) {
	preg_match($regexp, $download['url'],$matches);
	print_r($matches);
	$id = array_search($matches[1], $abbrs_matches);
	$apps[$download['url']] = (false === $id) ? 0 : $id;
}
print_r($apps);
/*
$regexp = "/download\.php\?(id|abbr)=([0-9a-zA-Z]+)/";
foreach ($downloads as $download) {
	preg_match($regexp, $download['url'],$matches);
	$kind = $matches[1];
	if ($kind == 'id') {
		$id = $matches[2];
	} else {
		$abbr = mysql_real_escape_string(strtoupper($matches[2]));
		$entry = $db->getRows("SELECT id FROM shine_applications WHERE abbreviation='".$abbr."'");
		$id = $entry[0]['id'];
	}
	$apps[$download['url']] = $id;
}
*/
foreach ($apps as $url=>$app_id) {
	$db->query("UPDATE shine_downloads SET app_id='" . mysql_real_escape_string($app_id) . "' WHERE url='" . mysql_real_escape_string($url) . "'");
}
?>