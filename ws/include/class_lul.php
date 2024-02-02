<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Border;

$lul = new LULManager();

class LULManager {

    function carica_da_db($dataInizio, $dataFine) {

        $query_lul = "SELECT ID_DIPENDENTE,DATA,ORE_PRESENZA_ORDINARIE  FROM ore_presenza_lul WHERE DATA BETWEEN '$dataInizio' AND '$dataFine'";
        $rows = select_list($query_lul);
        
        // trasformo la matrice $rows in una struttura $map_matr_ore
        $map_matr_ore = array();
        foreach ($rows as $row) {
            $matr = $row["ID_DIPENDENTE"];
            if($matr != ""){
                if (! isset($map_matr_ore[$matr])) $map_matr_ore[$matr] = array();
                $map_matr_ore[$matr][$row["DATA"]] = $row["ORE_PRESENZA_ORDINARIE"];
            }
        }        
        return $map_matr_ore;
    }

    function importExcel($filename, &$message, $typeFile) {
        global $con;
        ini_set('memory_limit', '-1');
        set_time_limit(400);
        if($typeFile == 'application/vnd.ms-excel' || $typeFile ==  'text/xls'){
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xls();
        } else {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        }
        
        $spreadSheet = $reader->load($filename);
        $excelSheet = $spreadSheet->getActiveSheet();
        $spreadSheetAry = $excelSheet->toArray();
        $numRows = count($spreadSheetAry);
        $annoMese = false;
        $ultimoGiornoMese = false;
        $primaRiga = 0;
        $matrInesistenti = array();
        $contatoreMatricole = 0;
        $mapMatricole = $this->getMapMatricole();
        $cntMatricoleInesistenti=0;
        while ($primaRiga < $numRows) {
            if ($spreadSheetAry[$primaRiga][0] == 'Azienda') {
                $meseSheet = $spreadSheetAry[$primaRiga+1][3];
                [$annoMese, $ultimoGiornoMese] = $this->leggiAzienda($primaRiga, $spreadSheetAry);
                if ($annoMese === false || $ultimoGiornoMese === false) {
                    return;
                }
                $primaRiga = $primaRiga + 3;
            } else if($spreadSheetAry[$primaRiga][0] == 'Matricola'){
                if ($annoMese === false || $ultimoGiornoMese === false) {
                    return;
                }
                $this->leggiOreDipendente($primaRiga, $annoMese, $ultimoGiornoMese, $mapMatricole, $spreadSheetAry, $message);
                if($spreadSheetAry[$primaRiga][1] != 0) {
                    $contatoreMatricole++;
                } else {
                    array_push($matrInesistenti,$spreadSheetAry[$primaRiga][1]);
                    $cntMatricoleInesistenti++;
                }
                $primaRiga = $primaRiga + 10;
            } else {
                break;
            }
        }
        if($cntMatricoleInesistenti > 0 ) {
            $message->error .= 'Nel File caricato sono presenti '.$cntMatricoleInesistenti.' matricole inesistenti con valore 0.<br/> <br/>';
        }
        $arrayMesi = array("Gennaio","Febbraio","Marzo","Aprile","Maggio","Giugno","Luglio","Agosto","Settembre","Ottobre","Novembre","Dicembre");
        $mese = $arrayMesi[intval($meseSheet)-1];
        $message->success .= 'Caricamento Effettuato correttamente.</br>
                              Mese Caricato: '.$mese.'</br>
                              Matricole file: '.$contatoreMatricole.'.</br>';
    }

