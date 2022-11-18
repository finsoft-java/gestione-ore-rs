<?php

$commesseManager = new CommesseManager();

//configurazione prime 5 colonne file excel
define('COL_TIPO_COMMESSA', 0);
define('COL_COD_COMMESSA', 1);
define('COL_TOT_ORE', 2);
define('COL_PCT_RD', 3);
define('COL_TOT_ORE_RD', 4);
//dalla 5 salvo header in array degli acronimi progetti
//select_value("SELECT ID_PROGETTO ... dall'acronimo")
// se viene null warning al'lutente e metti -1 nell'array

class CommesseManager
{

    public function get_commesse()
    {
        $sql = "SELECT COD_COMMESSA,PCT_COMPATIBILITA,NOTE,GIUSTIFICATIVO_FILENAME,TOT_ORE_PREVISTE,TOT_ORE_RD_PREVISTE,TIPOLOGIA
                FROM commesse c
                ORDER BY PCT_COMPATIBILITA DESC, COD_COMMESSA";
        $arr = select_list($sql);

        foreach ($arr as $id => $commessa) {
            $arr[$id]["PROGETTI"] = $this->get_progetti($commessa["COD_COMMESSA"]);
        }
        return $arr;
    }

    public function get_commessa($codCommessa)
    {
        $sql = "SELECT c.*
                FROM commesse c
                WHERE COD_COMMESSA='$codCommessa'";
        $obj = select_single($sql);
        if ($obj) {
            $obj["PROGETTI"] = $this->get_progetti($codCommessa);
        }
        return $obj;
    }

    public function get_commesse_periodo($dataInizio, $dataFine)
    {
        $sql = "SELECT c.*
                FROM commesse c
                WHERE DATA_INIZIO='$dataInizio' AND DATA_FINE='$dataFine'
                ORDER BY PCT_COMPATIBILITA DESC, COD_COMMESSA";
        $arr = select_list($sql);

        foreach ($arr as $id => $commessa) {
            $arr[$id]["PROGETTI"] = $this->get_progetti($commessa["COD_COMMESSA"], $dataInizio, $dataFine);
        }
        return $arr;
    }

    public function get_progetti($codCommessa, $dataInizio, $dataFine)
    {
        $sql = "SELECT p.ID_PROGETTO,p.ACRONIMO,pc.ORE_PREVISTE
                FROM progetti p
                JOIN progetti_commesse pc ON p.ID_PROGETTO=pc.ID_PROGETTO
                WHERE pc.COD_COMMESSA='$codCommessa' AND pc.DATA_INIZIO='$dataInizio' AND pc.DATA_FINE='$dataFine'";
        $arr = select_list($sql);
        return $arr;
    }

