<?php
class SMSGateway {
  
  private static $config;
  
  public static function setConfig($config){
    self::$config=$config;  
  }
  
  /**
   * Invia un SMS al gateway di NETFUN
   *
   * @param string $number Il numero di telefono comprensivo di 39  (senza il +)
   * @param string $msg Il messaggio (max 160 caratteri)
   * @return string il codice di risposta
   * 
   */
  public static function sendSMS($number,$sms_sender_text,$msg,$disableLoop=false){
    
    $logger=Zend_Registry::get("logger");
    
    if (isset(self::$config["NO_REAL_SEND"]) && self::$config["NO_REAL_SEND"]==true) {
      $logger->log("SIMULAZIONE: spedito UN SMS a $number: credito residuo=99999 millieuro      +Ok 74560 [99999999999999999]",Zend_Log::DEBUG);
      return array("99999999999999999",99999);
    }
    
    if (strlen($msg)>160){
      $msg=trunc($msg,160,false);
      $logger->log( "ATTENZIOE: SMS più lungo di 160 caratteri, troncamento.",Zend_Log::ALERT);  
    }
    
    $options=array(
      "smsUSER"=>self::$config["user"],
      "smsPASSWORD"=>self::$config["password"],
      "smsSENDER"=>$sms_sender_text,
      "smsTEXT"=>urlencode($msg),
      "smsGATEWAY"=>"10",
      "smsNUMBER"=>$number.""
    );
  
    $response=make_sync_post(self::$config["url"],$options);
    
    if (DEBUG){
      print "<pre>";
      print "------------------------";
      echo $response;
    }
    
    $rows=explode("\n",$response);
    $idT="";
    $credit=0;
    foreach($rows as $row){
      if (startsWith($row,"+Ok")){
        $credit=(int)substr($row,3,strpos($row,"[")-3);
        $logger->log("Spedito UN SMS a $number: credito residuo=".$credit." millieuro     $row",Zend_Log::DEBUG); 
        $ob_ndx=strpos($row,"[");
        $cb_ndx=strpos($row,"]");
        $idT=substr($row,$ob_ndx+1,$cb_ndx-$ob_ndx-1);
        /*if ($credit < self::$config["refund_thr"] && !$disableLoop){
          SMSGateway::sendSMS(self::$config["admin_phone"], "YOUWIN: il credito è inferiore ai ".self::$config["refund_thr"]." euro!",true);
          $logger->log("ATTENZIONE: il credito è inferiore ai ".self::$config["refund_thr"]." euro!",Zend_Log::INFO); 
        }*/
      }
    }
    return array($idT,$credit);
  }
  
}
?>