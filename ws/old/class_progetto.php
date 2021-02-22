<?php

$progettiManager = new ProgettiManager();
$progettiQuestionariManager = new ProgettiQuestionariManager();

class Progetto {
    private $_progetto_utenti;
    private $_progetto_questionari;
    
    function get_progetto_utenti() {
        global $progettiManager;
        if (!$this->_progetto_utenti) {
            $this->_progetto_utenti = $progettiManager->get_progetto_utenti($this->id_progetto);
        }
        return $this->_progetto_utenti;
    }
    
    function get_progetto_questionari() {
        global $progettiManager;
        if (!$this->_progetto_questionari) {
            $this->_progetto_questionari = $progettiManager->get_progetto_questionari($this->id_progetto);
        }
        return $this->_progetto_questionari;
    }
    
    function is_gia_compilato() {
        // Attenzione assicurarsi che questa info sia stata caricata da database
        return $this->gia_compilato == '1';
    }

    function utente_puo_modificarlo() {
        global $con, $logged_user;
        if ($logged_user->ruolo == '2') {
            return true;
        }
        $sql = "SELECT * FROM progetti_utenti WHERE id_progetto = '$this->id_progetto' AND nome_utente = '$logged_user->nome_utente' AND funzione >= '1'";
        if($result = mysqli_query($con, $sql)) {
            if($row = mysqli_fetch_assoc($result)) {
                return true;
            } else {
                return false;
            }
        } else {
            print_error(500, $con ->error);
        }
    }
}

class ProgettoUtenti {
    private $_utente;
    private $_progetto;

    function get_progetto() {
        global $progettiManager;
        if (!$this->_progetto) {
            $this->_progetto = $progettiManager->get_progetto($this->id_progetto);
        }
        return $this->_progetto;
    }
    
    function get_utente() {
        global $utentiManager;
        if (!$this->_utente) {
            $this->_utente = $utentiManager->get_utente($this->nome_utente);
        }
        return $this->_utente;
    }
}



class ProgettoQuestionari {
    private $questionario;
    private $progetto;
    
    function get_questionario() {
        global $questionariManager;
        if (!$this->questionario) {
            $this->questionario = $questionariManager->get_questionario($this->id_questionario);
        }
        return $this->questionario;
    }

    function get_progetto() {
        global $progettiManager;
        if (!$this->progetto) {
            $this->progetto = $progettiManager->get_progetto($this->id_progetto);
        }
        return $this->progetto;
    }

    function get_utenti_compilanti() {
        global $progettiManager;
        if (!$this->progetto) {
            $this->progetto = $progettiManager->get_utenti_compilanti($this->id_progetto,$this->id_questionario);
        }
        return $this->progetto;
    }
    
    
}

class ProgettiManager {
    
