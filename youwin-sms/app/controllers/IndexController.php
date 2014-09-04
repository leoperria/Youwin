<?php

class IndexController extends Zend_Controller_Action {

  public function init() {
    $this->aaac=Zend_Registry::get('aaac');
    $this->app_config=Zend_Registry::get('app_config');
  }

  public function preDispatch() {
    if (!$this->aaac->isLogged()) {
      if ('login' != $this->getRequest()->getActionName() && 'logout'!=$this->getRequest()->getActionName()) {
        $this->_helper->redirector('login','index');
      }
    }
  }

  public function indexAction() {
    require($this->app_config["BASE_PATH"]."/config/loader_config.php");
    $this->view->loader_config=$LOADER_CONFIG;
    $this->view->app_config=$this->app_config;
  }

  public function loginAction() {
    $req=$this->getRequest();
    $this->view->app_config=$this->app_config;

    //***************************************************** BYPASS LOGIN
    if ($this->app_config["BYPASS_LOGIN"][0]) {
      $res=$this->aaac->login($this->app_config["BYPASS_LOGIN"][1],$this->app_config["BYPASS_LOGIN"][2]);
      $this->_helper->redirector('index','index');
      return;
    }

    //***************************************************** BYPASS LOGIN
    if($req->isPost()) {
      if ($req->getParam('username')=='' || $req->getParam('password')=='') {
        $this->view->result='Inserire user e password.';
      }else {
        $user=$req->getParam('username');
        $password=$req->getParam('password');
        $res=$this->aaac->login($user,$password);
        if ($res) {
          $this->_helper->redirector('index','index');
        } else {
          $this->view->result='Accesso negato';
        }
      }
    }

  }

  public function logoutAction() {
    $this->aaac->logout();
    //***************************************************** BYPASS LOGIN
    if (!$this->app_config["BYPASS_LOGIN"][0]) {
      $this->_helper->redirector('login','index');
    }else {
      $this->_helper->viewRenderer->setNoRender(true);
    }
  }
}