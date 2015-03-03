<?php

require_once('BusinessRule.php');

class Businessrules_Model_AccessTypeWeight implements BusinessRule {
    
    public function __construct() {        
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
        }
        return $accountedTraffic;
    }
    
}

