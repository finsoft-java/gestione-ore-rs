<?php

$questionariCompilatiManager = new QuestionariCompilatiManager();

class QuestionarioCompilato {
    private $_progetto;
    private $_questionario;
    private $_tutte_le_risposte;
    
    function get_questionario() {
        global $questionariManager;
        if (!$this->_questionario) {
            $this->_questionario = $questionariManager->get_questionario($this->id_questionario);
        }
        return $this->_questionario;
    }

    function get_progetto() {
        global $progettiManager;
        if (!$this->_progetto) {
            $this->_progetto = $progettiManager->get_progetto($this->id_progetto);
        }
        return $this->_progetto;
    }
    
    /**
     * Prende gli utenti valutati dal risposte_quest_compilati
     * 
     * @return lista di oggetti Utente
     */
    function get_utenti_valutati() {
        
        if (!isset($this->utenti_valutati)) {
            global $con;
            $arr = [];

            $sql = "SELECT DISTINCT u.username, u.nome, u.cognome
                    FROM risposte_quest_compilati q
                    JOIN utenti u on q.nome_utente_valutato = u.username
                    WHERE progressivo_quest_comp='$this->progressivo_quest_comp'
                    ORDER BY username";
            /*
            $sql = "SELECT DISTINCT nome_utente, nome, cognome FROM `v_progetti_questionari_utenti` WHERE `id_progetto`='$this->id_progetto' AND ".
                " `id_questionario`='$this->id_questionario' AND `funzione`=`gruppo_valutati` ".
                " ORDER BY nome_utente";
                */
            if($result = mysqli_query($con, $sql)) {
                $cr = 0;
                while($row = mysqli_fetch_assoc($result))
                {
                    $obj = new Utente();
                    $obj->username      = $row['username'];
                    $obj->nome          = $row['nome'];
                    $obj->cognome       = $row['cognome'];
                    $arr[$cr++] = $obj;
                }
            } else {
                print_error(500, $con ->error);
            }
            $this->utenti_valutati = $arr;
        }
        return $this->utenti_valutati;
    }

    /**
     * Restituisce in un unico oggetto la sezione, le domande, e le relative risposte utente.
     */
    function get_sezione($progressivo_sezione, $nome_utente_valutato) {

        return $questionariCompilatiManager->get_sezione($this->progressivo_quest_comp, $progressivo_sezione, $nome_utente_valutato);
    }

    function get_tutte_le_risposte_divise_per_utente() {
        if (!$this->_tutte_le_risposte) {
            global $con;
            $arr = [];
            foreach ($this->get_utenti_valutati() as $u) {
                $arr[$u->username] = [];
            }
            
            $sql = "SELECT rq.*,ra.valore, d.coeff_valutazione,nvl(ra.valore,1)*d.coeff_valutazione as prodotto FROM `risposte_quest_compilati` rq left join questionari_compilati qc on rq.progressivo_quest_comp = qc.progressivo_quest_comp left join risposte_ammesse ra on qc.id_questionario = ra.id_questionario and ra.progressivo_sezione = rq.progressivo_sezione and ra.progressivo_domanda = rq.progressivo_domanda and ra.progressivo_risposta = rq.progressivo_risposta LEFT JOIN domande d on d.id_questionario = qc.id_questionario and d.progressivo_sezione = rq.progressivo_sezione and d.progressivo_domanda = rq.progressivo_domanda where rq.progressivo_quest_comp = '$this->progressivo_quest_comp'";
            if($result = mysqli_query($con, $sql)) {
                while($row = mysqli_fetch_assoc($result))
                {
                    $obj = new QuestionarioCompilatoRisposta($this, null);
                    $obj->progressivo_quest_comp    = $row['progressivo_quest_comp'];
                    $obj->progressivo_sezione       = $row['progressivo_sezione'];
                    $obj->progressivo_domanda       = $row['progressivo_domanda'];
                    $obj->nome_utente_valutato      = $row['nome_utente_valutato'];
                    $obj->progressivo_risposta      = $row['progressivo_risposta'];
                    $obj->risposta_aperta           = $row['risposta_aperta'];
                    $obj->note                      = $row['note'];
                    $obj->valore                    = $row['valore'];
                    $obj->coeff_valutazione         = $row['coeff_valutazione'];
                    $obj->prodotto                  = $row['prodotto'];
                    $arr[$obj->nome_utente_valutato][] = $obj;
                }
            } else {
                print_error(500, $con ->error);
            }
            $this->_tutte_le_risposte = $arr;
        }
        return $this->_tutte_le_risposte;
    }
    
