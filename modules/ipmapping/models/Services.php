<?php

class Ipmapping_Model_Services {

    private $db;

    function __construct() {
        $this->db = Zend_Db_Table::getDefaultAdapter();   
    }
    
    /**
     * PushBulk method
     * Receives an array of raw traffic data and inserts it into the database
     * @param array $rawData
     * @return boolean $status
     */
    public function pushBulk($rawData) {    
        
        if(empty($rawData)) {
            return true;
        }
        
        //Builds the insert in a single string, for better performance
        //and instead of various inserts
        $sql = "INSERT IGNORE INTO traffic (idTraffic, ip, macAddress, rawTraffic, accountedTraffic, dateTime, clientNr, accessType) VALUES ";
        
        $sqlMinute = "INSERT IGNORE INTO minTraffic (idTraffic, macAddress, accountedTraffic, dateTime) VALUES ";
        
        foreach($rawData as $row => $value) {
            $sql .= "('$value[0]', '$value[1]', '$value[2]', '$value[3]', 
                  '$value[3]', '$value[4]', '$value[5]', '$value[6]'),";
            
            $sqlMinute .= "('$value[0]', '$value[2]', 
                  '$value[3]', '$value[4]'),";
        }

        //Remove the last comma
        $sql = substr($sql, 0, -1);        
        $sqlMinute = substr($sqlMinute, 0, -1);  
        
        try {
            $this->db->getConnection()->exec($sql);
            $this->db->getConnection()->exec($sqlMinute);
        } catch (Exception $e) {
            $data = implode('|',$value);
            $log = fopen(APPLICATION_PATH . "/../logs/logIPMapping.txt","a");
            fwrite($log, "\n\n" . date('Y-m-d H:i:s') . "\n\n" . $e . "\n\n" . $data . "\n\n");
            fclose($log);
           return false;
        }        
        return true;
    }
    
    /**
     * RequestLastInsertedId method
     * Returns the last inserted id from the traffic table
     * @return int $result
     */
    public function requestLastInsertedId() {
        $sql = "SELECT MAX(idTraffic) FROM traffic";
        $result = $this->db->fetchOne($sql);
        return $result;
    }

}