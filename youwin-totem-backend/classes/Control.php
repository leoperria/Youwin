<?php
class Control {

  public function test() {
    $logger=Zend_Registry::get("logger");
    $logger->log("Control::test ************************",Zend_Log::DEBUG);
    return "OK";
  }


  public function simulation(){
    $db = Zend_Registry::get("db");
    $app_config=Zend_Registry::get("app_config");

    $PP=array(0.002,0.0025,0.003,0.0035,0.004,0.0045,0.005);

    foreach($PP as $prob){

      print "\n\n\nP=".($prob*100)."% #################################################################################################\n";
      Control::setProb(1, $prob);


      for ($N_test=0;$N_test<6;$N_test++){

        print "\n".($N_test+1).":-----------------------------------------------------------------------------------\n";
        $dateStart="2010-03-13";
        $dateStop="2010-03-31";

        // Pulizia
        $db->update("giornate_premi",array("qnt_vinta"=>0));
        $db->delete("giocate");

        // Effettua la simulazione
        $giocate=$db->fetchAll("SELECT * FROM giocate_in  WHERE date(timestamp)>=? AND date(timestamp)<=?  ORDER BY ID ",array($dateStart,$dateStop));
        $N=count($giocate);
        $i=0;
        $m=$N/100;
        foreach($giocate as $g){
          Control::gambleSim(1,$g["timestamp"],$g["codice"]);
        /*  if ($i % $m ==0 ){
            printf("%2.1f%% --> {$g["timestamp"]}\n",($i/$N)*100);
          }*/
          $i++;
        }

        // Calcola le metriche
        $giornate=$db->fetchAll("SELECT * FROM giornate WHERE data>=? AND data<=?  ORDER BY ID ",array($dateStart,$dateStop));
        $N=count($giornate);

        $N_giocate_acc=0;
        $N_premi_previsti_acc=0;
        $N_premi_vinti_acc=0;
        $accumulo=0;
        $gg_vincita_tardiva=0;
        foreach($giornate as $d){

            $N_giocate=$db->fetchOne("SELECT count(*) as cnt FROM giocate WHERE date(timestamp)=?",$d["data"]);
            $N_giocate_acc+=$N_giocate;

            $N_premi_previsti=$db->fetchOne("SELECT SUM(qnt_massimale)as cnt FROM giornate_premi WHERE ID_giornata=?",$d["ID"]);
            $N_premi_previsti_acc+=$N_premi_previsti;

            $N_premi_vinti=$db->fetchOne("SELECT SUM(qnt_vinta)as cnt FROM giornate_premi WHERE ID_giornata=?",$d["ID"]);
            $N_premi_vinti_acc+=$N_premi_vinti;

            $acc_prec=$accumulo;
            $accumulo+=$N_premi_previsti-$N_premi_vinti;


            $ora_ultima_vincita=$db->fetchOne("SELECT time(timestamp) FROM giocate WHERE vincente=1 AND date(timestamp)=? ORDER BY timestamp DESC LIMIT 1",$d["data"]);

            $gg=$db->fetchAll("SELECT time(timestamp) FROM giocate WHERE vincente=1 AND date(timestamp)=? AND time(timestamp)>='19:00:00' ORDER BY timestamp DESC LIMIT 1",$d["data"]);
            if (count($gg)>0){
              $gg_vincita_tardiva++;
            }



            printf("{$d["data"]}  $N_giocate   $N_premi_previsti    $N_premi_vinti/".($N_premi_previsti+$acc_prec)."  $accumulo    $ora_ultima_vincita\n");
        }



        echo "\n";
        printf("Accumulo finale=%d\n",$accumulo);
        printf("Media N_giocate = %2.1f \n",$N_giocate_acc/$N);
        printf("Percentuale giorni con vincita dopo 19:00:00 = %2.1f%% \n",($gg_vincita_tardiva/$N)*100);
        //printf("Media N_premi_previsti= %2.1f \n",$N_premi_previsti_acc/$N);
        //printf("Media N_premi_vinti= %2.1f \n",$N_premi_vinti_acc/$N);

      }
    }

    
  }


/**
   * Effettua una singola giocata da TOTEM
   *
   * @param int $ID_concorso
   * @param string $code
   * @return struct
   */
  public function gambleSim($ID_concorso, $timestamp, $code) {
    $logger = Zend_Registry::get("logger");
    $db = Zend_Registry::get("db");
    $logger->log("Control::gambleSim  ID_concorso=$ID_concorso code=$code*****************", Zend_Log::DEBUG);

    $vincente = false;
    $error = false;
    $ID_premio_vinto = 0;
    $premio = "";
    $prize_shortage = false;
    $res = array();
    $testDay = false;

    $ttt=explode(" ", $timestamp);
    $date=$ttt[0];
    $time=$ttt[1];

    try {

      // Filtra il codice (solo digit)
      $code = trim($code);
      $code = preg_replace("/[^0-9]/", "", $code);

      // Cerca il codice
      $codice = $db->fetchRow("SELECT * FROM codici_totem WHERE ID_concorso=? AND codice LIKE ? AND invalidato=0", array($ID_concorso, substr($code, 0, 4) . "%"));
      if ($codice === false) {
        throw new Exception("code='$code' non trovato, invalidato o non associato al concorso ID=" . $ID_concorso);
      }
      $code = $codice["codice"];

      // Invalida/aggiorna il codice
      $logger->log("Codice '$code' accettato.", Zend_Log::DEBUG);
  /*    if ($codice["uso_multiplo"]) {
        $db->query("UPDATE codici_totem SET numero_utilizzi=numero_utilizzi+1 WHERE codice=?", $code);
        $logger->log("Codice a USO MULTIPLO: numero_utilizzi=" . ($codice["numero_utilizzi"] + 1), Zend_Log::DEBUG);
      } else {
        $db->query("UPDATE codici_totem SET invalidato=1, numero_utilizzi=1, timestamp_invalidato=NOW() WHERE codice=?", $code);
        $logger->log("Codice a USO SINGOLO: invalidato", Zend_Log::DEBUG);
      }
*/
      
      // Cerca il concorso
      $ID_concorso = $codice["ID_concorso"];
      $logger->log("Cerco il concorso $ID_concorso", Zend_Log::DEBUG);
      $concorso = $db->fetchRow("SELECT * FROM concorsi WHERE ID=?", $ID_concorso);
      if ($concorso === false) {
        throw new Exception("concorso $ID_concorso non trovato.");
      }

      //$now = $db->fetchRow("SELECT current_date() as curr_date, current_time() as curr_time");

      $now["curr_date"]=$date;
      $now["curr_time"]=$time;

      // Cerca il record della giornata attuale basandosi sul timestamp corrente di MYSQL
      $giornata = $db->fetchRow("SELECT * FROM giornate WHERE data=? and ?>=ora_start AND ?<=ora_stop", array($now["curr_date"], $now["curr_time"], $now["curr_time"]));
      $logger->log("Cerco la giornata {$now["curr_date"]} tra le date del concorso...", Zend_Log::DEBUG);
      if ($giornata === false) {
        throw new Exception("giornata {$now["curr_date"]} non trovata o fuori dall'orario di gioco.");
      }
      if ($giornata["test"]) {
        $testDay = true;
      }

      // Effettua il tentativo e assegna l'eventuale premio
      $logger->log("L'utente tenta la sorte (P=" . ((double) $concorso["probabilita_vincita"] * 100) . "% di vincere)...", Zend_Log::DEBUG);
      $THECHAOS = Control::prob((double) $concorso["probabilita_vincita"]);
      if ($THECHAOS) {
        //THE CHAOS said YES!
        $logger->log("La sorte è DALLA SUA PARTE! vediamo se ci sono premi disponibili per oggi...", Zend_Log::DEBUG);

        // Calcola le quantita disponibili per i vari premi relativamente a questa giornata
        $disp_premi = $db->fetchAll("SELECT
             gp.ID_premio,p.codice,p.test,
             sum(qnt_massimale) as qnt_massimale,
             sum(qnt_vinta) as qnt_vinta,
             (sum(qnt_massimale) - sum(qnt_vinta)) as qnt_disponibile
             FROM giornate_premi gp
             LEFT JOIN giornate g ON gp.ID_giornata=g.ID
             LEFT JOIN premi p ON gp.ID_premio=p.ID
             WHERE gp.ID_concorso=? AND g.data<=?  GROUP BY gp.ID_premio",
            array($ID_concorso, $giornata["data"])
        );

        if (count($disp_premi) == 0) {
          throw new Exception(" ERRORE: count(disp_premi)==0 !! Impossibile !!");
        }

        // Creo il "cestino" dei premi disponibili per questa giornata
        // Nei giorni di test include SOLTANTO i premi di test.
        // Nei giorni normali NON mette i premi di test.
        $prize_basket = array();
        foreach ($disp_premi as $p) {
          if (($p["test"] && !$giornata["test"]) || (!$p["test"] && $giornata["test"])) {
            continue;
          }
          if ($p["qnt_disponibile"] > 0) {
            for ($pNdx = 0; $pNdx < $p["qnt_disponibile"]; $pNdx++) {
              $prize_basket[] = array("ID_premio" => $p["ID_premio"], "codice" => $p["codice"]);
            }
          }
        }
        $logger->log(dump($prize_basket), Zend_Log::DEBUG);

        if (count($prize_basket) == 0) {

          // FINITI I PREMI! mi spiace...
          $prize_shortage = true;
          $vincente = true;
          $logger->log("Mi spiace PER OGGI FINITI I PREMI", Zend_Log::DEBUG);
          $res = array(
            "winner" => false,
            "shortage" => true
          );

        } else {

          // Estrazione premio tra i disponibili questa giornata...
          $prize_ndx = mt_rand(0, count($prize_basket) - 1);
          $prize_data = $prize_basket[$prize_ndx];
          $prize = $db->fetchRow("SELECT * FROM premi WHERE ID=?", $prize_data["ID_premio"]);
          $vincente = true;
          $ID_premio_vinto = $prize["ID"];
          $premio = "({$prize["codice"]}) {$prize["denominazione"]}";
          $logger->log("  VINTO PREMIO !!!: (ndx=$prize_ndx) ID={$prize["ID"]} codice={$prize["codice"]}  descrizione={$prize["denominazione"]}", Zend_Log::DEBUG);

          // Aggiorno la quantità vinta
          $db->query("UPDATE giornate_premi SET qnt_vinta=qnt_vinta+1 WHERE ID_giornata=? AND ID_premio=? ", array($giornata["ID"], $prize["ID"]));

          // Preparo la risposta per il client
          $res = array(
            "winner" => true,
            "premio" => $prize,
            "win_timestamp" => $timestamp
          );
        }
      } else {
        // THE CHAOS said NO!
        $res = array(
          "winner" => false
        );
      }
    } catch (Exception $ex) {
      $logger->log("Codice rifiutato o errore relativo al concorso: exception=" . $ex->getMessage(), Zend_Log::NOTICE);
      $error = true;
      $res = array(
        "error" => true,
        "msg" => $ex->getMessage()
      );
    }

    $logger->log("Inserisco la giocata nell'archivio", Zend_Log::DEBUG);
    $db->insert("giocate", array(
      "ID_concorso" => $ID_concorso,
      "err" => (int) $error,
      "timestamp" => $timestamp,
      "codice" => $code,
      "vincente" => (int) $vincente,
      "ID_premio_vinto" => $ID_premio_vinto,
      "premio" => $premio,
      "shortage" => (int) $prize_shortage,
      "test" => $testDay
    ));

    return $res;
  }

