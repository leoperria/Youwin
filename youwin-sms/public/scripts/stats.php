<?
  require("../../core/xmlrpc_bootstrap.php");
  require('../../core/bootstrap_db_aaac.php');
  
  $ID_concorso=2;
  $db=Zend_Registry::get("db");
  
  $giornate=$db->fetchAll("SELECT * FROM giornate WHERE ID_concorso=$ID_concorso ");
  
  print("<pre>");

  
  
  $giornata=$giornate[0];
  $ora_start=(int)substr($giornata["ora_start"],0,2);
  $ora_stop=(int)substr($giornata["ora_stop"],0,2);
  $riga[0]=array();
  $riga[0][]="data";
  for($ora=$ora_start; $ora<$ora_stop; $ora++){
    $riga[0][]=$ora;
  }
  

  
  
  $i=1;
  foreach($giornate as $giornata){
    
    $riga[$i][]=$giornata["data"];
    
    $ora_start=(int)substr($giornata["ora_start"],0,2);
    $ora_stop=(int)substr($giornata["ora_stop"],0,2);
    
    for($ora=$ora_start; $ora<$ora_stop; $ora++){
      $cnt=(int)$db->fetchOne(
        "SELECT count(ID) as cnt  FROM giocate WHERE 
         date(timestamp)=? AND 
         err=0 AND time(timestamp)>=? AND 
         time(timestamp)<=?",array($giornata["data"],formatTime($ora,0,0),formatTime($ora,59,59)) 
      );
      $riga[$i][]=$cnt;
    }
    $i++;  
  }
  
  for($i=0; $i<count($riga); $i++){
    print join(";",$riga[$i])."\n";
  }
  
  
  
  
  function formatTime($h,$m,$s){
      
    $HH=$h."";
    if ($h<10){
      $HH="0".$HH;
    }
    
    $MM=$m."";
    if ($m<10){
      $MM="0".$MM;
    }
    
    $SS=$s."";
    if ($s<10){
      $SS="0".$SS;
    }
    
    return $HH.":".$MM.":".$SS;
    
  }