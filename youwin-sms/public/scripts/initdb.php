<?
  require("../../core/xmlrpc_bootstrap.php");
  require('../../core/bootstrap_db_aaac.php');
  
 
  $aaac=Zend_Registry::get("aaac");
  if (!$aaac->isLogged()) {
    die("Permesso negato");  
  }
  print "<pre>";
  
  
  
  
  $concorso = new Concorso();
  $concorso->orgid=1;
  $concorso->ID=2;
  $concorso->tipo=Concorso::TYPE_SMS;
  $concorso->nome="Kyanops";
  $concorso->ID_societa_promotrice=3;
  $concorso->phone_number="340 1476884";
  $concorso->sms_sender_text="Kyanops";
  $concorso->msg_winner="HAI VINTO %articolo% %premio%! Per ritirare il premio porta lo scontrino di gioco da Kyanops insieme al tuo telefonino con questo SMS";
  $concorso->msg_looser="Riprova, sarai più fortunato!";
  $concorso->data_inizio="2009-09-21";
  $concorso->data_fine="2009-10-30";
  $concorso->probabilita_vincita = 0.5;
  $concorso->save();
 
  
  $p=array(); 
  $p[0] = array("codice"=>"A", "denominazione"=>"TV LCD 20\"",                           "valore"=>100,   "qnt_totale"=>4);
  $p[1] = array("codice"=>"B", "denominazione"=>"paio di occhiali completi di lenti",    "valore"=>30,    "qnt_totale"=>50);
  for($np=0;$np<count($p);$np++){
    $newPremio=new Premio();
    $newPremio->merge($p[$np]);
    $concorso->Premi[$np]=$newPremio;
  }
  
  //************ Le giornate
  $interv=createDateInterval($concorso->data_inizio,$concorso->data_fine);
  for($ng=0; $ng<count($interv);$ng++){
    $newGiornata=new Giornata();
    $newGiornata->data=$interv[$ng];
    $newGiornata->ora_start="00:00:00";
    $newGiornata->ora_stop="23:59:59";
    $concorso->Giornate[$ng]=$newGiornata;
  }
  
   
  //************ Distribuzione premi/giornate  
  $coeff=array();
  for($ng=0;$ng<count($interv);$ng++){
    for($np=0;$np<count($p);$np++){
      $link=new GiornatePremi();
      $link->Premio=$concorso->Premi[$np];
      $link->Giornata=$concorso->Giornate[$ng];
      $link->qnt_massimale=0;
      $link->qnt_vinta=0;
      $link->save();
    }
  }
  
 
  
  // Assegnazione codici
/*  $ids=array(100001,130000);
  
  $db=Zend_Registry::get("db");
  $db->query("UPDATE codici SET ID_concorso=? WHERE ID>=? AND ID<=?",array(2,$ids[0],$ids[1]));*/
  
  
  function createDateInterval($dateStart,$dateStop){
       $ONE_DAY=3600*24;
       $start=strtotime($dateStart);
       $stop=strtotime($dateStop);
       $giorni=array();
       for ($giorno=$start; $giorno<=$stop; $giorno+=$ONE_DAY){
          $giorni[]=date("Y-m-d",$giorno);
       }
       return $giorni;
  }
