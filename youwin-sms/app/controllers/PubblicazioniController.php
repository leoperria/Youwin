<?php

/**
 * Omicronmedia(C) 2009
**/

class PubblicazioniController extends Ext_Controller_Action {

  
  public function preDispatch(){
   $this->db=Zend_Registry::get('db');
  }

  public function listAction(){
   $req=$this->getRequest();
   
   $sel=$this->db->select()->from(array("p"=>"pubblicazioni"))
      ->joinLeft(array("c"=>"categorie"),"p.ID_categoria=c.ID_categoria",array("categoria"=>"c.denominazione"))
      ->joinLeft(array("m"=>"mittenti"),"p.ID_mittente=m.ID_mittente",array("mittente"=>"m.denominazione"))
      ->joinLeft(array("l"=>"files_pubblicazioni"),"l.ID_pubblicazione=p.ID_pubblicazione",array("files"=>"COUNT(l.ID_file)"))
      ->joinLeft(array("f"=>"files"),"l.ID_file=f.ID_file")
      ->where("p.orgid=?",A::orgid());
   /** processa il fliltro e aggiungi eventuali condizioni **/
   if($req->getParam('data_inserimento')!=''){
   	$data_inserimento_start=$this->prepareTimestampData($req->getParam('data_inserimento'),array("/"),array("-"),"00:00:01");
   	$sel->where("p.data_inserimento>?",$data_inserimento_start);
   	$data_inserimento_stop=$this->prepareTimestampData($req->getParam('data_inserimento'),array("/"),array("-"),"23:59:59");
   	$sel->where("p.data_inserimento<?",$data_inserimento_stop);
   }
   if($req->getParam('pubblicato_dal')!='' && $req->getParam('pubblicato_al')){
   	$data_pubblicato_start=$this->prepareTimestampData($req->getParam('pubblicato_dal'),array("/"),array("-"),"00:00:01");
   	$sel->where("p.pubblicato_dal>?",$data_pubblicato_start);
   	$data_pubblicato_stop=$this->prepareTimestampData($req->getParam('pubblicato_al'),array("/"),array("-"),"23:59:59");
   	$sel->where("p.pubblicato_al<?",$data_pubblicato_stop);
   }
   if($req->getParam('oggetto')!=''){$sel->where("p.oggetto LIKE '".$req->getParam('oggetto')."%'");}
   if($req->getParam('autore')!=''){$sel->where("p.autore LIKE '".$req->getParam('autore')."%'");}
   if((int)$req->getParam('n_protocollo')>0){$sel->where("p.n_protocollo=?",(int)$req->getParam('n_protocollo'));}
   if((int)$req->getParam('anno_protocollo')>0){$sel->where("p.anno_protocollo=?",(int)$req->getParam('anno_protocollo'));}
   if((int)$req->getParam('ID_mittente')>0){$sel->where("p.ID_mittente=?",(int)$req->getParam('ID_mittente'));}
   if((int)$req->getParam('ID_categoria')>0){$sel->where("p.ID_categoria=?",(int)$req->getParam('ID_categoria'));}
   if($req->getParam('file_name')!=''){$sel->where("f.file_name LIKE '".$req->getParam('file_name')."%'");}
   $sel->order("p.n_protocollo")->group("p.ID_pubblicazione");
   list($pubblicazioni,$total)=DBUtils::pageQuery($sel,$this->getLimit(),$this->getOffset());
    $this->emitTableResult($pubblicazioni,$total);
  }
  
  
  public function getAction(){
    $db=$this->db;
  	$pubblicazione=$db->fetchRow(
      $db->select()
         ->from("pubblicazioni")
         ->where("ID_pubblicazione=?",$this->getRequest()->getParam('id'))
         ->where("orgid=?",A::orgid())
  	);
    $this->emitLoadData($pubblicazione);
  }