  /**
   * Imposta la probabilità di gioco del concorso
   *
   * @param int $ID_concorso
   * @param double $prob
   *
   * @return double
   */
  public function setProb($ID_concorso, $prob) {
    $logger=Zend_Registry::get("logger");
    $logger->log("GamblerManager::concorsoStats ID_concorso=$ID_concorso prob=$prob",Zend_Log::DEBUG);
    $db=Zend_Registry::get("db");
    $db->query("UPDATE concorsi SET probabilita_vincita=? WHERE ID=?",array((double)$prob,(int)$ID_concorso));
    return $db->fetchOne("SELECT probabilita_vincita FROM concorsi WHERE ID=?",$ID_concorso);
  }


  /**
   * Imposta automaticamente la probabilità di vincita in
   * modo da ottimizzare i premi vinti.
   *
   */
  public function autoSetProb(){

    $db=Zend_Registry::get("db");
    $logger=Zend_Registry::get("logger");
    $logger->log("GamblerManager::autoSetProb",Zend_Log::DEBUG);

    $now=$db->fetchRow("SELECT current_date() as curr_date, current_time() as curr_time");
    
    $now["curr_date"]="2010-03-16";
    


    $giornate=$db->fetchAll("SELECT * FROM giornate WHERE ID_concorso=1 AND data<=? ORDER BY data",$now["curr_date"]);

    //TODO: attenzione! possibile bug!: presuppone che tutte siano uguali come orari
    $giornata=$giornate[0];
    $ora_start=(int)substr($giornata["ora_start"],0,2);
    $ora_stop=(int)substr($giornata["ora_stop"],0,2);


    $t=array();

    $i=1;
    foreach($giornate as $giornata){
      $ora_start=(int)substr($giornata["ora_start"],0,2);
      $ora_stop=(int)substr($giornata["ora_stop"],0,2);
      $tot=0;
      for($ora=$ora_start; $ora<$ora_stop; $ora++){
        $cnt=(int)$db->fetchOne(
          "SELECT count(ID) as cnt  FROM giocate WHERE
           date(timestamp)=? AND
           err=0 AND time(timestamp)>=? AND
           time(timestamp)<=?",array($giornata["data"],$this->formatTime($ora,0,0),$this->formatTime($ora,59,59))
        );
        $h[$i][]=$cnt;
        $tot+=$cnt;
      }
      $t[$i]=$tot;
      $i++;
    }
    
    $res=array($h,$t);
    print "<pre>";
    print_r($res);
  }


