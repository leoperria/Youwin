<?
  require("../../core/xmlrpc_bootstrap.php");
  require('../../core/bootstrap_db_aaac.php');
  
 
  $aaac=Zend_Registry::get("aaac");
  if (!$aaac->isLogged()) {
    die("Permesso negato");  
  }
  print "<pre>";
  
  $db->getConnection()->exec('TRUNCATE TABLE societa_promotrici');
  $db->getConnection()->exec('TRUNCATE TABLE concorsi');
  $db->getConnection()->exec('TRUNCATE TABLE giornate');
  $db->getConnection()->exec('TRUNCATE TABLE premi');
  $db->getConnection()->exec('TRUNCATE TABLE giornate_premi');
  $db->getConnection()->exec('TRUNCATE TABLE giocate');

  
  //***************************************** CONCORSO
  $db->insert("concorsi",array(
      "orgid"=>1,
      "tipo"=>0,
      "nome"=>"BUON COMPLEANNO, CENTO DI QUESTI PREMI",
      "ID_societa_promotrice"=>$db->insert("societa_promotrici",array("denominazione"=>"Consorzio Galleria Commerciale Cortesantamaria")),
      "data_inizio"=>"2009-09-11",
      "data_fine"=>"2009-09-27",
      "probabilita_vincita"=>1.0,
      "screen_idle"=>"p1.swf",
      "screen_wait"=>"p5.swf",
      "screen_loose"=>"p4.swf",
      "screen_win_time"=>4000,
      "screen_loose_time"=>3500,
      "screen_wait_time"=>2700
  ));
  $ID_concorso=$db->lastInsertId();
  
  //***************************************** PREMI
  $IDp=array();
  $db->insert("premi",array(
    "codice"=>"P1", 
    "ID_concorso"=>$ID_concorso, 
    "articolo"=>"una",
    "denominazione"=>"Penna USB",
    "qnt_totale"=>102,
    "valore"=>1,
    "importo"=>6, 
    "screen_name"=>"p2.swf"
  ));
  $IDp[0]=$db->lastInsertId();
  
  $db->insert("premi",array(
    "codice"=>"P2", 
    "ID_concorso"=>$ID_concorso, 
    "articolo"=>"un",
    "denominazione"=>"Buono acquisto 25€",
    "qnt_totale"=>17,
    "valore"=>5,
    "importo"=>25, 
    "screen_name"=>"p3.swf"
  ));
  $IDp[1]=$db->lastInsertId();
  
  $db->insert("premi",array(
    "codice"=>"P3", 
    "ID_concorso"=>$ID_concorso, 
    "articolo"=>"un",
    "denominazione"=>"Buono acquisto 10€",
    "qnt_totale"=>316,
    "valore"=>2,
    "importo"=>10, 
    "screen_name"=>"p3.swf"
  ));
  $IDp[2]=$db->lastInsertId();
  
  
  //***************************************** GIORNATE
  $concorso= $db->fetchRow('SELECT * FROM concorsi WHERE ID=?',$ID_concorso);
  $interv=createDateInterval($concorso["data_inizio"],$concorso["data_fine"]);
  $IDg=array();
  for($ng=0; $ng<count($interv);$ng++){
     $db->insert("giornate",array(
       "ID_concorso"=>$ID_concorso,
       "data"=>$interv[$ng],
       "ora_start"=>"09:00:00",
       "ora_stop"=>"21:00:00"
    ));
    $IDg[]=$db->lastInsertId();
  }
  
   var_dump($IDg);
   var_dump($IDp);
   
  // ***************************************** PREMI/GIORNATE  
  for($ng=0;$ng<count($IDg);$ng++){
    for($np=0;$np<count($IDp);$np++){
      $db->insert("giornate_premi",array(
        "ID_giornata"=>$IDg[$ng],
        "ID_premio"=>$IDp[$np],
        "ID_concorso"=>$ID_concorso,
        "qnt_massimale"=>0,
        "qnt_vinta"=>0
      ));
    }
  }
  
  
  
  
  
  
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
