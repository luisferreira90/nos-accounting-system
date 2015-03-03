<?php

class Businessrules_IndexController extends Zend_Controller_Action {  

    public function init()
    {
    }

    public function indexAction()
    {
    }  
    
    public function soapAction() 
    {
        $this->getHelper('ViewRenderer')->setNoRender(true);
        
        //initialize server and set URI
        $server = new Zend_Soap_Server('http://netaccounting.nosmadeira.pt/businessrules/index/wsdl');
        
        //set SOAP service class
        $server->setClass('Businessrules_Model_Services');
        
        //handle request
        $server->handle();   
    }
    
    public function wsdlAction()
    {
      // disable layouts and renderers
      $this->getHelper('ViewRenderer')->setNoRender(true);

      // set up WSDL auto-discovery
      $wsdl = new Zend_Soap_AutoDiscover();

      // attach SOAP service class
      $wsdl->setClass('Businessrules_Model_Services');

      // set SOAP action URI
      $wsdl->setUri('http://netaccounting.nosmadeira.pt/businessrules/index/soap');
      
      $wsdl->setOperationBodyStyle(array('use' => 'literal'));

      // handle request
      $wsdl->handle();
    }
    
}