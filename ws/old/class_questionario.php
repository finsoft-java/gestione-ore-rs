<?php

$questionariManager = new QuestionariManager();

class Questionario {
    private $domande_appiattite;
    
    function is_gia_compilato() {
        // Attenzione assicurarsi che questa info sia stata caricata da database
        return $this->gia_compilato == '1';
    }
    
    function utente_puo_modificarlo() {
        global $logged_user;
        return utente_admin() or $this->flag_comune == '1' or $this->utente_creazione == $logged_user->nome_utente;
    }
    
    function get_sezioni() {
        if (!isset($this->sezioni)) {
            global $con;
            $arr = [];
            $sql = "SELECT * FROM sezioni WHERE id_questionario = '$this->id_questionario' ORDER BY progressivo_sezione";
            if($result = mysqli_query($con, $sql)) {
                $cr = 0;
                while($row = mysqli_fetch_assoc($result))
                {
                    $obj = new Sezione($this);
                    $obj->id_questionario        = $row['id_questionario'];
                    $obj->progressivo_sezione    = $row['progressivo_sezione'];
                    $obj->titolo                 = $row['titolo'];
                    $obj->descrizione            = $row['descrizione'];
                    // NON carico le domande
                    $arr[$cr++] = $obj;
                }
            } else {
                print_error(500, $con ->error);
            }
            $this->sezioni = $arr;
        }
        return $this->sezioni;
    }
    
    function get_sezione($progressivo_sezione, $explode = true) {
        global $con;
        $obj = new Sezione($this);
        $sql = "SELECT * FROM sezioni WHERE id_questionario = '$this->id_questionario' AND progressivo_sezione = '$progressivo_sezione'";
        
        if($result = mysqli_query($con, $sql)) {
            if($row = mysqli_fetch_assoc($result))
            {
                $obj->id_questionario       = $row['id_questionario'];
                $obj->progressivo_sezione   = $row['progressivo_sezione'];
                $obj->titolo                = $row['titolo'];
                $obj->descrizione           = $row['descrizione'];
                if ($explode) {
                    $obj->domande           = $obj->get_domande(true);
                }

            } else {
                return null;
            }
        } else {
            print_error(500, $con ->error);
        }
        
        return $obj;
    }

    function get_prossima_sezione() {
        global $con;
        $sql = "SELECT max(progressivo_sezione)+1 AS next FROM sezioni WHERE id_questionario = '$this->id_questionario'";
        if($result = mysqli_query($con, $sql)) {
            if($row = mysqli_fetch_assoc($result)) {
                return $row['next'];
            }
        } else {
            print_error(500, $con ->error);
        }
    }
    
    function get_domande_appiattite($explode = true) {
        if (!$this->domande_appiattite) {
            global $con, $BOOLEAN, $HTML_TYPE;
            $arr = [];
            $sql = "SELECT * FROM domande WHERE id_questionario = '$this->id_questionario' ORDER BY progressivo_sezione, progressivo_domanda";

            if($result = mysqli_query($con, $sql)) {
                $cr = 0;
                while($row = mysqli_fetch_assoc($result))
                {
                    $obj = new Domanda(null);
                    $obj->id_questionario       = $row['id_questionario'];
                    $obj->progressivo_sezione   = $row['progressivo_sezione'];
                    $obj->progressivo_domanda   = $row['progressivo_domanda'];
                    $obj->descrizione           = $row['descrizione'];
                    $obj->obbligatorieta        = $row['obbligatorieta'];
                    $obj->obbligatorieta_dec    = $row['obbligatorieta'] ? $BOOLEAN[$row['obbligatorieta']] : null;
                    $obj->rimescola             = $row['rimescola'];
                    $obj->rimescola_dec         = $row['rimescola'] ? $BOOLEAN[$row['rimescola']] : null;
                    $obj->coeff_valutazione     = $row['coeff_valutazione'];
                    $obj->html_type             = $row['html_type'];
                    $obj->html_type_dec         = $row['html_type'] ? $HTML_TYPE[$row['html_type']] : null;
                    $obj->html_pattern          = $row['html_pattern'];
                    $obj->html_min              = $row['html_min'];
                    $obj->html_max              = $row['html_max'];
                    $obj->html_maxlength        = $row['html_maxlength'];
                    if ($explode) {
                        $obj->risposte          = $obj->get_risposte_ammesse();
                    }
                    $arr[$cr++] = $obj;
                }
            } else {
                print_error(500, $con ->error);
            }
            $this->domande_appiattite = $arr;
        }
        return $this->domande_appiattite;
    }
}

###############################################################################################

