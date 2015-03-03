<?php
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

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

date_default_timezone_set('Europe/Lisbon');

$tables = array('traffic', 'dailyTraffic', 'qosHistory');

foreach ($tables as $table) {

    for ($i=33; $i<=36; $i++) {
        try {
                $partition = 'p' . str_replace('-', '', date('Y-m-d', strtotime("-$i day")));
                $sql = "ALTER TABLE $table DROP PARTITION $partition";
                $db->getConnection()->exec($sql);
        } catch(Exception $e) {
                //Do nothing - error handling for partition deletion since if one doesn't exist
                //mysql launches an error
        }	
    }

    try {
        $partition = 'p' . str_replace('-', '', date('Y-m-d', strtotime("+1 day")));
        $partitionRange = str_replace('-', '', date('Y-m-d', strtotime("+2 day")));
        $sql = "ALTER TABLE $table ADD PARTITION (PARTITION $partition VALUES LESS THAN (TO_DAYS($partitionRange)))";
        $db->getConnection()->exec($sql);
    } catch (Exception $e) {
        //Do nothing - if partition already exists there is no need to issue an error
    }
}


for ($i=9; $i<=35; $i++) {
    try {
        $partition = 'p' . str_replace('-', '', date('Y-m-d', strtotime("-$i day")));
        $sql = "ALTER TABLE hourlyTraffic DROP PARTITION $partition";
        $db->getConnection()->exec($sql);
    } catch(Exception $e) {
            //Do nothing - error handling for partition deletion since if one doesn't exist
            //mysql launches an error
    }	
}

try {
    $partition = 'p' . str_replace('-', '', date('Y-m-d', strtotime("+1 day")));
    $partitionRange = str_replace('-', '', date('Y-m-d', strtotime("+2 day")));
    $sql = "ALTER TABLE hourlyTraffic ADD PARTITION (PARTITION $partition VALUES LESS THAN (TO_DAYS($partitionRange)))";
    $db->getConnection()->exec($sql);
} catch (Exception $e) {
    //Do nothing - if partition already exists there is no need to issue an error
}

for ($i=3; $i<=6; $i++) {
    try {
        $partition = 'p' . str_replace('-', '', date('Y-m-d', strtotime("-$i day")));
        $sql = "ALTER TABLE minTraffic DROP PARTITION $partition";
        $db->getConnection()->exec($sql);
    } catch(Exception $e) {
            //Do nothing - error handling for partition deletion since if one doesn't exist
            //mysql launches an error
    }	
}

try {
    $partition = 'p' . str_replace('-', '', date('Y-m-d', strtotime("+1 day")));
    $partitionRange = str_replace('-', '', date('Y-m-d', strtotime("+2 day")));
    $sql = "ALTER TABLE minTraffic ADD PARTITION (PARTITION $partition VALUES LESS THAN (TO_DAYS($partitionRange)))";
    $db->getConnection()->exec($sql);
} catch (Exception $e) {
    //Do nothing - if partition already exists there is no need to issue an error
}