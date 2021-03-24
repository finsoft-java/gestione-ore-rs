<?php

$progettiManager = new ProgettiManager();

class ProgettiManager {
    
    function get_progetti($skip=null, $top=null, $orderby=null) {
        global $con;
        
        $sql0 = "SELECT COUNT(*) AS cnt ";
        $sql1 = "SELECT * ";
        $sql = "FROM progetti p ";
        
        if ($orderby && preg_match("/^[a-zA-Z0-9,_ ]+$/", $orderby)) {
            // avoid SQL-injection
            $sql .= " ORDER BY $orderby";
        } else {
            $sql .= " ORDER BY p.id_progetto DESC";
        }

        $count = select_single_value($sql0 . $sql);

        if ($top != null){
            if ($skip != null) {
                $sql .= " LIMIT $skip,$top";
            } else {
                $sql .= " LIMIT $top";
            }
        }        
        $progetti = select_list($sql1 . $sql);
        
        return [$progetti, $count];
    }
    
    function get_progetto($id_progetto) {
        $sql = "SELECT * FROM progetti p WHERE id_progetto = '$id_progetto'";
        return select_single($sql);
    }
    
    function get_progetto_wp($id_progetto, $anno=null, $mese=null) {
        $sql = "SELECT * FROM progetti_wp WHERE id_progetto = '$id_progetto' ";
        
        if (!empty($anno) and !empty($mese)) {
            $primo = "DATE('$anno-$mese-01')";
            $query .= "AND DATA_FINE >= $primo AND DATA_INIZIO <= LAST_DAY($primo)";
        }
        
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