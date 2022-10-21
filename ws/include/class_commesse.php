<?php

$commesseManager = new CommesseManager();

class CommesseManager {
    
    function get_commesse() {
        $sql = "SELECT ID_PROGETTO,COD_COMMESSA,PCT_COMPATIBILITA,NOTE,GIUSTIFICATIVO_FILENAME,
                CASE WHEN GIUSTIFICATIVO IS NULL THEN 'N' ELSE 'Y' END AS HAS_GIUSTIFICATIVO
                FROM commesse ";
        $arr = select_list($sql);
    
        foreach($arr as $id => $codCommessa) {
            $arr[$id]["PROGETTI"] = $this->get_progetti($cod_commessa);
        }
        return $arr;
    }

    function get_commessa($codCommessa) {
        $sql = "SELECT COD_COMMESSA,PCT_COMPATIBILITA,NOTE,GIUSTIFICATIVO_FILENAME,
                CASE WHEN GIUSTIFICATIVO IS NULL THEN 'false' ELSE 'true' END AS HAS_GIUSTIFICATIVO
                FROM commesse
                WHERE COD_COMMESSA='$codCommessa'";
        $obj = select_single($sql);
        if ($obj) {
            $obj["PROGETTI"] = $this->get_progetti($cod_commessa);
        }
        return $obj;
    }
    
    function get_progetti($cod_commessa) {
        $sql = "SELECT ID_PROGETTO,ORE_PREVISTE
                FROM progetti_commesse
                WHERE COD_COMMESSA='$cod_commessa'";
        $arr = select_list($sql);
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

    function elimina($codCommessa) {
        $sql = "DELETE FROM commesse WHERE AND cod_commessa = '$codCommessa'";
        execute_update($sql);
    }
    
    function upload_giustificativo($codCommessa, $tmpfilename, $origfilename) {
        global $con;

        $fileContent = addslashes(file_get_contents($tmpfilename)); 
        // speriamo non sia enorme

        $origfilename = $con->escape_string($origfilename);
        $sql = "UPDATE commesse
                SET GIUSTIFICATIVO_FILENAME='$origfilename', GIUSTIFICATIVO='$fileContent'
                WHERE cod_commessa = '$codCommessa'";
        execute_update($sql);
    }
    
    function download_giustificativo($codCommessa) {
        $sql = "SELECT GIUSTIFICATIVO_FILENAME, LENGTH(GIUSTIFICATIVO) AS LEN, GIUSTIFICATIVO
                FROM commesse
                WHERE cod_commessa = '$codCommessa'";
        $result = select_single($sql);

        header("Content-length: $result[LEN]");
        // header("Content-type: ???");
        header("Content-Disposition: attachment; filename=$result[GIUSTIFICATIVO_FILENAME]");
        ob_clean();
        flush();
        echo $result["GIUSTIFICATIVO"];
    }
    
    function elimina_giustificativo($codCommessa) {
        $sql = "UPDATE commesse
                SET GIUSTIFICATIVO=NULL,GIUSTIFICATIVO_FILENAME=NULL
                WHERE cod_commessa = '$codCommessa'";
        execute_update($sql);
    }

    function importExcel($filename, &$message, $typeFile) {
        //copiare dai LUL ?
    }

}
?>