  /**
   * Esegue una query generica - fetch all
   *
   * @param string $query
   * @param struct $params
   * @return struct
   */
  public function queryFetchAll($query,$params=null){
    $logger=Zend_Registry::get("logger");
    $logger->log("GamblerManager::queryFetchAll()  query=$query ",Zend_Log::DEBUG);
    $db=Zend_Registry::get("db");
    return $db->fetchAll($query,$params);
  }

   /**
   * Esegue una query generica - fetch one
   *
   * @param string $query
   * @param struct $params
   * @return struct
   */
  public function queryFetchOne($query,$params=null){
    $logger=Zend_Registry::get("logger");
    $logger->log("GamblerManager::queryFetchOne()  query=$query ",Zend_Log::DEBUG);
    $db=Zend_Registry::get("db");
    return $db->fetchOne($query,$params);
  }

   /**
   * Esegue una query generica - fetch row
   *
   * @param string $query
   * @param struct $params
   * @return struct
   */
  public function queryFetchRow($query,$params=null){
    $logger=Zend_Registry::get("logger");
    $logger->log("GamblerManager::queryFetchRow()  query=$query ",Zend_Log::DEBUG);
    $db=Zend_Registry::get("db");
    return $db->fetchRow($query,$params);
  }



   /**
   * Esegue una query generica
   *
   * @param string $query
   * @param struct $params
   * @return struct
   */
  public function query($query,$params=null){
    $logger=Zend_Registry::get("logger");
    $logger->log("GamblerManager::query()  query=$query ",Zend_Log::DEBUG);
    $db=Zend_Registry::get("db");
    return $db->query($query,$params);
  }