    function get_progetti($top=null, $skip=null, $orderby=null, $search=null, $mostra_solo_validi=false) {
        global $con, $STATO_PROGETTO, $BOOLEAN;
        $arr = array();
        $sql1 = "SELECT * ";
        $sql0 = "SELECT COUNT(*) AS cnt ";
        $sql = "FROM progetti p JOIN utenti u ON p.utente_creazione = u.username WHERE 1 ";
        if ($search){
            $search = strtoupper($search);
            $search = $con->escape_string($search);
            $sql .= " AND (UPPER(p.titolo) LIKE '%$search%' OR UPPER(CONCAT(IFNULL(u.cognome,''), ' ', IFNULL(u.nome,''))) LIKE '%$search%')";
        }
        if ($mostra_solo_validi) {
            $sql .= " AND p.stato in ('0', '1') ";
        }
        if ($orderby && preg_match("/^[a-zA-Z0-9,_ ]+$/", $orderby)) {
            // avoid SQL-injection
            $sql .= " ORDER BY $orderby";
        } else {
            $sql .= " ORDER BY p.data_creazione DESC";
        }

        if($result = mysqli_query($con, $sql0 . $sql)) {
            $count = mysqli_fetch_assoc($result)["cnt"];
        } else {
            print_error(500, $con ->error);
        }

        if ($top){
            if ($skip) {
                $sql .= " LIMIT $skip,$top";
            } else {
                $sql .= " LIMIT $top";
            }
        }
        if($result = mysqli_query($con, $sql1 . $sql)) {
            $cr = 0;
            while($row = mysqli_fetch_assoc($result))
            {
                $progetto = new Progetto();
                $progetto->id_progetto        = $row['id_progetto'];
                $progetto->titolo             = $row['titolo'];
                $progetto->stato              = $row['stato'];
                $progetto->stato_dec          = $STATO_PROGETTO[$row['stato']];
                $progetto->gia_compilato      = $row['gia_compilato'];
                $progetto->gia_compilato_dec  = $BOOLEAN[$row['gia_compilato']];
                $progetto->utente_creazione   = $row['utente_creazione'];
                $progetto->nome               = $row['nome'];
                $progetto->cognome            = $row['cognome'];
                $progetto->data_creazione     = $row['data_creazione'];
                $arr[$cr++] = $progetto;
            }
        } else {
            print_error(500, $con ->error);
        }
        return [$arr, $count];
    }

    function get_utenti_compilanti($id_progetto,$id_questionario) {
        global $con, $STATO_PROGETTO, $BOOLEAN;
        $arr = array();
        $sql = "SELECT pu.nome_utente FROM progetti_questionari pq INNER JOIN progetti_utenti pu on pq.id_progetto = pu.id_progetto AND pu.funzione= pq.gruppo_compilanti INNER join  utenti ut on pu.nome_utente = ut.username where pq.id_progetto= '$id_progetto' and pq.id_questionario = '$id_questionario'";
        if($result = mysqli_query($con, $sql)) {
            $cr = 0;
            while($row = mysqli_fetch_assoc($result))
            {
                $arr[$cr++] = $row['nome_utente'];
            } 
        } else {
            print_error(500, $con ->error);
        }
        return $arr;
    }
    
    function get_progetto($id_progetto) {
        global $con, $STATO_PROGETTO, $BOOLEAN;
        $progetto = new Progetto();
        $sql = "SELECT * FROM progetti p JOIN utenti u on p.utente_creazione = u.username WHERE id_progetto = '$id_progetto'";
        if($result = mysqli_query($con, $sql)) {
            if($row = mysqli_fetch_assoc($result))
            {
                $progetto->id_progetto        = $row['id_progetto'];
                $progetto->titolo             = $row['titolo'];
                $progetto->stato              = $row['stato'];
                $progetto->stato_dec          = $STATO_PROGETTO[$row['stato']];
                $progetto->gia_compilato      = $row['gia_compilato'];
                $progetto->gia_compilato_dec  = $BOOLEAN[$row['gia_compilato']];
                $progetto->utente_creazione   = $row['utente_creazione'];
                $progetto->nome               = $row['nome'];
                $progetto->cognome            = $row['cognome'];
                $progetto->data_creazione     = $row['data_creazione'];
                $progetto->utenti             = $progetto->get_progetto_utenti();
                $progetto->questionari        = $progetto->get_progetto_questionari();
            } else {
                return null;
            }
        } else {
            print_error(500, $con ->error);
        }
        return $progetto;
    }
    
    /**
     * Conta i progetti, divisi per stato.
     * Un record per ogni stato, più un record "tot" per il totale.
     */
    function count() {
        global $con;
        $arr = [];
        $sql = "SELECT stato, COUNT(*) AS cnt FROM progetti GROUP BY stato";
        if($result = mysqli_query($con, $sql)) {
            $cr = 0;
            $tot = 0;
            while($row = mysqli_fetch_assoc($result)) {
                $arr[$row["stato"]] = $row["cnt"];
                $cr++;
                $tot += $row['cnt'];
            }
            $arr['tot'] = $tot;
        } else {
            print_error(500, $con ->error);
        }
        return $arr;
    }

