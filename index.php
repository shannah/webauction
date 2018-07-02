<?php
$install = false;
$confIni = 'conf.ini';
if (!file_exists($confIni)) {
	$confIni = 'conf.ini.php';
}
if ( !file_exists($confIni) ){
	$install = true;

} else {

	$conf = parse_ini_file($confIni, true);
	if ( $conf['_database']['user'] == 'Your Username Here' ){
		$install = true;
	}
}

if ( $install ){
	header("Location: install.php");
	exit;
}
require_once 'include/functions.inc.php';
require_once 'config.inc.php';
require_once DATAFACE_INSTALLATION_PATH.'/dataface-public-api.php';
df_init(__FILE__, DATAFACE_INSTALLATION_URL);
$app =& Dataface_Application::getInstance();
$app->display();
?>