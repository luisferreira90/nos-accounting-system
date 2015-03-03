<?php

class RulesController extends Zend_Controller_Action {
    
    private $businessRules;

    public function init() {
        $this->businessRules = new Application_Model_Rules();
    }
    
    public function indexAction() {
        Zend_Loader::loadClass('Zend_View');
        $view = new Zend_View();
    }
       
    public function viewAction() {
        Zend_Loader::loadClass('Zend_View');
        $view = new Zend_View();
        $this->view->businessRulesList = $this->businessRules->getBusinessRules();
    }
    
    public function associateAction() {
        Zend_Loader::loadClass('Zend_View');
        $view = new Zend_View();
        $this->view->businessRulesList = $this->businessRules->getBusinessRules();
        if($_GET['br']) {
            $this->view->accessTypes = $this->businessRules->getAccessTypes();
        }
        if($_GET['at']) {
            $this->view->associatedRules = $this->businessRules->getAccessTypeToBusinessRule($_GET['at']);
            $this->view->count = count($this->view->associatedRules);
        }    
    }
    
    public function associatedAction() {
        $this->getHelper('ViewRenderer')->setNoRender(true);
        $this->businessRules->associateBusinessRule($_GET);
        header("Location: http://netaccounting.nosmadeira.pt/rules/");
    }
    
    public function createAction() {
        Zend_Loader::loadClass('Zend_View');
        $view = new Zend_View();
        $this->view->businessRulesList = $this->businessRules->getBusinessRules();
    }
    
    public function create2Action() {
        $this->getHelper('ViewRenderer')->setNoRender(true);
        $this->businessRules->createBusinessRule($_POST);
        header("Location: http://netaccounting.nosmadeira.pt/rules/create");
    }
    
}