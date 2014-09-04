<?php

/**
 * Omicronmedia(C) 2010
**/

class ConcorsiController extends Ext_Controller_Action {


  public function preDispatch(){
    $this->db=Zend_Registry::get('db');
    $globals= $this->db->fetchRow($this->db->select()->from("globals"));
    $this->ID_concorso=$globals["ID_concorso_attivo"];
  }


  /*** CONCORSI ****/
  
  public function listConcorsiAction(){
    $sel=$this->db->select()->from("concorsi");
    list($res,$total)=DBUtils::pageQuery($sel,$this->getLimit(),$this->getOffset());
    $this->emitTableResult($res,$total);
  }


  /*** GIORNATE ****/

  public function listGiorniAction(){
   // $ID_concorso=$this->getRequest()->getParam('ID_concorso');
    $sel=$this->db->select()
       ->from("giornate",array("ID","data","ora_start","ora_stop","giorno_settimana"=>"(DAYOFWEEK(data))"))
       ->where("ID_concorso=?",$this->ID_concorso)
       ->order("data");
    list($res,$total)=DBUtils::pageQuery($sel,$this->getLimit(),$this->getOffset());
    $this->emitTableResult($res,$total);
  }

  public function getGiornoAction(){
    $this->emitLoadData(
      $this->db->fetchRow(
        $this->db->select()
             ->from("giornate")
             ->where("ID=?",$this->getRequest()->getParam('id'))
        )
    );
  }

  public function saveGiornoAction(){
    $req=$this->getRequest();
    $data=array(
      "data"=>$req->getParam('data'),
      "ora_start"=>$req->getParam('ora_start'),
      "ora_stop"=>$req->getParam('ora_stop')
    ); 

    if ($req->getParam('id') === 'new') {
      $data["ID_concorso"]=$this->ID_concorso;        //$data["ID_concorso"]=$req->getParam('ID_concorso');
      $giorno=$this->db->fetchRow(
        $this->db->select()
             ->from("giornate")
             ->where("ID_concorso=?",$this->ID_concorso)
             ->where("data=?",$this->getRequest()->getParam('data'))
      );
      if ($giorno){
        $this->errors->addError("Il giorno inserito esiste gia'.");
        $this->emitSaveData();
        return;
      }
      $this->db->insert("giornate",$data);
    } else {
      $giorno=$this->db->fetchRow(
        $this->db->select()
             ->from("giornate")
             ->where("ID_concorso=?",$this->ID_concorso)
             ->where("ID<>?",$req->getParam('id'))
             ->where("data=?",$this->getRequest()->getParam('data'))
      );
      if ($giorno){
       $this->errors->addError("Il giorno inserito esiste gia'.");
       $this->emitSaveData();
       return;
      }
      $this->db->update("giornate",$data,"ID=".$req->getParam('id'));
    }
    $this->emitSaveData();
  }

  public function deleteGiornoAction(){
    $req=$this->getRequest();
    $control=$this->db->fetchOne("SELECT COUNT(1)as cnt FROM giornate_premi WHERE ID_giornata=?",$req->getParam('id'));
    if((int)$control>0){
     $this->errors->addError("Sono presenti dei record che fanno riferimento a questo oggetto.");
     $this->emitSaveData();
     return;
    }
    $this->db->delete("giornate","ID=".$req->getParam('id'));
    $this->emitSaveData();
  }


  public function svuotaGiornateAction(){
    $req=$this->getRequest();
    $control=$this->db->fetchOne(
       "SELECT COUNT(*)as cnt FROM giornate_premi WHERE ID_giornata IN ".
        "(SELECT ID FROM giornate WHERE ID_concorso=?)",$this->ID_concorso
    );
    if((int)$control>0){
     $this->errors->addError("E' necessario prima svuotare la tabella delle qnt. premi.");
     $this->emitSaveData();
     return;
    }
    $this->db->delete("giornate","ID_concorso=".$this->ID_concorso);
    $this->emitSaveData();
  }

