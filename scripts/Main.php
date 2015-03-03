<?php

//Here we verify if we can get a lock with flock() in the lockMain.txt file-
//If flock() returns true, it means this process has the lock and can continue
//execution. Otherwise it exits. This ensures that the cron can call this script
//every 4 minutes without creating data integrity problems in the DB, or 
//overloading the server with processes
$lock = fopen('/var/vhost/netaccounting/application/scripts/lockMain.txt', 'w');
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

//Then, build the shell command to call the Calculations script with different arguments,
//which represent the lower and upper limit for the queries, and execute
$cmd  = "php " . APPLICATION_PATH . "/scripts/Calculations.php 0 2499  & ";
$cmd .= "php " . APPLICATION_PATH . "/scripts/Calculations.php 2499 2499 & ";
$cmd .= "php " . APPLICATION_PATH . "/scripts/Calculations.php 4998 2499 & ";
$cmd .= "php " . APPLICATION_PATH . "/scripts/Calculations.php 7497 2499 & ";
$cmd .= "php " . APPLICATION_PATH . "/scripts/Calculations.php 9996 2499 & ";
$cmd .= "php " . APPLICATION_PATH . "/scripts/Calculations.php 12495 2499 & ";
$cmd .= "php " . APPLICATION_PATH . "/scripts/Calculations.php 14994 2499 & ";
$cmd .= "php " . APPLICATION_PATH . "/scripts/Calculations.php 17493 2499 & ";
$cmd .= "php " . APPLICATION_PATH . "/scripts/Calculations.php 19992 2499 & ";
$cmd .= "php " . APPLICATION_PATH . "/scripts/Calculations.php 22491 2499 & ";
$cmd .= "php " . APPLICATION_PATH . "/scripts/Calculations.php 24990 2499 & ";
$cmd .= "php " . APPLICATION_PATH . "/scripts/Calculations.php 27489 2499 & ";
$cmd .= "php " . APPLICATION_PATH . "/scripts/Calculations.php 29988 2499 & ";
$cmd .= "php " . APPLICATION_PATH . "/scripts/Calculations.php 32487 2499 & ";
$cmd .= "php " . APPLICATION_PATH . "/scripts/Calculations.php 34986 2499 & ";
$cmd .= "php " . APPLICATION_PATH . "/scripts/Calculations.php 37485 2499 ; ";

exec($cmd);

//Call the script to ensure all subnets from the same MAC Address have the same QoS
exec("php " . APPLICATION_PATH . "/scripts/CurrentQosMaintenance.php > /dev/null 2>/dev/null");