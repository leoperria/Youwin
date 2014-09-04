<?php
  require(dirname(__FILE__)."/../../core/xmlrpc_bootstrap.php");
  require(dirname(__FILE__).'/../../core/bootstrap_db_aaac.php');
  require(dirname(__FILE__)."/../../classes/GamblerManager.php");
  
  print "<pre>";
  $db=Zend_Registry::get("db");
   
  $i=1;
  $res=$db->fetchAll("SELECT * FROM sms_delivery");
  
  foreach($res as $delivery){
  
    $str=str_replace("[","",$delivery["testo"]);
    $str=str_replace("]","",$str);
    $str=str_replace("string","",$str);
    $split=split("\"",$str);
    
    $result=array("smsRSN"=>"");
    if (trim($split[1])=="smsuser"){ $result["smsuser"]=$split[3]; } else throw new Exception("BAD FORMAT");
    if (trim($split[5])=="smsNUMBER"){ $result["smsNUMBER"]=$split[7]; } else throw new Exception("BAD FORMAT");
    if (trim($split[9])=="smsDATA"){ $result["IDT"]=$split[11]; } else throw new Exception("BAD FORMAT");
    if (trim($split[13])=="smsMESSAGGIO"){ $result["smsMESSAGGIO"]=$split[15]; } else throw new Exception("BAD FORMAT");
    if (trim($split[17])=="smsDATADELIVERY"){ $result["smsDATADELIVERY"]=$split[19]; } else throw new Exception("BAD FORMAT");
    if (trim($split[21])=="smsDELIVERY"){ $result["smsDELIVERY"]=$split[23]; } else throw new Exception("BAD FORMAT");
    if (trim($split[25])=="smsRSN"){ $result["smsRSN"]=$split[27]; }

    $result["dataora"]=$delivery["dataora"];
    $i++;
    
    
    
    $db->insert("delivery",$result);
  }
