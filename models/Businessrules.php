<?php

class Application_Model_BusinessRules {
    
    private $db;
    
    public function __construct() {
        $this->db = Zend_Db_Table::getDefaultAdapter();
    }
    
    public function getAccessTypes() {
        $sql = "SELECT * FROM accessType";
        return $this->db->fetchAll($sql);
    }
    
    public function getAccessTypePriorities() {
        $sql = "SELECT * FROM accessTypePriority";
        return $this->db->fetchAll($sql);
    }
      
    public function getAccessTypePriority($id) {
        $sql = "SELECT * FROM accessTypePriority WHERE idAccessTypePriority = $id";
        return $this->db->fetchRow($sql);
    }
    
    public function getAccessTypeToBusinessRules() {
        $sql = "SELECT * FROM accessTypeToBusinessRule";
        return $this->db->fetchRow($sql);
    }
    
    public function getAccessTypeToBusinessRule($id) {
        $sql = "SELECT * FROM accessTypeToBusinessRule WHERE accessType = $id";
        return $this->db->fetchAll($sql);
    }
    
    public function getBusinessRules() {
        $sql = "SELECT * FROM businessRules";
        return $this->db->fetchAll($sql);
    }
    
    public function getBusinessRule($id) {
        $sql = "SELECT * FROM businessRules WHERE idBusinessRule = $id";
        return $this->db->fetchAll($sql);
    }
      
}