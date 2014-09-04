<?
  require(dirname(__FILE__)."/../../core/xmlrpc_bootstrap.php");
  require(dirname(__FILE__).'/../../core/bootstrap_db_aaac.php');
  require(dirname(__FILE__)."/../../classes/GamblerManager.php");
 
 $aaac=Zend_Registry::get("aaac");
 if (!$aaac->isLogged()) {
   die("Permesso negato");  
 }
 
 $db=Zend_Registry::get("db");
 
 $res=$db->fetchAll("SELECT * FROM codici ORDER BY codice");
 
 echo "<pre>";
 $i=0;
 $prev=0;
 $min=100000;
 $max=0;
 $tot=0;
 
 $classi=array();
$classi100=array(); 
 foreach($res as $row){
     $code=(int)$row["codice"];
   $diff=$code-$prev;
   if ($diff<$min){
     $min=$diff;
   }
   if ($diff>$max){
     $max=$diff;
   }
   
   
   if ($diff<=100){
     if (!isset($classi100[$diff])){
       $classi100[$diff]=0;
     }
     $classi100[$diff]++;
   }
   
   $ndx=floor($diff / 100);
   if (!isset($classi[$ndx])){
     $classi[$ndx]=0;
   }
   $classi[$ndx]++;
   
   
   $tot+=$diff;
   
   $prev=$code;
   
   $i++;

   
   
 }
 ksort($classi);
 ksort($classi100);
 print_r($classi100);
 print_r($classi);
 
 echo "MIN=".$min."\n";
 echo "MAX=".$max."\n";
 echo "AVG=".($tot/$i)."\n";