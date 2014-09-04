<?php
    

  require(dirname(__FILE__)."/../../core/xmlrpc_bootstrap.php");
  require(dirname(__FILE__).'/../../core/bootstrap_db_aaac.php');
  require(dirname(__FILE__)."/../../classes/GamblerManager.php");
  /*
  
 $aaac=Zend_Registry::get("aaac");
 if (!$aaac->isLogged()) {
   die("Permesso negato");  
 }
 
 
  define("msec",1000);
    print "<pre>";

  $gm=new GamblerManager();
  ini_set('output_buffering', 0);
  ini_set('implicit_flush', 1);
  ob_start();
  
  $giornate=$db->fetchAll("SELECT * FROM giornate WHERE ID_concorso=? AND data<='2009-05-30'",1);
  $ncodes_per_day=102;
  
  foreach($giornate as $giornata){

    echo "GIORNATA ".$giornata["data"]." ****************************************************\n";
    $codes=$gm->assignCodes(1,$ncodes_per_day);
    
    $step=floor(24*60*60 / $ncodes_per_day *0.9);
    $time=10; // 10 sec dopo la mezzanotte
    foreach($codes as $code){
      
    $options=array(
        "BODY"       =>$code["code"],
        "DATE"       =>dateConv($giornata["data"]),
        "TIME"       =>secToTime($time+=$step),
        "OADC"       =>"+393284347808",
        "ADC"        =>"+393202043268",
        "TEST"       =>"1"
      );   
      
     // print "CODICE {$code["code"]}  {$options["DATE"]} {$options["TIME"]}\n";
      $response=make_sync_post($app_config["BASE_URL"]."/_smsgateway.php",$options);
      //print $response;
      usleep(15*msec);
      
      ob_flush(); flush();
        
    }
  
  }
  
  ob_flush(); flush();
  
  function secToTime($secs){
     return gmdate("H:i:m",$secs);
  }
  
function dateConv($date_str){
  $date_str_parts = split('[:/.\ \-]', $date_str );
  return gmdate("d/m/Y",gmmktime(0,0,0,$date_str_parts[1],$date_str_parts[2],$date_str_parts[0]));
}*/