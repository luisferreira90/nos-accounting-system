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

$client = new Zend_Soap_Client("http://policyserver.nosmadeira.pt/accounting/index/wsdl");
//Gather all data from the currentQos table as object
$array = $db->fetchAll("SELECT subnet AS ip, sceQos AS package FROM currentQos WHERE sceQos IS NOT NULL", null, Zend_DB::FETCH_OBJ); 
$client->pushBulk($array);