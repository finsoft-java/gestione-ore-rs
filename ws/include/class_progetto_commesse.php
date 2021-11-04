<?php

$progettiCommesseManager = new ProgettiCommesseManager();

class ProgettiCommesseManager {
    
    function get_commesse($id_progetto) {
        $arr = array();
        $sql = "SELECT ID_PROGETTO,COD_COMMESSA,PCT_COMPATIBILITA,NOTE,GIUSTIFICATIVO_FILENAME,
                CASE WHEN GIUSTIFICATIVO IS NULL THEN 'false' ELSE 'true' END AS HAS_GIUSTIFICATIVO
                FROM progetti_commesse WHERE id_progetto = '$id_progetto'";
        $arr = select_list($sql);
        return $arr;
    }
    
    function get_commessa($id_progetto, $codCommessa) {
        $arrProgettiWp = array();
        $sql = "SELECT ID_PROGETTO,COD_COMMESSA,PCT_COMPATIBILITA,NOTE,GIUSTIFICATIVO_FILENAME,
                CASE WHEN GIUSTIFICATIVO IS NULL THEN 'false' ELSE 'true' END AS HAS_GIUSTIFICATIVO
                FROM progetti_commesse WHERE id_progetto = '$id_progetto' and cod_commessa='$codCommessa'";
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
    
    function upload_giustificativo($id_progetto, $codCommessa, $tmpfilename, $origfilename) {
        global $con;
        $tmpfilename = str_replace("\\", "/", $tmpfilename);
        $origfilename = $con->escape_string($origfilename);
        $sql = "UPDATE progetti_commesse
                SET GIUSTIFICATIVO_FILENAME='$origfilename', GIUSTIFICATIVO=LOAD_FILE('$tmpfilename')
                WHERE id_progetto = '$id_progetto' AND cod_commessa = '$codCommessa'";
        execute_update($sql);
    }
    
    function download_giustificativo($id_progetto, $codCommessa) {
        $sql = "SELECT GIUSTIFICATIVO_FILENAME, LENGTH(GIUSTIFICATIVO), GIUSTIFICATIVO
                WHERE id_progetto = '$id_progetto' AND cod_commessa = '$codCommessa'";
        list($origfilename, $size, $content) = execute_query($sql);
        $type = mime_content_type($origfilename); // works if file does not exists?
        header("Content-length: $size");
        header("Content-type: $type");
        header("Content-Disposition: attachment; filename=$origfilename");
        ob_clean();
        flush();
        echo $content;
    }
    
    function elimina_giustificativo($id_progetto, $codCommessa) {
        $sql = "UPDATE progetti_commesse
                SET GIUSTIFICATIVO=NULL,GIUSTIFICATIVO_FILENAME=NULL
                WHERE id_progetto = '$id_progetto' AND cod_commessa = '$codCommessa'";
        execute_update($sql);
    }

}
?>