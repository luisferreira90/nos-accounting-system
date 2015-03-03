<?php

class SubnetController extends Zend_Controller_Action {
    
    private $db;

    public function init() {
        $this->db = Zend_Db_Table::getDefaultAdapter(); 
    }
    
    public function indexAction() {
        Zend_Loader::loadClass('Zend_View');
        $view = new Zend_View();
        $subnetSql =  "SELECT DISTINCT(subnet) FROM overrideQos";
        $this->view->subnetlist = $this->db->fetchAll($subnetSql);
    }
    
    public function checksubnetAction() {
        $this->getHelper('ViewRenderer')->setNoRender(true);
        
        if (trim($_POST['qos']) == "") {
            echo "You have to define a QoS.";
            die();
        }
        if (trim($_POST['subnet']) == "") {
            echo "You have to define a subnet.";
            die();
        }
        
        $subnet = $_POST['subnet'];
        $qos = $_POST['qos'];    
        $description = $_POST['description'];
        $addresses = array();
        
        //Separate the number of subnet bits from the subnet itself
        list($ip, $len) = explode('/', $subnet);

        //Create an array with all the IP addresses that belong to the subnet
        if (($min = ip2long($ip)) !== false) {
            $max = ($min | (1<<(32-$len))-1);
            for ($i = $min; $i <= $max; $i++)
              $addresses[] = long2ip($i);
          }
          
        $now = new Zend_Db_Expr('NOW()');
        $sql = "INSERT INTO overrideQos (ip, subnet, sceQos, description) "
                . "VALUES (?, ?, ?, ?)";
        $stmt = new Zend_Db_Statement_Pdo($this->db, $sql);
        
        foreach ($addresses as $value) {           
            try {
                $insert=array(
                    '0' => $value,
                    '1' => $subnet,
                    '2' => $qos,
                    '3' => $description);
                $stmt->execute($insert);
            } catch(Exception $e) {
                var_dump($e);
            }
        }
    }
    
    public function deletesubnetAction() {
        $this->getHelper('ViewRenderer')->setNoRender(true);
        $this->db->delete('overrideQos', "subnet = '$_POST[sel_subnet]'");
        header("Location: http://netaccounting.nosmadeira.pt/subnet/index");
    }
}