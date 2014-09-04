<?php

/*************************************************************
 * 
 * File di configurazione
 * 
 * 
 */
 
set_include_path(
   dirname(__FILE__)."/../../ZendFramework/library".PATH_SEPARATOR.
   dirname(__FILE__)."/../../ZendExt"
);

$app_config=array(
  "PRODUCT_NAME"=>"YOUWIN-TOTEM",
  "PRODUCT_VERSION"=>"V 1.0",
  "PRODUCTION"=>true,
  "SESSION_NAMESPACE"=>"YOUWIN_TOTEM_ADMIN",
  "MAX_CODES_PER_TRANSACTION"=>200
);

$app_config["MYSQL_HOST"]="";
$app_config["MYSQL_USER"]="";
$app_config["MYSQL_PASS"]="";
if (getenv("COMPUTERNAME")=="LEONARDO-PC1"){
  $app_config["MYSQL_DB"]="youwin_totem_test";
}else{
  $app_config["MYSQL_DB"]="youwin_totem";
}
$app_config["DB_PROFILER"]=true;
$app_config["BASE_PATH"]=dirname(__FILE__)."/..";
$app_config["BASE_URL"]="http://localhost/youwin-totem-backend/public";
$app_config["EXTJS_URL"]="http://localhost/ext-3.3.0";
$app_config["LOG_DESTINATIONS"]=array("file");