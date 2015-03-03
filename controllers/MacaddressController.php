<?php

class MacaddressController extends Zend_Controller_Action {
    
    private $db;

    public function init() {
        $this->db = Zend_Db_Table::getDefaultAdapter(); 
    }
    
    public function indexAction() {
        Zend_Loader::loadClass('Zend_View');
        $view = new Zend_View();
        $macaddressSql =  "SELECT DISTINCT(macAddress) FROM macAddressOverride";
        $this->view->macaddresslist = $this->db->fetchAll($macaddressSql);
    }
    
    public function addmacaddressAction() {
        $this->getHelper('ViewRenderer')->setNoRender(true);
        
        if (trim($_POST['qos']) == "") {
            echo "You have to define a QoS.";
            die();
        }
        if (trim($_POST['macAddress']) == "") {
            echo "You have to define a MACAddress.";
            die();
        }
        if (strlen($_POST['macAddress'])!= 12) {
            echo "Invalid MACAddress length.";
            die();
        }
        
        $macAddress = $_POST['macAddress'];
        $description = $_POST['description'];
        $qos = $_POST['qos'];       

        try {
            $this->db->insert('macAddressOverride', array('macAddress' => $macAddress, 'sceQos' => $qos, 'description' => $description));
            header("Location: http://netaccounting.nosmadeira.pt/macaddress/index");
        } catch (Exception $e) {
            var_dump($e);
        }
    }
    
    public function deletemacaddressAction() {
        $this->getHelper('ViewRenderer')->setNoRender(true);
        $this->db->delete('macAddressOverride', "macAddress = '$_POST[macAddress]'");
        header("Location: http://netaccounting.nosmadeira.pt/macaddress/index");
    }
}