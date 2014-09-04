<?php

require_once("SMSGateway.class.php");

class GamblerManagerSMS {

  private $config;

  public function __construct(){
    $this->config=Zend_Registry::get("app_config");
  }

  /**
   * Metodo di test del server
   *
   * @param int $ID_concorso
   * @return string
   */
  public function test($ID_concorso){
    $logger=Zend_Registry::get("logger");
    $logger->log("GamblerManager::test concorso=$ID_concorso ************************",Zend_Log::DEBUG);
    return "OK Concorso $ID_concorso";
  }

  /**
   * Effettua una giocata da SMS
   *
   * Si aspetta una struttura del tipo:
   *
   * $data=array(
   *    "msg"=>"266266"                               // Testo dell'SMS privato dell'eventuale keyword in caso di SMSC condiviso
   *    "timestamp"=>"2009-03-25 10:25:37"            // timestamp del messaggio (formato mysql)
   *    "phone"=>"393202043268"                       // numero di telefono del GW
   *    "sender"=>"393284347808"                      // numero di telefono dl chiamante
   *    "keyword"=>"#555#"                            // eventuale keyword in caso di SMSC condiviso
   * )
   *
   *
   * @param unknown_type $data
   */
  public function gambleSMS($data){

    $db=Zend_Registry::get("db");
    $logger=Zend_Registry::get("logger");

    $logger->log("GamblerManager::gambleSMS *******************************************************",Zend_Log::DEBUG);
    $logger->log("RAW SMS DATA:  phone={$data["sender"]} timestamp={$data["timestamp"]} raw_msg={$data["msg"]}",Zend_Log::DEBUG);

    //DEBUG

    $ID_concorso=0;
    $code="";
    $error=false;
    $vincente=false;
    $credit=-1;
    $ID_premio_vinto=0;
    $premio="";
    $prize_shortage=false;
    $IDT="";

    try {

      // Ricava il codice e lo ripulisce dai caratteri indesiderati
      $code=trim($data["msg"]);

      // Filtra i codici
      if ($this->config["CODE_DIGITONLY"]){
        $code=ereg_replace("[^0-9]","",$code);
      }

      $codice=$db->fetchRow("SELECT * FROM codici WHERE codice=?",$code);
      if ($codice===false){
        throw new Exception("code='$code' non trovato");
      }
      
      if ($codice["invalidato"]){
         throw new Exception("code='$code' già invalidato (giocato).");
      }
      
      // Cerca e carica il concorso
      $ID_concorso=$codice["ID_concorso"];
      $logger->log("Cerco il concorso $ID_concorso",Zend_Log::DEBUG);
      $concorso=$db->fetchRow("SELECT * FROM concorsi WHERE ID=?",$ID_concorso);
      if ($concorso===false){
        throw new Exception("concorso $ID_concorso non trovato.");
      }
     

      // Gestisce lo stato di "assengato"
      if ($concorso["lazy_assign_codes"]){
         // I codici non sono "assegnati" perchè provvengono da un macchina di distribuzione codici offline
         // In questo caso ignora il fatto che non siano assegnati e gli marchia immediatamente come assegnati
         $db->query("UPDATE codici SET assegnato=1, timestamp_assegnato=NOW() WHERE codice=?",$code);
         $logger->log("Codice $code assegnato in questo momento. (lazy_assign_codes==true)" ,Zend_Log::DEBUG);
      }else{
        // In questo caso invece controlla che il codici sia stato marchiato come "assegnato" altrimenti fallisce.
        if (!$codice["assegnato"]){
            throw new Exception("code='$code' non assegnato (e lazy_assign_codes==false)");
        }
      }
 
      // Invalida il codice
      $logger->log("Codice '$code' accettato.",Zend_Log::DEBUG);
      if ($codice["uso_multiplo"]){
        $db->query("UPDATE codici SET numero_utilizzi=numero_utilizzi+1 WHERE codice=?",$code);
        $logger->log("Codice a USO MULTIPLO: numero_utilizzi=".($codice["numero_utilizzi"]+1),Zend_Log::DEBUG);
      }else{
        $db->query("UPDATE codici SET invalidato=1, numero_utilizzi=1, timestamp_invalidato=NOW() WHERE codice=?",$code);
        $logger->log("Codice a USO SINGOLO: invalidato",Zend_Log::DEBUG);
      }
      
       
      // Cerca la giornata basandosi sul timestamp dell'SMS e filtrando gli SMS fuori orari o fuori concorso
      $date=substr($data["timestamp"],0,10);
      $time=substr($data["timestamp"],11,8);
      $giornata=$db->fetchRow("SELECT * FROM giornate WHERE ID_concorso=? AND data=? AND ora_start<=? AND ora_stop>=? ",array($ID_concorso,$date,$time,$time));
      $logger->log("Cerco la giornata $date tra le date del concorso...",Zend_Log::DEBUG);
      if ($giornata===false){
        throw new Exception("Timestamp $date $time dell'SMS non valido o fuori concorso.");
      }

     // Effettua il tentativo, assegna l'eventuale premio e spedisce il messaggio per comunicare l'esito.
      $logger->log("L'utente tenta la sorte (P=".((double)$concorso["probabilita_vincita"]*100)."% di vincere)...",Zend_Log::DEBUG);
      $THECHAOS=$this->prob((double)$concorso["probabilita_vincita"]);
      if ($THECHAOS){
        $logger->log("La sorte è DALLA SUA PARTE! vediamo se ci sono premi...",Zend_Log::DEBUG);

        // Calcola le quantita disponibili per i vari premi relativamente a questa giornata
        $disp_premi=$db->fetchAll("SELECT
            gp.ID_premio,p.codice,
            sum(qnt_massimale) as qnt_massimale, 
            sum(qnt_vinta) as qnt_vinta,
            (sum(qnt_massimale) - sum(qnt_vinta)) as qnt_disponibile   
            FROM giornate_premi gp 
            LEFT JOIN giornate g ON gp.ID_giornata=g.ID 
            LEFT JOIN premi p ON gp.ID_premio=p.ID 
            WHERE gp.ID_concorso=? AND g.data<=?  GROUP BY gp.ID_premio
          ",array($ID_concorso,$giornata["data"] ));

        if (count($disp_premi)==0){
          throw new Exception(" ERRORE: count(disp_premi)==0 !! Impossibile !!");
        }

        // Creo il "cestino" dei premi disponibili per questa giornata
        $prize_basket=array();
        foreach ($disp_premi as $p){
          if ($p["qnt_disponibile"]>0){
            for ($pNdx=0; $pNdx<$p["qnt_disponibile"];$pNdx++){
              $prize_basket[]=array ("ID_premio"=>$p["ID_premio"],"codice"=>$p["codice"]);
            }
          }
        }

        //TODO: Migliorare il dump del $prize_basket -> non var_dump ma un conteggio
        $logger->log( dump($prize_basket),Zend_Log::DEBUG);

        if (count($prize_basket)==0){

          // FINITI I PREMI! mi spiace...
          $prize_shortage=true;
          $logger->log( "Mi spiace PER OGGI FINITI I PREMI",Zend_Log::DEBUG);

        }else{

          // Estrazione premio tra i disponibili questa giornata...
          $prize_ndx=mt_rand(0,count($prize_basket)-1);
          $prize_data=$prize_basket[$prize_ndx];
          $prize=$db->fetchRow("SELECT * FROM premi WHERE ID=?",$prize_data["ID_premio"]);

          // TODO: Controllo di sicurezza disponibilità premi
          // SELECT count(*) as cnt from giocate where ID_premio_vinto=29 and vincente=1 and shortage=0

          $vincente=true;
          $ID_premio_vinto=$prize["ID"];
          $premio="({$prize["codice"]}) {$prize["denominazione"]}";
          $logger->log( "  VINTO PREMIO !!!: (ndx=$prize_ndx) ID={$prize["ID"]} codice={$prize["codice"]}  descrizione={$prize["denominazione"]}",Zend_Log::DEBUG);

          // Aggiorno la quantità vinta
          $db->query("UPDATE giornate_premi SET qnt_vinta=qnt_vinta+1 WHERE ID_giornata=? AND ID_premio=? ",array($giornata["ID"],$prize["ID"]));

          // Invio l'SMS
          $sms=formatStr($concorso["msg_winner"],array(
               "articolo"=>trim($prize["articolo"]),
               "premio"=>trim($prize["denominazione"])
          ));
          $logger->log("Invio l'SMS di conferma della vincita:\n$sms",Zend_Log::DEBUG);
          list($IDT,$credit)=SMSGateway::sendSMS($data["sender"],$concorso["sms_sender_text"],$sms);
        }
      }

      if (!$vincente){
        $logger->log("PERSO: Invio l'SMS che esorta a ritentare...",Zend_Log::DEBUG);
        list($IDT,$credit)=SMSGateway::sendSMS($data["sender"],$concorso["sms_sender_text"],$concorso["msg_looser"]);
      }


    }catch(Exception $ex){
      $logger->log("Codice rifiutato: exception=".$ex->getMessage(),Zend_Log::NOTICE);
      $error=true;
    }

    $logger->log("Inserisco la giocata nell'archivio",Zend_Log::DEBUG);

    if($credit>=0){
      $db->query("UPDATE globals SET sms_credit=?",$credit);
    }

    $db->insert("giocate",array(
        "ID_concorso"=>$ID_concorso,
        "err"=>$error,
        "timestamp"=>date("Y-m-d H:i:s",time()),
        "sms_timestamp"=>$data["timestamp"],
        "numero_telefono"=>$data["sender"],
        "codice"=>$code,
        "vincente"=>$vincente,
        "ID_premio_vinto"=>$ID_premio_vinto,
        "premio"=>$premio,
        "shortage"=>$prize_shortage,
        "IDT"=>$IDT
    ));


  }


  /**
   * Restituisce le informazioni sul concorso e sul server
   *
   * @param int $ID_concorso
   * @return struct Informazioni sul concorso
   */
  public function infoConcorso($ID_concorso){
    $logger=Zend_Registry::get("logger");
    $db=Zend_Registry::get("db");
    $ts=date("Y-m-d H:i:s",time());
    $logger->log("GamblerManager::infoConcorso  time_stamp=$ts *****************",Zend_Log::DEBUG);
    $concorso=$db->fetchRow("SELECT * FROM concorsi WHERE ID=?",$ID_concorso);
    $concorso["server_timestamp"]=$ts;
    return $concorso;
  }

  /**
   * Inserisce un pagamento, calcola il numero di codici
   * da assegnare e restituisce tale numero
   *
   * TODO: da refattorizzare /spostare in un'altra classe.
   *
   * @param int $ID_concorso
   * @param double $amount
   * @return int Un numero di codici da assegnare
   *
   */
  public function insertPayment($ID_concorso, $amount){
    $logger=Zend_Registry::get("logger");
    $db=Zend_Registry::get("db");
    $logger->log("GamblerManager::insertPayment  ID_concorso=$ID_concorso amount=$amount *****************",Zend_Log::DEBUG);
    $original_amount=$amount;
    $amount=(double)$amount;
    if ($amount<0 ){
      $amount=0.0;
    }
    if ($amount<=0.0){
      return 0;
    }
    try{
      $ID_concorso=(int)$ID_concorso;
      $ncodici=1;
      $ncodici += floor($amount / 25.00);
      $db->insert("pagamenti",array(
            "ID_concorso"=>$ID_concorso,
            "dataora"=>date("Y-m-d H:i:s",time()),
            "importo"=>$original_amount,
            "ncodici"=>$ncodici
      ));
      return (int)$ncodici;
    }catch(Exception $ex){
      $logger->log("Exception: ".$ex->getMessage()."\n".$ex->getTraceAsString(), Zend_log::CRIT);
      throw $ex;
    }
  }
  
  

  /**
   * Restituisce $n codici
   *
   *
   * @param int $ID_concorso
   * @param int $sub_unit_code
   * @param int $n
   * @return struct Un array di codici assegnati
   */
  public function getCodes($ID_concorso, $sub_unit_code,$n){
    $logger=Zend_Registry::get("logger");
    $db=Zend_Registry::get("db");
     
    try{
      $n=(int)$n;
      if ($n<=0 || $n>$this->config["MAX_CODES_PER_TRANSACTION"]){
        throw new Exception("Numero di codici richiesti $n non valido.");
      }
      /*  $db->query("SET AUTOCOMMIT=0;");
       $db->query("LOCK TABLES codici WRITE, concorsi READ");*/
      $db->beginTransaction();
      $logger->log("GamblerManager::assignCodes N=$n *******************************************************",Zend_Log::DEBUG);
      try{
        $concorso=$db->fetchRow("SELECT * FROM concorsi WHERE ID=?",$ID_concorso);
        if ($concorso===false){
          throw new Exception("Concorso ID=$ID_concorso non trovato.");
        }
        $sql="SELECT * FROM codici WHERE 
             assegnato =0 AND 
             invalidato =0 AND 
             uso_multiplo =0 AND 
             ID_concorso = ? AND
             ID_punto_vendita= ?
             ORDER BY ID LIMIT 0,$n";
        $res=$db->fetchAll($sql, array($ID_concorso,$sub_unit_code));
         
        if (count($res)==0){
          //list($IDT,$credit)=SMSGateway::sendSMS($this->config["ADMIN_PHONE"],"YOUWIN","ERRORE FATALE: sono terminati i codici!");
          throw new Exception("ERRORE FATALE! Sono terminati i codici!");
        }
        $codes=array();
        $ids=array();
        foreach($res as $r){
          $codes[]=array("code"=>$r["codice"]);
          $ids[]=$r["ID"];
        }
        $db->query("UPDATE codici SET assegnato=assegnato+1, timestamp_assegnato=NOW() WHERE ID in (".join(',',$ids).")");
        foreach($codes as $c){
          $logger->log("Assegnato CODICE ".$c["code"],Zend_Log::DEBUG);
        }
        $db->commit();
        /*  $db->query("COMMIT");
         $db->query("UNLOCK TABLES");*/
        return $codes;
      }catch(Exception $ex){
        /*  $db->query("ROLLBACK");
         $db->query("UNLOCK TABLES");*/
        $db->rollback();
        throw $ex;
      }
       
    }catch(Exception $ex){
      $logger->log("Exception: ".$ex->getMessage()."\n".$ex->getTraceAsString(), Zend_log::CRIT);
      throw $ex;
    }

  }


  /**
   * Genera l'insieme di codici da giocare !
   *
   * ATTENZIONE: annulla tutti i codici precedenti.. da eseguire con estrema cautela!
   *
   */
  public function generateCodes(){
     
    ini_set('output_buffering', 0);
    ini_set('implicit_flush', 1);
    //ob_end_flush();
    ob_start();

    echo "<pre>";

    $db=Zend_Registry::get("db");

    for ($i=0;$i<100000;){

      $new_code=$this->generateCode($this->config["CODE_LENGTH"]);

      $found = $db->fetchOne('SELECT ID FROM codici WHERE codice = ? ',$new_code);

      if ($found!==false){
        echo "$new_code già presente.\n";
        ob_flush(); flush();
        continue;
      }

      $db->insert("codici",array(
          "ID_concorso"=>null,
          "codice"=>$new_code,
          "uso_multiplo"=>false,
          "invalidato"=>false,
          "numero_utilizzi"=>0
      ));

      if ($i % 1000 == 0 ){
        echo $i ." codici generati.\n";
        ob_flush(); flush();
      }

      $i++;

    }
    echo $i ." codici generati in TOTALE.\n";
    ob_flush(); flush();

  }
   
  /**
   * Genera un valore boolean tale
   * che la probabilità di uscita del valore
   * true è $p
   *
   * @param float $p
   * @return boolean
   */
  public static function prob($p) {
    return mt_rand()<=$p*mt_getrandmax();
  }


  /**
   * Genera un codice casuale
   *
   * @param unknown_type $length Il numero di caratteri del codice
   * @return unknown
   */
  function generateCode($length)
  {
    $code= "";
    $possible = "0123456789";
    $i = 0;
    while ($i < $length) {
      $char = substr($possible, mt_rand(0, strlen($possible)-1), 1);
      $code.= $char;
      $i++;
    }
    return $code;
  }


}
