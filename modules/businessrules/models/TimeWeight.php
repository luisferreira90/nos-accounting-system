<?php

require_once('BusinessRule.php');

class Businessrules_Model_TimeWeight implements BusinessRule {
    
    private $db;
    
    public function __construct() {
        $this->db = Zend_Db_Table::getDefaultAdapter();
    }
    
    public function execute($traffic, $businessRule, $accountedTraffic) {
        
        $sql = "SELECT SUM(accountedTraffic), macAddress FROM (
                SELECT macAddress, SUM(accountedTraffic) AS accountedTraffic
                FROM minTraffic 
                WHERE macAddress = '$traffic[macAddress]'
                AND dateTime BETWEEN DATE_SUB('$traffic[dateTime]', INTERVAL $businessRule[endsOn]) 
                AND DATE_SUB('$traffic[dateTime]', INTERVAL $businessRule[startsOn])
                AND dateTime >= DATE_SUB('$traffic[dateTime]', INTERVAL 1440 MINUTE)
                GROUP BY macAddress
                
                UNION
                SELECT macAddress, SUM(accountedTraffic) AS accountedTraffic
                FROM hourlyTraffic 
                WHERE macAddress = '$traffic[macAddress]'
                AND dateTime BETWEEN DATE_SUB('$traffic[dateTime]', INTERVAL $businessRule[endsOn]) 
                AND DATE_SUB('$traffic[dateTime]', INTERVAL $businessRule[startsOn])
                AND dateTime < DATE_SUB('$traffic[dateTime]', INTERVAL 1440 MINUTE)
                GROUP BY macAddress
                
                UNION
                SELECT macAddress, SUM(accountedTraffic) AS accountedTraffic 
                FROM dailyTraffic 
                WHERE macAddress = '$traffic[macAddress]'
                AND dateTime BETWEEN DATE_SUB('$traffic[dateTime]', INTERVAL $businessRule[endsOn]) 
                AND DATE_SUB('$traffic[dateTime]', INTERVAL $businessRule[startsOn])
                AND dateTime < DATE_SUB('$traffic[dateTime]', INTERVAL 10080 MINUTE)
                GROUP BY macAddress ) x GROUP BY macAddress";

        $result = $this->db->fetchOne($sql);
        
        if($result > 0) {
            $accountedTraffic += $result * $businessRule['percentage'];  
        }

        return $accountedTraffic;

    }
    
}

