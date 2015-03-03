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

//Since the minTraffic table only has traffic up until
//24 hours, we gather all the data from those last 24 hours in case an hour failed
for($i = 1; $i<=24; $i++) {
    //The $today variable is actually the date and hour of the last hour. 
    //So in most cases it really is "today", but in some instances (e.g. after midnight),
    //it's actually the previous day at 11PM
    $today = date('Y-m-d H', strtotime("-$i hour"));
    $hour = date('H',strtotime("-$i hour"));

    //This query groups all the traffic that all macAddresses did at the last hour
    //and inserts it into the hourlyTraffic table. The data is gathered from the
    //minuteTraffic table because it has less data than the traffic table, thus
    //implying a faster execution time
    $sql = "INSERT IGNORE INTO hourlyTraffic SELECT macAddress, SUM(accountedTraffic), '$today' 
        FROM minTraffic WHERE DATE(dateTime) = DATE('$today') AND HOUR(dateTime) = '$hour' GROUP BY macAddress";

    $result = $db->getConnection()->exec($sql);
}