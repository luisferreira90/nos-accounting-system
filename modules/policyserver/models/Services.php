<?php

class Policyserver_Model_Services {
    
    private $db;

    function __construct() {
        $this->db = Zend_Db_Table::getDefaultAdapter();   
    }
    
     /**
     * Verifies the priority of a given IP
     * @param string $ip
     * @return object $pair
     */
    public function requestPackageByIp($ip) {
        $sql = "SELECT subnet as ip, sceQos as package FROM currentQos WHERE subnet LIKE '$ip/32'";
        $pair = $this->db->fetchRow($sql, null, Zend_DB::FETCH_OBJ);
        if(!$pair) {
            $sql = "SELECT subnet as ip, sceQos as package FROM overrideQos WHERE ip LIKE '$ip'";
            $pair = $this->db->fetchRow($sql, null, Zend_DB::FETCH_OBJ);
        }
        return $pair;
    }
    
     /**
     * Verifies the priority of a given MacAddress
     * @param string $macAddress
     * @return int $priority
     */
    public function requestPackageByMacAddress($macAddress) {
        $sql = "SELECT sceQos AS package FROM currentQos WHERE macAddress = $macAddress";
        $priority = $this->db->fetchOne($sql);
        return $priority;
    }

}