<?php

$progettiCommesseManager = new ProgettiCommesseManager();

class ProgettiCommesseManager {
    
    /*function get_commesse($id_progetto) {
        $sql = "SELECT pc.ID_PROGETTO,pc.COD_COMMESSA,c.PCT_COMPATIBILITA,c.NOTE,c.GIUSTIFICATIVO_FILENAME,
                CASE WHEN c.GIUSTIFICATIVO IS NULL THEN 'N' ELSE 'Y' END AS HAS_GIUSTIFICATIVO
                FROM progetti_commesse pc
                JOIN commesse c ON c.COD_COMMESSA=pc.COD_COMMESSA AND 
                WHERE pc.ID_PROGETTO = '$id_progetto' AND pc.ORE_PREVISTE>0
                ORDER BY pc.COD_COMMESSA";
        $arr = select_list($sql);
        return $arr;
    }
    
    function get_commessa($id_progetto, $codCommessa) {
        $sql = "SELECT pc.ID_PROGETTO,pc.COD_COMMESSA,c.PCT_COMPATIBILITA,c.NOTE,c.GIUSTIFICATIVO_FILENAME,
                CASE WHEN c.GIUSTIFICATIVO IS NULL THEN 'N' ELSE 'Y' END AS HAS_GIUSTIFICATIVO
                FROM progetti_commesse pc
                JOIN commesse c ON c.COD_COMMESSA=pc.COD_COMMESSA 
                WHERE pc.ID_PROGETTO = '$id_progetto' and pc.COD_COMMESSA ='$codCommessa'";
        $obj = select_single($sql);
        return $obj;
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
    }*/

}
?>