  /**
   * Restituisce varie statistiche sul concorso
   *
   * @param int $ID_concorso
   * @param boolean $genMessage
   *
   * @return struct
   */
  public function concorsoStats($ID_concorso,$genMessage=true) {

    $logger=Zend_Registry::get("logger");
    $logger->log("GamblerManager::concorsoStats ID_concorso=$ID_concorso genMessage=$genMessage",Zend_Log::DEBUG);

    $db=Zend_Registry::get("db");


    $res=array();

    $res["server_timestamp"]=date("d-m-Y H:i:s",time());

    $concorso=$db->fetchRow("SELECT * FROM concorsi WHERE ID=?",$ID_concorso);
    if ($concorso===false) {
      throw new Exception("concorso $ID_concorso non trovato.");
    }


    // ***** Errate

    $res["g_err_tot"]=(int) $db->fetchOne(
            "SELECT count(ID) as cnt FROM giocate
         WHERE ID_concorso=? AND date(timestamp)<=date(NOW()) AND err=1",
            array($ID_concorso)
    );

    $res["g_err_oggi"]=(int) $db->fetchOne(
            "SELECT count(ID) as cnt FROM giocate
         WHERE ID_concorso=? AND date(timestamp)>=date(NOW()) AND  date(timestamp)<=date(NOW()) AND err=1",
            array($ID_concorso)
    );

  /*  $res["g_err_30min"]=(int) $db->fetchOne(
            "SELECT count(ID) as cnt FROM giocate
         WHERE ID_concorso=? AND timestamp>=(NOW() - INTERVAL 30 MINUTE) AND timestamp<=NOW() AND err=1",
            array($ID_concorso)
    );

    $res["g_err_1h"]=(int) $db->fetchOne(
            "SELECT count(ID) as cnt FROM giocate
         WHERE ID_concorso=? AND timestamp>=(NOW() - INTERVAL 1 HOUR) AND timestamp<=NOW() AND err=1",
            array($ID_concorso)
    );

    $res["g_err_3h"]=(int) $db->fetchOne(
            "SELECT count(ID) as cnt FROM giocate
         WHERE ID_concorso=? AND timestamp>=(NOW() - INTERVAL 3 HOUR) AND timestamp<=NOW() AND err=1",
            array($ID_concorso)
    );*/


    // ***** Valide
    $res["g_valide_tot"]=(int) $db->fetchOne(
            "SELECT count(ID) as cnt FROM giocate
         WHERE ID_concorso=? AND date(timestamp)<=date(NOW()) AND err=0",
            array($ID_concorso)
    );

    $res["g_valide_oggi"]=(int) $db->fetchOne(
            "SELECT count(ID) as cnt FROM giocate
         WHERE ID_concorso=? AND date(timestamp)>=date(NOW()) AND  date(timestamp)<=date(NOW()) AND err=0",
            array($ID_concorso)
    );

  /*  $res["g_valide_30min"]=(int) $db->fetchOne(
            "SELECT count(ID) as cnt FROM giocate
         WHERE ID_concorso=? AND timestamp>=(NOW() - INTERVAL 30 MINUTE) AND timestamp<=NOW() AND err=0",
            array($ID_concorso)
    );

    $res["g_valide_1h"]=(int) $db->fetchOne(
            "SELECT count(ID) as cnt FROM giocate
         WHERE ID_concorso=? AND timestamp>=(NOW() - INTERVAL 1 HOUR) AND timestamp<=NOW() AND err=0",
            array($ID_concorso)
    );

    $res["g_valide_3h"]=(int) $db->fetchOne(
            "SELECT count(ID) as cnt FROM giocate
         WHERE ID_concorso=? AND timestamp>=(NOW() - INTERVAL 3 HOUR) AND timestamp<=NOW() AND err=0",
            array($ID_concorso)
    );*/


    //***** Vincenti

    $res["g_vincenti_tot"]=(int) $db->fetchOne(
            "SELECT count(ID) as cnt FROM giocate
         WHERE ID_concorso=? AND date(timestamp)<=date(NOW()) AND err=0 AND vincente=1 AND shortage=0",
            array($ID_concorso)
    );

    $res["g_vincenti_oggi"]=(int) $db->fetchOne(
            "SELECT count(ID) as cnt FROM giocate
         WHERE ID_concorso=? AND date(timestamp)>=date(NOW()) AND  date(timestamp)<=date(NOW()) AND err=0 AND vincente=1 AND shortage=0",
            array($ID_concorso)
    );

   /* $res["g_vincenti_30min"]=(int) $db->fetchOne(
            "SELECT count(ID) as cnt FROM giocate
         WHERE ID_concorso=? AND timestamp>=(NOW() - INTERVAL 30 MINUTE) AND timestamp<=NOW() AND err=0 AND vincente=1 AND shortage=0",
            array($ID_concorso)
    );

    $res["g_vincenti_1h"]=(int) $db->fetchOne(
            "SELECT count(ID) as cnt FROM giocate
         WHERE ID_concorso=? AND timestamp>=(NOW() - INTERVAL 1 HOUR) AND timestamp<=NOW() AND err=0 AND vincente=1 AND shortage=0",
            array($ID_concorso)
    );

    $res["g_vincenti_3h"]=(int) $db->fetchOne(
            "SELECT count(ID) as cnt FROM giocate
         WHERE ID_concorso=? AND timestamp>=(NOW() - INTERVAL 3 HOUR) AND timestamp<=NOW() AND err=0 AND vincente=1 AND shortage=0",
            array($ID_concorso)
    );*/


    //****** Shortage

    $res["g_shortage_tot"]=(int) $db->fetchOne(
            "SELECT count(ID) as cnt FROM giocate
         WHERE ID_concorso=?  AND date(timestamp)<=date(NOW()) AND err=0 AND vincente=1 AND shortage=1",
            array($ID_concorso)
    );

    $res["g_shortage_oggi"]=(int) $db->fetchOne(
            "SELECT count(ID) as cnt FROM giocate
         WHERE ID_concorso=? AND date(timestamp)>=date(NOW()) AND  date(timestamp)<=date(NOW()) AND err=0 AND vincente=1 AND shortage=1",
            array($ID_concorso)
    );

  /*  $res["g_shortage_30min"]=(int) $db->fetchOne(
            "SELECT count(ID) as cnt FROM giocate
         WHERE ID_concorso=? AND timestamp>=(NOW() - INTERVAL 30 MINUTE) AND timestamp<=NOW() AND err=0 AND vincente=1 AND shortage=1",
            array($ID_concorso)
    );

    $res["g_shortage_1h"]=(int) $db->fetchOne(
            "SELECT count(ID) as cnt FROM giocate
         WHERE ID_concorso=? AND timestamp>=(NOW() - INTERVAL 1 HOUR) AND timestamp<=NOW() AND err=0 AND vincente=1 AND shortage=1",
            array($ID_concorso)
    );

    $res["g_shortage_3h"]=(int) $db->fetchOne(
            "SELECT count(ID) as cnt FROM giocate
         WHERE ID_concorso=? AND timestamp>=(NOW() - INTERVAL 3 HOUR) AND timestamp<=NOW() AND err=0 AND vincente=1 AND shortage=1",
            array($ID_concorso)
    );*/


    //****** Altro


    $res["last_g"]=$db->fetchOne(
            "SELECT timestamp FROM giocate WHERE ID_concorso=? and err=0 ORDER BY timestamp DESC LIMIT 0,1",
            array($ID_concorso)
    );

    $res["last_win"]=$db->fetchOne(
            "SELECT timestamp FROM giocate WHERE ID_concorso=? AND vincente=1 ORDER BY timestamp DESC LIMIT 0,1",
            array($ID_concorso)
    );


    $res["p_vincita"]=$concorso["probabilita_vincita"];

    if ($genMessage) {

      $msg="";

      // Totali
      $msg.="T:";
      $msg.=$res["g_valide_tot"]." ";   // Valide
      $msg.=$res["g_vincenti_tot"]." "; // Vincenti
      $msg.=$res["g_shortage_tot"]." "; // In Shortage
      $msg.=$res["g_err_tot"]." ";      // Errate
      $msg.="\n";

      // Oggi
      $msg.="O:";
      $msg.=$res["g_valide_oggi"]." ";  // Valide
      $msg.=$res["g_vincenti_oggi"]." ";// Vincenti
      $msg.=$res["g_shortage_oggi"]." ";// Shortage
      $msg.=$res["g_err_oggi"]." ";     // Errate
      $msg.="\n";

//      // Ultima mezzora
//      $msg.="30m:";
//      $msg.=$res["g_valide_30min"]." ";  // Valide
//      $msg.=$res["g_vincenti_30min"]." ";// Vincenti
//      $msg.=$res["g_shortage_30min"]." ";// Shortage
//      $msg.=$res["g_err_30min"]." ";     // Errate
//      $msg.="\n";

//      // Ultima ora
//      $msg.="1H:";
//      $msg.=$res["g_valide_1h"]." ";  // Valide
//      $msg.=$res["g_vincenti_1h"]." ";// Vincenti
//      $msg.=$res["g_shortage_1h"]." ";// Shortage
//      $msg.=$res["g_err_1h"]." ";     // Errate
//      $msg.="\n";

//      // Ultime 3 ore
//      $msg.="3H:";
//      $msg.=$res["g_valide_3h"]." ";  // Valide
//      $msg.=$res["g_vincenti_3h"]." ";// Vincenti
//      $msg.=$res["g_shortage_3h"]." ";// Shortage
//      $msg.=$res["g_err_3h"]." ";     // Errate
//      $msg.="\n";

      // Altro
      $msg.="LG:".$res["last_g"]."\n";
      $msg.="LW:".$res["last_win"]."\n";

      $msg.="P:".$concorso["probabilita_vincita"];
      return $msg;
    }else {
      return $res;
    }

  }


 

