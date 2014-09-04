<?php

/**
 * Omicronmedia(C) 2009
**/

class MittentiController extends Ext_Controller_Action {

  
  public function preDispatch(){
   $this->db=Zend_Registry::get('db');
  }

  public function listAction(){
   $sel=$this->db->select()->from("mittenti")->where("orgid=?",A::orgid());
   list($mittenti,$total)=DBUtils::pageQuery($sel,$this->getLimit(),$this->getOffset());
    $this->emitTableResult($mittenti,$total);
  }
  
  
  public function getAction(){
    $db=$this->db;
  	$mittente=$db->fetchRow(
      $db->select()
         ->from("mittenti")
         ->where("ID_mittente=?",$this->getRequest()->getParam('id'))
         ->where("orgid=?",A::orgid())
  	);
    $this->emitLoadData($mittente);
  }

  public function saveAction(){
    $req=$this->getRequest(); 
    $data=array("denominazione"=>$req->getParam('denominazione'),"orgid"=>A::orgid());       
    if ($req->getParam('id') === 'new') {
     $this->db->insert("mittenti",$data);
    } else { 
     $this->db->update("mittenti",$data,"ID_mittente=".$req->getParam('id'));
    }
    $this->emitSaveData();
  }

  public function deleteAction(){
    $req=$this->getRequest();
    /** se esiste una pubblicazione con questo mittente blocca l'eliminazione**/
    $control=$this->db->fetchOne("SELECT COUNT(1)as cnt FROM pubblicazioni WHERE ID_mittente=?",$req->getParam('id'));
    if((int)$control>0){
     $this->errors->addError("Esiste una o pi&ugrave; pubblicazioni con questo mittente, impossibile eliminare il mittente selezionato");
     $this->emitSaveData();
     return;
    }
    $this->db->delete("mittenti","ID_mittente=".$req->getParam('id'));
    $this->emitSaveData();
  }
}