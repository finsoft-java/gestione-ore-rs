<?php

$commesseManager = new CommesseManager();

    //configurazione prime 5 colonne file excel
    define('COL_TIPO_COMMESSA',  0);
    define('COL_COD_COMMESSA',   1);
    define('COL_TOT_ORE',        2);
    define('COL_PCT_RD',         3);
    define('COL_TOT_ORE_RD',     4);

class CommesseManager {
    
    function get_commesse() {
        $sql = "SELECT c.*,
                CASE WHEN GIUSTIFICATIVO IS NULL THEN 'N' ELSE 'Y' END AS HAS_GIUSTIFICATIVO
                FROM commesse c";
        $arr = select_list($sql);
    
        foreach($arr as $id => $commessa) {
            $arr[$id]["PROGETTI"] = $this->get_progetti($commessa["COD_COMMESSA"]);
        }
        return $arr;
    }

    function get_commessa($codCommessa) {
        $sql = "SELECT c.*,
                CASE WHEN GIUSTIFICATIVO IS NULL THEN 'false' ELSE 'true' END AS HAS_GIUSTIFICATIVO
                FROM commesse c
                WHERE COD_COMMESSA='$codCommessa'";
        $obj = select_single($sql);
        if ($obj) {
            $obj["PROGETTI"] = $this->get_progetti($codCommessa);
        }
        return $obj;
    }
    
    function get_progetti($codCommessa) {
        $sql = "SELECT p.ID_PROGETTO,p.ACRONIMO,pc.ORE_PREVISTE
                FROM progetti p
                JOIN progetti_commesse pc ON p.ID_PROGETTO=pc.ID_PROGETTO
                WHERE pc.COD_COMMESSA='$codCommessa'";
        $arr = select_list($sql);
        return $arr;
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
        $spreadSheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filename);
        $numOfSheets = $spreadSheet->getSheetCount();
        // Mi aspetto un unico sheet
        $this->importSheet($spreadSheet->getSheet(0), $message);   
     }

     function importSheet($excelSheet, &$message) {
        global $con, $panthera;

        $spreadSheetAry = $excelSheet->toArray();
        
        // salto la header
        $firstRow = 1;
        $numRows = count($spreadSheetAry);
        if ($numRows <= 1) {
            $message->error .= 'Il file deve contenere almeno una riga (header esclusa).<br/>';
            return;
        }

        $contatore = 0;
        for ($curRow = $firstRow; $curRow < $numRows; ++$curRow) {
    
            if(!isset($spreadSheetAry[$curRow][0])) {
                continue;
            }

            $tipologiaCommessa = $spreadSheetAry[$curRow][COL_TIPO_COMMESSA];
            $codCommessa = $spreadSheetAry[$curRow][COL_COD_COMMESSA];
            $totOreCommessa = $spreadSheetAry[$curRow][COL_TOT_ORE];
            $pctCompatibilita = $spreadSheetAry[$curRow][COL_PCT_RD];
            $totOreRd = $spreadSheetAry[$curRow][COL_TOT_ORE_RD];

            if (!$tipologiaCommessa || !$codCommessa || !$pctCompatibilita) {
                $message->error .= "Campi obbligatori non valorizzati alla riga $curRow<br/>";
                continue;
            }

            //TODO write query
            $query = "REPLACE INTO commesse (COD_COMMESSA,PCT_COMPATIBILITA,TOT_ORE_PREVISTE,TOT_ORE_RD_PREVISTE,TIPOLOGIA) " .
                        "VALUES('$codCommessa','$pctCompatibilita','$totOreCommessa','$totOreRd','$tipologiaCommessa')";
            execute_update($query);
            ++$contatore;
        }

        $message->success .= "Caricamento concluso. $contatore righe caricate.<br/>";
    }


}
?>