<?php
class FileController extends Ext_Controller_Action
{
   
  /**
   * Gestisce il download di un file
   *
   */
  public function getAction() {
    $fm=new Zend_FileManager($this->getRequest(), $this->getResponse(),Zend_Registry::get("db"));
    $fm->processDonwload();
  }


  /**
   * Gestisce l'upload di un file
   *
   */
  public function uploadAction() {
    $fm=new Zend_FileManager($this->getRequest(), $this->getResponse(),Zend_Registry::get("db"));
    $this->emitSaveData(array("success"=>true,"id"=>$fm->processUpload()));
  }

}
