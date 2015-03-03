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

$client = new Zend_Soap_Client('http://netaccounting.nosmadeira.pt/businessrules/index/wsdl');

$macAddress = '00265b8af260';
$startDate = "2014-09-01 15:15:00";
$endDate = "2014-09-29 13:00:00";
$result = $client->trafficHistory($macAddress, $startDate, $endDate);

var_dump($result);