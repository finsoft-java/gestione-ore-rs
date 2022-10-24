<?php

$partecipantiManager = new PartecipantiManager();

class PartecipantiManager {
    
    function get_partecipanti($skip=null, $top=null, $orderby=null) {
        global $con;
        
        $sql0 = "SELECT COUNT(*) AS cnt ";
        $sql1 = "SELECT * ";
        $sql = "FROM partecipanti_globali p ";
        
        if ($orderby && preg_match("/^[a-zA-Z0-9,_ ]+$/", $orderby)) {
            // avoid SQL-injection
            $sql .= " ORDER BY $orderby";
        } else {
            $sql .= " ORDER BY p.id_dipendente DESC";
        }

        $count = select_single_value($sql0 . $sql);

        if ($top != null){
            if ($skip != null) {
                $sql .= " LIMIT $skip,$top";
            } else {
                $sql .= " LIMIT $top";
            }
        }        
        $data = select_list($sql1 . $sql);
        
        return [$data, $count];
    }
    
    function get_partecipante($id_dipendente) {
        $sql = "SELECT * FROM partecipanti_globali p WHERE id_dipendente = '$id_dipendente'";
        return select_single($sql);
    }

    function crea($json_data) {
        global $con, $logged_user;
        $id = $con->escape_string($json_data->ID_DIPENDENTE);
        $sql = insert("partecipanti_globali", ["ID_DIPENDENTE" => $id,
                                   "MANSIONE" => $con->escape_string($json_data->MANSIONE),
                                   "COSTO" => $con->escape_string($json_data->COSTO),
                                   "PCT_UTILIZZO" => $con->escape_string($json_data->PCT_UTILIZZO)
                                  ]);
        execute_update($sql);
        return $this->get_partecipante($id);
    }
    
    function aggiorna($progetto, $json_data) {

        global $con;
		
        $sql = update("partecipanti_globali", [
                                    "MANSIONE" => $con->escape_string($json_data->MANSIONE),
                                    "COSTO" => $con->escape_string($json_data->COSTO),
                                    "PCT_UTILIZZO" => $con->escape_string($json_data->PCT_UTILIZZO)
                                    ], ["ID_DIPENDENTE" => $con->escape_string($json_data->ID_DIPENDENTE)]);

        execute_update($sql);
    }
    
    function elimina($id_dipendente) {
        $sql = "DELETE FROM partecipanti_globali WHERE id_dipendente = '$id_dipendente'";
        execute_update($sql);
    }

    function importExcel($filename, &$message, $typeFile) {
        //copiare dai LUL ?
    }
}
?>