<?php

$sezioniManager = new SezioniManager();

class Sezione {
    private $_questionario;

    function __construct($questionario) {
        $this->_questionario = $questionario;
    }
    
    function get_questionario() {
        if (!$this->_questionario) {
            $this->_questionario = $questionariManager->get_questionario($this->id_questionario);
        }
        return $this->_questionario;
    }
    
    function get_domanda($progressivo_domanda, $explode = true) {
        global $sezioniManager;
        return $sezioniManager->get_domanda($this->id_questionario, $this->progressivo_sezione, $progressivo_domanda, $explode);
    }

    function get_domande($explode = true) {
        if (!isset($this->domande)) {
            global $con, $BOOLEAN, $HTML_TYPE;
            $html_type = '';
           
            $arr = [];
            $sql = "SELECT * FROM domande WHERE id_questionario = '$this->id_questionario' AND progressivo_sezione = '$this->progressivo_sezione' ORDER BY progressivo_domanda";
            if($result = mysqli_query($con, $sql)) {
                $cr = 0;
                while($row = mysqli_fetch_assoc($result))
                {
                    $obj = new Domanda($this);
                    $obj->id_questionario       = $row['id_questionario'];
                    $obj->progressivo_sezione   = $row['progressivo_sezione'];
                    $obj->progressivo_domanda   = $row['progressivo_domanda'];
                    $obj->descrizione           = $row['descrizione'];
                    $obj->obbligatorieta        = ($row['obbligatorieta'] == '1' ? true : false);
                    $obj->obbligatorieta_dec    = ($row['obbligatorieta'] != null) ? $BOOLEAN[$row['obbligatorieta']] : null;
                    $obj->rimescola             = ($row['rimescola'] == '1' ? true : false);
                    $obj->rimescola_dec         = ($row['rimescola'] != null) ? $BOOLEAN[$row['rimescola']] : null;
                    $obj->coeff_valutazione     = $row['coeff_valutazione'];
                    $obj->html_type             = $row['html_type'];
                    $obj->html_type_dec         = ($row['html_type'] != null) ? $HTML_TYPE[$row['html_type']] : null;
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
            $this->domande = $arr;
        }
        return $this->domande;
    }
    function get_prossima_domanda() {
        global $con;
        $sql = "SELECT max(progressivo_domanda) AS next FROM domande WHERE id_questionario = '$this->id_questionario' and progressivo_sezione = '$this->progressivo_sezione'";
        if($result = mysqli_query($con, $sql)) {
            if($row = mysqli_fetch_assoc($result)) {
                return $row['next']+1;
            }
        } else {
            print_error(500, $con ->error);
        }
    }
}

################################################################################################

class Domanda {
    private $_sezione;

    function __construct($sezione) {
        $this->_sezione = $sezione;
    }
    
    function get_questionario() {
        return $this->get_sezione()->get_questionario();
    }
    
    function get_sezione() {
        global $sezioniManager;
        if (!$this->_sezione) {
            $this->_sezione = $sezioniManager->get_sezione($this->id_questionario, $this->progressivo_sezione);
        }
        return $this->_sezione;
    }

    function get_risposte_ammesse() {
        if (!isset($this->risposte_ammesse)) {
            global $con;
            $arr = [];
            $id_questionario = $this->id_questionario;
            $sql = "SELECT * FROM risposte_ammesse WHERE id_questionario = '".$this->id_questionario."' AND progressivo_sezione = '$this->progressivo_sezione' " .
                    " AND progressivo_domanda = '$this->progressivo_domanda' ORDER BY progressivo_risposta";
            if($result = mysqli_query($con, $sql)) {
                $cr = 0;
                while($row = mysqli_fetch_assoc($result))
                {
                    $obj = new RispostaAmmessa($this);
                    $obj->id_questionario       = $row['id_questionario'];
                    $obj->progressivo_sezione   = $row['progressivo_sezione'];
                    $obj->progressivo_domanda   = $row['progressivo_domanda'];
                    $obj->progressivo_risposta  = $row['progressivo_risposta'];
                    $obj->descrizione           = $row['descrizione'];
                    $obj->valore                = $row['valore'];
                    $arr[$cr++] = $obj;
                }
            } else {
                print_error(500, $con ->error);
            }
            $this->risposte_ammesse = $arr;
        }
        return $this->risposte_ammesse;
    }

    function get_risposta_ammessa($progressivo_risposta) {
        global $con;
        $obj = new RispostaAmmessa($this);
        $sql = "SELECT * FROM risposte_ammesse WHERE id_questionario = '$this->id_questionario' AND progressivo_sezione = '$this->progressivo_sezione' " .
                " AND progressivo_domanda = '$this->progressivo_domanda' AND progressivo_risposta='$progressivo_risposta'";
        if($result = mysqli_query($con, $sql)) {
            if($row = mysqli_fetch_assoc($result)) {
                $obj->id_questionario       = $row['id_questionario'];
                $obj->progressivo_sezione   = $row['progressivo_sezione'];
                $obj->progressivo_domanda   = $row['progressivo_domanda'];
                $obj->progressivo_risposta  = $row['progressivo_risposta'];
                $obj->descrizione           = $row['descrizione'];
                $obj->valore                = $row['valore'];
            } else {
                return null;
            }
        } else {
            print_error(500, $con ->error);
        }
        return $obj;
    }

    function is_domanda_aperta() {
        return !$this->get_risposte_ammesse();
    }

    /**
     * Se la domanda ha già una risposta, calcolo il punteggio 
     * della domanda come punteggio della risposta * coefficiente di valutazione 
     */
    function get_punteggio() {
        if ($this->risposta) {
            return $this->risposta->get_punteggio() * $this->coeff_valutazione;
        }
        return null;
    }
}

######################################################################################################

class RispostaAmmessa {
    private $_domanda;
    
    function __construct($domanda) {
        $this->_domanda = $domanda;
    }
    
    function get_questionario() {
        return $this->get_domanda()->get_sezione()->get_questionario();
    }
    
    function get_sezione() {
        return $this->get_domanda()->get_sezione();
    }

    function get_domanda() {
        global $sezioniManager;
        if (!$this->_domanda) {
            $this->_domanda = $sezioniManager->get_domanda($this->id_questionario, $this->progressivo_sezione, $this->progressivo_domanda);
        }
        return $this->_domanda;
    }
}

######################################################################################################

class SezioniManager {

    function get_sezione($id_questionario, $progressivo_sezione, $explode = true) {
        global $con;
        $obj = new Sezione(null);
        $sql = "SELECT * FROM sezioni WHERE id_questionario = '$id_questionario' AND progressivo_sezione = '$progressivo_sezione'";
        
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
            print_error(500, $con->error);
        }
        
        return $obj;
    }

    function crea($json_data) {
        global $con, $sezioniManager;
        $sql = insert("sezioni", ["id_questionario" => $json_data->id_questionario,
                                  "progressivo_sezione" => $json_data->progressivo_sezione,
                                  "titolo" => $con->escape_string($json_data->titolo),
                                  "descrizione" => $con->escape_string($json_data->descrizione)]);
        mysqli_query($con, $sql);
        if ($con ->error) {
            print_error(500, $con->error);
        }
        return $sezioniManager->get_sezione($json_data->id_questionario, $json_data->progressivo_sezione);
    }
    
    function aggiorna($sezione, $json_data) {
        global $con;
        $sql = update("sezioni", ["titolo" => $con->escape_string($json_data->titolo),
                                  "descrizione" => $con->escape_string($json_data->descrizione)],
                                 ["id_questionario" => $sezione->id_questionario,
                                  "progressivo_sezione" => $sezione->progressivo_sezione]);
        mysqli_query($con, $sql);
        if ($con ->error) {
            print_error(500, $con ->error);
        }
        
        $sezione->titolo = $json_data->titolo;
        $sezione->descrizione = $json_data->descrizione;
        return $sezione;
    }
    
    function elimina($id_questionario, $progressivo_sezione) {
        global $con;
        $sql = "DELETE FROM sezioni WHERE id_questionario = '$id_questionario' AND progressivo_sezione = '$progressivo_sezione'"; //on delete cascade!
        mysqli_query($con, $sql);
        if ($con ->error) {
            print_error(500, $con ->error);
        }
    }

    function duplica($sezione) {
        global $con, $sezioniManager;
        $nuovo_progressivo_sezione = $sezione->get_questionario()->get_prossima_sezione();
        $sql = insert_select("sezioni", ["id_questionario", "progressivo_sezione", "titolo", "descrizione"],
                                        ["progressivo_sezione" => $nuovo_progressivo_sezione],
                                        ["id_questionario" => $sezione->id_questionario,
                                        "progressivo_sezione" => $sezione->progressivo_sezione]
                                        );
        mysqli_query($con, $sql);
        if ($con ->error) {
            // Tipicamente, qui potrebbe esserci un problema di concorrenza
            print_error(500, $con ->error);
        }
        $this->_duplica_domande($sezione, $nuovo_progressivo_sezione);
        $this->_duplica_risposte($sezione, $nuovo_progressivo_sezione);
        return $sezioniManager->get_sezione($sezione->id_questionario, $nuovo_progressivo_sezione);
    }

    function _duplica_domande($sezione, $nuovo_progressivo_sezione) {
        global $con;
        $sql = insert_select("domande", ["id_questionario", "progressivo_sezione", "progressivo_domanda", "descrizione", "obbligatorieta", "coeff_valutazione", "html_type", "html_pattern", "html_min", "html_max", "html_maxlength", "rimescola"],
                                                 ["progressivo_sezione" => $nuovo_progressivo_sezione],
                                                 ["id_questionario" => $sezione->id_questionario,
                                                 "progressivo_sezione" => $sezione->progressivo_sezione]
                                                );
        mysqli_query($con, $sql);
        if ($con ->error) {
            // Tipicamente, qui potrebbe esserci un problema di concorrenza
            print_error(500, $con ->error);
        }
    }

    function _duplica_risposte($sezione, $nuovo_progressivo_sezione) {
        global $con;
        $sql = insert_select("risposte_ammesse", ["id_questionario", "progressivo_sezione", "progressivo_domanda", "progressivo_risposta", "descrizione", "valore"],
                                             ["progressivo_sezione" => $nuovo_progressivo_sezione],
                                             ["id_questionario" => $sezione->id_questionario,
                                             "progressivo_sezione" => $sezione->progressivo_sezione]
                                            );
        mysqli_query($con, $sql);
        if ($con ->error) {
            // Tipicamente, qui potrebbe esserci un problema di concorrenza
            print_error(500, $con ->error);
        }
    }

    function duplica_domanda($domanda) {
        global $con;
        $sezione = $domanda->get_sezione();
        $nuovo_progressivo_domanda = $sezione->get_prossima_domanda($domanda->id_questionario, $domanda->progressivo_sezione);
        $sql = insert_select("domande", ["id_questionario", "progressivo_sezione", "progressivo_domanda", "descrizione", "obbligatorieta", "coeff_valutazione", "html_type", "html_pattern", "html_min", "html_max", "html_maxlength", "rimescola"],
                                             ["progressivo_domanda" => $nuovo_progressivo_domanda],
                                             ["id_questionario" => $domanda->id_questionario,
                                             "progressivo_sezione" => $domanda->progressivo_sezione,
                                             "progressivo_domanda" => $domanda->progressivo_domanda]
                                            );
        mysqli_query($con, $sql);
        if ($con ->error) {
            // Tipicamente, qui potrebbe esserci un problema di concorrenza
            print_error(500, $con ->error);
        }
        $this->_duplica_risposte_domanda($domanda, $nuovo_progressivo_domanda);
        return $sezione->get_domanda($nuovo_progressivo_domanda);
    }

    function _duplica_risposte_domanda($domanda, $nuovo_progressivo_domanda) {
        global $con;
        $sql = insert_select("risposte_ammesse", ["id_questionario", "progressivo_sezione", "progressivo_domanda", "progressivo_risposta", "descrizione", "valore"],
                                                 ["progressivo_domanda" => $nuovo_progressivo_domanda],
                                                 ["id_questionario" => $domanda->id_questionario,
                                                 "progressivo_sezione" => $domanda->progressivo_sezione,
                                                 "progressivo_domanda" => $domanda->progressivo_domanda]
                                                );
        mysqli_query($con, $sql);
        if ($con ->error) {
            // Tipicamente, qui potrebbe esserci un problema di concorrenza
            print_error(500, $con ->error);
        }
    }

    function creaDomandaERisposte($sezione, $json_data) {
        global $con, $sezioniManager;
        $progressivo_domanda = $sezione->get_prossima_domanda();
        $sql = insert("domande", ["id_questionario" => $json_data->id_questionario,
                                    "progressivo_sezione" => $json_data->progressivo_sezione,
                                    "progressivo_domanda" => $progressivo_domanda,
                                    "descrizione" => $con->escape_string($json_data->descrizione),
                                    "obbligatorieta" => ($json_data->obbligatorieta == true ? '1' : '0'),
                                    "coeff_valutazione" => $json_data->coeff_valutazione,
                                    "html_type" => $json_data->html_type,
                                    "html_pattern" => $json_data->html_pattern,
                                    "html_min" => $json_data->html_min,
                                    "html_max" => $json_data->html_max,
                                    "html_maxlength" => $json_data->html_maxlength,
                                    "rimescola" => ($json_data->rimescola == true ? '1' : '0')]);
        mysqli_query($con, $sql);
        if ($con ->error) {
            print_error(500, $con ->error);
        }
        $this->_insert_risposte($json_data->risposte);
        return $sezioniManager->get_domanda($sezione->id_questionario, $json_data->progressivo_sezione, $progressivo_domanda);
    }
    function _insert_risposte($risposte) {
        global $con, $sezioniManager;
        if(isset($risposte) && $risposte != null){
            foreach ($risposte as $r) {
                $sql = insert("risposte_ammesse", ["id_questionario" => $r->id_questionario,
                                                    "progressivo_sezione" => $r->progressivo_sezione,
                                                    "progressivo_domanda" => $r->progressivo_domanda,
                                                    "progressivo_risposta" => $r->progressivo_risposta,
                                                    "descrizione" => $con->escape_string($r->descrizione),
                                                    "valore" => $r->valore]);
                mysqli_query($con, $sql);
                if ($con ->error) {
                    print_error(500, $con ->error);
                }
            }
        }
        
    }
    function aggiornaDomandaERisposte($domanda, $json_data) {
        global $con, $sezioniManager;
        $sql = update("domande", [ "descrizione" => $con->escape_string($json_data->descrizione),
                                    "obbligatorieta" => ($json_data->obbligatorieta == true ? '1' : '0'),
                                    "coeff_valutazione" => $json_data->coeff_valutazione,
                                    "html_type" => $json_data->html_type,
                                    "html_pattern" => $json_data->html_pattern,
                                    "html_min" => $json_data->html_min,
                                    "html_max" => $json_data->html_max,
                                    "html_maxlength" => $json_data->html_maxlength,
                                    "rimescola" => ($json_data->rimescola == true ? '1' : '0')],
                                 [ "id_questionario" => $json_data->id_questionario,
                                 "progressivo_sezione" => $json_data->progressivo_sezione,
                                 "progressivo_domanda" => $json_data->progressivo_domanda]);
        mysqli_query($con, $sql);
        if ($con ->error) {
            print_error(500, $con ->error);
        }
        // faccio DELETE e INSERT
        $sql = "DELETE FROM risposte_ammesse WHERE id_questionario='$json_data->id_questionario' AND ".
                "progressivo_sezione='$json_data->progressivo_sezione' AND progressivo_domanda='$json_data->progressivo_domanda'";
        mysqli_query($con, $sql);
        if ($con ->error) {
            print_error(500, $con ->error);
        }
        $this->_insert_risposte($json_data->risposte);
        return $sezioniManager->get_domanda($json_data->id_questionario, $json_data->progressivo_sezione, $json_data->progressivo_domanda);
    }
    
    function eliminaDomandaERisposte($id_questionario, $progressivo_sezione, $progressivo_domanda) {
        global $con;
        $sql = "DELETE FROM domande WHERE id_questionario = '$id_questionario' AND progressivo_sezione = '$progressivo_sezione' ".
            "AND progressivo_domanda = '$progressivo_domanda'"; // on delete cascade!!!
        mysqli_query($con, $sql);
        if ($con ->error) {
            print_error(500, $con ->error);
        }
    }
    
    function get_domanda($id_questionario, $progressivo_sezione, $progressivo_domanda, $explode = true) {
        global $con, $BOOLEAN, $HTML_TYPE;
        $domanda = new Domanda(null);
        $sql = "SELECT * FROM domande WHERE id_questionario = '$id_questionario' AND progressivo_sezione = '$progressivo_sezione' " .
                "AND progressivo_domanda = '$progressivo_domanda'";

        if($result = mysqli_query($con, $sql)) {
            if($row = mysqli_fetch_assoc($result)) {
                $domanda->id_questionario       = $row['id_questionario'];
                $domanda->progressivo_sezione   = $row['progressivo_sezione'];
                $domanda->progressivo_domanda   = $row['progressivo_domanda'];
                $domanda->descrizione           = $row['descrizione'];
                $domanda->obbligatorieta        = ($row['obbligatorieta'] == '1' ? true : false);
                $domanda->obbligatorieta_dec    = ($row['obbligatorieta'] != null) ? $BOOLEAN[$row['obbligatorieta']] : null;
                $domanda->rimescola             = ($row['rimescola'] == '1' ? true : false);
                $domanda->rimescola_dec         = ($row['rimescola'] != null) ? $BOOLEAN[$row['rimescola']] : null;
                $domanda->coeff_valutazione     = $row['coeff_valutazione'];
                $domanda->html_type             = $row['html_type'];
                $domanda->html_type_dec         = ($row['html_type'] != null) ? $HTML_TYPE[$row['html_type']] : null;
                $domanda->html_pattern          = $row['html_pattern'];
                $domanda->html_min              = $row['html_min'];
                $domanda->html_max              = $row['html_max'];
                $domanda->html_maxlength        = $row['html_maxlength'];
                if ($explode) {
                    $domanda->risposte  = $domanda->get_risposte_ammesse();
                }
            } else {
                return null;
            }
        } else {
            print_error(500, $con ->error);
        }
        return $domanda;
    }
    function CleanString($s){
        return str_replace("'","''",$s);
    }
}


?>