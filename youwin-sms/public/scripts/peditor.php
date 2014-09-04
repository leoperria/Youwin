<?
  require("../../core/xmlrpc_bootstrap.php");
  require('../../core/bootstrap_db_aaac.php');
 
  
  echo "<style>
  
    body{
      font-family:verdana;
      font-size:10px;
    }
    
    td {
     font-size:11px;
    }
    
    tr:hover {
      background-color:yellow;
    }
    
    input {
     font-size:11px;
    }
    
    input.qnt {
      text-align:center;
      height:16px;
      width:17px;
      border:1px solid LightGray;
    }
  
    table.grid{
     border-collapse: collapse;
    
    }
    td.qnt_cell{
     border:0px solid LightGray;
     border-right:1px solid black;
     padding-left:5px;
    }
    
    td.result{
    width:40px;text-align:right;
    font-style:italic;
    }
  </style>
  
  ";
  
  $aaac=Zend_Registry::get("aaac");
  if (!$aaac->isLogged()) {
    die("Permesso negato");  
  }
  
  
   $ID_concorso=(int)$db->fetchOne("SELECT ID_concorso_attivo FROM globals WHERE ID=1");
  
  $db=Zend_Registry::get("db");
  
  $n_giornate=(int)$db->fetchOne("SELECT count(ID) as cnt FROM giornate WHERE ID_concorso=?",$ID_concorso);
  $n_premi=(int)$db->fetchOne("SELECT count(ID) as cnt FROM premi WHERE ID_concorso=?",$ID_concorso);
  
  
  $res=$db->fetchAll("
    SELECT gp.ID_giornata,gp.ID_premio, p.codice, p.denominazione, g.data,  gp.qnt_massimale, gp.qnt_vinta FROM 
    giornate g 
    LEFT JOIN giornate_premi gp ON gp.ID_giornata=g.ID    
    LEFT JOIN premi p ON gp.ID_premio=p.ID 
    WHERE g.ID_concorso = ? 
    ORDER BY g.data,p.ID",$ID_concorso);
  

  if (isset($_POST["SALVA"])){
    // Salva
    foreach($res as $r){
      $ID_giornata=$r["ID_giornata"];
      $ID_premio=$r["ID_premio"];
      
      // qnt massimale      
      $newval=(int)$_POST["in_{$ID_giornata}_{$ID_premio}"];
      $oldval=(int)$r["qnt_massimale"];
      if ($newval!=$oldval){
        echo $ID_giornata."/".$ID_premio.",MASSIMALE: {$oldval} --> {$newval} <br>";
        $db->query("UPDATE giornate_premi SET qnt_massimale=? WHERE ID_giornata=? AND ID_premio=?",array($newval,$ID_giornata,$ID_premio));
      }
      
      // qnt vinta
      $newval=(int)$_POST["won_in_{$ID_giornata}_{$ID_premio}"];
      $oldval=(int)$r["qnt_vinta"];
      if ($newval!=$oldval){
        echo $ID_giornata."/".$ID_premio.",VINTA: {$oldval} --> {$newval} <br>";
        $db->query("UPDATE giornate_premi SET qnt_vinta=? WHERE ID_giornata=? AND ID_premio=?",array($newval,$ID_giornata,$ID_premio));
      }
       
    }
    
    echo "salvato...";
  }
  
  
  
  $res=$db->fetchAll("
    SELECT gp.ID_giornata,gp.ID_premio, p.codice, p.denominazione, g.data, (p.valore * gp.qnt_massimale) as score,gp.qnt_massimale, gp.qnt_vinta FROM 
    giornate g 
    LEFT JOIN giornate_premi gp ON gp.ID_giornata=g.ID    
    LEFT JOIN premi p ON gp.ID_premio=p.ID 
    WHERE g.ID_concorso = ? 
    ORDER BY g.data,p.ID",$ID_concorso);


  print "N_giornate=".$n_giornate."\n";
  print "N_premi=".$n_premi."\n";
  
  echo "<form action=\"#\" method=\"post\">";
  echo "<input type=\"submit\" value=\"salva\" name=\"SALVA\"/>";
  
  echo "<table class=\"grid\">";
  
  echo "<tr>";
     echo "<td>&nbsp;</td>";
    for ($p=0; $p<$n_premi; $p++){
      echo "<td style=\"text-align:center;\"><strong>".$res[$p]["codice"]."</strong></td>";
    }
     echo "<td class=\"result\">N.premi</td>";
       echo "<td class=\"result\">Score</td>";
  echo "</tr>";
  $cnt=0;
  
  $qnt_tot=array();
  $qnt_vinta_tot=array();
  for ($p=0;$p<$n_premi;$p++){
    $qnt_tot[$p]=0;
    $qnt_vinta_tot[$p]=0;
  }
  for ($g=0; $g<$n_giornate; $g++){
    echo "<tr>";
    
    $data=$res[$cnt]["data"];
    $gsett=(int)gmdate("N",strtotime($data));
    if ($gsett==5  ){
      $style="color:blue";
    }elseif ($gsett==6){
      $style="color:red";
    }else{
      $style="";
    }
    $cnt_premi=0;
    $cnt_premi_vinti=0;
    $tot_score=0;
    echo "<td ><span style=\"$style\">{$res[$cnt]['data']}</span></td>";
    for ($p=0; $p<$n_premi; $p++){
             
        echo "<td class=\"qnt_cell\">";
        $ID_giornata=$res[$cnt]["ID_giornata"];
        $ID_premio=$res[$cnt]["ID_premio"];
        $score=$res[$cnt]["score"];
        $qnt=$res[$cnt]["qnt_massimale"] ? $res[$cnt]["qnt_massimale"] : "";
        $qnt_vinta=$res[$cnt]["qnt_vinta"] ? $res[$cnt]["qnt_vinta"] : "";
        
        $qnt_tot[$p]+=$qnt;
        $qnt_vinta_tot[$p]+=$qnt_vinta;
        $cnt_premi+=$qnt;
        $cnt_premi_vinti+=$qnt_vinta;
        $tot_score+=$score;
        echo "<input class=\"qnt\" style=\"color:green\" type=\"text\" name=\"in_{$ID_giornata}_{$ID_premio}\" value=\"{$qnt}\"></input> ";
        echo "<input class=\"qnt\" style=\"color:red\" type=\"text\" name=\"won_in_{$ID_giornata}_{$ID_premio}\" value=\"{$qnt_vinta}\"></input> ";
        
        if ($qnt_tot[$p]>0){
          echo "<span style=\"color:green\">&nbsp;{$qnt_tot[$p]}</span>";
        }else{
          echo "<span style=\"color:LightGray\">&nbsp;{$qnt_tot[$p]}</span>";
        }
        if ($qnt_vinta_tot[$p]>0){
          echo "<span style=\"color:red\">&nbsp;{$qnt_vinta_tot[$p]}</span>";
        }else{
          echo "<span style=\"color:LightGray\">&nbsp;{$qnt_vinta_tot[$p]}</span>";
        }
        
        $diff=$qnt_tot[$p]-$qnt_vinta_tot[$p];
        if ($diff>0){
          echo "<span style=\"color:blue\">&nbsp;{$diff}</span>";
        }
        echo "</td>";
        $cnt++;
    }
    echo "<td class=\"result\">$cnt_premi</td>";
    echo "<td class=\"result\">$tot_score</td>";
    echo "</tr>";
  }
  
  echo "<tr >";
  echo "<td>TOT</td>";
  for ($p=0; $p<$n_premi; $p++){
    echo "<td style=\"top:5px;border-top:1px solid black;text-align:right;padding-right:2px;\"><span style=\"color:green\">&nbsp;$qnt_tot[$p]</span><span style=\"color:red\">&nbsp;$qnt_vinta_tot[$p]</span></td>";   
  }
  echo "<td class=\"result\"></td>";
  echo "<td class=\"result\"></td>";
  echo "</tr>";
  
  echo "</table>";
  echo "</form>";
  
 