    function importExcelRD($filename, &$message, $typeFile, $idCaricamento) {
        global $con, $panthera, $progettiManager,$rapportini;
        ini_set('memory_limit', '-1');
        set_time_limit(400);
        if($typeFile == 'application/vnd.ms-excel' || $typeFile ==  'text/xls'){
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xls();
        } else {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        }
        
        $spreadSheet = $reader->load($filename);
        $annoMese = false;
        $ultimoGiornoMese = false;
        $primaRiga = 0;
        $matrInesistenti = array();
        $contatoreRigeInserite = 0;
        $cntMatricoleInesistenti=0;
        $sheetData= "";
        $denominazione="";
        $sql = "";
        $sheetCount = $spreadSheet->getSheetCount();
        
        for ($i = 0; $i < $sheetCount; $i++) {
            $sheet = $spreadSheet->getSheet($i);
            $sheetData = $sheet->toArray();
            
            $numRows = count($sheetData);
            $rigaProgetto = 8;
            $datiUtente = $panthera->getAllDataMatricolaByName($sheetData[0][1]);
            $denominazione = trim($datiUtente[0]["DENOMINAZIONE"]);
            $matricola = trim($datiUtente[0]["MATRICOLA"]);
            $idDipendente = $datiUtente[0]["ID_DIPENDENTE"]; 
            $pezzi_comando_sql = []; 
            while ($rigaProgetto < $numRows) {
                $progetto = trim($sheetData[$rigaProgetto][0]);
                if($progetto == "TOT - " || $progetto == "") {
                    break;
                }
                if (strpos($progetto, "TOT -") === 0) {   
                    $rigaProgetto += 2;
                }
                [$annoMese, $ultimoGiornoMese] = $this->leggiMeseRD($primaRiga, $sheetData);
                for($a= 1; $a <= $ultimoGiornoMese; $a++){
                    if (strpos($progetto, "TOT -") === 0) {   
                        break;
                    }
                    $data = "$annoMese-$a";
                    $ore = $sheetData[$rigaProgetto][$a+1];
                    if($ore == ''){
                        $ore = 0;
                    }
                    if(($matricola == 0 || $matricola == null || $matricola == "") && ($idDipendente == 0 || $idDipendente == null || $idDipendente == "")) {
                        $message->error .= 'Nel File caricato sono presenti errori nella matricola |'.$sheetData[0][1].'| alla data '.$data;
                        break;
                    }
                    if($data == 0 || $data == null || $data == "" ) {
                        $message->error .= 'Nel File caricato sono presenti errori nella data '.$data;
                        break;
                    }
                    $pezzi_comando_sql[] = "('$idCaricamento','$progetto','$matricola','$data','$ore','$idDipendente')";
                    $contatoreRigeInserite++;
                }    
                $rigaProgetto++;
            }
            
            $sql = " INSERT INTO ore_presenza_progetti (ID_CARICAMENTO,PROGETTO,MATRICOLA_DIPENDENTE,DATA,ORE_PRESENZA_ORDINARIE,ID_DIPENDENTE) VALUES ".implode(',', $pezzi_comando_sql);
//            echo "||".$i.") ".$sql."||";
            execute_update($sql);
        }

        if($contatoreRigeInserite == 0 ) {
            $message->error .= 'Nel File caricato sono presenti errori';
        }
        $message->success .= 'Caricamento Effettuato correttamente.</br>
                              Righe importate per Utente '.$denominazione.': '.$contatoreRigeInserite.'.</br>';
    }

    /**
     * Carica da Panthera la lista dei dipendenti, e crea una mappa che
     * converte la matricola nell'ID dipendente
     */
    function getMapMatricole() {
        global $panthera;
        $matricole = $panthera->getUtenti();
        $mapMatricole =  [];
        if (count($matricole) > 0) {
            foreach($matricole as $m) {
                $mapMatricole[$m['MATRICOLA']] = $m['ID_DIPENDENTE'];
            }
        }
        return $mapMatricole;
    }

    function leggiAzienda($primaRiga, &$spreadSheetAry) {
        $mese = $spreadSheetAry[$primaRiga+1][3];
        if (empty($mese)) {
            $message->error .= 'Bad file. Non riesco a identificare il mese del documento.<br/>';
            return [false, false];
        }

        $anno = $spreadSheetAry[$primaRiga+1][4];
        if (empty($anno)) {
            $message->error .= 'Bad file. Non riesco a identificare l\'anno del documento.<br/>';
            return [false, false];
        }
        $dataLul = $anno."-".$mese;
        $data_lul = new DateTime($dataLul);
        $data_lul->modify('last day of this month');
        $ultimoGiornoMese = $data_lul->format('d');
        $this->elimina($anno,$mese);
        return ["$anno-$mese", $ultimoGiornoMese];
    }

    function leggiOreDipendente($primaRiga, $annoMese, $ultimoGiornoMese, $mapMatricole, &$spreadSheetAry, &$message) {
        $pezzi_comando_sql = [];
        $matricola = $spreadSheetAry[$primaRiga][1];
        if($matricola != 0){
            $sql = "";
            for($a= 1; $a <= $ultimoGiornoMese; $a++) {
                $data = "$annoMese-$a";
                $ore = $spreadSheetAry[$primaRiga+2][$a];
                if($ore == ''){
                    $ore = 0;
                }
                $idDipendente = '';
                if (!empty($matricola) && isset($mapMatricole[$matricola])) {
                    $idDipendente = $mapMatricole[$matricola];
                    array_push($pezzi_comando_sql, "('$matricola','$idDipendente','$data','$ore')");
                } else {
                    $message->error .= 'Nel File caricato è presente la matricola |'.$matricola.'| che non esiste.<br/> Non inserisco la riga<br/>';
                    break;
                }
            }
            if(is_array($pezzi_comando_sql) && !empty($pezzi_comando_sql)) {
                $sql = "INSERT INTO ore_presenza_lul (MATRICOLA_DIPENDENTE,ID_DIPENDENTE,DATA,ORE_PRESENZA_ORDINARIE) VALUES " .
                    implode(',', $pezzi_comando_sql);
                execute_update($sql);
            }
        }
    }

