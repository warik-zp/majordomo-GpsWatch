<?php
error_reporting(E_ALL);

chdir(dirname(__FILE__) . '/../');
include_once("./config.php");
include_once("./lib/loader.php");
include_once("./lib/threads.php");
set_time_limit(0);
// connecting to database
$db = new mysql(DB_HOST, '', DB_USER, DB_PASSWORD, DB_NAME);
include_once("./load_settings.php");
include_once(DIR_MODULES . "control_modules/control_modules.class.php");
$ctl = new control_modules();
echo date("H:i:s") . " running " . basename(__FILE__) . PHP_EOL;

include_once(DIR_MODULES . 'app_GpsWatch/app_GpsWatch.class.php');
$gpswatch_module = new app_GpsWatch();
$gpswatch_module->initServer();

while (1)
{
    setGlobal((str_replace('.php', '', basename(__FILE__))) . 'Run', time(), 1);
   
    $gpswatch_module->cycle();
   
    if (file_exists('./reboot') || IsSet($_GET['onetime']))
    {
      $db->Disconnect();
      exit;
    }
}
DebMes("Unexpected close of cycle: " . basename(__FILE__));
