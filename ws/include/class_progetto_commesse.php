<?php

$progettiCommesseManager = new ProgettiCommesseManager();

class ProgettiCommesseManager {
    
    function get_commesse($id_progetto) {
        $arr = array();
        $sql = "SELECT * FROM progetti_commesse WHERE id_progetto = '$id_progetto'";
        $arr = select_list($sql);
        return $arr;
    }
    
    function get_commessa($id_progetto, $codCommessa) {
        $arrProgettiWp = array();
        $sql = "SELECT * FROM progetti_commesse WHERE id_progetto = '$id_progetto' and cod_commessa='$codCommessa'";
        $obj = select_single($sql);
        return $obj;
    }
    
    function crea($json_data) {
        global $con;

        $sql = insert("progetti_commesse", [
                                    "ID_PROGETTO" => $json_data->ID_PROGETTO,
                                    "COD_COMMESSA" => $con->escape_string($json_data->COD_COMMESSA),
                                    "PCT_COMPATIBILITA" => $json_data->PCT_COMPATIBILITA,
                                    "NOTE" => $con->escape_string($json_data->NOTE)
                                  ]);
        execute_update($sql);
        return $this->get_commessa($json_data->ID_PROGETTO, $json_data->COD_COMMESSA);
    }
    
    function aggiorna($progetto, $json_data) {
        global $con;

        $sql = update("progetti_commesse", [
                                    "PCT_COMPATIBILITA" => $json_data->PCT_COMPATIBILITA,
                                    "NOTE" => $con->escape_string($json_data->NOTE)
                                  ], [
                                    "ID_PROGETTO" => $json_data->ID_PROGETTO,
                                    "COD_COMMESSA" => $con->escape_string($json_data->COD_COMMESSA)
                                  ]);
        execute_update($sql);        
        return $this->get_commessa($json_data->ID_PROGETTO, $json_data->COD_COMMESSA);
    }
    
    function elimina($id_progetto, $codCommessa) {
        $sql = "DELETE FROM progetti_commesse WHERE id_progetto = '$id_progetto' AND cod_commessa = '$codCommessa'";
        execute_update($sql);
    }

}
?>