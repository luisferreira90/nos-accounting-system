<?php

class Application_Model_Rules {
    
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
        $sql = "SELECT * FROM accessTypeToBusinessRule WHERE accessType = $id ORDER BY sequence";
        return $this->db->fetchAll($sql);
    }
    
    public function getBusinessRules() {
        $sql = "SELECT idBusinessRule, name, hour, weekDay, monthDay, month, "
             . "description, percentage, startsOn, endsOn FROM businessRule";
        return $this->db->fetchAll($sql);
    }
    
    public function getBusinessRule($id) {
        $sql = "SELECT * FROM businessRule WHERE idBusinessRule = $id";
        return $this->db->fetchAll($sql);
    }
    
    public function createBusinessRule($array) {
        
        if(!is_numeric($array['percentage']) || $array['percentage'] < 1 || $array['percentage'] != round($array['percentage'])) {
            echo "You have to input an integer value in the percentage field.";
            die();
        }
        
        if((!is_numeric($array['starts']) 
                || $array['starts'] < 1 
                || $array['starts'] != round($array['starts'])
                || !is_numeric($array['ends']) 
                || $array['ends'] < 1 
                || $array['ends'] != round($array['ends'])
                ) && $array['type'] == 2) {
            echo "You have to input an integer value in the starts on and ends on fields.";
            die();
        }
        
        $percentage = $array['percentage'] / 100;
        
        $hour = '';
        $weekDay = '';
        $monthDay = '';
        $month = '';
        
        //These entries have the SET datatype in MySQL,
        //so we convert them to a single string first before inserting
        foreach($array['hour'] as $var) {
            $hour .= $var . ',';
        }
        foreach($array['weekday'] as $var) {
            $weekDay .= $var . ',';
        }        
        foreach($array['monthday'] as $var) {
            $monthDay .= $var . ',';
        }        
        foreach($array['month'] as $var) {
            $month .= $var . ',';
        }
        
        if(empty($hour)) {
            for($i=0; $i<=23; $i++)
                $hour .= $i . ',';
        }
        
        if(empty($weekDay) && empty($monthDay)) {
            for($i=0; $i<=23; $i++)
                $weekDay .= $i . ',';
        }
        
        if(!empty($weekDay) && empty($monthDay)) {
            $monthDay = NULL;
        }
        
        if(empty($weekDay) && !empty($monthDay)) {
            $weekDay = NULL;
        }
        
        if(empty($month)) {
            for($i=0; $i<=23; $i++)
                $month .= $i . ',';
        }
        
        $hour = substr($hour, 0, -1);
        $weekDay = substr($weekDay, 0, -1);
        $monthDay = substr($monthDay, 0, -1);
        $month = substr($month, 0, -1);
        
        $starts = $array['starts'] * 60;
        $ends = $array['ends'] * 60;
        $starts .= ' MINUTE';
        $ends .= ' MINUTE';
        
        if($array['type']==1) {
            $starts = $ends = NULL;
            $className = 'Businessrules_Model_HappyHour';
        }
        if($array['type']==2) {
            $hour = $weekDay = $monthDay = $month = NULL;          
            $className = 'Businessrules_Model_TimeWeight';
        }
        if($array['type']==3) {
            $starts = $ends = NULL;
            $className = 'Businessrules_Model_AccessTypeWeight';
        }     
        
        $array = array(
            'name' => $array['name'],
            'className' => $className,
            'hour' => $hour,
            'weekDay' => $weekDay,
            'monthDay' => $monthDay,
            'month' => $month,
            'description' => $array['description'],
            'percentage' => $percentage,
            'startsOn' => $starts,
            'endsOn' => $ends          
        );
        try {
            return $this->db->insert('businessRule', $array);
        } catch(Exception $e) {
            var_dump($e);
        }
    }
    
    public function associateBusinessRule($array) {
        $list = $this->getAccessTypeToBusinessRule($array['at']);
        if(!empty($list)) {
            foreach($list as $row => $value) {
                if($value['sequence'] >= $array['seq']) {
                    $this->db->update('accessTypeToBusinessRule',
                            array('sequence' => $value['sequence']+1),
                            'idAccessTypeToBusinessRule = ' . "$value[idAccessTypeToBusinessRule]");
                }
            }
        }            
        $insert = array(
            'accessType' => $array['at'],
            'businessRule' => $array['br'],
            'sequence' => $array['seq']
        );
        $this->db->insert('accessTypeToBusinessRule', $insert);        
    }
      
}