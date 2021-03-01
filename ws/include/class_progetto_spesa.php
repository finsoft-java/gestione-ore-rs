<?php

$progettiSpesaManager = new ProgettiSpesaManager();

class ProgettoSpesa {
    private $_progetto;
    
    function get_progetto() {
        global $progettiSpesaManager;
        if (!$this->_progetto) {
            $this->_progetto = $progettiSpesaManager->get_progetto($this->id_progetto);
        }
        return $this->_progetto;
    }
}

class ProgettiSpesaManager {
    
    function get_progetto($id_progetto) {
        global $con, $STATO_PROGETTO, $BOOLEAN;
        $progetto = new Progetto();
        $sql = "SELECT * FROM progetti_spese p WHERE id_progetto = '$id_progetto'";
    }   

    function crea($json_data) {
        global $con, $logged_user;
        $sql = insert("progetti", ["ID_PROGETTO" => null,
                                   "TITOLO" => $con->escape_string($json_data->TITOLO),
                                   "ACRONIMO" => $json_data->ACRONIMO,
                                   "GRANT_NUMBER" => $json_data->GRANT_NUMBER,
                                   "ABSTRACT" => $json_data->ABSTRACT,
                                   "MONTE_ORE_TOT" => $json_data->MONTE_ORE_TOT,
                                   "DATA_INIZIO" => $json_data->DATA_INIZIO,
                                   "DATA_FINE" => $json_data->DATA_FINE,
                                   "COSTO_MEDIO_UOMO" => $json_data->COSTO_MEDIO_UOMO,
                                   "COD_TIPO_COSTO_PANTHERA" => $json_data->COD_TIPO_COSTO_PANTHERA,
                                   "MATRICOLA_SUPERVISOR" => $json_data->MATRICOLA_SUPERVISOR
                                  ]);
        mysqli_query($con, $sql);
        if ($con ->error) {
            print_error(500, $con ->error);
        }
        $id_progetto = mysqli_insert_id($con);
        return $this->get_progetto($id_progetto);
    }
    
    function aggiorna($progetto, $json_data) {
        global $con, $STATO_PROGETTO;
        $titolo = $con->escape_string($json_data->TITOLO);
        
        $sql = update("progetti", [
                                    "TITOLO" => $con->escape_string($json_data->TITOLO),
                                    "ACRONIMO" => $json_data->ACRONIMO,
                                    "GRANT_NUMBER" => $json_data->GRANT_NUMBER,
                                    "ABSTRACT" => $json_data->ABSTRACT,
                                    "MONTE_ORE_TOT" => $json_data->MONTE_ORE_TOT,
                                    "DATA_INIZIO" => $json_data->DATA_INIZIO,
                                    "DATA_FINE" => $json_data->DATA_FINE,
                                    "COSTO_MEDIO_UOMO" => $json_data->COSTO_MEDIO_UOMO,
                                    "COD_TIPO_COSTO_PANTHERA" => $json_data->COD_TIPO_COSTO_PANTHERA,
                                    "MATRICOLA_SUPERVISOR" => $json_data->MATRICOLA_SUPERVISOR
                                  ], ["ID_PROGETTO" => $json_data->ID_PROGETTO]);
        mysqli_query($con, $sql);
        if ($con ->error) {
            print_error(500, $con ->error);
        }
    }
    
    function elimina($id_progetto) {
        global $con;
        $sql = "DELETE FROM progetti WHERE id_progetto = '$id_progetto'";  //on delete cascade! (FIXME funziona anche con i questionari?!?)
        mysqli_query($con, $sql);
        if ($con ->error) {
            print_error(500, $con ->error);
        }
    }

}
?>