    function crea($json_data) {
        global $con, $logged_user;
        $sql = insert("progetti", ["id_progetto" => null,
                               "titolo" => $con->escape_string($json_data->titolo),
                               "stato" => ($json_data->stato ? $json_data->stato : '0'),
                               "gia_compilato" => '0',
                               "utente_creazione" => $logged_user->nome_utente]);
        mysqli_query($con, $sql);
        if ($con ->error) {
            print_error(500, $con ->error);
        }
        $id_progetto = mysqli_insert_id($con);
        return $this->get_progetto($id_progetto);
    }
    
    function aggiorna($progetto, $json_data) {
        global $con, $STATO_PROGETTO;
        $this->controllaStato($progetto,$json_data->stato);
        $titolo = $con->escape_string($json_data->titolo);
        $stato = $con->escape_string($json_data->stato);
        $sql = "UPDATE progetti SET titolo='$titolo', stato='$stato' WHERE id_progetto = '$progetto->id_progetto'";
        mysqli_query($con, $sql);
        if ($con ->error) {
            print_error(500, $con ->error);
        }
    }
    
    function cambia_stato($progetto, $nuovo_stato) {
        global $con, $STATO_PROGETTO;
        
        $this->controllaStato($progetto,$nuovo_stato);

        $sql = "UPDATE progetti SET stato='$nuovo_stato' WHERE id_progetto = '$progetto->id_progetto'";
        mysqli_query($con, $sql);
        if ($con ->error) {
            print_error(500, $con ->error);
        }
        $progetto->stato = $nuovo_stato;
        return $progetto;
    }
    
    function elimina($id_progetto) {
        global $con;
        $sql = "DELETE FROM progetti WHERE id_progetto = '$id_progetto'";  //on delete cascade! (FIXME funziona anche con i questionari?!?)
        mysqli_query($con, $sql);
        if ($con ->error) {
            print_error(500, $con ->error);
        }
    }

    function duplica($progetto) {
        // Di fatto copio soltanto il titolo
        global $con, $logged_user, $progettiManager;
        
        $titolo = $progetto->titolo;
        if (!$titolo) {
            $titolo = 'Nuovo progetto';
        }

        $titolo = $con->escape_string($titolo);
        for ($i = 1; $i < 1000; ++$i) {
            $titolo = "$titolo (Copia)";
            $sql = "SELECT 1 FROM progetti where titolo = '$titolo'";
            mysqli_query($con, $sql);
            if($result = mysqli_query($con, $sql)) {
                if(! mysqli_fetch_assoc($result)) {
                    // La copia non esiste ancora
                    break;
                }
            } else {
                print_error(500, $con ->error);
            }
        }

        $sql = insert_select("progetti", ["id_progetto", "stato", "titolo", "utente_creazione"],
                                            ["id_progetto" => null,
                                            "stato" => '0',
                                            "titolo" => $titolo,
                                            "gia_compilato" => '0',
                                            "utente_creazione" => $logged_user->nome_utente],
                                            ["id_progetto" => $progetto->id_progetto]
                                            );
                                            //no data_creazione
        mysqli_query($con, $sql);
        if ($con ->error) {
            // Tipicamente, qui potrebbe esserci un problema di concorrenza
            print_error(500, $con ->error);
        }
        $nuovo_id_progetto = mysqli_insert_id($con);
        $this->_duplica_progetto_questionari($progetto, $nuovo_id_progetto);
        $this->_duplica_progetto_utenti($progetto, $nuovo_id_progetto);
        return $progettiManager->get_progetto($nuovo_id_progetto);
    }

