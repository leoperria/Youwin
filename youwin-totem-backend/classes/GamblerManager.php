<?php

class GamblerManager {

  private $config;

  public function __construct() {
    $this->config = Zend_Registry::get("app_config");
  }

  /**
   * Metodo di test del server
   *
   * @param int $ID_concorso
   * @return string
   */
  public function test($ID_concorso) {
    $logger = Zend_Registry::get("logger");
    $logger->log("GamblerManager::test concorso=$ID_concorso ************************", Zend_Log::DEBUG);
    return "OK Concorso $ID_concorso";
  }

  /**
   * Enter description here...
   *
   */
  public function calibrate() {
    $nv = 0;
    for ($i = 0; $i < 10000; $i++) {
      $vinto = GamblerManager::prob(0.5);
      if ($vinto) {
        $nv++;
      }
    }
    return $nv;
  }

  /**
   * Effettua una singola giocata da TOTEM
   *
   * @param int $ID_concorso
   * @param string $code
   * @return struct
   */
  public function gamble($ID_concorso, $code) {
    $logger = Zend_Registry::get("logger");
    $db = Zend_Registry::get("db");
    $logger->log("GamblerManager::gamble  ID_concorso=$ID_concorso code=$code*****************", Zend_Log::DEBUG);

    $vincente = false;
    $error = false;
    $ID_premio_vinto = 0;
    $premio = "";
    $prize_shortage = false;
    $res = array();
    $testDay = false;

    try {

      // Elimina gli spazi laterali
      $code = trim($code);

      // Filtra il codice (solo digit)
      $code = preg_replace("/[^0-9]/", "", $code);

      // Minima lunghezza
      if (strlen($code)<4){
         throw new Exception("code='$code' non valido");
      }

      // Cerca il codice (considerando soltanto i primi 4 digit)
      $codice = $db->fetchRow("SELECT * FROM codici_totem WHERE ID_concorso=? AND codice LIKE ? AND invalidato=0", array($ID_concorso, substr($code, 0, 4) . "%"));
      if ($codice === false) {
        throw new Exception("code='$code' non trovato, invalidato o non associato al concorso ID=" . $ID_concorso);
      }
      $code = $codice["codice"];

      // Invalida/aggiorna il codice
      $logger->log("Codice '$code' accettato.", Zend_Log::DEBUG);
      if ($codice["uso_multiplo"]) {
        $db->query("UPDATE codici_totem SET numero_utilizzi=numero_utilizzi+1 WHERE codice=?", $code);
        $logger->log("Codice a USO MULTIPLO: numero_utilizzi=" . ($codice["numero_utilizzi"] + 1), Zend_Log::DEBUG);
      } else {
        $db->query("UPDATE codici_totem SET invalidato=1, numero_utilizzi=1, timestamp_invalidato=NOW() WHERE codice=?", $code);
        $logger->log("Codice a USO SINGOLO: invalidato", Zend_Log::DEBUG);
      }

      // Cerca il concorso
      $ID_concorso = $codice["ID_concorso"];
      $logger->log("Cerco il concorso $ID_concorso", Zend_Log::DEBUG);
      $concorso = $db->fetchRow("SELECT * FROM concorsi WHERE ID=?", $ID_concorso);
      if ($concorso === false) {
        throw new Exception("concorso $ID_concorso non trovato.");
      }

      $now = $db->fetchRow("SELECT current_date() as curr_date, current_time() as curr_time");

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
      $THECHAOS = $this->prob((double) $concorso["probabilita_vincita"]);
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
            "win_timestamp" => date("d/m/Y H:i:s", time())
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
      "timestamp" => date("Y-m-d H:i:s", time()),
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
   * Restituisce le informazioni sul concorso e sul server
   *
   * @param int $ID_concorso
   * @return struct Informazioni sul concorso
   */
  public function infoConcorso($ID_concorso) {
    $logger = Zend_Registry::get("logger");
    $db = Zend_Registry::get("db");
    $ts = date("Y-m-d H:i:s", time());
    $logger->log("GamblerManager::infoConcorso  time_stamp=$ts *****************", Zend_Log::DEBUG);
    $concorso = $db->fetchRow("SELECT c.* FROM concorsi c WHERE c.ID=?", $ID_concorso);
    $concorso["server_timestamp"] = $ts;
    $concorso["screen_names"] = $db->fetchAll("SELECT DISTINCT screen_name FROM premi WHERE ID_concorso=?", $ID_concorso);
    return $concorso;
  }

  /**
   * Restituisce $n codici SMS
   *
   *
   * @param int $ID_concorso
   * @param int $sub_unit_code
   * @param int $n
   * @return struct Un array di codici assegnati
   */
  public function getSMSIntegrationCodes($ID_concorso, $sub_unit_code, $n) {
    $logger = Zend_Registry::get("logger");
    $db = Zend_Registry::get("db");

    try {
      $n = (int) $n;
      if ($n <= 0 || $n > $this->config["MAX_CODES_PER_TRANSACTION"]) {
        throw new Exception("Numero di codici richiesti $n non valido.");
      }

      $db->beginTransaction();
      $logger->log("GamblerManager::getSMSIntegrationCodes N=$n,ID_concorso=$ID_concorso,sub_unit_code=$sub_unit_code ***************************", Zend_Log::DEBUG);
      try {

        $sql = "SELECT * FROM codici WHERE
             assegnato =0 AND
             invalidato =0 AND
             uso_multiplo =0 AND
             ID_concorso = ? AND
             sub_unit_code= ?
             ORDER BY ID LIMIT 0,$n";
        $res = $db->fetchAll($sql, array($ID_concorso, $sub_unit_code));

        if (count($res) == 0) {
          //list($IDT,$credit)=SMSGateway::sendSMS($this->config["ADMIN_PHONE"],"YOUWIN","ERRORE FATALE: sono terminati i codici!");
          throw new Exception("ERRORE FATALE! Sono terminati i codici!");
        }
        $codes = array();
        $ids = array();
        foreach ($res as $r) {
          $codes[] = array("code" => $r["codice"]);
          $ids[] = $r["ID"];
        }
        $db->query("UPDATE codici SET assegnato=assegnato+1, timestamp_assegnato=NOW() WHERE ID in (" . join(',', $ids) . ")");
        foreach ($codes as $c) {
          $logger->log("Assegnato CODICE " . $c["code"], Zend_Log::DEBUG);
        }
        $db->commit();

        return $codes;
      } catch (Exception $ex) {
        $db->rollback();
        throw $ex;
      }
    } catch (Exception $ex) {
      $logger->log("Exception: " . $ex->getMessage() . "\n" . $ex->getTraceAsString(), Zend_log::CRIT);
      throw $ex;
    }
  }

  // TODO: da terminare...
  public function uploadSMSCodes($codes) {

    $logger = Zend_Registry::get("logger");
    $db = Zend_Registry::get("db");

    $globals = $this->db->fetchRow($this->db->select()->from("globals"));
    if ($globals["protect"]) {
      echo "ERRORE: i dati del concorso sono prottetti da scrittura.";
      return;
    }

    // Cancella i codici precedenti
    // $db->delete("codici");

    foreach ($codes as $c) {
      // Inserisci
    }
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
