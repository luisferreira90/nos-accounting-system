<?php

class Businessrules_Model_Calculations {

    private $db;

    function __construct() {
        $this->db = Zend_Db_Table::getDefaultAdapter();   
    }
    

    /**
     * Initiates the accounting system processing cycle by providing a list
     * of traffic data
     * @param int $lowerLimit
     * @param int $upperLimit
     */
    public function getList($lowerLimit, $upperLimit) {
        $sql = "SELECT * FROM traffic
                WHERE isAccountable IS NULL
                LIMIT $lowerLimit , $upperLimit";
            $list = $this->db->fetchAll($sql);
            sleep(8);
            $this->getBusinessRules($list); 
    }
      
    
    /**
     * @param array $list
     * Calculates each business rule for each entry in the list and updates the DB
     */
    public function getBusinessRules($list) {
        //Gets all of the ip overrides and puts them in a single string
        $sqlOverrides = "SELECT ip FROM overrideQos";
        $overrideList = $this->db->fetchAll($sqlOverrides);
        $overrides = "_|";
        foreach($overrideList as $row => $value) {
           $overrides .= $value['ip'] . "|"; 
        }
        
        //Gets all of the macAddress overrides and puts them in a single string
        $sqlMacOverrides = "SELECT macAddress FROM macAddressOverride";
        $macOverrideList = $this->db->fetchAll($sqlMacOverrides);
        $macOverrides = "_|";
        foreach($macOverrideList as $row => $value) {
           $macOverrides .= $value['macAddress'] . "|"; 
        }
        
        //Gets all Business Rules, ordered by the sequence in which they are applied
        $sqlBusinessRules = "SELECT * FROM accessTypeToBusinessRule ORDER BY SEQUENCE";
        $businessRules = $this->db->fetchAll($sqlBusinessRules);
        
        //Gets all the priority levels, ordered by the max traffic they allow
        $sqlPriority = "SELECT * FROM accessTypePriority ORDER BY maxTraffic";
        $priority = $this->db->fetchAll($sqlPriority);
                         
        
          
        //Starts to iterate over the list
        foreach ($list as $row => $traffic) {
            //Verifies if the current IP exists in the override list
            $boolOverride = strpos($overrides, "|" . $traffic['ip'] . "|");
            //Verifies if the current macAddress exists in the macOverride list
            $boolMacOverride = strpos($macOverrides, "|" . $traffic['macAddress'] . "|");
            
            //Each record is treated as a transaction. Therefore, either it fully
            //processes the data for that record or it doesn't do anything at all
            $this->db->beginTransaction();
            
            //If the ip isn't an override then it's a normal IP and processing starts
            if(!$boolOverride && !$boolMacOverride) {
                try {
                    $accountedTraffic = $this->executeBusinessRules($businessRules, $traffic);
                    $qos = $this->verifyQos($traffic, $accountedTraffic, $priority);
                    
                    //Concatenates the mask to the IP
                    $traffic['ip'] .= "/32";
        
                    $this->currentQos($traffic, $accountedTraffic, $qos);
                    $this->qosHistory($traffic, $accountedTraffic, $qos);
                    $this->db->update('traffic', 
                            array('isAccountable' => 1), 
                            'idTraffic = ' . "$traffic[idTraffic]");
                    
                    $this->db->commit();
                } catch (Exception $e) {
                    $this->db->rollback();
                    $log = fopen(APPLICATION_PATH . "/../logs/logBusinessRules.txt","a");
                    $data = implode('|',$traffic);
                    fwrite($log, date('Y-m-d H:i:s') . "\n" . $e . "\n" . $data . "\n\n\n\n");
                    fclose($log);
                }
            }
            //If it is an override, it marks the element as accounted for in the DB
            else {
                try {
                    //If it is a macAddress override, we treat it right away 
                    //because in the macAddressOverride table we don't have IP's
                    if($boolMacOverride > 0) {
                        $this->macOverrideQos($traffic);
                        $qosSql = "SELECT sceQos FROM macAddressOverride WHERE macAddress = '$traffic[macAddress]'";
                    }
                    else {
                        $qosSql = "SELECT sceQos FROM overrideQos WHERE ip = '$traffic[ip]'";
                    }

                    $traffic['ip'] .= "/32";
                    $sceQos = $this->db->fetchOne($qosSql);
                    $qosHistory = array(
                        'processedTraffic' => '0',
                        'ip' => $traffic['ip'],
                        'macAddress' => $traffic['macAddress'],
                        'dateTime' => $traffic['dateTime'],
                        'sceQos' => $sceQos,
                        'mapsServiceParameter' => $traffic['mapsServiceParameter'],
                        'accessType' => $traffic['accessType'],
                        'clientNr' => $traffic['clientNr'],
                        'internalQos' => 'NULL',
                        'isOverride' => '1'
                    );
        
                    $this->db->insert('qosHistory', $qosHistory);
  
                    $this->db->update('traffic', 
                            array('isAccountable' => 1), 
                            'idTraffic = ' . $traffic['idTraffic']);
                    $this->db->commit();
                } catch (Exception $e) {
                    $this->db->rollback();
                    $log = fopen(APPLICATION_PATH . "/../logs/logBusinessRules.txt","a");
                    $data = implode('|',$traffic);
                    fwrite($log, date('Y-m-d H:i:s') . "\n" . $e . "\n" . $data . "\n\n\n\n");
                    fclose($log);
                }
            }
        }
        $this->ipOverrideQos();
    }

    
    /**
     * Executes the business rules according to their class name
     * @param array $businessRules
     * @param array $traffic
     * @return int $accountedTraffic
     */
    public function executeBusinessRules($businessRules, $traffic) {

        $accountedTraffic = $traffic['rawTraffic'];
        //Starts iterating over the business rules list
        //to see which rules apply to this IP
        foreach ($businessRules as $row => $businessRule) {
            //If this businessRule applies to this accessType, executes it
            if($businessRule['accessType'] == $traffic['accessType']) {
                $sql = "SELECT * FROM businessRule "
                     . "WHERE idBusinessRule = $businessRule[businessRule]";
                $result = $this->db->fetchRow($sql);
                $class = new $result['className'];            
                $accountedTraffic = $class->execute($traffic, $result, $accountedTraffic);
            }
        }
        return $accountedTraffic;
    }
    
    
    /**
     * 
     * Saves the data to the currentQos table, making it available to the policyServer
     * @param array $traffic
     * @param int $accountedTraffic
     * @param int $qos
     */
    public function currentQos($traffic, $accountedTraffic, $qos) {
        //Query to update the current QoS table. It inserts if the data is new
        //but if the IP already exists, it only updates
        $currentQos = "INSERT INTO currentQos (subnet, macAddress, sceQos, dateTime, accountedTraffic, internalQos, accessType) "
                    . "VALUES ('$traffic[ip]', '$traffic[macAddress]', '$qos[qos]', NOW(), $accountedTraffic, '$qos[internalQos]', '$traffic[accessType]') "
                    . "ON DUPLICATE KEY UPDATE "
                    . "sceQos = '$qos[qos]', "
                    . "internalQos = '$qos[internalQos]', "
                    . "accessType = '$traffic[accessType]', "
                    . "macAddress = '$traffic[macAddress]',"
                    . "dateTime = NOW(), "
                    . "accountedTraffic = $accountedTraffic";
        
        $this->db->getConnection()->exec($currentQos);
    }
    
    
    /**
     * Saves the processed data to the history table
     * @param array $traffic
     * @param int $accountedTraffic
     * @param int $qos
     */
    public function qosHistory($traffic, $accountedTraffic, $qos) {
        $qosHistory = array(
            'processedTraffic' => $accountedTraffic,
            'ip' => $traffic['ip'],
            'macAddress' => $traffic['macAddress'],
            'dateTime' => $traffic['dateTime'],
            'sceQos' => $qos['qos'],
            'mapsServiceParameter' => $traffic['mapsServiceParameter'],
            'accessType' => $traffic['accessType'],
            'clientNr' => $traffic['clientNr'],
            'internalQos' => $qos['internalQos']
        );
        
        $this->db->insert('qosHistory', $qosHistory);
    }
     

