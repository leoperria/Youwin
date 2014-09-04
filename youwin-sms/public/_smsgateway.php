<?php
/*
$tm=gmmktime(12, 59, 59, 04, 10, 2009);
//$tm=time();

$sms_data=array(
  "msg"       =>"#555#".$_GET["code"],
  "status"    =>"INCOMING SMS",
  "timestamp" =>gmdate("d-M-Y h:i:s A",$tm), //"25-Mar-2009 12:25:37 PM",
  "phone"     =>"393202043268",
  "sender"    =>"393284347808",
  "encoding"  =>"7bit",
  "udh"       =>"",
  "dcs"       =>""
);*/

/*echo "<pre>";
var_dump($sms_data);*/

try{
  ob_start();
  require(dirname(__FILE__)."/../core/bootstrap.php");
  require(dirname(__FILE__)."/../classes/GamblerManagerSMS.php");

  if (isset($_POST["TEST"])){
    $app_config["SMSGATEWAY"]["NO_REAL_SEND"]=true;
  }
  
  SMSGateway::setConfig($app_config["SMSGATEWAY"]);
  define ("DEBUG",false);
  
  $gm=new GamblerManagerSMS();
  
  /**
   * Condizionamento dei dati provenienti da NETFUN
   * 
   */
  
  if (!isset($mode)){
    $mode="SIM";
  }
  
  if ($mode=="SMSC"){
    
    /* Nel caso di NETFUN SMSC con KEYWORD arriva la seguente struttura:
     *  
     *  array(8) {
     *    ["msg"]=>
     *    string(9) "#555#266266"  // #555# è la keyword da separare
     *    ["status"]=>
     *    string(12) "INCOMING SMS"
     *    ["timestamp"]=>
     *    string(23) "25-Mar-2009 10:25:37 AM"
     *    ["phone"]=>
     *    string(12) "393202043268"
     *    ["sender"]=>
     *    string(12) "393284347808"
     *    ["encoding"]=>
     *    string(4) "7bit"
     *    ["udh"]=>
     *    string(0) ""
     *    ["dcs"]=>
     *    string(0) ""
     *   }
     */
    $sms_data=array(
      "msg"       =>$_POST["Msg"],
      "status"    =>$_POST["Status"],
      "timestamp" =>$_POST["TimeStamp"],
      "phone"     =>$_POST["Phone"],
      "sender"    =>$_POST["Sender"],
      "encoding"  =>$_POST["Encoding"],
      "udh"       =>$_POST["Udh"],
      "dcs"       =>$_POST["Dcs"]
    );
    
    if (startsWith($sms_data["msg"],"*") || startsWith($sms_data["msg"],"#")){
      $msg=str_replace("*","#",$sms_data["msg"]);
      $keyword=substr($sms_data["msg"],0,strrpos($msg,"#")+1);
      $msg=substr($sms_data["msg"],strrpos($msg,"#")+1);
    }else{
      $msg=$sms_data["msg"];
      $keyword="";
    }
    
    $gamble_data=array(
      "msg"=>$msg,
      "keyword"=>$keyword,
      "timestamp"=>parseNetfunDateSMSC($sms_data["timestamp"]),
      "phone"=>$sms_data["phone"],
      "sender"=>$sms_data["sender"]
    );
      
  }else if($mode=="SIM"){

    $sms_data=array(
      "msg"       =>$_POST["BODY"],
      "status"    =>"",
      "timestamp" =>$_POST["DATE"]." ".$_POST["TIME"],
      "sender"    =>$_POST["OADC"],
      "phone"     =>$_POST["ADC"],
      "encoding"  =>"",
      "udh"       =>"",
      "dcs"       =>""
    );
    
    $gamble_data=array(
      "msg"=>$sms_data["msg"],
      "keyword"=>"",
      "timestamp"=>parseNetfunDateSIM($sms_data["timestamp"]),
      "phone"=>substr($sms_data["phone"],1),
      "sender"=>substr($sms_data["sender"],1)
    );
    
  }
  
  if (!isset($_POST["TEST"])){
    //$gamble_data["timestamp"]="2009-05-30 12:00:00";
  }
  $gm->gambleSMS($gamble_data);

 
  
}catch(Exception $ex){
  $logger=Zend_Registry::get("logger");
  $logger->log("_smsgateway.php: exception=".$ex->getMessage(),Zend_Log::CRIT);
}

 $sms_data["log"]=ob_get_clean();
  $db->insert("sms_ricevuti",$sms_data);



function parseNetfunDateSIM($date_str){
  $base_struc     = split('[:/.\ \-]', "d/M/Y h:i:s");
  $date_str_parts = split('[:/.\ \-]', $date_str );
  $de = array();
  $de['ore']=(int)$date_str_parts[3];
  $de['minuti']=(int)$date_str_parts[4];
  $de['secondi']=(int)$date_str_parts[5];;
  $de['mese']=$date_str_parts[1];
  $de['giorno']=(int)$date_str_parts[0];
  $de['anno']=(int)$date_str_parts[2];
  return gmdate("Y-m-d H:i:s",gmmktime(
    $de["ore"],
    $de["minuti"],
    $de["secondi"],
    $de["mese"],
    $de["giorno"],
    $de["anno"]
  ));
}


function parseNetfunDateSMSC($date_str){
  $Mtom=array(
    "Jan"=>1,
    "Feb"=>2,
    "Mar"=>3,
    "Apr"=>4,
    "May"=>5,
    "Jun"=>6,
    "Jul"=>7,
    "Aug"=>8,
    "Sep"=>9,
    "Oct"=>10,
    "Nov"=>11,
    "Dec"=>12
  );
  $base_struc     = split('[:/.\ \-]', "d-M-Y h:i:s A");
  $date_str_parts = split('[:/.\ \-]', $date_str );
  $de = array();
  $de['ore']=(int)$date_str_parts[3];
  if ($de['ore']==12){
    $de['ore']=0;
  }
  if ($date_str_parts[6]=="PM" || $date_str_parts[6]=="pm"){
    $de['ore']+=12;
  }
  $de['minuti']=(int)$date_str_parts[4];
  $de['secondi']=(int)$date_str_parts[5];;
  $de['mese']=$Mtom[$date_str_parts[1]];
  $de['giorno']=(int)$date_str_parts[0];
  $de['anno']=(int)$date_str_parts[2];
  return gmdate("Y-m-d H:i:s",gmmktime(
    $de["ore"],
    $de["minuti"],
    $de["secondi"],
    $de["mese"],
    $de["giorno"],
    $de["anno"]
  ));
}

