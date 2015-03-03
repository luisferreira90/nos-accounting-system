<?php

define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../../application'));

define('APPLICATION_ENV',
    (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'development'));

require_once "Zend/Application.php";    
require_once APPLICATION_PATH . "/modules/businessrules/models/Calculations.php";

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

$businessRules = new Businessrules_Model_Calculations;

//Start the processing with both the $argv serving as the lower and upper limit
//for the queries
$businessRules->getList($argv[1], $argv[2]);

$db->closeConnection();