    function get_progr_ultima_sezione() {
        global $con;
        $sql = "SELECT MAX(s.progressivo_sezione) AS progressivo_sezione FROM questionari_compilati q " .
                "JOIN sezioni s ON s.id_questionario=q.id_questionario ".
                "WHERE q.progressivo_quest_comp = $this->progressivo_quest_comp";
        if($result = mysqli_query($con, $sql)) {
            if($row = mysqli_fetch_assoc($result)) {
                return $row['progressivo_sezione'];
            }
        } else {
            print_error(500, $con ->error);
        }
    }
    
    function get_progr_prima_sezione() {
        global $con;
        $sql = "SELECT MIN(s.progressivo_sezione) AS progressivo_sezione FROM questionari_compilati q " .
                "JOIN sezioni s ON s.id_questionario=q.id_questionario ".
                "WHERE q.progressivo_quest_comp = $this->progressivo_quest_comp";
        if($result = mysqli_query($con, $sql)) {
            if($row = mysqli_fetch_assoc($result)) {
                return $row['progressivo_sezione'];
            }
        } else {
            print_error(500, $con ->error);
        }
    }
    
    function is_compilabile() {
        return ($this->stato < '2') and ($this->get_progetto()->stato == '1') and ($this->get_questionario()->stato == '1');
    }
}

#####################################################################################

class QuestionarioCompilatoRisposta {
    private $_questionario_compilato;
    private $_domanda;
    private $_risposta;
    
    function __construct($questionario_compilato, $domanda) {
        $this->_questionario_compilato = $questionario_compilato;
        $this->_domanda = $domanda;
    }

    function get_questionario_compilato() {
        global $questionariCompilatiManager;
        if (!$this->_questionario_compilato) {
            $this->_questionario_compilato = $questionariCompilatiManager->get_questionario_compilato($this->progressivo_quest_comp);
        }
        return $this->_questionario_compilato;
    }

    function get_sezione() {
        return $this->get_domanda()->get_sezione();
    }

    function get_domanda() {
        global $sezioniManager;
        if (!$this->_domanda) {
            $id_questionario = $this->get_questionario_compilato()->id_questionario;
            $this->_domanda = $sezioniManager->get_domanda($id_questionario, $this->progressivo_sezione, $this->progressivo_domanda);
        }
        return $this->_domanda;
    }

    function get_risposta_ammessa() {
        if (!$this->_risposta and $this->progressivo_risposta) {
            $this->_risposta = $this->get_domanda()->get_risposta_ammessa($this->progressivo_risposta);
        }
        return $this->_risposta;
    }
    
    function get_desc_risposta() {
        if ($this->risposta_aperta) {
            return $this->risposta_aperta;
        } elseif ($this->progressivo_risposta) {
            return $this->get_risposta_ammessa()->descrizione;
        }
        return null;
    }
    
    function get_punteggio() {
        if ($this->progressivo_risposta) {
            return $this->get_risposta_ammessa()->valore;
        } else {
            return 0;
        }
    }
}

#############################################################################################################

class VistaQuestionariCompilabili {
    private $questionario;
    private $progetto;
    private $ultimo_quest_comp;
    private $utenti_valutati;
    
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

    /**
     * Restituisce tutti gli utenti del progetto
     * 
     * @return lista di oggetti ProgettoUtenti
     */
    function get_progetto_utenti() {
        return $this->get_progetto()->get_progetti_utenti();
    }