  public function creaIntervalloAction(){
    $req=$this->getRequest();

    if (strtotime($req->getParam("data_start")) > strtotime($req->getParam("data_stop")) ){
      $this->errors->addError("La data iniziale non pu&ograve; essere posteriore alla data finale dell'intervallo.");
      $this->emitSaveData();
      return;
    }

    $giorni=$this->createDateInterval($req->getParam("data_start"), $req->getParam("data_stop"));

    foreach($giorni as $g){
      $control=$this->db->fetchOne(
              "SELECT COUNT(1) as cnt FROM ".
              " giornate WHERE ID_concorso=? ".
              " AND data=?",
              array($this->ID_concorso,$g)
      );
      if((int)$control>0){
       $this->errors->addError("Sono presenti giornate che si sovrappongono all'intervallo dato.<br>Nessuna data generata.");
       $this->emitSaveData();
       return;
      }
    }

    foreach($giorni as $g){
      $this->db->insert("giornate",array(
        "ID_concorso"=>$this->ID_concorso,
        "data"=>$g,
        "ora_start"=>$req->getParam("ora_start"),
        "ora_stop"=>$req->getParam("ora_stop")
      ));

    }

    $this->emitSaveData(array("success"=>true,"message"=>"Intervallo creato correttamente."));
  }

  private function createDateInterval($dateStart,$dateStop){
     $ONE_HOUR=3600;
     $ONE_DAY=$ONE_HOUR*24;
     $start=strtotime($dateStart." 12:00:00 GMT");
     $stop=strtotime($dateStop." 12:00:00 GMT");
     $giorni=array();
     for ($giorno=$start; $giorno<=$stop; $giorno+=$ONE_DAY){
        $giorni[]=gmdate("Y-m-d",$giorno);
     }
     return $giorni;
  }



  /*** PREMI ****/

  public function listPremiAction(){
   // $ID_concorso=$this->getRequest()->getParam('ID_concorso');
    $sel=$this->db->select()
       ->from("premi")
       ->where("ID_concorso=?",$this->ID_concorso)
       ->order("codice");
    list($res,$total)=DBUtils::pageQuery($sel,$this->getLimit(),$this->getOffset());
    $this->emitTableResult($res,$total);
  }

  public function getPremioAction(){
    $this->emitLoadData(
      $this->db->fetchRow(
        $this->db->select()
             ->from("premi")
             ->where("ID=?",$this->getRequest()->getParam('id'))
        )
    );
  }

  public function savePremioAction(){
    $req=$this->getRequest();
    $data=array(
      "codice"=>$req->getParam('codice'),
      "articolo"=>$req->getParam('articolo'),
      "denominazione"=>$req->getParam('denominazione'),
      "qnt_totale"=>(int)$req->getParam('qnt_totale'),
      "valore"=>(float)$req->getParam('valore'),
      "importo"=>(float)$req->getParam('importo'),
      "test"=>(int)$req->getParam("test"),
      "screen_name"=>$req->getParam("screen_name")
    );

    if ($req->getParam('id') === 'new') {
      $data["ID_concorso"]=$this->ID_concorso;        //$data["ID_concorso"]=$req->getParam('ID_concorso');
      $this->db->insert("premi",$data);
    } else {
      $this->db->update("premi",$data,"ID=".$req->getParam('id'));
    }
    $this->emitSaveData();
  }

  public function deletePremioAction(){
    $req=$this->getRequest();
    $control=$this->db->fetchOne("SELECT COUNT(1)as cnt FROM giornate_premi WHERE ID_premio=?",$req->getParam('id'));
    if((int)$control>0){
     $this->errors->addError("Sono presenti dei record che fanno riferimento a questo oggetto.");
     $this->emitSaveData();
     return;
    }
    $this->db->delete("premi","ID=".$req->getParam('id'));
    $this->emitSaveData();
  }

}