  public function saveAction(){
  	$req=$this->getRequest();
  	/** controllo campi **/
  	$error=false;
    if($req->getParam('oggetto')==''){$this->errors->addError("Il campo '<b>Oggetto</b>' non pu&ograve; essere vuoto");$error=true;}
  	if((int)$req->getParam('ID_categoria')==0){$this->errors->addError("Il campo '<b>Categoria</b>' non pu&ograve; essere vuoto");$error=true;}
  	if((int)$req->getParam('ID_mittente')==0){$this->errors->addError("Il campo '<b>Mittente</b>' non pu&ograve; essere vuoto");$error=true;}
  	if($req->getParam('autore')==''){$this->errors->addError("Il campo '<b>Autore</b>' non pu&ograve; essere vuoto");$error=true;}
  	if($req->getParam('data_inserimento')==''){$this->errors->addError("Il campo '<b>Data creazione</b>' non pu&ograve; essere vuoto");$error=true;}
  	if($req->getParam('pubblicato_dal')==''){$this->errors->addError("Il campo '<b>Pubblicato dal</b>' non pu&ograve; essere vuoto");$error=true;}
  	if($req->getParam('pubblicato_al')==''){$this->errors->addError("Il campo '<b>Pubblicato al</b>' non pu&ograve; essere vuoto");$error=true;}
  	if((int)$req->getParam('n_protocollo')==0){$this->errors->addError("Il campo '<b>Numero protocollo</b>' non pu&ograve; essere vuoto");$error=true;}
  	if((int)$req->getParam('anno_protocollo')==0){$this->errors->addError("Il campo '<b>Anno protocollo</b>' non pu&ograve; essere vuoto");$error=true;}
  	if($error==true){
  	 $this->emitSaveData();
  	 return;
  	}
  	/** effettua solo un update poichè la pubblicazione è stata creata in precedenza**/
  	$record=$this->db->fetchRow("SELECT *,DATE_FORMAT(data_inserimento,'%d/%m/%Y')as d_ins,DATE_FORMAT(pubblicato_dal,'%d/%m/%Y')as p_dal,DATE_FORMAT(pubblicato_al,'%d/%m/%Y')as p_al FROM pubblicazioni WHERE ID_pubblicazione=?",$req->getParam('id'));
  	$n_prot=$this->db->fetchOne("SELECT N_ultimo_protocollo FROM organizations WHERE orgid=?",A::orgid());
  	if((int)$req->getParam('n_protocollo')>(int)$n_prot['N_ultimo_protocollo']){
  	  $this->db->update('organizations',array("N_ultimo_protocollo"=>$req->getParam('n_protocollo')),"orgid=".A::orgid());
  	}
    $data=array(
      "ID_categoria"=>((int)$req->getParam('ID_categoria')>0)?(int)$req->getParam('ID_categoria'):null,
      "ID_mittente"=>((int)$req->getParam('ID_mittente')>0)?(int)$req->getParam('ID_mittente'):null,
      "autore"=>$req->getParam('autore'),
      "n_protocollo"=>(int)$req->getParam('n_protocollo'),
      "anno_protocollo"=>(int)$req->getParam('anno_protocollo'),
      "oggetto"=>$req->getParam('oggetto'),
      "note"=>$req->getParam('note')
    );
    /** CONTROLLI SULLE DATE**/
    $data_inserimento=($req->getParam('data_inserimento')!='')?self::prepareTimestampData($req->getParam('data_inserimento'),array("/","."),array("-","-"),"00:00:01"):false;
    $pubblicato_dal=($req->getParam('pubblicato_dal')!='')?self::prepareTimestampData($req->getParam('pubblicato_dal'),array("/","."),array("-","-"),"00:00:01"):false;
    $pubblicato_al=($req->getParam('pubblicato_al')!='')?self::prepareTimestampData($req->getParam('pubblicato_al'),array("/","."),array("-","-"),"00:00:01"):false;
    if($data_inserimento!=false && $req->getParam('data_inserimento')!=$record['d_ins'])$data=apply($data,array("data_inserimento"=>$data_inserimento));
    if($pubblicato_dal!=false && $req->getParam('pubblicato_dal')!=$record['p_dal'])$data=apply($data,array("pubblicato_dal"=>$pubblicato_dal));
    if($pubblicato_al!=false && $req->getParam('pubblicato_al')!=$record['p_al'])$data=apply($data,array("pubblicato_al"=>$pubblicato_al));
    $this->db->update('pubblicazioni',$data,"ID_pubblicazione=".$req->getParam('id'));
    $this->emitSaveData();
  }

  public function deleteAction(){
    $req=$this->getRequest();
    /** ci sono files collegati?**/
    $files=$this->db->fetchAll("SELECT * FROM files_pubblicazioni WHERE ID_pubblicazione=?",$req->getParam('id'));
    $this->db->delete('pubblicazioni',"ID_pubblicazione=".$req->getParam('id'));
    if(count($files)>0 && $files!=false){
     $this->db->delete("files_pubblicazioni","ID_pubblicazione=".$req->getParam('id'));
     foreach($files as $f){
      $this->db->delete("files","ID_file=".$f['ID_file']);
     }
    }
    $this->emitSaveData();
  }
  
  public function listfilesAction(){
   $r=$this->getRequest();
   //$select=$this->db->select()->from("files",array("ID_file","ID_pubblicazione","order_id","mime_type","description","file_name","size"))->where("ID_pubblicazione=?",$r->getParam('id'));
   $select=$this->db->select()->from(array("l"=>"files_pubblicazioni"))
     ->joinLeft(array("f"=>"files"),"l.ID_file=f.ID_file")
     ->where("l.ID_pubblicazione=?",$r->getParam('id'))
     ->order("l.order_id");
   $files=$this->db->fetchAll($select);
   $this->emitTableResult($files);
  }
  