    function _duplica_progetto_questionari($progetto, $nuovo_id_progetto) {
        global $con;
        $sql = insert_select("progetti_questionari", ["id_progetto", "id_questionario", "tipo_questionario", "gruppo_compilanti", "gruppo_valutati", "autovalutazione"],
                                        ["id_progetto" => $nuovo_id_progetto],
                                        ["id_progetto" => $progetto->id_progetto]
                                        );
        mysqli_query($con, $sql);
        if ($con ->error) {
            // Tipicamente, qui potrebbe esserci un problema di concorrenza
            print_error(500, $con ->error);
        }
    }

    function _duplica_progetto_utenti($progetto, $nuovo_id_progetto) {
        global $con;
        $sql = insert_select("progetti_utenti", ["id_progetto", "nome_utente", "funzione"],
                                        ["id_progetto" => $nuovo_id_progetto],
                                        ["id_progetto" => $progetto->id_progetto]
                                        );
        mysqli_query($con, $sql);
        if ($con ->error) {
            // Tipicamente, qui potrebbe esserci un problema di concorrenza
            print_error(500, $con ->error);
        }
    }
    
    function controllaStato($progetto, $nuovo_stato){
        $stato_old = $progetto->stato;
        if($nuovo_stato == '1'){
            if(count($progetto->questionari) == 0 || $progetto->questionari[0] == null){
                print_error(400, "Un progetto senza questionari non può essere valido");
            }
            for($i = 0; $i < count($progetto->questionari); $i++){
                if($progetto->questionari[$i]->stato_questionario == '0'){
                    print_error(400, "Un progetto non può essere Valido se ha questionari in Bozza");
                }
            }
        }
    }

    function utente_puo_creare_progetti() {
        global $logged_user;
        return $logged_user->ruolo >= '1';
    }
    
    function get_progetto_utenti($id_progetto, $funzione_or_null = null, $include_me = true) {
        global $con, $FUNZIONE, $logged_user;
        $arr = [];
        $sql = "SELECT * FROM progetti_utenti WHERE id_progetto = '$id_progetto'";
        if ($funzione_or_null) {
            $sql .= " AND funzione = '$funzione_or_null'";
        }
        if (!$include_me) {
            $sql .= " AND nome_utente <> '$logged_user->nome_utente'";
        }
        
        if($result = mysqli_query($con, $sql)) {
            $cr = 0;
            while($row = mysqli_fetch_assoc($result))
            {
                $obj = new ProgettoUtenti();
                $obj->id_progetto     = $row['id_progetto'];
                $obj->nome_utente     = $row['nome_utente'];
                $obj->funzione        = $row['funzione'];
                $obj->funzione_dec    = $FUNZIONE[$row['funzione']];
                $arr[$cr++] = $obj;
            }
        } else {
            print_error(500, $con ->error);
        }
        return $arr;
    }
    
    function get_progetto_questionari($id_progetto, $id_questionario_or_null = null) {
        global $con, $STATO_QUESTIONARIO, $GRUPPI, $TIPO_QUESTIONARIO, $BOOLEAN;
        $arr = [];
        $sql = "SELECT * FROM v_progetti_questionari WHERE id_progetto = '$id_progetto' ";
        if ($id_questionario_or_null) {
            $sql .= " AND id_questionario = '$id_questionario_or_null' ";
        }
        
        if($result = mysqli_query($con, $sql)) {
            $cr = 0;
            while($row = mysqli_fetch_assoc($result))
            {
                if($row['gruppo_compilanti'] != ''){
                    $gruppo_compilanti = $row['gruppo_compilanti'];
                    $gruppo_compilanti_dec = $GRUPPI[$row['gruppo_compilanti']];
                }else{
                    $gruppo_compilanti = '';
                    $gruppo_compilanti_dec = '';
                }
                if($row['gruppo_valutati'] != ''){
                    $gruppo_valutati = $row['gruppo_valutati'];
                    $gruppo_valutati_dec = $GRUPPI[$row['gruppo_valutati']];
                }else{
                    $gruppo_valutati = '';
                    $gruppo_valutati_dec = '';
                }
                
                $obj = new ProgettoQuestionari();
                $obj->id_progetto            = $row['id_progetto'];
                $obj->id_questionario        = $row['id_questionario'];
                $obj->titolo_questionario    = $row['titolo_questionario'];
                $obj->stato_questionario     = $row['stato_questionario'];
                $obj->stato_questionario_dec = $STATO_QUESTIONARIO[$row['stato_questionario']];
                $obj->tipo_questionario      = $row['tipo_questionario'];
                $obj->tipo_questionario_dec  = $TIPO_QUESTIONARIO[$row['tipo_questionario']];
                $obj->gruppo_compilanti      = $gruppo_compilanti;
                $obj->gruppo_compilanti_dec  = $gruppo_compilanti_dec;
                $obj->gruppo_valutati        = $gruppo_valutati;
                $obj->gruppo_valutati_dec    = $gruppo_valutati_dec;
                $obj->autovalutazione        = $row['autovalutazione'];
                $obj->autovalutazione_dec    = $BOOLEAN[$row['autovalutazione']];
                $obj->ut_creaz_questionario  = $row['ut_creaz_questionario'];
                $obj->data_creaz_questionario= $row['data_creaz_questionario'];
                $arr[$cr++] = $obj;
            }
        } else {
            print_error(500, $con ->error);
        }
        return $arr;
    }
    
