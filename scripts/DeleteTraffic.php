<?php

$lock = fopen('/var/vhost/netaccounting/application/scripts/lockDelete.txt', 'w');
if(!flock($lock, LOCK_EX | LOCK_NB)) {
    exit();
}

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

$sql = "SELECT * FROM deleteTraffic WHERE deleted = '0'";
$data = $db->fetchRow($sql);

if(empty($data)) {
    die();
}

$sqlMinute = "DELETE FROM minTraffic WHERE macAddress = '$data[macAddress]'";
$sqlHourly = "DELETE FROM hourlyTraffic WHERE macAddress = '$data[macAddress]'";
$sqlDaily = "DELETE FROM dailyTraffic WHERE macAddress = '$data[macAddress]'";
$sqlTraffic = "DELETE FROM traffic WHERE macAddress = '$data[macAddress]'";
$sqlHistory = "DELETE FROM qosHistory WHERE macAddress = '$data[macAddress]'";

$db->beginTransaction();
if($data['allData']) {
    try {
        $db->getConnection()->exec($sqlMinute);
        $db->getConnection()->exec($sqlHourly);
        $db->getConnection()->exec($sqlDaily);
        $db->getConnection()->exec($sqlTraffic);
        $db->getConnection()->exec($sqlHistory);
        $db->update('deleteTraffic', array('deleted' => '1'), 'idDeleteTraffic = ' . $data['idDeleteTraffic']);
        $db->commit();
    } catch (Exception $e) {
        $db->rollback();
    }	
}
else {
    try {
        $db->getConnection()->exec($sqlMinute);
        $db->getConnection()->exec($sqlHourly);
        $db->getConnection()->exec($sqlDaily);
        $db->update('deleteTraffic', array('deleted' => '1'), 'idDeleteTraffic = ' . $data['idDeleteTraffic']);
        $db->commit();
    } catch (Exception $e) {
        $db->rollback();
    }	
}