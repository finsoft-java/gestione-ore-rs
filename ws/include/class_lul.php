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

    function importExcel($filename, &$message, $typeFile) {
        global $con;
        if($typeFile == 'application/vnd.ms-excel' || $typeFile ==  'text/xls'){
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xls();
        } else {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        }
        
        $spreadSheet = $reader->load($filename);
        $excelSheet = $spreadSheet->getActiveSheet();
        $spreadSheetAry = $excelSheet->toArray();
        $numRows = count($spreadSheetAry);
        $primaRiga = 0;
        while ($primaRiga < $numRows) {
            if($spreadSheetAry[$primaRiga][0] == 'Azienda'){
                [$annoMese, $ultimoGiornoMese] = $this->leggiAzienda($primaRiga,$spreadSheetAry);                
                $primaRiga = $primaRiga + 3;
            }else if($spreadSheetAry[$primaRiga][0] == 'Matricola'){
                $this->leggiOreDipendente($primaRiga,$annoMese,$ultimoGiornoMese,$spreadSheetAry);
                $primaRiga = $primaRiga + 10;
            } else {
                break;
            }
        }
        $message->success .= 'Caricamento Effettuato correttamente.</br>';
    }

    function leggiAzienda($primaRiga,$spreadSheetAry) {
        $mese = $spreadSheetAry[$primaRiga+1][3];
        if (empty($mese)) {
            $message->error .= 'Bad file. Non riesco a identificare il mese del documento.<br/>';
            return;
        }

        $anno = $spreadSheetAry[$primaRiga+1][4];
        if (empty($anno)) {
            $message->error .= 'Bad file. Non riesco a identificare l\'anno del documento.<br/>';
            return;
        }
        $dataLul = $anno."-".$mese;
        $data_lul = new DateTime($dataLul);
        $data_lul->modify('last day of this month');
        $ultimoGiornoMese = $data_lul->format('d');
        $this->elimina($anno,$mese);
        return [$anno.'-'.$mese, $ultimoGiornoMese];
    }

    function leggiOreDipendente($primaRiga,$annoMese,$ultimoGiornoMese,$spreadSheetAry) {
        for($a= 1; $a <= $ultimoGiornoMese; $a++){
            $ora_lavorata = $spreadSheetAry[$primaRiga+2][$a];
            $this->crea($spreadSheetAry[$primaRiga][1],$annoMese."-".$a,$ora_lavorata);
        }

    }

    function crea($matricola,$data,$ore) {
        global $con;
        $sql = "INSERT INTO ore_presenza_lul (MATRICOLA_DIPENDENTE,DATA,ORE_PRESENZA_ORDINARIE) VALUES ('$matricola','$data',$ore)";
        mysqli_query($con, $sql);
        if ($con ->error) {
            print_error(500, $con ->error);
        }
    }
        
    function elimina($anno, $mese) {
        global $con;
        $sql = "DELETE FROM ore_presenza_lul WHERE YEAR(DATA)=$anno AND MONTH(DATA)=$mese";
        mysqli_query($con, $sql);
        if ($con ->error) {
            print_error(500, $con ->error);
        }
    }
}
?>