    function get_progetti_questionari_validi($id_progetto) {
        global $con, $STATO_QUESTIONARIO, $GRUPPI, $TIPO_QUESTIONARIO, $BOOLEAN;
        $arr = [];
        $sql = "SELECT * FROM v_progetti_questionari WHERE id_progetto = '$id_progetto' AND stato_questionario='1'";
        
        if($result = mysqli_query($con, $sql)) {
            $cr = 0;
            while($row = mysqli_fetch_assoc($result))
            {
                $obj = new ProgettoQuestionari();
                $obj->id_progetto            = $row['id_progetto'];
                $obj->titolo_progetto        = $row['titolo_progetto'];
                $obj->id_questionario        = $row['id_questionario'];
                $obj->titolo_questionario    = $row['titolo_questionario'];
                $obj->tipo_questionario      = $row['tipo_questionario'];
                $obj->tipo_questionario_dec  = $TIPO_QUESTIONARIO[$row['tipo_questionario']];
                $obj->gruppo_compilanti      = $row['gruppo_compilanti'];
                $obj->gruppo_compilanti_dec  = $GRUPPI[$row['gruppo_compilanti']];
                $obj->gruppo_valutati        = $row['gruppo_valutati'];
                $obj->gruppo_valutati_dec    = $GRUPPI[$row['gruppo_valutati']];
                $obj->autovalutazione        = $row['autovalutazione'];
                $obj->autovalutazione_dec    = $BOOLEAN[$row['autovalutazione']];
                $obj->utente_creazione       = $row['ut_creaz_questionario'];
                $obj->data_creazione         = $row['data_creaz_questionario'];
                $arr[$cr++] = $obj;
            }
        } else {
            print_error(500, $con ->error);
        }
        return $arr;
    }
    
    function get_utenti_funzioni($progetto){
        global $con, $RUOLO;
        $arr = array();
        $sql = "SELECT ut.username as username, pr_ut.funzione as funzione, ut.nome as nome, ut.cognome as cognome FROM utenti as ut ".
            "LEFT JOIN progetti_utenti pr_ut ON ut.username = pr_ut.nome_utente AND pr_ut.id_progetto = '".$progetto."' ".
            "ORDER BY ut.cognome ASC";
        if($result = mysqli_query($con, $sql)) {
            $cr = 0;
            while($row = mysqli_fetch_assoc($result))
            {
                $utente = new Utente();
                $utente->nominativo   = $row["cognome"]." ".$row["nome"];
                $utente->nome_utente   = $row["username"];
                $utente->id_progetto = $progetto;
                $utente->responsabileL1 = false;
                $utente->responsabileL2 = false;
                $utente->utenteFinale = false;
                $utente->funzione = $row["funzione"];
                if($row["funzione"] != null){                    
                    switch ($row["funzione"]) {
                        case 0:
                            $utente->utenteFinale = true;
                            break;
                        case 1:
                            $utente->responsabileL2 = true;
                            break;
                        case 2:
                            $utente->responsabileL1 = true;
                            break;
                    }
                }
                $arr[$cr++] = $utente;
            }
        } else {
            print_error(500, $con ->error);
        }
        return $arr;
    }
    
