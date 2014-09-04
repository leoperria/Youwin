<?
 require("../../core/xmlrpc_bootstrap.php");
 require('../../core/bootstrap_db_aaac.php');
 require("../../classes/GamblerManager.php");
 
 $aaac=Zend_Registry::get("aaac");
 if (!$aaac->isLogged()) {
   die("Permesso negato");  
 }
 $gm=new GamblerManager();
 $gm->generateCodes();
 