  public function createAction(){
  	$n_prot=$this->db->fetchAll("SELECT N_ultimo_protocollo FROM organizations WHERE orgid=?",A::orgid());
  	/** seleziona l'ultima pubblicazione per controllare se deve azzerare la numerazione**/
  	$lastPub=$this->db->fetchRow("SELECT * FROM pubblicazioni WHERE orgid=? AND anno_protocollo=? ORDER BY n_protocollo DESC LIMIT 1,0",array(A::orgid(),date('Y',time())));
  	if(count($lastPub)>0 && isset($lastPub['n_protocollo'])){
  		/** siamo nello stesso anno della ultima pubblicazione e la numerazione deve continuare **/
  	  $numero_protocollo=(int)$n_prot[0]['N_ultimo_protocollo'];
  	}else{
  	 /** non esistono pubblicazioni in questo anno, è ora di azzerare il numero di protocollo **/
  	 $this->db->update("organizations",array("N_ultimo_protocollo"=>0),"orgid=".A::orgid());
  	 $numero_protocollo=0;
  	}
    $data=array(
      "orgid"=>A::orgid(),
      "ID_user"=>$this->aaac->getCurrentUser()->ID_user,
      "autore"=>$this->aaac->getCurrentUser()->nome." ".$this->aaac->getCurrentUser()->cognome,
      "n_protocollo"=>(int)$numero_protocollo+1,
      "anno_protocollo"=>date("Y",time())
    );
    $this->db->insert("pubblicazioni",$data);
    $this->emitSaveData(array("success"=>true,"newid"=>$this->db->lastInsertId()));
  }
  
  public function undocreateAction(){
   $this->db->delete("pubblicazioni","ID_pubblicazione=".$this->getRequest()->getParam('id'));
   $this->emitSaveData();
  }
  
  public function addfileAction(){
    $fm=new Zend_FileManager($this->getRequest(), $this->getResponse(),$this->db,array("FILE_DATA_TABLE"=>"files","THUMBNAILS_TABLE"=>false));
    $lastId=$fm->processUpload();
    /*$data=array(
     "mime_type"=>$file['mime_type'],
     "file_name"=>$file['file_name'],
     "description"=>utf8_encode($this->getRequest()->getParam('description')),
     "file_size"=>$file['file_size'],
     "file_blob"=>$file['file_blob']
    );
    $this->db->insert("files",$data);
    $lastId=$this->db->lastInsertId();*/
    $last_order_id=$this->db->fetchOne("SELECT order_id FROM files_pubblicazioni WHERE ID_pubblicazione=? ORDER BY order_id DESC",$this->getRequest()->getParam('ID_pubblicazione'));
    $data_relation=array(
     "ID_pubblicazione"=>$this->getRequest()->getParam('ID_pubblicazione'),
     "ID_file"=>(int)$lastId,
     "order_id"=>((int)$last_order_id+1)
    );
    $this->db->insert("files_pubblicazioni",$data_relation);
    $this->emitSaveData();
  }
  
  public function deletefileAction(){
  	/** la pubblicazione è appartenente a questo comune-organizzazione??**/
   $control=$this->db->fetchOne("SELECT ID_pubblicazione FROM pubblicazioni WHERE orgid=? AND ID_pubblicazione=?",array(A::orgid(),$this->getRequest()->getParam('idPubblicazione')));
   if((int)$control>0){
     $this->db->delete("files","ID_file=".$this->getRequest()->getParam('idFile'));
     $this->db->delete("files_pubblicazioni","ID_file=".$this->getRequest()->getParam('idFile')." AND ID_pubblicazione=".$this->getRequest()->getParam('idPubblicazione'));
   } else {
     $this->errors->addError("ERRORE:: il file fa riferimento ad una pubblicazione inesistente");
   }
   $this->emitSaveData();
  }
  
  public function fileeditAction(){
    $req=$this->getRequest();
    $data1=array();
    $data2=array();
    if((int)$req->getParam('order_id')>0)$data2=apply($data2,array('order_id'=>(int)$req->getParam('order_id')));
    if($req->getParam('description')!='')$data1=apply($data1,array('description'=>$req->getParam('description')));
    if(count($data1)>0){
     $this->db->update("files",$data1,"ID_file=".(int)$req->getParam('id'));
    }
    if(count($data2)>0){
     $this->db->update("files_pubblicazioni",$data2,"ID_file=".(int)$req->getParam('id'));
    }
    $this->emitSaveData();
  }
  
  public function downloadfileAction(){
    $req=$this->getRequest();
    /** è un file appartenente alla organizzazione di chi lo sta scaricando?**/
    $try=$this->db->fetchOne("SELECT ID_pubblicazione FROM pubblicazioni WHERE ID_pubblicazione=? AND orgid=?",array((int)$req->getParam("ID_pubblicazione"),A::orgid()));
    if($try!=false){
      $fm=new Zend_FileManager($this->getRequest(), $this->getResponse(),$this->db,array("FILE_DATA_TABLE"=>"files","THUMBNAILS_TABLE"=>false));
      $fm->processDownload();
    }else{
      $this->errors->addError("Attenzione:: il file non appartiene a questa pubblicazione");
      $this->emitSaveData();
      return;
    }
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