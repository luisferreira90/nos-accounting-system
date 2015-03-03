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

date_default_timezone_set('Europe/Lisbon');

//Since the hourlyTraffic table only has traffic up until 7 days, we gather
//all the data from those last 7 days in case one or more days failed
for($i = 1; $i<=7; $i++) {
    $yesterday = date('Y-m-d', strtotime( "-$i days" ));

    //Here we group all traffic made by all macAddresses during the course of a day,
    //gathering the data from the hourlyTraffic table, since it has the same data
    //but in less records when compared to the minuteTraffic table
    $sql = "INSERT IGNORE INTO dailyTraffic SELECT macAddress, SUM(accountedTraffic), '$yesterday' 
        FROM hourlyTraffic WHERE DATE(dateTime) = '$yesterday' 
        GROUP BY macAddress";

    $db->getConnection()->exec($sql);
}