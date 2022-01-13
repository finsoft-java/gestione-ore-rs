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

    function crea($json_data) {
        global $con, $logged_user;
		
		$dataUltRep = null;
		if ($json_data->DATA_ULTIMO_REPORT) {
			// mi aspetto una data del tipo 2020-05-31T22:00:00.000Z
			// non funziona... c'è un baco nella conversione della data, decresce sempre di 1g
			$dataUltRep = substr($con->escape_string($json_data->DATA_ULTIMO_REPORT),0,10);
        }

        $sql = insert("progetti", ["ID_PROGETTO" => null,
                                   "TITOLO" => $con->escape_string($json_data->TITOLO),
                                   "ACRONIMO" => $con->escape_string($json_data->ACRONIMO),
                                   "GRANT_NUMBER" => $con->escape_string($json_data->GRANT_NUMBER),
                                   "ABSTRACT" => $con->escape_string($json_data->ABSTRACT),
                                   "MONTE_ORE_TOT" => $json_data->MONTE_ORE_TOT,
                                   "OBIETTIVO_BUDGET_ORE" => $json_data->OBIETTIVO_BUDGET_ORE,
                                   "DATA_INIZIO" => $json_data->DATA_INIZIO,
                                   "DATA_FINE" => $json_data->DATA_FINE,
                                   "COSTO_MEDIO_UOMO" => $json_data->COSTO_MEDIO_UOMO,
                                   "COD_TIPO_COSTO_PANTHERA" => $json_data->COD_TIPO_COSTO_PANTHERA,
                                   "ID_SUPERVISOR" => $json_data->ID_SUPERVISOR,
                                   "ORE_GIA_ASSEGNATE" => $json_data->ORE_GIA_ASSEGNATE,
                                   "DATA_ULTIMO_REPORT" => $dataUltRep
                                  ]);
        execute_update($sql);
        $id_progetto = mysqli_insert_id($con);
        return $this->get_progetto($id_progetto);
    }
    
    function aggiorna($progetto, $json_data) {
        global $con, $STATO_PROGETTO;
        $titolo = $con->escape_string($json_data->TITOLO);
		
		$dataUltRep = null;
		if ($json_data->DATA_ULTIMO_REPORT) {
			// mi aspetto una data del tipo 2020-05-31T22:00:00.000Z
			// non funziona... c'è un baco nella conversione della data, decresce sempre di 1g
			// $dataUltRep = substr($con->escape_string($json_data->DATA_ULTIMO_REPORT),0,10);
			$dataUltRep = $con->escape_string($json_data->DATA_ULTIMO_REPORT);
        }
		
        $sql = update("progetti", [
                                    "TITOLO" => $con->escape_string($json_data->TITOLO),
                                    "ACRONIMO" => $con->escape_string($json_data->ACRONIMO),
                                    "GRANT_NUMBER" => $con->escape_string($json_data->GRANT_NUMBER),
                                    "ABSTRACT" => $con->escape_string($json_data->ABSTRACT),
                                    "MONTE_ORE_TOT" => $json_data->MONTE_ORE_TOT,
                                    "OBIETTIVO_BUDGET_ORE" => $json_data->OBIETTIVO_BUDGET_ORE,
                                    "DATA_INIZIO" => $json_data->DATA_INIZIO,
                                    "DATA_FINE" => $json_data->DATA_FINE,
                                    "COSTO_MEDIO_UOMO" => $json_data->COSTO_MEDIO_UOMO,
                                    "COD_TIPO_COSTO_PANTHERA" => $json_data->COD_TIPO_COSTO_PANTHERA,
                                    "ID_SUPERVISOR" => $json_data->ID_SUPERVISOR,
                                    "ORE_GIA_ASSEGNATE" => $json_data->ORE_GIA_ASSEGNATE,
                                    "DATA_ULTIMO_REPORT" => $dataUltRep
                                  ], ["ID_PROGETTO" => $json_data->ID_PROGETTO]);
        execute_update($sql);
    }
    
    function elimina($id_progetto) {
        $sql = "DELETE FROM progetti WHERE id_progetto = '$id_progetto'";  //on delete cascade! (FIXME funziona anche con i questionari?!?)
        execute_update($sql);
    }

}
?>