    function leggiMeseRD($primaRiga, &$spreadSheetAry) {
        $dataLul = $spreadSheetAry[$primaRiga+2][0];//JANUARY 2022
        $data_lul = new DateTime($dataLul);
        $mese = date_format($data_lul, "m");
        $anno = date_format($data_lul, "Y");
        $data_lul->modify('last day of this month');
        $ultimoGiornoMese = $data_lul->format('d');
        return ["$anno-$mese", $ultimoGiornoMese];
        
    }
        
    function elimina($anno, $mese) {
        $sql = "DELETE FROM ore_presenza_lul WHERE YEAR(DATA)=$anno AND MONTH(DATA)=$mese";
        execute_update($sql);
    }
    
    function get_all($skip=null, $top=null, $orderby=null, $month=null, $matricola=null, $dataInizio=null, $dataFine=null) {
        global $con;
        
        $sql0 = "SELECT COUNT(*) AS cnt ";
        $sql1 = "SELECT * ";
        $sql = "FROM ore_presenza_lul p WHERE 1 ";
        if (($dataInizio !== null && $dataInizio !== "") && ($dataFine !== null && $dataFine !== "")) {
            $sql .= "AND (data BETWEEN '$dataInizio' AND '$dataFine') ";
        } else {
            if ($month !== null && $month !== '') {
                // in forma YYYY-MM
                $month = substr($con->escape_string($month), 0, 7);
                $first = "DATE('$month-01')";
                $sql .= "AND (data BETWEEN $first AND LAST_DAY($first)) ";
            }
        }
        
        if ($matricola !== null && $matricola !== '') {
            $matricola = $con->escape_string($matricola);
            $sql .= "AND (MATRICOLA_DIPENDENTE='$matricola' or ID_DIPENDENTE='$matricola')";
        }

        if ($orderby && preg_match("/^[a-zA-Z0-9,_ ]+$/", $orderby)) {
            // avoid SQL-injection
            $sql .= " ORDER BY $orderby";
        } else {
            $sql .= " ORDER BY MATRICOLA_DIPENDENTE, DATA";
        }

        $count = select_single_value($sql0 . $sql);

        if ($top != null) {
            if ($skip != null) {
                $sql .= " LIMIT $skip,$top";
            } else {
                $sql .= " LIMIT $top";
            }
        }    
        //echo $sql1 . $sql;    
        $oggetti = select_list($sql1 . $sql);
        
        return [$oggetti, $count];
    }

    function getSpecchietto($skip = null, $top = null, $month=null, $matricola=null, $dataInizio=null, $dataFine=null) {
        global $con;
        $sql1 = "SELECT DISTINCT MATRICOLA_DIPENDENTE, MONTH(DATA) AS MESE, SUM(ORE_PRESENZA_ORDINARIE) AS ORE_LAVORATE ";
        $sql = "FROM ore_presenza_lul WHERE 1 ";

        if (($dataInizio !== null && $dataInizio !== "") && ($dataFine !== null && $dataFine !== "")) {
            $sql .= "AND (data BETWEEN '$dataInizio' AND '$dataFine') ";
        } else {
            if ($month !== null && $month !== '') {
                // in forma YYYY-MM
                $month = substr($con->escape_string($month), 0, 7);
                $first = "DATE('$month-01')";
                $sql .= "AND (data BETWEEN $first AND LAST_DAY($first)) ";
            }
        }
        
        if ($matricola !== null && $matricola !== '') {
            $matricola = $con->escape_string($matricola);
            $sql .= "AND (MATRICOLA_DIPENDENTE='$matricola' or ID_DIPENDENTE='$matricola')";
        }

        $sql .= " GROUP BY MONTH(DATA), MATRICOLA_DIPENDENTE ORDER BY MATRICOLA_DIPENDENTE, DATA";
        
        $oggettiCnt = select_list($sql1 . $sql);
        $count = count($oggettiCnt);
        if ($top != null) {
            if ($skip != null) {
                $sql .= " LIMIT $skip,$top";
            } else {
                $sql .= " LIMIT $top";
            }
        }
        $oggetti = select_list($sql1 . $sql);
        
        return [$oggetti, $count];
    }

