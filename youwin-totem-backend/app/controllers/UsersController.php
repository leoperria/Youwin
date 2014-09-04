<?php

/**
 * Omicronmedia(C) 2009
 * Gli utenti possono essere Developer o Superuser
 * Solo i developer possono creare superuser
 * i superuser devono appartenere ad una organizzazione (per adesso il developer non fa niente)
 * solo il superuser può creare altri utenti editori nella sua organization
 * 
**/

class UsersController extends Ext_Controller_Action {

  
  public function preDispatch(){
   $this->db=Zend_Registry::get('db');
  }

  public function listAction(){
   if (!$this->aaac->isSuperUser() && !$this->aaac->isDeveloper()){return;}
   $orgid=($this->aaac->isDeveloper())? $this->getRequest()->getParam('orgid'):A::orgid();
   $sel=$this->db->select()->from("users",array("ID_user","titolo","nome","cognome","user","active"))->where("orgid=?",$orgid);
   if(!$this->aaac->isDeveloper()){
     $sel->where("superuser!=?",1)->where("developer!=?",1);
   }
   list($users,$total)=DBUtils::pageQuery($sel,$this->getLimit(),$this->getOffset());
    $this->emitTableResult($users,$total);
  }

  public function getAction(){
    $db=$this->db;
    if (!$this->aaac->isSuperUser() && !$this->aaac->isDeveloper()){return;}
    $fields=array("nome","cognome","titolo","active","user");
    if($this->aaac->isDeveloper()){
     $fields=array("nome","cognome","titolo","active","user","superuser","developer");
    } 
    $orgid=($this->aaac->isDeveloper())? $this->getRequest()->getParam('orgid'):A::orgid(); 	
  	$user=$db->fetchRow(
      $db->select()
         ->from("users",$fields)
         ->where("ID_user=?",$this->getRequest()->getParam('id'))
         ->where("orgid=?",$orgid)
  	);
    $this->emitLoadData($user);
  }

  public function saveAction(){
    $req=$this->getRequest();
    if (!$this->aaac->isSuperUser() && !$this->aaac->isDeveloper()){return;}
    if ( $req->getParam("id")==$this->aaac->getCurrentUser()->ID_user &&($req->getParam("active")!=$this->aaac->getCurrentUser()->active )) {
       $this->errors->addError("L'utente Amministratore non pu&ograve; alterare il proprio stato.");
       $this->errors->setCloseAfterErrors(true); //Chiede la chiusura della finestra dopo la visualizzazione dell'errore.
       $this->emitSaveData();
       return;
    }
    $orgid=($this->aaac->isDeveloper())? $this->getRequest()->getParam('orgid'):A::orgid(); 
    if ($req->getParam('id') === 'new') {
      $password=szGenPass::generatePassword(6); 
      $record =array(
        "orgid"=>(int)$orgid,
        "superuser"=>($this->aaac->isDeveloper())? $req->getParam('superuser'):0,
        "developer"=>($this->aaac->isDeveloper())? $req->getParam('developer'):0,
        "password"=>md5($password));  
    } else {
      $record =	$this->db->fetchRow("SELECT * FROM users WHERE ID_user=? AND orgid=?",array((int)$req->getParam('id'),$orgid));
      if($this->aaac->isDeveloper()){
        $record["superuser"]=(int)$req->getParam('superuser');
        $record["developer"]=(int)$req->getParam('developer');
      }
    }
    $record=apply($record,array(
      "titolo"=> $req->getParam("titolo"),
      "nome"=> $req->getParam("nome"),
      "cognome" => $req->getParam("cognome"),
      "user" => $req->getParam("user"),
      "active" =>$req->getParam("active")
    ));
    
    //Se l'utente è nuovo devo conoscere la password
     if ($req->getParam('id') === 'new') {
       $this->db->insert("users",$record);
       $this->emitSaveData(array("success"=>true,"message"=>"Password: ".$password));
     }else{
       $this->db->update('users',$record,"ID_user=".$req->getParam('id'));
       $this->emitSaveData();
     }
  }
  
  
  
  /**
   * Cancella un certo utente
   */
  public function deleteAction(){
    $req=$this->getRequest();
    // Soltanto superuser e developer possono cancellare un utente
    if (!$this->aaac->isSuperUser() && !$this->aaac->isDeveloper()){return;} 
    $user=$this->db->fetchRow("SELECT * FROM users WHERE ID_user=?",array($req->getParam('id')));   
    // Gli utenti superuser o developer non possono essere eliminati 
    if ((int)$user['superuser']==1 || (int)$user["developer"]==1){
      $this->errors->addError("L'utente Amministratore non pu&ograve; essere cancellato.");
      $this->emitSaveData();
      return;
    }
    $addwhere=($this->aaac->isDeveloper())?"":" AND orgid=".A::orgid();
    $q=$this->db->delete("users","ID_user=".(int)$this->getRequest()->getParam('id').$addwhere);       
    $this->emitJson(array("success"=>true));
  }

  /**
   * Permette di cambiare la password
   *
   */
  public function setpasswordAction(){
    $req=$this->getRequest();
    $cu=$this->aaac->getCurrentUser();
    
    // Soltanto developer,superuser e l'utente stesso possono cambiare la password
    if ($cu->developer || $cu->superuser || $cu->ID_user==$req->getParam('id')){
      $orgid=($this->aaac->isDeveloper())? $cu->orgid:A::orgid(); 
      $password=$this->getRequest()->getParam('password');
      $record=$this->db->query(
      	$this->db->select()
        ->from('users')
      	->where('ID_user=?',$this->getRequest()->getParam('id'))
      	->where('orgid=?',$orgid)
      )->fetchOBject();
              
      $record->password=md5($password);
      /** gestione delle validazioni ? **/
      //$this->db->update("users",array("password"=>$record->password),"ID_user=".$this->getRequest()->getParam('id'));
    }else{
      $this->errors->addError("Operazione non consentita.");
    }
    $this->emitSaveData(); 
  }

  public function getinfoAction(){
    $cu=$this->aaac->getCurrentUser();
    if($cu){
      if($cu->developer==1){
       $orgName="Kinesistemi S.r.l.";
      }else{
       $organization=$this->db->query("SELECT * FROM organizations WHERE orgid=?",$cu->orgid)->fetchObject();
       $orgName=$organization->denominazione;
      }
      $res=array(
         "ID_user"=>$cu->ID_user,
         "nome"=>$cu->nome,
         "cognome"=>$cu->cognome,
         "titolo"=>($cu->titolo!="" && $cu->titolo!=null)?$cu->titolo:"",
         "ente"=>$orgName
       );
      $this->emitLoadData($res);
    }
  }
  
  public function getlevelAction(){
   $this->emitLoadData(array(
    "superuser"=>($this->aaac->getCurrentUser()->superuser==1),
    "developer"=>($this->aaac->getCurrentUser()->developer==1)
   ));
  }
}