class QuestionariManager {
    function get_questionari_validi() {
        global $con, $STATO_QUESTIONARIO, $BOOLEAN, $logged_user;
        $arr = array();
        $sql = "SELECT q.id_questionario,q.titolo,q.stato,q.gia_compilato,q.flag_comune,q.utente_creazione,q.data_creazione, MAX(id_progetto) as id_progetto 
                    FROM `questionari` q 
                    left join progetti_questionari pq on q.id_questionario = pq.id_questionario ";
        if (!utente_admin()) {
            $sql .= " WHERE (utente_creazione='$logged_user->nome_utente' OR flag_comune='1') AND stato = 1 ";
        }else{
            $sql .= " WHERE stato = 1 ";
        }
        $sql .= "GROUP by q.id_questionario,q.titolo,q.stato,q.gia_compilato,q.flag_comune,q.utente_creazione,q.data_creazione";
        
        if($result = mysqli_query($con, $sql)) {
            $cr = 0;
            while($row = mysqli_fetch_assoc($result))
            {
                $questionario = new Questionario();
                $questionario->id_questionario        = $row['id_questionario'];
                $questionario->titolo                 = $row['titolo'];
                $questionario->stato                  = $row['stato'];
                $questionario->stato_dec              = ($row['stato'] != null) ? $STATO_QUESTIONARIO[$row['stato']] : null;
                $questionario->gia_compilato          = $row['gia_compilato'];
                $questionario->gia_compilato_dec      = ($row['gia_compilato'] != null) ? $BOOLEAN[$row['gia_compilato']] : null;
                $questionario->flag_comune            = ($row['flag_comune'] == '1' ? true : false);
                $questionario->flag_comune_dec        = ($row['flag_comune'] != null) ? $BOOLEAN[$row['flag_comune']] : null;
                $questionario->utente_creazione       = $row['utente_creazione'];
                $questionario->data_creazione         = $row['data_creazione'];
                $questionario->id_progetto         = $row['id_progetto'];
                $arr[$cr++] = $questionario;
            }
        } else {
            print_error(500, $con ->error);
        }
        return $arr;
    }

