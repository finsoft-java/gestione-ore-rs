<?php

$partecipantiManager = new PartecipantiManager();

    //configurazione colonne file excel
    define('COL_DIPENDENTE',     0);
    define('COL_PCT_IMPEGO',    1);
    define('COL_MANSIONE',      2);
    define('COL_COSTO',         3);

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
        $id = $con->escape_string($json_data->ID_DIPENDENTE);
		
        $sql = update("partecipanti_globali", [
                                    "MANSIONE" => $con->escape_string($json_data->MANSIONE),
                                    "COSTO" => $con->escape_string($json_data->COSTO),
                                    "PCT_UTILIZZO" => $con->escape_string($json_data->PCT_UTILIZZO)
                                  ], ["ID_DIPENDENTE" => $id]);
        execute_update($sql);
    }
    
    function elimina($id_dipendente) {
        $sql = "DELETE FROM partecipanti_globali WHERE id_dipendente = '$id_dipendente'";
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

            $dipendente = $spreadSheetAry[$curRow][COL_DIPENDENTE];
            $pctImpiego = $spreadSheetAry[$curRow][COL_PCT_IMPEGO];
            $mansione = $spreadSheetAry[$curRow][COL_MANSIONE];
            $costo = $spreadSheetAry[$curRow][COL_COSTO];

            if (!$dipendente || !$pctImpiego) {
                $message->error .= "Campi obbligatori non valorizzati alla riga $curRow<br/>";
                continue;
            }

            // TODO incrociare ID_DIPENDENTE con Panthera

            $query = "REPLACE INTO partecipanti_globali (ID_DIPENDENTE,PCT_UTILIZZO,MANSIONE,COSTO) " .
                        "VALUES('$dipendente','$pctImpiego','$mansione','$costo')";
            execute_update($query);
            ++$contatore;
        }

        $message->success .= "Caricamento concluso. $contatore righe caricate.<br/>";
    }

}
?>