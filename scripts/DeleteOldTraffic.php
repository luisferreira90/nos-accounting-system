<?php

define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../../application'));

define('APPLICATION_ENV',
    (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'development'));

require_once "Zend/Application.php";    

$application = new Zend_Application(
    APPLICATION_ENV,
    APPLICATION_PATH . '/configs/application.ini'
);

$application->bootstrap();

$db = Zend_Db::factory('Pdo_Mysql', array(
    'username' => 'root',
    'password' => '123456',
    'dbname'   => 'accounting'
));
     
$sqlMinute = "DELETE FROM minTraffic WHERE dateTime < DATE_SUB(NOW(), INTERVAL 1440 MINUTE)";
$sqlHourly = "DELETE FROM hourlyTraffic WHERE dateTime < DATE_SUB(NOW(), INTERVAL 10080 MINUTE)";
$sqlDaily = "DELETE FROM dailyTraffic WHERE dateTime < DATE_SUB(NOW(), INTERVAL 30 DAY)";
$sqlTraffic = "DELETE FROM traffic WHERE dateTime < DATE_SUB(NOW(), INTERVAL 30 DAY)";
$sqlHistory = "DELETE FROM qosHistory WHERE dateTime < DATE_SUB(NOW(), INTERVAL 30 DAY)";

try {
    $db->getConnection()->exec($sqlMinute);
    $db->getConnection()->exec($sqlHourly);
    $db->getConnection()->exec($sqlDaily);
    $db->getConnection()->exec($sqlTraffic);
    $db->getConnection()->exec($sqlHistory); 
} catch (Exception $e) {
    $log = fopen(APPLICATION_PATH . "/../logs/logDeleteOldTraffic.txt","a");
    fwrite($log, date('Y-m-d H:i:s') . "\n" . $e . "\n\n\n\n");
    fclose($log);
}

    