    public function controllo_pct_commessa($json_data)
    {
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
            if ($pct + $json_data->PCT_COMPATIBILITA > 100.01) {
                print_error(400, "La commessa $json_data->COD_COMMESSA &egrave; gi&agrave; utilizzata al $pct% in altri progetti");
            }
        }
    }

    public function importExcel($filename, &$message, $typeFile, $dataInizio, $dataFine)
    {
        $spreadSheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filename);
        $numOfSheets = $spreadSheet->getSheetCount();
        // Mi aspetto un unico sheet
        $this->importSheet($spreadSheet->getSheet(0), $message, $dataInizio, $dataFine);
    }

    public function importSheet($excelSheet, &$message, $dataInizio, $dataFine)
    {
        global $con, $panthera;

        $spreadSheetAry = $excelSheet->toArray(null, true, false);

        // salto la header
        $firstRow = 1;
        $numRows = count($spreadSheetAry);
        if ($numRows <= 1) {
            $message->error .= 'Il file deve contenere almeno una riga (header esclusa).<br/>';
            return;
        }

        $contatore = 0;
        for ($curRow = $firstRow; $curRow < $numRows; ++$curRow) {

            $tipologiaCommessa = $con->escape_string($spreadSheetAry[$curRow][COL_TIPO_COMMESSA]);
            $codCommessa = $con->escape_string($spreadSheetAry[$curRow][COL_COD_COMMESSA]);
            $totOreCommessa = $spreadSheetAry[$curRow][COL_TOT_ORE];
            $pctCompatibilita = $spreadSheetAry[$curRow][COL_PCT_RD];
            $totOreRd = $spreadSheetAry[$curRow][COL_TOT_ORE_RD];

            if (!$codCommessa || !$pctCompatibilita) {
                $message->error .= "Campi obbligatori non valorizzati alla riga $curRow<br/>";
                continue;
            }

            $query = "SELECT COUNT(*) FROM commesse WHERE COD_COMMESSA='$codCommessa' AND DATA_INIZIO='$dataInizio' AND DATA_FINE='$dataFine'";
            $count = select_single_value($query);
            if ($count == 0) {
                $query = "INSERT INTO commesse (COD_COMMESSA,PCT_COMPATIBILITA,TOT_ORE_PREVISTE,TOT_ORE_RD_PREVISTE,TIPOLOGIA, DATA_INIZIO, DATA_FINE)
                        VALUES('$codCommessa',100*$pctCompatibilita,'$totOreCommessa','$totOreRd','$tipologiaCommessa', '$dataInizio', '$dataFine')";
            } else {
                $query = "UPDATE commesse SET PCT_COMPATIBILITA=100*$pctCompatibilita,TOT_ORE_PREVISTE='$totOreCommessa',TOT_ORE_RD_PREVISTE='$totOreRd',TIPOLOGIA='$tipologiaCommessa'
                        WHERE COD_COMMESSA='$codCommessa' AND DATA_INIZIO='$dataInizio' AND DATA_FINE='$dataFine'";
            }
            execute_update($query);
            ++$contatore;
        }

        $cols = count($spreadSheetAry[0]);
        $projectMap = [];
        //ricavo idProgetto dall'acronimo nell'header
        for ($curCol = 5; $curCol < $cols; ++$curCol) {
            $acronimo = $spreadSheetAry[0][$curCol];
            $query = "SELECT ID_PROGETTO from progetti WHERE ACRONIMO = '$acronimo'";
            $projectMap[$curCol] = select_single_value($query); //mappa acronimo -> idProgetto
            if (empty($projectMap[$curCol])) {
                $message->error .= "Acronimo non trovato: $acronimo <br/>";
            }
        }

        $contatore = 0;
        for ($curRow = $firstRow; $curRow < $numRows; ++$curRow) {
            foreach ($projectMap as $curCol => $idProgetto) {
                $codCommessa = $spreadSheetAry[$curRow][COL_COD_COMMESSA];
                $valueOre = empty($spreadSheetAry[$curRow][$curCol]) ? 0.0 : $spreadSheetAry[$curRow][$curCol];
                if (!empty($idProgetto)) {
                    $query = "REPLACE INTO progetti_commesse (ID_PROGETTO,COD_COMMESSA,ORE_PREVISTE, DATA_INIZIO, DATA_FINE)
                    VALUES('$idProgetto','$codCommessa','$valueOre', '$dataInizio', '$dataFine')";
                    execute_update($query);
                }
            }
            ++$contatore;
        }
        $message->success .= "Caricamento concluso. $contatore righe caricate.<br/>";
    }

    public function get_periodi()
    {
        $sql = "SELECT DISTINCT
                    DATE_FORMAT(DATA_INIZIO,'%Y-%m-%d') AS DATA_INIZIO,
                    DATE_FORMAT(DATA_FINE,'%Y-%m-%d') AS DATA_FINE
                FROM commesse
                ORDER BY DATA_FINE DESC, DATA_INIZIO DESC";
        return select_list($sql);
    }

    public function elimina_periodo($dataInizio, $dataFine)
    {
        $sql = "DELETE FROM commesse WHERE
                    DATA_INIZIO=DATE('$dataInizio') AND
                    DATA_FINE=DATE('$dataFine')";
        execute_update($sql);
    }

}
