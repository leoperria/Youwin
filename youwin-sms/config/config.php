<?php

/*************************************************************
 * 
 * File di configurazione per la modalità SMS
 * 
 * 
 */
$app_config=array(
  "PRODUCT_NAME"=>"YOUWIN-SMS",
  "PRODUCT_VERSION"=>"V 1.0",
  "SESSION_NAMESPACE"=>"YOUWIN_SMS_ADMIN",
  "PRODUCTION"=>true,
  "CODE_LENGTH"=>8,
  "CODE_DIGITONLY"=>true,
  "MAX_CODES_PER_TRANSACTION"=>200,
  "SUPERPASSWORD"=>"",
  "ADMIN_PHONE"=>"",
  "ADMIN_MAIL"=>"",
  "SMSGATEWAY"=>array( // NETFUN
    "user"=>"",
    "password"=>"",
    "url"=>"http://213.140.1.123/PostNetfunID.asp",
    "refund_thr"=>20000
  ) 
);


if (getenv("windir")!= "") {
  set_include_path(
     dirname(__FILE__)."/../../ZendFramework/library".PATH_SEPARATOR.
     dirname(__FILE__)."/../../ZendExt"
  );
  $app_config["PRODUCTION"]=false;
  $app_config["MYSQL_HOST"]="";
  $app_config["MYSQL_USER"]="";
  $app_config["MYSQL_PASS"]="";
  $app_config["MYSQL_DB"]="";
  $app_config["DB_PROFILER"]=true;
  $app_config["BASE_PATH"]=dirname(__FILE__)."/..";
  $app_config["BASE_URL"]="http://localhost/youwin-sms/public";
  $app_config["EXTJS_URL"]="http://localhost/ext-3.1.1";
  $app_config["LOG_DESTINATIONS"]=array("file"); //array("firebug","file");
 
}else{
  set_include_path(
    "/var/www/ZendFramework1.9/library".PATH_SEPARATOR.
    "/var/www/ZendExt"
  );
  $app_config["PRODUCTION"]=true;
  $app_config["MYSQL_HOST"]="localhost";
  $app_config["MYSQL_USER"]="admin";
  $app_config["MYSQL_PASS"]="gMLS1-qJwf";
  $app_config["MYSQL_DB"]="youwin_sms";
  $app_config["DB_PROFILER"]=true;
  $app_config["BASE_PATH"]=dirname(__FILE__)."/..";
  $app_config["BASE_URL"]="";
  $app_config["EXTJS_URL"]=";
  $app_config["LOG_DESTINATIONS"]=array("file");
}


