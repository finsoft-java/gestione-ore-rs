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

        $mese = $spreadSheetAry[1][3];
        if (empty($mese)) {
            $message->error .= 'Bad file. Non riesco a identificare il mese del documento.<br/>';
            return;
        }

        $anno = $spreadSheetAry[1][4];
        if (empty($anno)) {
            $message->error .= 'Bad file. Non riesco a identificare l\'anno del documento.<br/>';
            return;
        }
        
        $dataLul = $anno."-".$mese;
        $data_lul = new DateTime($dataLul);
        $data_lul->modify('last day of this month');
        $ultimoGiornoMese = $data_lul->format('d');
        
        $contatoreDipendenti = 3;
        $contatoreOreDipendenti = 5;

        for($e= 1; ;$e++){

            if($e != 1){
                $contatoreDipendenti = $contatoreDipendenti + 10;
                $contatoreOreDipendenti = $contatoreOreDipendenti + 10;
            }

            $this->elimina($dataLul,$spreadSheetAry[$contatoreDipendenti][1]);

            if($spreadSheetAry[$contatoreDipendenti][0] == 'Matricola'){
                for($a= 1; $a <= $ultimoGiornoMese; $a++){
                    $ora_lavorata = $spreadSheetAry[$contatoreOreDipendenti][$a];
                    $this->crea($spreadSheetAry[$contatoreDipendenti][1],$anno."-".$mese."-".$a,$ora_lavorata);
                }
            } else {
                break;
            }
        }
        $message->success .= 'Caricamento Effettuato correttamente.</br>';
    }

    function crea($matricola,$data,$ore) {
        global $con, $logged_user;
        $sql = insert("ore_presenza_lul", ["MATRICOLA_DIPENDENTE" => $matricola,
                                   "DATA" => $data,
                                   "ORE_PRESENZA_ORDINARIE" => $ore
                                  ]);
        mysqli_query($con, $sql);
        if ($con ->error) {
            print_error(500, $con ->error);
        }
    }
        
    function elimina($data,$matricola) {
        global $con;
        $datafine = new DateTime($data);
        $datainizio = new DateTime($data);
        $datafine->modify('last day of this month');
        $datainizio->modify('first day of this month');
        $ultimoGiornoMese = $datafine->format('d');
        $primoGiornoMese = $datainizio->format('d');
        $sql = "DELETE FROM ore_presenza_lul WHERE MATRICOLA_DIPENDENTE = '$matricola' and DATA >= CAST('$data-$primoGiornoMese' AS DATE) AND DATA <= CAST('$data-$ultimoGiornoMese' AS DATE)";
        mysqli_query($con, $sql);
        if ($con ->error) {
            print_error(500, $con ->error);
        }
    }
}
?>