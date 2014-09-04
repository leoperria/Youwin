<?php
try{
  ob_start();
  var_dump($_POST);
  require(dirname(__FILE__)."/../core/bootstrap.php");
  require(dirname(__FILE__)."/../classes/GamblerManagerSMS.php");
  SMSGateway::setConfig($app_config["SMSGATEWAY"]);
  $logger=Zend_Registry::get("logger");


  
  //IDT   dataora   smsuser   smsNUMBER   smsMESSAGGIO  smsDATADELIVERY   smsDELIVERY   smsSender   smsRSN
  
/*array(7) {
 ["smsuser"]=>
 string(11) "kinesistemi"
 ["smsNUMBER"]=>
 string(13) "+393284347808"
 ["smsDATA"]=>*
 string(17) "09041009091989803"
 ["smsMESSAGGIO"]=>
 string(50) "The SMS sent to 00393284347808 has been delivered "
 ["smsDATADELIVERY"]=>
 string(14) "20090410090929"
 ["smsDELIVERY"]=>
 string(1) "S"
 ["smsSender"]=>
 string(6) "Tecnet"
}
*/
  
  $db->insert("sms_delivery",array(
    "IDT"=>$_POST["smsDATA"],
    "dataora"=>date("Y-m-d H:i:s",time()),
    "smsuser"=>$_POST["smsuser"],
    "smsNUMBER"=>$_POST["smsNUMBER"],
    "smsMESSAGGIO"=>$_POST["smsMESSAGGIO"],
    "smsDATADELIVERY"=>$_POST["smsDATADELIVERY"],
    "smsDELIVERY"=>$_POST["smsDELIVERY"],
    "smsSender"=>$_POST["smsSender"],
    "smsRSN"=>isset($_POST["smsRSN"]) ? $_POST["smsRSN"] : "" 
  ));
  
  $logger->log(" SMS DELIVERY: IDT=".$_POST["smsDATA"]." number=".$_POST["smsNUMBER"]." delivery=".$_POST["smsDELIVERY"],Zend_Log::DEBUG);
     
  $msg=ob_get_clean();  
}catch(Exception $ex){
  $msg=ob_get_clean();
  $logger->log("_smsdelivery.php: exception=".$ex->getMessage(),Zend_Log::CRIT);
  $logger->log("LOG:\n\n".$msg,Zend_Log::CRIT);
}
echo "+ok";

