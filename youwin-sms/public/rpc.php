<?
  require("../core/bootstrap.php");
  require("../classes/GamblerManagerSMS.php");
  require 'Zend/XmlRpc/Server.php';
  
  $server = new Zend_XmlRpc_Server();
  Zend_XmlRpc_Server_Fault::attachFaultException('Exception');
  $server->setClass('GamblerManagerSMS','youwin');
  echo $server->handle();
  