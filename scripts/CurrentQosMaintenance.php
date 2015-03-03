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

//Select all macAddresses which have more than one entry in the currentQos table
$sqlList = "SELECT macAddress, COUNT(*) c "
         . "FROM accounting.currentQos "
         . "WHERE macAddress IS NOT NULL "
         . "GROUP BY macAddress HAVING c > 1";

$list = $db->fetchAll($sqlList);

//Then, for each macAddress... 
foreach($list as $row => $macAddress) {
    
    //...We select the instance in which the accountedTraffic
    //is highest and it's respective sceQos, as well as the current dateTime
    $sql = "SELECT accountedTraffic, sceQos, internalQos FROM currentQos "
         . "WHERE macAddress = '$macAddress[macAddress]' " 
         . "AND dateTime = (SELECT max(dateTime) "
         . "FROM currentQos WHERE macAddress = '$macAddress[macAddress]')";
    
    $data = $db->fetchRow($sql);
    
    //And we update all the instances of that macAddress with the
    //accountedTraffic and sceQos we have just been given
    $db->update('currentQos', 
                $data, 
                "macAddress = '$macAddress[macAddress]'");
    
}