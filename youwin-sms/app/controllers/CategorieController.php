<?php

/**
 * Omicronmedia(C) 2009
**/

class CategorieController extends Ext_Controller_Action {

  
  public function preDispatch(){
    $this->db=Zend_Registry::get('db');
  }

  public function listAction(){
   $sel=$this->db->select()->from("categorie")->where("orgid=?",A::orgid())->order('order_id');
   list($categorie,$total)=DBUtils::pageQuery($sel,$this->getLimit(),$this->getOffset());
    $this->emitTableResult($categorie,$total);
  }
 
  public function getAction(){
    $db=$this->db;
  	$categoria=$db->fetchRow(
      $db->select()
         ->from("categorie")
         ->where("ID_categoria=?",$this->getRequest()->getParam('id'))
         ->where("orgid=?",A::orgid())
  	);
    $this->emitLoadData($categoria);
  }

  public function saveAction(){
    $req=$this->getRequest();
    $data=array("denominazione"=>$req->getParam('denominazione'),"orgid"=>A::orgid(),"order_id"=>(int)$req->getParam('order_id'));    
    if ($req->getParam('id') === 'new') {
     $this->db->insert("categorie",$data);
    } else { 
     $this->db->update("categorie",$data,"ID_categoria=".$req->getParam('id'));
    }
    $this->emitSaveData();
  }

  public function deleteAction(){
    $req=$this->getRequest();
    /** se esiste una pubblicazione con questa categoria blocca l'eliminazione**/
    $control=$this->db->fetchOne("SELECT COUNT(1)as cnt FROM pubblicazioni WHERE ID_categoria=?",$req->getParam('id'));
    if((int)$control>0){
     $this->errors->addError("Esiste una o pi&ugrave; pubblicazioni con questa categoria, impossibile eliminare la categoria selezionata");
     $this->emitSaveData();
     return;
    }
    $this->db->delete("categorie","ID_categoria=".$req->getParam('id'));
    $this->emitSaveData();
  }
}