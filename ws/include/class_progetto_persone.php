<?php

$progettiPersoneManager = new ProgettiPersoneManager();

class ProgettiPersoneManager {
    
    function get_persone($id_progetto) {
        $arr = array();
        $sql = "SELECT g.*
                    FROM progetti_persone p
                    JOIN persone_globali g ON p.ID_DIPENDENTE=g.ID_DIPENDENTE
                    WHERE p.ID_PROGETTO = '$id_progetto'";
        $arr = select_list($sql);
        return $arr;
    }
    
    function get_persona($id_progetto, $matricola) {
        $arrProgettiWp = array();
        $sql = "SELECT g.*
                    FROM progetti_persone
                    JOIN persone_globali g ON p.ID_DIPENDENTE=g.ID_DIPENDENTE
                    WHERE p.ID_PROGETTO = '$id_progetto' and p.ID_DIPENDENTE='$matricola'";
        $obj = select_single($sql);
        return $obj;
    }
    
    function crea($json_data) {
        global $con;

        $sql = insert("progetti_persone", [
                                    "ID_PROGETTO" => $json_data->ID_PROGETTO,
                                    "ID_DIPENDENTE" => $con->escape_string($json_data->ID_DIPENDENTE),
                                    "PCT_IMPIEGO" => $json_data->PCT_IMPIEGO
                                  ]);
        execute_update($sql);
        return $this->get_persona($json_data->ID_PROGETTO, $json_data->ID_DIPENDENTE);
    }
    
    function aggiorna($progetto, $json_data) {
        global $con;

        $sql = update("progetti_persone", [
                                    "PCT_IMPIEGO" => $json_data->PCT_IMPIEGO
                                  ], [
                                    "ID_PROGETTO" => $json_data->ID_PROGETTO,
                                    "ID_DIPENDENTE" => $con->escape_string($json_data->ID_DIPENDENTE)
                                  ]);
        execute_update($sql);        
        return $this->get_persona($json_data->ID_PROGETTO, $json_data->ID_DIPENDENTE);
    }
    
    function elimina($id_progetto, $matricola) {
        $sql = "DELETE FROM progetti_persone WHERE ID_DIPENDENTE = '$matricola' AND id_progetto = '$id_progetto'";
        execute_update($sql);
    }

}
?>