    /**
     * Inserts/updates the static ip's, subnets and their QoS in the currentQos table
     */
    public function ipOverrideQos() {                
        $sql = "INSERT INTO currentQos (subnet, sceQos, dateTime) "
             . "SELECT DISTINCT(subnet), sceQos, NOW() "
             . "FROM overrideQos "
             . "ON DUPLICATE KEY UPDATE "
             . "dateTime = NOW()";
        $this->db->getConnection()->exec($sql);
    }
    
    
    /**
     * @param array $traffic
     * Inserts the overriden macAddress qos into the currentQos table
     */
    public function macOverrideQos($traffic) {    
        //Concatenates the subnet mask to the IP
        $traffic['ip'] .= "/32";
        
        //Checks what's the QoS from the macAddress
        $sqlQos = "SELECT sceQos FROM macAddressOverride "
                . "WHERE macAddress = '$traffic[macAddress]'";
        $qos = $this->db->fetchOne($sqlQos);
        
        //Adds the macAddress to the currentQos table
        $currentQos = "INSERT INTO currentQos (subnet, macAddress, sceQos, dateTime) "
                    . "VALUES ('$traffic[ip]', '$traffic[macAddress]', $qos, NOW()) "
                    . "ON DUPLICATE KEY UPDATE "
                    . "sceQos = $qos, "
                    . "macAddress = '$traffic[macAddress]',"
                    . "dateTime = NOW()";
        $this->db->getConnection()->exec($currentQos);
    }
    
       
    /**
     * Checks which QoS this IP should have based on it's accessType and accountedTraffic
     * @param array $traffic
     * @param int $accountedTraffic
     * @param array $priority
     * @return array $qosArray
     */
    public function verifyQos($traffic, $accountedTraffic, $priority) {
        $qos = 1;  
        $internalQos = 1;
        foreach ($priority as $row => $value) {
            if($value['accessType'] == $traffic['accessType']) {
                $qos = $value['sceQos'];
                $internalQos = $value['internalLevel'];
                if ($value['maxTraffic'] >= $accountedTraffic) {
                    break;
                }
            }
        }
        $qosArray = array('qos' => $qos,
                          'internalQos' => $internalQos);
        return $qosArray;
    }

}