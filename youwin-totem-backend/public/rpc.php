<?
  require("../core/bootstrap.php");
  require("../classes/GamblerManager.php");
  require 'Zend/XmlRpc/Server.php';
  
  $server = new Zend_XmlRpc_Server();
  Zend_XmlRpc_Server_Fault::attachFaultException('Exception');
  $server->setClass('GamblerManager','youwin');
  echo $server->handle();
  