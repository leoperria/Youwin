<?php

/**
 * Omicronmedia(C) 2010
**/

class ControlcenterController extends Ext_Controller_Action {


  public function preDispatch(){
    $this->db=Zend_Registry::get('db');
    $this->globals= $this->db->fetchRow($this->db->select()->from("globals"));
    $this->ID_concorso=$this->globals["ID_concorso_attivo"];
  }


  public function listCalendarioAction(){
    $this->emitTableResult(array(),0);
  }


  public function creaCalendarioAction(){




     if ($this->globals["protect"]){
       $this->errors->addError("I dati del concorso sono protetti da scrittura.");
       $this->emitSaveData();
       return;
     }



     // Cancella il calendario esistente
     $this->db->delete("giornate_premi","ID_concorso=".$this->ID_concorso);

     $nonricreare=$this->getRequest()->getParam('nonricreare');
     if ($nonricreare=="false"){
       // Carica tutti i giorni
       $giorni=$this->db->fetchAll(
         $this->db->select()
         ->from("giornate")
         ->where("ID_concorso=?",$this->ID_concorso)
         ->order("data")
       );

       // Carica tutti i premi
       $premi=$this->db->fetchAll(
         $this->db->select()
         ->from("premi")
         ->where("ID_concorso=?",$this->ID_concorso)
         ->order("codice")
       );

       // Crea il calendario
       foreach($premi as $p){
         foreach($giorni as $g){
           $this->db->insert("giornate_premi",array(
             "ID_concorso"=>$this->ID_concorso,
             "ID_giornata"=>$g["ID"],
             "ID_premio"=>$p["ID"],
             "qnt_massimale"=>0,
             "qnt_vinta"=>0
           ));
         }
       }
     }
     $this->emitSaveData();
  }

}