  /**
   * Restituisce la statistica oraria delle giocate
   *
   * @param int $ID_concorso
   * @return struct
   */
  public function giocateStats($ID_concorso){

    $logger=Zend_Registry::get("logger");
    $logger->log("GamblerManager::giocateStats ID_concorso=$ID_concorso",Zend_Log::DEBUG);
    $db=Zend_Registry::get("db");

    $giornate=$db->fetchAll("SELECT * FROM giornate WHERE ID_concorso=$ID_concorso ORDER BY data");
    $giornata=$giornate[0];
    $ora_start=(int)substr($giornata["ora_start"],0,2);
    $ora_stop=(int)substr($giornata["ora_stop"],0,2);
    $riga[0]=array();
    $riga[0][]="data";
    for($ora=$ora_start; $ora<$ora_stop; $ora++){
      $riga[0][]=$ora;
    }
    $riga[0][]="TOT";

    $i=1;
    foreach($giornate as $giornata){
      $riga[$i][]=$giornata["data"];
      $ora_start=(int)substr($giornata["ora_start"],0,2);
      $ora_stop=(int)substr($giornata["ora_stop"],0,2);
      $tot=0;
      for($ora=$ora_start; $ora<$ora_stop; $ora++){
        $cnt=(int)$db->fetchOne(
          "SELECT count(ID) as cnt  FROM giocate WHERE
           date(timestamp)=? AND
           err=0 AND time(timestamp)>=? AND
           time(timestamp)<=?",array($giornata["data"],$this->formatTime($ora,0,0),$this->formatTime($ora,59,59))
        );
        $riga[$i][]=$cnt;
        $tot+=$cnt;
      }
      $riga[$i][]=$tot;
      $i++;
    }

    return $riga;

  }


  private function formatTime($h,$m,$s){

    $HH=$h."";
    if ($h<10){
      $HH="0".$HH;
    }

    $MM=$m."";
    if ($m<10){
      $MM="0".$MM;
    }

    $SS=$s."";
    if ($s<10){
      $SS="0".$SS;
    }

    return $HH.":".$MM.":".$SS;

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
    return mt_rand() <= $p * mt_getrandmax();
  }

  
}