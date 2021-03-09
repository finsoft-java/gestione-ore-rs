<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Border;

$lul = new LULManager();

class LULManager {

    function carica_da_db($anno, $mese) {
        $primo = "DATE('$anno-$mese-01')";

        $query_lul = "SELECT MATRICOLA_DIPENDENTE,DATA,ORE_PRESENZA_ORDINARIE " .
                    "FROM ore_presenza_lul " .
                    "WHERE DATA >= $primo AND DATA <= LAST_DAY($primo)";
        $rows = select_list($query_lul);
        
        // trasformo la matrice $rows in una struttura $map_matr_ore
        $map_matr_ore = array();
        foreach ($rows as $row) {
            $matr = $row["MATRICOLA_DIPENDENTE"];
            $data = new DateTime($row["DATA"]);
            if (! isset($map_matr_ore[$matr])) $map_matr_ore[$matr] = array();
            $map_matr_ore[$matr][$data->format('j')] = $row["ORE_PRESENZA_ORDINARIE"];
        }
        
        return $map_matr_ore;
    }

    function importExcel($filename, &$message) {
        global $con;
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $spreadSheet = $reader->load($filename);

        $excelSheet = $spreadSheet->getActiveSheet();
        $spreadSheetAry = $excelSheet->toArray();
        
        $numRows = count($spreadSheetAry);
        return false;
        if(isset($spreadSheetAry[0][7])){
            $titolo_progetto = $spreadSheetAry[0][7];
        } else {
            $message->error .= 'Bad file. Non riesco a identificare le corrette colonne del file.<br/>';
            return;
        }
        $id_progetto = select_single_value("SELECT ID_PROGETTO FROM PROGETTI WHERE TITOLO='$titolo_progetto'"); // FIXME chiave unica?!?        
        if (empty($id_progetto)) {
            $message->error .= 'Bad file. Non riesco a identificare il titolo del progetto.<br/>';
            return;
        }
        
        $anno = $spreadSheetAry[2][9];
        if (empty($anno)) {
            $message->error .= 'Bad file. Non riesco a identificare l\'anno del rapportino.<br/>';
            return;
        }
        $mese = $spreadSheetAry[2][13]; // e.g. February
        if (empty($mese)) {
            $message->error .= 'Bad file. Non riesco a identificare il mes del rapportino.<br/>';
            return;
        }
        $mese = date_parse($mese)['month'];

        $matricola = '';
        // skip first rows
        for ($i = 0; $i <= $numRows; ++$i) {
            if (strpos($spreadSheetAry[$i][0], 'Activity details') !== false) {
                break;
            }
        }
        
        if ($i == $numRows) {
            $message->error .= 'Bad file. Non trovo la stringa "Activity details".<br/>';
            return;
        }
        
        $matricola = $spreadSheetAry[$i][23];
        if (empty($matricola)) {
            $message->error .= 'Bad file. Non riesco a identificare la matricola utente.</br>';
            return;
        }
        
        ++$i;
        $riga_date = $i;
        ++$i;
        
        for ( ; $i <= $numRows; ++$i) {
            if (empty($spreadSheetAry[$i][0])) {
                continue;
            }
            if ($spreadSheetAry[$i][0] === 'TOT') {
                break;
            }
            $titolo_wp = mysqli_real_escape_string($con, $spreadSheetAry[$i][0]);
            $id_wp = select_single_value("SELECT ID_WP FROM PROGETTI_WP WHERE ID_PROGETTO=$id_progetto AND TITOLO='$titolo_wp'");
            
            for ($day = 1; $day < count($spreadSheetAry[$riga_date]); ++$day) { // OFFSET == 0
                if ($spreadSheetAry[$riga_date][$day] === 'TOT') {
                    break;
                }
                $data = "$anno-$mese-$day";
                $ore = $spreadSheetAry[$i][$day];

                if ($ore > 0) {
                    $query = "replace into ore_consuntivate(MATRICOLA_DIPENDENTE,DATA,ID_PROGETTO,ID_WP,ORE_LAVORATE) values('$matricola','$data',$id_progetto,$id_wp,$ore)";
                    execute_update($query);
                }
            }
        }
        
        $message->success .= 'Caricamento Effettuato correttamente.</br>';
    }
}
?>