    function save_utenti_funzioni($progetto, $lista_utenti_funzioni){
        global $con, $RUOLO;
        
        $old_utenti = $this->get_utenti_funzioni($progetto->id_progetto);
        foreach($old_utenti as $u) {
            if ($u !== null && $u->funzione !== null) {
                $old_utenti[$u->nome_utente] = $u;
            }
        }
        $gia_compilato = $progetto->is_gia_compilato();

        foreach ($lista_utenti_funzioni as $u) {
            $old_utente = isset($old_utenti[$u->nome_utente]) ? $old_utenti[$u->nome_utente] : null;
            $new_funzione = null;
            if ($u->utenteFinale) {
                $new_funzione = '0';
            } elseif  ($u->responsabileL2) {
                $new_funzione = '1';
            } elseif  ($u->responsabileL1) {
                $new_funzione = '2';
            }
            
            if ($new_funzione == null) {
                //utente non legato al progetto
                if ($old_utente) {
                    // utente eliminato dal progetto
                    $sql = "DELETE FROM progetti_utenti WHERE id_progetto = '$progetto->id_progetto' AND nome_utente='$u->nome_utente'";
                    mysqli_query($con, $sql);
                    if ($con ->error) {
                        print_error(500, $con ->error);
                    }
                    if ($gia_compilato) {
                        $sql = "DELETE b.* FROM `risposte_quest_compilati` AS b WHERE `nome_utente_valutato`='$u->nome_utente' AND (SELECT id_progetto FROM questionari_compilati a WHERE a.progressivo_quest_comp=b.progressivo_quest_comp AND a.id_progetto)='$progetto->id_progetto'";
                        mysqli_query($con, $sql);
                        $sql = "DELETE FROM `questionari_compilati` WHERE `utente_compilazione` = '$u->nome_utente' AND `id_progetto`='$progetto->id_progetto'";
                        // assuming ON DELETE CASCADE
                        mysqli_query($con, $sql);
                    }
                }
                continue;
            }
            
            if (!$old_utente) {
                // nuovo utente
                $sql = "INSERT IGNORE INTO progetti_utenti (id_progetto, nome_utente, funzione)  VALUES('$progetto->id_progetto', '$u->nome_utente', '$new_funzione')";
                mysqli_query($con, $sql);
            } elseif ($new_funzione != $u->funzione) {
                // modificata funzione utente (non dovrebbe succedere)
                $sql = "UPDATE progetti_utenti SET funzione='$new_funzione' WHERE id_progetto='$progetto->id_progetto' AND nome_utente='$u->nome_utente'";
                mysqli_query($con, $sql);
            }
            
            if ($gia_compilato) {
                // potrei dover invalidare delle compilazioni
                // devo guardare la tabella progetti questionari e vedere se ci sono questionari con gruppo_valutati = new_funzione
                
                $sql = "INSERT IGNORE INTO `risposte_quest_compilati`(`progressivo_quest_comp`, `progressivo_sezione`, `progressivo_domanda`, `nome_utente_valutato`) " .
                        "SELECT progressivo_quest_comp, progressivo_sezione, progressivo_domanda, '$u->nome_utente' ".
                        "FROM v_questionari_domande v " .
                        "JOIN questionari_compilati c ON c.id_questionario=v.id_questionario " .
                        "WHERE v.id_questionario IN (SELECT id_questionario FROM `progetti_questionari` WHERE `id_progetto`='$progetto->id_progetto' AND `gruppo_valutati`='$new_funzione' ) ";
                mysqli_query($con, $sql);

                $sql = "UPDATE `questionari_compilati` SET `stato`='0' WHERE `id_progetto`='$progetto->id_progetto' AND `stato`='1' AND " .
                        "id_questionario IN (SELECT id_questionario FROM `progetti_questionari` WHERE `id_progetto`='$progetto->id_progetto' AND `gruppo_valutati`='$new_funzione' ) ";
                mysqli_query($con, $sql);
            }
            
        }
    }
}

