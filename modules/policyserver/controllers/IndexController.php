<?php

class Policyserver_IndexController extends Zend_Controller_Action {
    
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
        $server = new Zend_Soap_Server('http://netaccounting.nosmadeira.pt/policyserver/index/wsdl');
        
        //set SOAP service class
        $server->setClass('Policyserver_Model_Services');
        
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
      $wsdl->setClass('Policyserver_Model_Services');
      
      $wsdl->setOperationBodyStyle(array('use' => 'literal'));

      // set SOAP action URI
      $wsdl->setUri('http://netaccounting.nosmadeira.pt/policyserver/index/soap');

      // handle request
      $wsdl->handle();
    }

}