    /**
     * Restituisce, tra gli utenti del progetto, quelli che sono da valutare
     * 
     * @return lista di stringhe (soltanto il nome utente)
     */
    function get_nomi_utenti_valutati() {
        global $progettiManager;
        if (!$this->utenti_valutati){
            global $con;
            $include_me = ($this->autovalutazione == '1');
            $funzione = $this->gruppo_valutati;
            if($funzione != null){
                $progetto_utenti_valutati = $progettiManager->get_progetto_utenti($this->id_progetto, $funzione, $include_me);
                $this->utenti_valutati = array_map(function($x) {return $x->nome_utente;}, $progetto_utenti_valutati);
            }else{
                $this->utenti_valutati = null;
            }            
        }
        return $this->utenti_valutati;
    }
}

#############################################################################################################

class QuestionariCompilatiManager {
    
       
    function get_domande_mancanti($progressivo_quest_comp) {
            global $con;
            $arr = [];
            //$sql = "SELECT r.progressivo_sezione, r.progressivo_domanda, r.nome_utente_valutato, d.descrizione, ut.nome, ut.cognome FROM risposte_quest_compilati r JOIN questionari_compilati qc on r.progressivo_quest_comp = qc.progressivo_quest_comp JOIN domande d on d.id_questionario = qc.id_questionario and d.progressivo_sezione = r.progressivo_sezione and d.progressivo_domanda = r.progressivo_domanda LEFT JOIN utenti ut on ut.username = r.nome_utente_valutato where r.progressivo_quest_comp = '$progressivo_quest_comp' and d.obbligatorieta = '1' and r.progressivo_risposta is NULL AND r.risposta_aperta is NULL";
            $sql = "SELECT DISTINCT r.progressivo_sezione, r.nome_utente_valutato, ut.nome, ut.cognome FROM risposte_quest_compilati r JOIN questionari_compilati qc on r.progressivo_quest_comp = qc.progressivo_quest_comp JOIN domande d on d.id_questionario = qc.id_questionario and d.progressivo_sezione = r.progressivo_sezione and d.progressivo_domanda = r.progressivo_domanda LEFT JOIN utenti ut on ut.username = r.nome_utente_valutato where r.progressivo_quest_comp = '$progressivo_quest_comp' and d.obbligatorieta = '1' and r.progressivo_risposta is NULL AND r.risposta_aperta is NULL";

            if($result = mysqli_query($con, $sql)) {
                $cr = 0;
                while($row = mysqli_fetch_assoc($result))
                {
                    $obj = new stdClass();
                    $obj->progressivo_sezione    = $row['progressivo_sezione'];
                    //$obj->progressivo_domanda    = $row['progressivo_domanda'];
                    $obj->nome_utente_valutato   = $row['nome_utente_valutato'];
                    //$obj->descrizione            = $row['descrizione'];
                    $obj->nominativo             = $row['cognome']." ".$row['nome'];
                    // NON carico le domande
                    $arr[$cr++] = $obj;
                }
            } else {
                print_error(500, $con ->error);
            }
            return $arr;
    }


    function get_questionario_compilato($progressivo_quest_comp) {
        global $con, $STATO_QUEST_COMP;
        $obj = new QuestionarioCompilato();
        $sql = "SELECT * FROM questionari_compilati WHERE progressivo_quest_comp = '$progressivo_quest_comp'";
        if($result = mysqli_query($con, $sql)) {
            if($row = mysqli_fetch_assoc($result)) {
                $obj->progressivo_quest_comp    = $row['progressivo_quest_comp'];
                $obj->id_progetto               = $row['id_progetto'];
                $obj->id_questionario           = $row['id_questionario'];
                $obj->stato                     = $row['stato'];
                $obj->stato_dec                 = ($row['stato'] != null) ? $STATO_QUEST_COMP[$row['stato']] : null;
                $obj->utente_compilazione       = $row['utente_compilazione'];
                $obj->data_compilazione         = $row['data_compilazione'];
                $obj->utente_valutato_corrente  = $row['utente_valutato_corrente'];
                $obj->progr_sezione_corrente    = $row['progr_sezione_corrente'];
                $obj->compilabile               = $obj->is_compilabile();
                $obj->sezioni                   = $obj->get_questionario()->get_sezioni(); // solo la lista, non esplosa
                $obj->utenti_valutati           = $obj->get_utenti_valutati();
                $obj->progetto                  = $obj->get_progetto();
                $obj->questionario              = $obj->get_questionario();
                $obj->is_compilato              = $this->is_questionario_compilato($obj);
            } else {
                return null;
            }
        } else {
          print_error(500, $con ->error);
        }
        return $obj;
    }
    
