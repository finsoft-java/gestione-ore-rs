<?php

$progettiCommesseManager = new ProgettiCommesseManager();

class ProgettiCommesseManager {
    
    function get_commesse($id_progetto) {
        $arr = array();
        $sql = "SELECT ID_PROGETTO,COD_COMMESSA,PCT_COMPATIBILITA,NOTE,GIUSTIFICATIVO_FILENAME,
                CASE WHEN GIUSTIFICATIVO IS NULL THEN 'N' ELSE 'Y' END AS HAS_GIUSTIFICATIVO
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

        $this->controllo_pct_commessa($json_data);

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

        $this->controllo_pct_commessa($json_data);

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
    
    function controllo_pct_commessa($json_data) {
        if ($json_data->PCT_COMPATIBILITA >= 100) {
            // commessa di progetto
            $sql = "SELECT count(*)
                    FROM progetti_commesse
                    WHERE id_progetto <> '$json_data->ID_PROGETTO' and cod_commessa='$json_data->COD_COMMESSA'";
            $count = select_single_value($sql);
            if ($count > 0) {
                print_error(400, "La commessa $json_data->COD_COMMESSA &egrave; gi&agrave; utilizzata in altri progetti");
            }
        } else {
            // commessa compatibile
            $sql = "SELECT sum(PCT_COMPATIBILITA)
                    FROM progetti_commesse
                    WHERE id_progetto <> '$json_data->ID_PROGETTO' and cod_commessa='$json_data->COD_COMMESSA'";
            $pct = select_single_value($sql);
            if ($pct +  $json_data->PCT_COMPATIBILITA > 100.01) {
                print_error(400, "La commessa $json_data->COD_COMMESSA &egrave; gi&agrave; utilizzata al $pct% in altri progetti");
            }
        }
    }

    function elimina($id_progetto, $codCommessa) {
        $sql = "DELETE FROM progetti_commesse WHERE id_progetto = '$id_progetto' AND cod_commessa = '$codCommessa'";
        execute_update($sql);
    }
    
    function upload_giustificativo($id_progetto, $codCommessa, $tmpfilename, $origfilename) {
        global $con;

        $fileContent = addslashes(file_get_contents($tmpfilename)); 
        // speriamo non sia enorme

        $origfilename = $con->escape_string($origfilename);
        $sql = "UPDATE progetti_commesse
                SET GIUSTIFICATIVO_FILENAME='$origfilename', GIUSTIFICATIVO='$fileContent'
                WHERE id_progetto = '$id_progetto' AND cod_commessa = '$codCommessa'";
        execute_update($sql);
    }
    
    function download_giustificativo($id_progetto, $codCommessa) {
        $sql = "SELECT GIUSTIFICATIVO_FILENAME, LENGTH(GIUSTIFICATIVO) AS LEN, GIUSTIFICATIVO
                FROM progetti_commesse
                WHERE id_progetto = '$id_progetto' AND cod_commessa = '$codCommessa'";
        $result = select_single($sql);

        header("Content-length: $result[LEN]");
        // header("Content-type: ???");
        header("Content-Disposition: attachment; filename=$result[GIUSTIFICATIVO_FILENAME]");
        ob_clean();
        flush();
        echo $result["GIUSTIFICATIVO"];
    }
    
    function elimina_giustificativo($id_progetto, $codCommessa) {
        $sql = "UPDATE progetti_commesse
                SET GIUSTIFICATIVO=NULL,GIUSTIFICATIVO_FILENAME=NULL
                WHERE id_progetto = '$id_progetto' AND cod_commessa = '$codCommessa'";
        execute_update($sql);
    }

}
?>