    function get_questionari($top=null, $skip=null, $orderby=null, $search=null, $mostra_solo_validi=false) {
        global $con, $STATO_QUESTIONARIO, $BOOLEAN, $logged_user;
        $arr = array();
        $sql0 = "SELECT COUNT(*) AS cnt FROM `questionari` q 
                    JOIN utenti u ON q.utente_creazione = u.username
                    WHERE 1 ";
        $sql1 = "SELECT q.id_questionario,q.titolo,q.stato,q.gia_compilato,q.flag_comune,q.utente_creazione,q.data_creazione, MAX(id_progetto) as id_progetto,u.nome,u.cognome 
                    FROM `questionari` q 
                    JOIN utenti u ON q.utente_creazione = u.username
                    left join progetti_questionari pq on q.id_questionario = pq.id_questionario
                    WHERE 1 ";
        $sql = "";
        if (!utente_admin()) {
            $sql .= " AND ( utente_creazione='$logged_user->nome_utente' OR flag_comune='1' ) ";
        }
        if ($search){
            $search = strtoupper($search);
            $search = $con->escape_string($search);
            $sql .= " AND ( UPPER(q.titolo) LIKE '%$search%' OR UPPER(CONCAT(IFNULL(u.cognome,''), ' ', IFNULL(u.nome,''))) LIKE '%$search%' )";
        }
        if ($mostra_solo_validi) {
            $sql .= " AND q.stato in ('0', '1') ";
        }
        
        if($result = mysqli_query($con, $sql0 . $sql)) {
            $count = mysqli_fetch_assoc($result)["cnt"];
        } else {
            print_error(500, $con ->error);
        }

        $sql .= " GROUP BY q.id_questionario,q.titolo,q.stato,q.gia_compilato,q.flag_comune,q.utente_creazione,q.data_creazione";
        if ($orderby && preg_match("/^[a-zA-Z0-9,_ ]+$/", $orderby)) {
            // avoid SQL-injection
            $sql .= " ORDER BY $orderby";
        } else {
            $sql .= " ORDER BY q.data_creazione DESC";
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
                $questionario = new Questionario();
                $questionario->id_questionario        = $row['id_questionario'];
                $questionario->titolo                 = $row['titolo'];
                $questionario->stato                  = $row['stato'];
                $questionario->stato_dec              = ($row['stato'] != null) ? $STATO_QUESTIONARIO[$row['stato']] : null;
                $questionario->gia_compilato          = $row['gia_compilato'];
                $questionario->gia_compilato_dec      = ($row['gia_compilato'] != null) ? $BOOLEAN[$row['gia_compilato']] : null;
                $questionario->flag_comune            = ($row['flag_comune'] == '1' ? true : false);
                $questionario->flag_comune_dec        = ($row['flag_comune'] != null) ? $BOOLEAN[$row['flag_comune']] : null;
                $questionario->utente_creazione       = $row['utente_creazione'];
                $questionario->nome                   = $row['nome'];
                $questionario->cognome                = $row['cognome'];
                $questionario->data_creazione         = $row['data_creazione'];
                $questionario->id_progetto            = $row['id_progetto'];
                $arr[$cr++] = $questionario;
            }
        } else {
            print_error(500, $con ->error);
        }
        return [$arr, $count];
    }
    
    function get_questionario($id_questionario) {
        global $con, $STATO_QUESTIONARIO, $BOOLEAN;
        $questionario = new Questionario();
        $sql = "SELECT * FROM questionari WHERE id_questionario = '$id_questionario'";
        
        if($result = mysqli_query($con, $sql)) {
            if($row = mysqli_fetch_assoc($result))
            {
                $questionario->id_questionario        = $row['id_questionario'];
                $questionario->titolo                 = $row['titolo'];
                $questionario->stato                  = $row['stato'];
                $questionario->stato_dec              = ($row['stato'] != null) ? $STATO_QUESTIONARIO[$row['stato']] : null;
                $questionario->gia_compilato          = $row['gia_compilato'];
                $questionario->gia_compilato_dec      = ($row['gia_compilato'] != null) ? $BOOLEAN[$row['gia_compilato']] : null;
                $questionario->flag_comune            = ($row['flag_comune'] == '1' ? true : false);
                $questionario->flag_comune_dec        = ($row['flag_comune'] != null) ? $BOOLEAN[$row['flag_comune']] : null;
                $questionario->utente_creazione       = $row['utente_creazione'];
                $questionario->data_creazione         = $row['data_creazione'];
                $questionario->sezioni                = $questionario->get_sezioni();    // solo la lista, non esplosa
            } else {
                print_error(404, 'Not found');
            }
        } else {
            print_error(500, $con ->error);
        }
        return $questionario;
    }
    
    /**
     * Conta i questionari, divisi per stato.
     * Un record per ogni stato, più un record "tot" per il totale.
     */
    function count() {
        global $con;
        $arr = [];
        $sql = "SELECT stato, COUNT(*) AS cnt FROM questionari GROUP BY stato";
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
        $sql = insert("questionari", ["id_questionario" => null,
                                  "titolo" => $con->escape_string($json_data->titolo),
                                  "stato" => ($json_data->stato ? $json_data->stato : '0'),
                                  "flag_comune" => ($json_data->flag_comune ? $json_data->flag_comune : '0'),
                                  "gia_compilato" => '0',
                                  "utente_creazione" => $logged_user->nome_utente]);
        mysqli_query($con, $sql);
        if ($con ->error) {
            print_error(500, $con ->error);
        }
        $id_questionario = mysqli_insert_id($con);
        return $this->get_questionario($id_questionario);
    }
    
    function aggiorna($questionario, $json_data) {
        global $con, $STATO_QUESTIONARIO, $BOOLEAN;
        $this->controllo_stato($questionario, $json_data->stato);
        $this->controlla_sezioni($questionario, $json_data->stato);
        $sql = update("questionari", ["titolo" => $con->escape_string($json_data->titolo),
                                  "stato" => $json_data->stato,
                                  "flag_comune" => ($json_data->flag_comune ? $json_data->flag_comune : '0')],
                                 ["id_questionario" => $questionario->id_questionario]);
        mysqli_query($con, $sql);
        if ($con ->error) {
            print_error(500, $con ->error);
        }
        
        $questionario->titolo = $json_data->titolo;
        $questionario->stato = $json_data->stato;
        $questionario->stato_dec = $STATO_QUESTIONARIO[$json_data->stato];
        $questionario->flag_comune = $json_data->flag_comune;
        $questionario->flag_comune_dec = $BOOLEAN[$json_data->flag_comune];
        return $questionario;
    }
    function controllo_stato($questionario, $nuovo_stato){
        global $con;
        $stato_old = $questionario->stato;
        if(($nuovo_stato == '0' || $nuovo_stato == '2') && $stato_old == '1'){
            $sql = "select * from progetti_questionari pq JOIN progetti p on pq.id_progetto= p.id_progetto where pq.id_questionario = '$questionario->id_questionario' and p.stato = '1'";
            $result = mysqli_query($con, $sql);
            if ($con ->error) {
                print_error(500, $con ->error);
            }
            if($result->num_rows > 0){
                print_error(400, "Il questionario non può ritornare in Bozza/Annullato se un Progetto a cui è associato è Valido");
            }            
        }
    }
    function controlla_sezioni($questionario, $nuovo_stato){
        global $con;
        $stato_old = $questionario->stato;
        if($nuovo_stato == '1' && $stato_old == '0'){
            $sql = "SELECT d.*, s.* FROM `domande` d INNER JOIN sezioni s on d.id_questionario= s.id_questionario AND d.progressivo_sezione = s.progressivo_sezione where s.id_questionario ='$questionario->id_questionario'";
            $result = mysqli_query($con, $sql);
            if ($con ->error) {
                print_error(500, $con ->error);
            }
            if($result->num_rows == 0){
                print_error(400, "Il questionario non può essere Valido se non ci sono Sezioni con domande associate");
            }            
        }
    }

    function elimina($id_questionario) {
        global $con;
        $sql = "DELETE FROM questionari WHERE id_questionario = '$id_questionario'";  //on delete cascade! (FIXME funziona anche con i questionari?!?)
        mysqli_query($con, $sql);
        if ($con ->error) {
            print_error(500, $con ->error);
        }
        return $this;
    }
    
    function cambia_stato($questionario, $nuovo_stato) {
        global $con;
        $sql = "UPDATE questionari SET stato='$nuovo_stato' WHERE id_questionario = '$questionario->id_questionario'";
        mysqli_query($con, $sql);
        if ($con ->error) {
            print_error(500, $con ->error);
        }
        $questionario->stato = $nuovo_stato;
        return $questionario;
    }

    function utente_puo_creare_questionari() {
        global $logged_user;
        return $logged_user->ruolo >= '1';
    }

    function duplica($questionario) {
        
        // Di fatto copio soltanto il titolo
        global $con, $logged_user, $questionariManager;

        $titolo = $questionario->titolo;
        if (!$titolo) {
            $titolo = 'Nuovo questionario';
        }

        $titolo = $con->escape_string($titolo);
        for ($i = 1; $i < 1000; ++$i) {
            $titolo = "$titolo (Copia)";
            $sql = "SELECT 1 FROM questionari where titolo = '$titolo'";
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

        $sql = insert_select("questionari", ["id_questionario", "stato", "flag_comune", "titolo", "utente_creazione"],
                                            ["id_questionario" => null,
                                            "stato" => '0',
                                            "flag_comune" => '0',
                                            "titolo" => $titolo,
                                            "gia_compilato" => '0',
                                            "utente_creazione" => $logged_user->nome_utente],
                                            ["id_questionario" => $questionario->id_questionario]
                                            );
                                            //no data_creazione
        mysqli_query($con, $sql);
        if ($con ->error) {
            // Tipicamente, qui potrebbe esserci un problema di concorrenza
            print_error(500, $con ->error);
        }
        $nuovo_id_questionario = mysqli_insert_id($con);
        $this->_duplica_sezioni($questionario, $nuovo_id_questionario);
        $this->_duplica_domande($questionario, $nuovo_id_questionario);
        $this->_duplica_risposte($questionario, $nuovo_id_questionario);
        return $questionariManager->get_questionario($nuovo_id_questionario);
    }

    function _duplica_sezioni($questionario, $nuovo_id_questionario) {
        global $con;
        $sql = insert_select("sezioni", ["id_questionario", "progressivo_sezione", "titolo", "descrizione"],
                                        ["id_questionario" => $nuovo_id_questionario],
                                        ["id_questionario" => $questionario->id_questionario]
                                        );
        mysqli_query($con, $sql);
        if ($con ->error) {
            // Tipicamente, qui potrebbe esserci un problema di concorrenza
            print_error(500, $con ->error);
        }
    }

    function _duplica_domande($questionario, $nuovo_id_questionario) {
        global $con;
        $sql = insert_select("domande", ["id_questionario", "progressivo_sezione", "progressivo_domanda", "descrizione", "obbligatorieta", "coeff_valutazione", "html_type", "html_pattern", "html_min", "html_max", "html_maxlength", "rimescola"],
                                        ["id_questionario" => $nuovo_id_questionario],
                                        ["id_questionario" => $questionario->id_questionario]
                                        );
        mysqli_query($con, $sql);
        if ($con ->error) {
            // Tipicamente, qui potrebbe esserci un problema di concorrenza
            print_error(500, $con ->error);
        }
    }

    function _duplica_risposte($questionario, $nuovo_id_questionario) {
        global $con;
        $sql = insert_select("risposte_ammesse", ["id_questionario", "progressivo_sezione", "progressivo_domanda", "progressivo_risposta", "descrizione", "valore"],
                                                 ["id_questionario" => $nuovo_id_questionario],
                                                 ["id_questionario" => $questionario->id_questionario]
                                                 );
        mysqli_query($con, $sql);
        if ($con ->error) {
            // Tipicamente, qui potrebbe esserci un problema di concorrenza
            print_error(500, $con ->error);
        }
    }
}

?>