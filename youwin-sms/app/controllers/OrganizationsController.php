<?php

class OrganizationsController extends Ext_Controller_Action {

  
  public function preDispatch(){
   $this->db=Zend_Registry::get('db');
  }

  public function listAction(){
   if (!$this->aaac->isDeveloper()){return;}
   $sel=$this->db->select()->from(array("o"=>"organizations"))->joinLeft(array("t"=>"tipologie_enti"),"o.ID_tipologia_ente=t.ID_tipologia_ente",array("tipo_ente"=>"t.denominazione"));
   list($organizations,$total)=DBUtils::pageQuery($sel,$this->getLimit(),$this->getOffset());
    $this->emitTableResult($organizations,$total);
  }
  
  public function getAction(){
   $db=$this->db;
   if (!$this->aaac->isDeveloper()){return;}
   $organization=$db->fetchRow($db->select()->from("organizations")->where("orgid=?",$this->getRequest()->getParam('id')));
   $this->emitLoadData($organization);
  }
  
  public function loadtypeAction(){
   $province=$this->db->fetchAll("SELECT * FROM tipologie_enti ORDER BY denominazione");
   $this->emitTableResult($province);
  } 
  
  public function listprovinceAction(){
   $province=$this->db->fetchAll("SELECT * FROM province");
   $this->emitTableResult($province);
  } 
  
  public function saveAction(){
   $req=$this->getRequest();
   if(!$this->aaac->isDeveloper()){return;}
   if ($req->getParam('id') === 'new') {
   	 $data_iscrizione=($req->getParam('data_iscrizione')!='')? self::prepareTimestampData($req->getParam('data_iscrizione'),array("/","."),array("-","-")):false;
     $record=array(
     	"N_ultimo_protocollo"=>0,
     	"ID_file_simbolo"=>null,
        "data_iscrizione"=>($data_iscrizione!=false)?$data_iscrizione:null
     );  
    } else {
     $record =$this->db->fetchRow("SELECT * FROM organizations WHERE orgid=?",array((int)$req->getParam('id')));
    }
    
    $record=apply($record,array(
      "ID_tipologia_ente"=>$req->getParam('ID_tipologia_ente'),
      "denominazione"=> $req->getParam("denominazione"),
      "indirizzo"=> $req->getParam("indirizzo"),
      "localita"=> $req->getParam("localita"),
      "cap"=> $req->getParam("cap"),
      "ID_provincia"=> $req->getParam("ID_provincia"),
      "tel"=> $req->getParam("tel"),
      "fax"=> $req->getParam("fax"),
      "codfis" => $req->getParam("codfis"),
      "piva" => $req->getParam("piva")
    ));
    
    //Se l'utente è nuovo devo conoscere la password
     if ($req->getParam('id') === 'new') {
       $this->db->insert("organizations",$record);
     }else{
       $this->db->update('organizations',$record,"orgid=".$req->getParam('id'));
     }
      $this->emitSaveData();
  }
  
  private function prepareTimestampData($string_data,$separator,$replace_with,$time=false){
   if($time===false){
    $time=date("H:i:s");
   }
   $d=str_replace($separator,$replace_with,$string_data);
   $d_parts=explode("-",$d);
   return $d_parts[2]."-".$d_parts[1]."-".$d_parts[0]." ".$time;
  }
}