    function getDateFirma($periodo) {
        global $panthera;
        $anno = substr($periodo, 0, 4);
        $mese = substr($periodo, 5, 2);
        $primo = "DATE('$anno-$mese-01')";
        $ultimo = "LAST_DAY($primo)";
        $metaMeseSucc = "DATE('$anno-$mese-15') + INTERVAL 1 MONTH";
        $primoMeseSucc = "$primo + INTERVAL 1 MONTH";
        $ultimoMeseSucc = "LAST_DAY($primo + INTERVAL 1 MONTH)";

        // Questa e' la griglia che verra' presentata all'utente
        $query = "SELECT DISTINCT oc.ID_DIPENDENTE, p.ID_PROGETTO, p.TITOLO, p.ID_SUPERVISOR
                    FROM ore_consuntivate_progetti oc
                    JOIN progetti p ON p.ID_PROGETTO = oc.ID_PROGETTO
                    WHERE p.DATA_FINE >= $primo AND p.DATA_INIZIO <= $ultimo
                    GROUP BY 1,2,3,4
                    ORDER BY 1,3";
        $dateFirma = select_list($query);

        // ciclo precompilazione Data_firma
        for ($i = 0 ; $i < count($dateFirma); $i++) {
            $idSupervisor = $dateFirma[$i]['ID_SUPERVISOR'];
            $idDipendente = $dateFirma[$i]['ID_DIPENDENTE'];

            $dateFirma[$i]['ULTIMA_PRESENZA_DIP'] = $this->getUltimaPresenzaPeriodo($idDipendente, $primo, $ultimo);
            $dateFirma[$i]['ULTIMA_PRESENZA_SUP'] = $this->getUltimaPresenzaPeriodo($idSupervisor, $primo, $ultimo);
            $dateFirma[$i]['PRIMA_PRESENZA_SUCC_DIP'] = $this->getPrimaPresenzaPeriodo($idDipendente, $primoMeseSucc, $ultimoMeseSucc);
            $dateFirma[$i]['PRIMA_PRESENZA_SUCC_SUP'] = $this->getPrimaPresenzaPeriodo($idSupervisor, $primoMeseSucc, $ultimoMeseSucc);
            
            $dateFirma[$i]['DATA_FIRMA'] = $this->getUltimoGiornoLavorativoPeriodo($primo, $ultimo);

            // compilo anche nome dipendente e supervisor
            $nome_dipendente = $panthera->getUtenteByIdDipendente($idDipendente);
            $nome_supervisor = $panthera->getUtenteByIdDipendente($idSupervisor);
            $dateFirma[$i]['NOME_DIPENDENTE'] = $nome_dipendente;
            $dateFirma[$i]['NOME_SUPERVISOR'] = $nome_supervisor;
        }

        return $dateFirma;
    }

    function getPrimaPresenzaPeriodo($idDipendente, $primo, $ultimo) {
        $query = "SELECT MIN(a.DATA) FROM ore_presenza_lul a
                WHERE a.ORE_PRESENZA_ORDINARIE > 0
                AND a.ID_DIPENDENTE = '$idDipendente'
                AND a.DATA BETWEEN $primo AND $ultimo";
        return select_single_value($query);
    }

    function getUltimaPresenzaPeriodo($idDipendente, $primo, $ultimo) {
        $query = "SELECT MAX(a.DATA) FROM ore_presenza_lul a
                WHERE a.ORE_PRESENZA_ORDINARIE > 0
                AND a.ID_DIPENDENTE = '$idDipendente'
                AND a.DATA BETWEEN $primo AND $ultimo";
        return select_single_value($query);
    }

    function getUltimoGiornoLavorativoPeriodo($primo, $ultimo) {
        // 1=Sunday, 2=Monday, 3=Tuesday, 4=Wednesday, 5=Thursday, 6=Friday, 7=Saturday
        $query = "SELECT MAX(a.DATA) FROM ore_presenza_lul a
                WHERE a.DATA BETWEEN $primo AND $ultimo
                AND DAYOFWEEK(a.DATA) <> 1 AND DAYOFWEEK(a.DATA) <> 7";
        return select_single_value($query);
    }

    function salvaDateFirma($annoMese, $json_data) {
        $sql = "DELETE FROM date_firma WHERE ANNO_MESE = '$annoMese'";    
        execute_update($sql);
    
        for ($i = 0; $i < count($json_data->data_firma) - 1; $i++) {
            if ($json_data->data_firma[$i] != null) {
                if ($json_data->data_firma[$i]->DATA_FIRMA != null) {
                    $sql = insert("date_firma", ["ANNO_MESE" => $annoMese,
                                                "ID_PROGETTO" => $json_data->data_firma[$i]->ID_PROGETTO,
                                                "DATA_FIRMA" => $json_data->data_firma[$i]->DATA_FIRMA,
                                                "ID_DIPENDENTE" => $json_data->data_firma[$i]->ID_DIPENDENTE
                                                ]);
                    execute_update($sql);
                }
            }
        }
    }
}

?>