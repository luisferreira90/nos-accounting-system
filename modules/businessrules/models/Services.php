<?php

class Businessrules_Model_Services {
    
    private $db;

    function __construct() {
        $this->db = Zend_Db_Table::getDefaultAdapter();   
    }   
    
    
    /**
     * Function that verifies if a date is in the correct format
     * @param date $date
     * @param string $format
     * @return boolean
     */
    private function validateDate($date, $format = 'Y-m-d H:i:s')
    {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) == $date;
    }
    
    
    /**
     * Function that verifies if a given value is a positive integer
     * @param int $value
     * @return boolean
     */
    private function isInteger($value) {
        if(!is_numeric($value) || $value < 0 || $value != round($value))
            return false;
        else
            return true;
    }
    
    
    /**
     * Standard function for the structure used in all SOAP replies
     * @param int $errorId
     * @param string $errorText
     * @return object $object
     */
    private function response($errorId, $errorText) {
        $object = new stdClass();
        $object->errorId = $errorId;
        $object->errorText = $errorText;
        return $object;
    }
    
    
    /**
     * Returns the current package of a given MAC Address
     * @param string $macAddress
     * @return object $result
     */
    public function currentPackage($macAddress) {
        //Checks if MAC Address length is correct
        if(strlen($macAddress)!= 12) {
            return $this->response(1, 'Invalid MACAddress length');
        }
        
        //Checks if MAC Address exists and what's it's SCE QoS
        $sql = "SELECT sceQos, internalQos, accountedTraffic FROM currentQos WHERE macAddress = '$macAddress'";
        $currentPackage = $this->db->fetchOne($sql);
        if(!$currentPackage) {
            return $this->response(2, 'MACAddress not found');
        }
        
        $result = $this->response(0, 'OK');
        $result->currentPackage = $currentPackage;
        return $result;
    }
    
    
    /**
     * Gathers all processed traffic data from a given MAC Address
     * @param string $macAddress
     * @param string $startDate
     * @param string $endDate
     * @return object $result
     */
    public function trafficHistory($macAddress, $startDate, $endDate) {
        if(!$this->validateDate($startDate) || !$this->validateDate($endDate)) {
            return $this->response(1, 'One of the dates is invalid. Format is Y-m-d H:i:s');
        }
        
        //Checks if the start date is more recent than the end date
        //Comparison can be done this way since we have ensured
        //that the date format is always Y-m-d H:i:s
        if($startDate > $endDate) {
            return $this->response(2, "Start date is higher than end date");
        }
     
        //We create a DateTime object from $startDate to verify
        //if the minutes are 00, 15, 30 or 45. If they are not,
        //we update them because the qosHistory table only has
        //traffic at those minutes, regardless of hour or date
        $dateTime = new DateTime($startDate);
        $hour = $dateTime->format('H');
        $minute = $dateTime->format('i');

        if ($minute > 0 && $minute <= 15 ) {
            $dateTime->setTime($hour, 15, 00);
        }
        else if ($minute > 15 && $minute <= 30 ) {
            $dateTime->setTime($hour, 30, 00);
        }
        else if ($minute > 30 && $minute <= 45 ) {
            $dateTime->setTime($hour, 45, 00);
        }
        else {
            $dateTime->setTime($hour, 00, 00);
        }

        //We update $startDate with the new format
        //after the calculations
        $startDate = $dateTime->format('Y-m-d H:i:s');

        $sql = "SELECT SUM(accountedTraffic) AS accountedTraffic, dateTime "
                     . "FROM traffic WHERE macAddress = '$macAddress' "
                     . "AND dateTime BETWEEN '$startDate' AND '$endDate' GROUP BY dateTime";
        
        //We get the traffic from the qosHistory table
        $trafficHistory = $this->db->fetchAll($sql);

        //Like with $startDate, we convert it into a DateTime object
        $endDate = new DateTime($endDate);

        //Instantiation of the array to be returned
        $finalArray = array();
        //Count variable to iterate the $trafficHistory array
        $count=0;

        //We make $startDate a DateTime object of itself
        $startDate = new DateTime($startDate);

        //Now we need to see if for every 15 minutes of traffic from
        //the $startDate until the $endDate there is a correspondence
        //in the $trafficHistory array
        while($startDate <= $endDate) {

            //This will be the string to use in date comparisons
            //always updated in each while cicle with 15 more minutes
            $dateString = $startDate->format('Y-m-d H:i:s');


            //If the correct time exists in the array, it should be the same
            //as the current $dateString and we add the sub-array to the finalArray.
            //We also increment count because if we found a match in the $trafficHistory
            //array, there is no need to use that record again. On to the next one
            if(@$trafficHistory[$count]['dateTime'] == $dateString) {
                array_push($finalArray, $trafficHistory[$count]);   
                $count++;
            }
            //If we don't find a record in the same time that we are checking
            //we insert a new sub-array with 0 traffic for that date into the
            //final array. We also don't increment $count
            else {
                $dateInsert = $startDate->format('Y-m-d H:i:s');
                array_push($finalArray, array('accountedTraffic' => '0', 'dateTime' => "$dateInsert"));
            }
            //Add 15 minutes to the dateTime object
            $startDate->add(new DateInterval('PT15M'));
        }
        
        if(empty($finalArray)) 
            return $this->response(3, "No data found");
        
        
        $result = $this->response(0, 'OK');
        $result->trafficHistory = $finalArray;
        return $result;
    }
    
    
    /**
     * Returns the traffic from the last day, last week and last three weeks
     * @param string $macAddress
     * @return object $response
     */
    public function calculatedTraffic($macAddress) {

        if(strlen($macAddress)!= 12) {
            return $this->response('1', 'Invalid MACAddress length');
        }

        $sql = "SELECT (SELECT SUM(accountedTraffic) FROM minTraffic WHERE macAddress = '$macAddress' AND dateTime BETWEEN 
        DATE_SUB(NOW(), INTERVAL 1440 MINUTE) AND DATE_SUB(NOW(), INTERVAL 0 MINUTE) GROUP BY macAddress) AS lastDay,
        (SELECT SUM(accountedTraffic) FROM hourlyTraffic WHERE macAddress = '$macAddress' AND dateTime BETWEEN 
        DATE_SUB(NOW(), INTERVAL 10080 MINUTE) AND DATE_SUB(NOW(), INTERVAL 1440 MINUTE) GROUP BY macAddress) AS lastWeek,
        (SELECT SUM(accountedTraffic) FROM dailyTraffic WHERE macAddress = '$macAddress' AND dateTime BETWEEN 
        DATE_SUB(NOW(), INTERVAL 30240 MINUTE) AND DATE_SUB(NOW(), INTERVAL 10080 MINUTE) GROUP BY macAddress) AS lastThreeWeeks";
        
        $calculatedTraffic = $this->db->fetchRow($sql);
        $response = $this->response('0', 'Ok');
        $response->calculatedTraffic = $calculatedTraffic;

        return $response;
    }
    
    
    /**
     * Gathers the history of all packages from a given MAC Address
     * @param string $macAddress
     * @param string $startDate
     * @param string $endDate
     * @return object $result
     */
    public function packageHistory($macAddress, $startDate, $endDate) {
        if(!$this->validateDate($startDate) || !$this->validateDate($endDate)) {
            return $this->response(1, 'One of the dates is invalid. Format is Y-m-d H:i:s');
        }
        
        //Checks if the start date is more recent than the end date
        //Comparison can be done this way since we have ensured
        //that the date format is always Y-m-d H:i:s
        if($startDate > $endDate) {
            return $this->response(2, "Start date is higher than end date");
        }
        
        $sql = "SELECT sceQos, internalQos, dateTime, isOverride "
             . "FROM qosHistory WHERE macAddress = '$macAddress' "
             . "AND dateTime BETWEEN '$startDate' AND '$endDate'";      
        $packageHistory = $this->db->fetchAll($sql);
        
        if(empty($packageHistory)) {
            $sql = "SELECT sceQos, internalQos, dateTime, isOverride "
             . "FROM qosHistory WHERE macAddress = '$macAddress' "
             . "AND dateTime = (SELECT MAX(dateTime) "
             . "FROM qosHistory WHERE macAddress = '$macAddress' "
             . "AND dateTime <= '$startDate')";
            $packageHistory = $this->db->fetchAll($sql);
            
            if(empty($packageHistory)) 
                return $this->response(3, "No data found");
        }
        
        $result = $this->response(0, 'OK');
        $result->packageHistory = $packageHistory;
        return $result;
    }
    
    /**
     * Deletes traffic data from a given MAC Address
     * @param string $macAddress
     * @return object $response
     */
    public function deleteTrafficHistory($macAddress) {
        try {
            if(strlen($macAddress)!= 12) {
                return $this->response(1, 'Invalid MACAddress length');
            }

            $sql = "SELECT idDeleteTraffic FROM deleteTraffic 
                    WHERE macAddress = '$macAddress' 
                    AND deleted = '0'";
            $isDeleted = $this->db->fetchOne($sql);

            if($isDeleted > 0) {
                $result = $this->response(0, "MAC Address '$macAddress' is already scheduled for deletion");
                $result->idDelete = $isDeleted;
                return $result;
            }

            $data = array('macAddress' => "$macAddress", 'allData' => '0');
            $this->db->insert('deleteTraffic', $data);
            $id = $this->db->lastInsertId();

            $result = $this->response(0, "MAC Address '$macAddress' traffic data scheduled for deletion");
            $result->idDelete = $id;
            return $result;
        } catch (Exception $e) {
            $log = fopen(APPLICATION_PATH . "/../logs/logDeleteOldTraffic.txt","a");
            fwrite($log, date('Y-m-d H:i:s') . "In DeleteTrafficHistory web service: \n" . $e . "n\n\n\n\n");
            fclose($log);
        }
    }
    
    
    /**
     * Deletes all records from a given MAC Address
     * @param string $macAddress
     * @return object $result
     */
    public function deleteHistory($macAddress) {
        try {
            //Checks if MACAddress length is correct
            if(strlen($macAddress)!= 12) {
                return $this->response(1, 'Invalid MACAddress length');
            }

            $sql = "SELECT idDeleteTraffic, allData FROM deleteTraffic 
                    WHERE macAddress = '$macAddress' 
                    AND deleted = '0'";
            $isDeleted = $this->db->fetchRow($sql);

            if($isDeleted['idDeleteTraffic'] > 0 AND $isDeleted['allData'] == 1) {
                $result = $this->response(0, "MAC Address '$macAddress' is already scheduled for deletion.");
                $result->idDelete = $isDeleted['idDeleteTraffic'];
                return $result;
            }

            if($isDeleted['idDeleteTraffic'] > 0 AND $isDeleted['allData'] == 0) {
                $this->db->update('deleteTraffic', array('allData' => '1'), 'idDeleteTraffic = ' . $isDeleted['idDeleteTraffic']);
                $result = $this->response(0, "MAC Address '$macAddress' updated to delete all data.");
                $result->idDelete = $isDeleted['idDeleteTraffic'];
                return $result;
            }

            $data = array('macAddress' => "$macAddress", 'allData' => '1');
            $this->db->insert('deleteTraffic', $data);
            $id = $this->db->lastInsertId();

            $result = $this->response(0, "MAC Address '$macAddress' data scheduled for deletion");
            $result->idDelete = $id;
            return $result;
        } catch (Exception $e) {
            $log = fopen(APPLICATION_PATH . "/../logs/logDeleteOldTraffic.txt","a");
            fwrite($log, date('Y-m-d H:i:s') . "In DeleteHistory web service: \n" . $e . "n\n\n\n\n");
            fclose($log);
        }       
    }
    
    
    /**
     * Creates a MAC Address override
     * @param string $macAddress
     * @param string $qos
     * @param string $description
     * @return object $response
     */
    public function addMacAddressOverride ($macAddress, $qos, $description) {
        if(strlen($macAddress)!= 12) {
            return $this->response(1, 'Invalid MACAddress length');
        }
        if (!$this->isInteger($qos)) {
            return $this->response(2, 'Invalid QoS');
        }       

        try {
            $this->db->insert('macAddressOverride', array(
                'macAddress' => $macAddress, 
                'sceQos' => $qos, 
                'description' => $description));
            return $this->response(0, 'Record inserted');
        } catch (Exception $e) {
            return $this->response(3, 'Error inserting data');
        }
    }
    
    
    /**
     * Deletes a MAC Address entry from the override table
     * @param string $macAddress
     * @return object $response
     */
    public function deleteMacAddressOverride ($macAddress) {
        if(strlen($macAddress)!= 12) {
            return $this->response(1, 'Invalid MACAddress length');
        }
        try {
            $this->db->delete('macAddressOverride', "macAddress = '$macAddress'");
            return $this->response(0, 'Record deleted');
        } catch (Exception $e) {
            return $this->response(0, 'Error deleting data');
        }
    }
    
    
    /**
     * Creates a subnet override in the database
     * @param string $subnet
     * @param string $qos
     * @param string $description
     * @return object $response
     */
    public function addSubnetOverride($subnet, $mask, $qos, $description = NULL) {
        if(!$this->isInteger($qos)) {
            return $this->response('1', 'Invalid QoS');
        }
        if(!ip2long($subnet)) {
            return $this->response('2', 'Invalid IP/subnet');
        }
        if(!$this->isInteger($mask) || $mask > 32 || $mask < 1) {
            return $this->response('3', 'Invalid subnet mask');
        }
        $addresses = array();

        //Create an array with all the IP addresses that belong to the subnet
        if (($min = ip2long($subnet)) !== false) {
            $max = ($min | (1<<(32-$mask))-1);
            for ($i = $min; $i <= $max; $i++)
              $addresses[] = long2ip($i);
        }
        
        $subnetInsert = $subnet . '/' . $mask;
          
        $sql = "INSERT INTO overrideQos (ip, subnet, sceQos, description) VALUES ";       
        foreach ($addresses as $value) {
            $sql .= "('$value', '$subnetInsert', '$qos', '$description'),";
        }
        $sql = substr($sql, 0, -1);
        
        try {
            $this->db->exec($sql);
            return $this->response('0', 'Records inserted');
        } catch (Exception $e) {
            return $this->response('1', 'Couldn\'t insert the subnet records');
        }        
    }
    
    
    /**
     * Deletes a subnet override from the database
     * @param string $subnet
     * @param string $mask
     * @return object $response
     */
    public function deleteSubnetOverride($subnet, $mask) {
        if(!ip2long($subnet)) {
            return $this->response('1', 'Invalid IP/subnet');
        }
        if(!$this->isInteger($mask) || $mask > 32 || $mask < 1) {
            return $this->response('2', 'Invalid subnet mask');
        }
        
        $subnet = $subnet . '/' . $mask;
        try {
            $this->db->delete('overrideQos', "subnet = '$subnet'");
             return $this->response('0', 'Records deleted');          
        } catch (Exception $e) {
            return $this->response('3', 'Couldn\'t delete records');
        }

    }
 
}
