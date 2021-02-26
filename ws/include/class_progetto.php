<?php

$progettiManager = new ProgettiManager();

class Progetto {
    private $_progetto;
    
    function get_progetto() {
        global $progettiManager;
        if (!$this->_progetto) {
            $this->_progetto = $progettiManager->get_progetto($this->id_progetto);
        }
        return $this->_progetto;
    }
}

class ProgettiManager {
    
    function get_progetti($top=null, $skip=null, $orderby=null, $search=null, $mostra_solo_validi=false) {
        global $con, $STATO_PROGETTO, $BOOLEAN;
        $arr = array();
        $sql1 = "SELECT * ";
        $sql0 = "SELECT COUNT(*) AS cnt ";
        $sql = "FROM progetti p ";
        
        if ($orderby && preg_match("/^[a-zA-Z0-9,_ ]+$/", $orderby)) {
            // avoid SQL-injection
            $sql .= " ORDER BY $orderby";
        } else {
            $sql .= " ORDER BY p.id_progetto DESC";
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
                $progetto->idProgetto        = $row['ID_PROGETTO'];
                $progetto->acronimo             = $row['ACRONIMO'];
                $progetto->titolo              = $row['TITOLO'];
                $progetto->grantNumber          = $row['GRANT_NUMBER'];
                $progetto->abstract      = $row['ABSTRACT'];
                $progetto->monteOreTot  = $row['MONTE_ORE_TOT'];
                $progetto->dataInizio   = $row['DATA_INIZIO'];
                $progetto->dataFine               = $row['DATA_FINE'];
                $progetto->costoMedioUomo            = $row['COSTO_MEDIO_UOMO'];
                $progetto->codTipoCostoPanthera     = $row['COD_TIPO_COSTO_PANTHERA'];
                $progetto->matricolaSupervisor     = $row['MATRICOLA_SUPERVISOR'];
                $arr[$cr++] = $progetto;
            }
        } else {
            print_error(500, $con ->error);
        }
        return [$arr, $count];
    }
    
    function get_progetto($id_progetto) {
        global $con, $STATO_PROGETTO, $BOOLEAN;
        $progetto = new Progetto();
        $sql = "SELECT * FROM progetti p WHERE id_progetto = '$id_progetto'";
        return select_list($sql);
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