    /**
     * Conta i questionari, divisi per stato.
     * Un record per ogni stato, più un record "tot" per il totale.
     */
    function count() {
        global $con;
        $arr = [];
        $sql = "SELECT stato, COUNT(*) AS cnt FROM questionari_compilati GROUP BY stato";
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

    /**
     * Conta le risposte, divise per stato questionario.
     * Un record per ogni stato, più un record "tot" per il totale.
     */
    function count_risposte() {
        global $con;
        $arr = [];
        $sql = "SELECT q.stato, COUNT(*) AS cnt FROM questionari_compilati q JOIN risposte_quest_compilati r ON q.progressivo_quest_comp=r.progressivo_quest_comp GROUP BY q.stato";
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

    /*
     * Restituisce le risposte (non necessariamente compilate) per la sezione data.
     * $progressivo_sezione potrebbe essere null (scaricamento xlsx)
     * $nome_utente_valutato potrebbe essere null (questionari generici o scaricamento xlsx)
     */
    function get_risposte($progressivo_quest_comp, $progressivo_sezione = null, $nome_utente_valutato = null) {
        global $con;
        $arr = [];
        
        $sql = "SELECT * FROM risposte_quest_compilati WHERE progressivo_quest_comp = '$progressivo_quest_comp' ";
        if ($progressivo_sezione) {
            $sql .= " AND progressivo_sezione = '$progressivo_sezione'";
        }
        if ($nome_utente_valutato) {
            $sql .= " AND nome_utente_valutato = '$nome_utente_valutato'";
        }
                        
        if($result = mysqli_query($con, $sql)) {
            $cr = 0;
            while($row = mysqli_fetch_assoc($result))
            {
                $obj = new QuestionarioCompilatoRisposta(null, null);
                $obj->progressivo_quest_comp    = $row['progressivo_quest_comp'];
                $obj->progressivo_sezione       = $row['progressivo_sezione'];
                $obj->progressivo_domanda       = $row['progressivo_domanda'];
                $obj->nome_utente_valutato      = $row['nome_utente_valutato'];
                $obj->progressivo_risposta      = $row['progressivo_risposta'];
                $obj->risposta_aperta           = $row['risposta_aperta'];
                $obj->note                      = $row['note'];
                $arr[$cr++] = $obj;
            }
        } else {
            print_error(500, $con ->error);
        }
        return $arr;
    }

    /**
     * Carica una sezione del questionario da compilare.
     * Restituisce in un unico oggetto la sezione, le domande, e le relative risposte utente.
     * Se utente e sezione sono nulli, cerca di "indovinare" quale deve caricare.
     */
    function get_sezione_questionario_compilato($progressivo_quest_comp, $progressivo_sezione = null, $utente_valutato = null) {
        $q = $this->get_questionario_compilato($progressivo_quest_comp);

        if (!$utente_valutato) {
            if ($q->get_utenti_valutati()) {
                $utente_valutato = $q->get_utenti_valutati()[0];
                // Altrimenti, è un questionario generico, non prevede utenti da valutare
            }
        }
        if (!$progressivo_sezione) {
            $progressivo_sezione = $q->get_progr_sezione_corrente($utente_valutato);
            //se non ce ne sono, bisogna lanciare un errore... non dovrebbe succedere...
        }

        $sezione = $q->get_questionario()->get_sezione($progressivo_sezione);
        $risposte = $this->get_risposte($progressivo_quest_comp, $progressivo_sezione, $utente_valutato);
        foreach ($sezione->domande as $d) {
            foreach ($risposte as $r) {
                if ($r->progressivo_domanda == $d->progressivo_domanda) {
                    $d->risposta = $r;
                    break;
                }
            }
        }
        return $sezione;
    }

    function get_questionari_compilati($id_progetto, $id_questionario) {
        global $con, $STATO_QUEST_COMP;
        $arr = [];
        $sql = "SELECT * FROM questionari_compilati WHERE id_progetto = '$id_progetto' AND id_questionario = '$id_questionario'";
        
        //TODO sarebbe comodo aggiugnere l'informazione di quale sia la prossima sezione da compilare
        
        if($result = mysqli_query($con, $sql)) {
            $cr = 0;
            while($row = mysqli_fetch_assoc($result)) {
                $obj = new QuestionarioCompilato();
                $obj->progressivo_quest_comp   = $row['progressivo_quest_comp'];
                $obj->id_progetto              = $row['id_progetto'];
                $obj->id_questionario          = $row['id_questionario'];
                $obj->stato                    = $row['stato'];
                $obj->stato_dec                = $STATO_QUEST_COMP[$row['stato']];
                $obj->utente_compilazione      = $row['utente_compilazione'];
                $obj->data_compilazione        = $row['data_compilazione'];
                //TODO caricare domande e risposte
                $arr[$cr++] = $obj;
            }
        } else {
          print_error(500, $con ->error);
        }
        return $arr;
    }
    
    function get_vista_questionario_compilabile_o_compilato($id_progetto, $id_questionario) {
        global $con,$STATO_PROGETTO,$STATO_QUESTIONARIO,$STATO_QUEST_COMP,$GRUPPI,$BOOLEAN;
        $sql = "SELECT * FROM v_questionari_compilabili_per_utente WHERE id_progetto = '$id_progetto' AND id_questionario = '$id_questionario' ";
        $obj = new VistaQuestionariCompilabili();
        if($result = mysqli_query($con, $sql)) {
            if($row = mysqli_fetch_assoc($result))
            {
                
                if($row['gruppo_compilanti'] != null){
                    $gruppo_compilanti = $row['gruppo_compilanti'];
                    $gruppo_compilanti_dec = $GRUPPI[$row['gruppo_compilanti']];
                } else {
                    $gruppo_compilanti = null;
                    $gruppo_compilanti_dec = null;
                }
                if($row['gruppo_valutati'] != null){
                    $gruppo_valutati = $row['gruppo_valutati'];
                    $gruppo_valutati_dec = $GRUPPI[$row['gruppo_valutati']];
                } else {
                    $gruppo_valutati = null;
                    $gruppo_valutati_dec = null;
                }
                if($row['stato_quest_comp'] != null){
                    $stato_quest_comp       = $row['stato_quest_comp'];
                    $stato_quest_comp_dec   = $STATO_QUEST_COMP[$row['stato_quest_comp']];
                } else {                    
                    $stato_quest_comp       = null;
                    $stato_quest_comp_dec   = null;
                }

                $obj->id_progetto            = $row['id_progetto'];
                $obj->titolo_progetto        = $row['titolo_progetto'];
                $obj->stato_progetto         = $row['stato_progetto'];
                $obj->stato_progetto_dec     = $STATO_PROGETTO[$row['stato_progetto']];
                $obj->id_questionario        = $row['id_questionario'];
                $obj->titolo_questionario    = $row['titolo_questionario'];
                $obj->stato_questionario     = $row['stato_questionario'];
                $obj->stato_questionario_dec = $STATO_QUESTIONARIO[$row['stato_questionario']];
                $obj->gruppo_compilanti      = $gruppo_compilanti;
                $obj->gruppo_compilanti_dec  = $gruppo_compilanti_dec;
                $obj->gruppo_valutati        = $gruppo_valutati;
                $obj->gruppo_valutati_dec    = $gruppo_valutati_dec;
                $obj->autovalutazione        = $row['autovalutazione'];
                $obj->autovalutazione_dec    = $BOOLEAN[$row['autovalutazione']];
                $obj->progressivo_quest_comp = $row['progressivo_quest_comp'];
                $obj->stato_quest_comp       = $stato_quest_comp;
                $obj->stato_quest_comp_dec   = $stato_quest_comp_dec;
                $obj->data_compilazione      = $row['data_compilazione'];
                $obj->nome_utente            = $row['nome_utente'];

            } else {
                return null;
            }
        } else {
            print_error(500, $con ->error);
        }
        return $obj;
    }
    
    function get_vista_questionari_compilabili_o_compilati($storici, $id_progetto_or_null = null, $id_questionario_or_null = null,
            $top=null, $skip=null, $orderby=null, $search=null, $mostra_solo_admin=false) {
        global $con, $logged_user, $BOOLEAN, $STATO_QUESTIONARIO, $STATO_PROGETTO, $GRUPPI, $STATO_QUEST_COMP;
        $arr = [];
        if ($storici) {
            $vista = "v_questionari_storici_per_utente";
        } else {
            $vista = "v_questionari_compilabili_per_utente";
        }
        $sql0 = "SELECT COUNT(*) AS cnt ";
        $sql1 = "SELECT * ";
        $sql = "FROM $vista WHERE 1=1 ";
        if (!($storici and utente_admin()) || $mostra_solo_admin) {
            // solo gli amministratori possono vedere tutto, e solo nella pagina dello storico
            $sql .= " AND nome_utente = '$logged_user->nome_utente' ";
        }
        if ($id_progetto_or_null) {
            $sql .= " AND id_progetto = '$id_progetto_or_null' ";
        } 
        if ($id_questionario_or_null) {
            $sql .= " AND id_questionario = '$id_questionario_or_null' ";
        }
        if ($search){
            $search = strtoupper($search);
            $search = $con->escape_string($search);
            $sql .= " AND ( UPPER(titolo_progetto) LIKE '%$search%' OR UPPER(titolo_questionario) LIKE '%$search%' OR UPPER(CONCAT(IFNULL(nome,''), ' ', IFNULL(cognome,''))) LIKE '%$search%' ) ";
        }
        
        if ($orderby && preg_match("/^[a-zA-Z0-9,_ ]+$/", $orderby)) {
            // avoid SQL-injection
            $sql .= " ORDER BY $orderby";
        } else {
            $sql .= " ORDER BY id_progetto DESC, id_questionario DESC";
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
                if($row['stato_quest_comp'] != null){
                    $stato_quest_comp       = $row['stato_quest_comp'];
                    $stato_quest_comp_dec   = $STATO_QUEST_COMP[$row['stato_quest_comp']];
                }else{                    
                    $stato_quest_comp       = null;
                    $stato_quest_comp_dec   = null;
                }
                $obj = new VistaQuestionariCompilabili();
                $obj->id_progetto            = $row['id_progetto'];
                $obj->titolo_progetto        = $row['titolo_progetto'];
                $obj->stato_progetto         = $row['stato_progetto'];
                $obj->stato_progetto_dec     = $row['stato_progetto'] ? $STATO_PROGETTO[$row['stato_progetto']] : null;                
                $obj->id_questionario        = $row['id_questionario'];
                $obj->titolo_questionario    = $row['titolo_questionario'];
                $obj->stato_questionario     = $row['stato_questionario'];
                $obj->stato_questionario_dec = $row['stato_questionario'] ? $STATO_QUESTIONARIO[$row['stato_questionario']] : null;
                $obj->gruppo_compilanti      = $row['gruppo_compilanti'];
                $obj->gruppo_compilanti_dec  = $row['gruppo_compilanti'] ? $GRUPPI[$row['gruppo_compilanti']] : null;                
                $obj->gruppo_valutati        = $row['gruppo_valutati'];
                $obj->gruppo_valutati_dec    = $row['gruppo_valutati'] ? $GRUPPI[$row['gruppo_valutati']] : null;
                $obj->autovalutazione        = $row['autovalutazione'];
                $obj->autovalutazione_dec    = $row['autovalutazione'] ? $BOOLEAN[$row['autovalutazione']] : null;
                $obj->progressivo_quest_comp = $row['progressivo_quest_comp'];
                $obj->stato_quest_comp       = $stato_quest_comp;
                $obj->stato_quest_comp_dec   = $stato_quest_comp_dec;
                $obj->data_compilazione      = $row['data_compilazione'];
                $obj->nome_utente            = $row['nome_utente'];
                $obj->nome            = $row['nome'];
                $obj->cognome            = $row['cognome'];
                $arr[$cr++] = $obj;
            }
        } else {
            print_error(500, $con ->error);
        }
        return [$arr, $count];
    }

    function crea_questionario_compilato($questionarioCompilabile) {
        global $con, $logged_user;
        
        $sql = "UPDATE questionari SET gia_compilato='1' WHERE id_questionario = '$questionarioCompilabile->id_questionario'";
        mysqli_query($con, $sql);
        if ($con ->error) {
            print_error(500, $con ->error);
        }
        
        $sql = "UPDATE progetti SET gia_compilato='1' WHERE id_progetto = '$questionarioCompilabile->id_progetto'";
        mysqli_query($con, $sql);
        if ($con ->error) {
            print_error(500, $con ->error);
        }
        
        $sql = insert("questionari_compilati", ["progressivo_quest_comp" => null,
                                                "id_progetto" => $questionarioCompilabile->id_progetto,
                                                "id_questionario" => $questionarioCompilabile->id_questionario,
                                                "stato" => "0",
                                                "utente_compilazione" => $logged_user->nome_utente]);
                                                //no data_compilazione
        mysqli_query($con, $sql);
        if ($con ->error) {
            print_error(500, $con ->error);
        }
        $progressivo_quest_comp = mysqli_insert_id($con);
        $utenti_valutati = $questionarioCompilabile->get_nomi_utenti_valutati();
        if($utenti_valutati != null && $utenti_valutati[0] != null){
            foreach($utenti_valutati as $utente_valutato) {
                $sql = "INSERT INTO `risposte_quest_compilati`(`progressivo_quest_comp`, `progressivo_sezione`, `progressivo_domanda`, `nome_utente_valutato`) " .
                        "SELECT $progressivo_quest_comp, progressivo_sezione, progressivo_domanda, '$utente_valutato' ".
                        "FROM v_questionari_domande " .
                        "WHERE id_questionario = '$questionarioCompilabile->id_questionario' ";
                mysqli_query($con, $sql);
                if ($con ->error) {
                    print_error(500, $con ->error);
                }
            }
        }else{
                $sql = "INSERT INTO `risposte_quest_compilati`(`progressivo_quest_comp`, `progressivo_sezione`, `progressivo_domanda`, `nome_utente_valutato`) " .
                        "SELECT $progressivo_quest_comp, progressivo_sezione, progressivo_domanda, null ".
                        "FROM v_questionari_domande " .
                        "WHERE id_questionario = '$questionarioCompilabile->id_questionario' ";
                mysqli_query($con, $sql);
                if ($con ->error) {
                    print_error(500, $con ->error);
                }
        }
        
        return $this->get_questionario_compilato($progressivo_quest_comp);
    }
    
    function update_risposte_sezione($json_data_array, $questionario_compilato, $progressivo_sezione, $nome_utente_valutato) {
        // Mi aspetto che il frontend salvi in una botta sola tutte le risposte della sezione corrente
        // e che le passi in un array
        $completo = false;

        $sezione = $this->get_sezione_questionario_compilato($questionario_compilato->progressivo_quest_comp, $progressivo_sezione, $nome_utente_valutato);


        foreach ($json_data_array as $json_data) {
            $this->update_singola_risposta($json_data);
        }

        $this->aggiorna_sezione_e_utente_correnti($questionario_compilato, $progressivo_sezione, $nome_utente_valutato);
    }
    
    function update_singola_risposta($json_data) {
        global $con;
        $sql = update("risposte_quest_compilati", ["progressivo_risposta" => $json_data->progressivo_risposta,
                                                   "risposta_aperta" => $json_data->risposta_aperta,
                                                   "note" => $con->escape_string($json_data->note)],
                                                  ["progressivo_quest_comp" => $json_data->progressivo_quest_comp,
                                                   "progressivo_sezione" => $json_data->progressivo_sezione,
                                                   "progressivo_domanda" => $json_data->progressivo_domanda,
                                                   "nome_utente_valutato" => $json_data->nome_utente_valutato]);
        mysqli_query($con, $sql);
        if ($con ->error) {
            print_error(500, $con ->error);
        }
    }

    /**
     * Corrente = l'ultimo utente / sezione che sia stato compilato completamente
     */
    function aggiorna_sezione_e_utente_correnti($questionario_compilato, $progressivo_sezione, $utente_valutato) {
        global $con;
        $old_progressivo_sezione = $questionario_compilato->progr_sezione_corrente;
        $old_utente_valutato = $questionario_compilato->utente_valutato_corrente;


        // posso aggiornare solo la sezione immediatamente successiva a quella presente su db
        $indice_old = -1;
        if ($old_progressivo_sezione) {
            $indice_old = $this->get_indice_sezione_utente($questionario_compilato, $old_progressivo_sezione, $old_utente_valutato);
        }
        $indice_new = $this->get_indice_sezione_utente($questionario_compilato, $progressivo_sezione, $utente_valutato);
        /*if ($indice_new > $indice_old+1) {
            print_error(500, "Qualcosa è andato storto. Stai salvando la sezione sbagliata. Prova a risalvarle tutte dalla prima all'ultima.");
        }*/
        if ($indice_new < $indice_old+1) {
            return;
            // l'utente sta risalvando una vecchia sezione, salvataggio ok ma non aggiorno i progressivi
        }

        // e la posso aggiornare solo se davvero è stata completata
        /*$sezione = $this->get_sezione_questionario_compilato($questionario_compilato->progressivo_quest_comp, $progressivo_sezione, $utente_valutato);
        foreach ($sezione->domande as $d) {
            if ($d->obbligatorieta == '1') {
                if (!$d->risposta || ($d->risposta->risposta_aperta == null && $d->risposta->progressivo_risposta == null)) {
                    print_error(403, "La sezione non è completa");
                }
            }
        }*/

        // ok, procedo ad aggiornarla
        $sql = "UPDATE questionari_compilati SET progr_sezione_corrente='$progressivo_sezione' ";
        if ($utente_valutato && $questionario_compilato->get_utenti_valutati()) {
            $sql .= ", utente_valutato_corrente='$utente_valutato' ";
        }
        $sql .= "WHERE progressivo_quest_comp = '$questionario_compilato->progressivo_quest_comp'";
        mysqli_query($con, $sql);
        if ($con ->error) {
            print_error(500, $con ->error);
        }
    }

    /**
     * Restituisce un indice univoco per identificare sezione+utente
     */
    function get_indice_sezione_utente($questionario_compilato, $progressivo_sezione, $utente_valutato) {
        $utenti = $questionario_compilato->get_utenti_valutati();
        $j = 0;
        if ($utenti) {
            foreach ($utenti as $u) {
                if ($u->username == $utente_valutato) {
                    break;
                }
                ++$j;
            }
        }
        $sezioni = $questionario_compilato->get_questionario()->get_sezioni();
        $i = 0;
        foreach ($sezioni as $s) {
            if ($s->progressivo_sezione == $progressivo_sezione) {
                break;
            }
            ++$i;
        }
        return $j * count($sezioni) + $i;
}

    function cambia_stato($questionario_compilato, $nuovo_stato) {
        global $con;
        $sql = "UPDATE questionari_compilati SET stato='$nuovo_stato' WHERE progressivo_quest_comp = '$questionario_compilato->progressivo_quest_comp'";
        mysqli_query($con, $sql);
        if ($con ->error) {
            print_error(500, $con ->error);
        }
        $questionario_compilato->stato = $nuovo_stato;
        return $questionario_compilato;
    }

    /**
     * Mi dice se il questionario è stato completato o meno, 
     * basandosi sui campi utente_valutato_corrente e progr_sezione_corrente
     */
    function is_questionario_compilato($questionario_compilato) {
        global $questionariCompilatiManager;

        if (!$questionario_compilato->progr_sezione_corrente) {
            return false;
        }
        $utenti_valutati = $questionario_compilato->get_utenti_valutati();
        $ultimo_utente = $utenti_valutati ? end($utenti_valutati)->username : null;
        $ultima_sezione = $questionario_compilato->get_progr_ultima_sezione();
        return ("$questionario_compilato->utente_valutato_corrente" == "$ultimo_utente" and $questionario_compilato->progr_sezione_corrente == $ultima_sezione);
    }
}

?>