class ProgettiQuestionariManager {
    
    function get_progetto_questionari($id_progetto, $id_questionario) {
        
        global $progettiManager;
        $pq = $progettiManager->get_progetto_questionari($id_progetto, $id_questionario);
        // $pq e' un array con 1 elemento
        if (!$pq) {
            return null;
        }
        return $pq[0];
    }
    
    function crea($json_data) {
        global $con, $logged_user;
        $sql = insert("progetti_questionari", [
                               "id_progetto" => $json_data->id_progetto,
                               "id_questionario" => $json_data->id_questionario,
                               "tipo_questionario" => $json_data->tipo_questionario,
                               "gruppo_compilanti" => $json_data->gruppo_compilanti,
                               "gruppo_valutati" => $json_data->gruppo_valutati,
                               "autovalutazione" => $json_data->autovalutazione]);
        mysqli_query($con, $sql);
        if ($con ->error) {
            print_error(500, $con ->error);
        }
        return $this->get_progetto_questionari($json_data->id_progetto, $json_data->id_questionario);
    }
    
    function aggiorna($pq, $json_data) {
        global $con, $BOOLEAN, $GRUPPI, $TIPO_QUESTIONARIO;
        $sql = update("progetti_questionari", ["tipo_questionario" => $json_data->tipo_questionario,
                                 "gruppo_compilanti" => $json_data->gruppo_compilanti,
                                 "gruppo_valutati" => $json_data->gruppo_valutati,
                                 "autovalutazione" => $json_data->autovalutazione],
                                ["id_progetto" => $pq->id_progetto,
                                "id_questionario" => $pq->id_questionario]);
        mysqli_query($con, $sql);
        if ($con ->error) {
            print_error(500, $con ->error);
        }
        
        if($json_data->gruppo_compilanti != ''){
            $gruppo_compilanti = $json_data->gruppo_compilanti;
            $gruppo_compilanti_dec = $GRUPPI[$json_data->gruppo_compilanti];
        }else{
            $gruppo_compilanti = '';
            $gruppo_compilanti_dec = '';
        }
        if($json_data->gruppo_valutati != ''){
            $gruppo_valutati = $json_data->gruppo_valutati;
            $gruppo_valutati_dec = $GRUPPI[$json_data->gruppo_valutati];
        }else{
            $gruppo_valutati = '';
            $gruppo_valutati_dec = '';
        }

        $pq->tipo_questionario = $json_data->tipo_questionario;
        $pq->tipo_questionario_dec = $TIPO_QUESTIONARIO[$pq->tipo_questionario];
        $pq->gruppo_compilanti = $gruppo_compilanti;
        $pq->gruppo_compilanti_dec = $gruppo_compilanti_dec;
        $pq->gruppo_valutati = $gruppo_valutati;
        $pq->gruppo_valutati_dec = $gruppo_valutati_dec;
        $pq->autovalutazione = $json_data->autovalutazione;
        $pq->autovalutazione_dec = $BOOLEAN[$pq->autovalutazione];
        return $pq;
    }
    
    function elimina($id_progetto, $id_questionario) {
        global $con;
        $sql = "DELETE FROM progetti_questionari WHERE id_progetto = '$id_progetto' and id_questionario = '$id_questionario'";
        mysqli_query($con, $sql);
        if ($con ->error) {
            print_error(500, $con ->error);
        }
    }
}

?>