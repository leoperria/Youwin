<?
  require("../core/bootstrap.php");
  require 'Zend/XmlRpc/Server.php';
  $client = new Zend_XmlRpc_Client($app_config["BASE_URL"]."/rpc.php");

  try{
    print "<pre>";
    $res=$client->call('youwin.gamble',array(1,"12345670") );
    var_dump($res);
    print "</pre>";
       
  }catch(Exception $ex){
    echo $ex->getMessage();
    $httpclient=$client->getHttpClient();
    echo "<br><br><strong>Response BODY:</strong><pre style=\"color:red\">";
    echo $httpclient->getLastResponse()->getRawBody();
    print "</pre>"; 
  }
