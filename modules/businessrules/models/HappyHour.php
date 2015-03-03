<?php

require_once('BusinessRule.php');

class Businessrules_Model_HappyHour implements BusinessRule {
    
    private $db;
    
    public function __construct() {
        $this->db = Zend_Db_Table::getDefaultAdapter();
    }
        
    public function execute($traffic, $businessRule, $accountedTraffic) {
        $dateTime = new DateTime($traffic['dateTime']);
        $hour = explode(',', $businessRule['hour']);
        $weekDay = explode(',', $businessRule['weekDay']);
        $monthDay = explode(',', $businessRule['monthDay']);
        $month = explode(',', $businessRule['month']);
        if (in_array($dateTime->format('n'), $month)
            && (in_array($dateTime->format('j'), $monthDay)
            || in_array($dateTime->format('N'), $weekDay))
            && in_array($dateTime->format('G'), $hour)) {
            
            $accountedTraffic *= $businessRule['percentage'];   
            $this->db->update('traffic', 
                array('accountedTraffic' => $accountedTraffic), 'idTraffic = ' . "$traffic[idTraffic]");
            $this->db->update('minTraffic', 
                array('accountedTraffic' => $accountedTraffic), 'idTraffic = ' . "$traffic[idTraffic]");
        }   
        return $accountedTraffic;
    }
    
}

