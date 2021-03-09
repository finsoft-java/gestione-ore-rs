<?php

// cfr. https://phppot.com/php/import-excel-file-into-mysql-database-using-php/

include("include/all.php");    
$con = connect();

use PhpOffice\PhpSpreadsheet\Reader\Xlsx;


if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    //do nothing, HTTP 200
    exit();
}
    
require_logged_user_JWT();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //==========================================================
    $message = (object) [
    'error' => '',
    'success' => '',
    ];
    $allowedFileType = [
        'application/vnd.ms-excel',
        'text/xls',
        'text/xlsx',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-excel'
    ];

    for ($filenum = 0; $filenum < count($_FILES["file"]["name"]); ++$filenum) {
        $message->success .= "Analysing file " . $_FILES["file"]["name"][$filenum] . "...<br/>";
        $filename =  $_FILES["file"]["tmp_name"][$filenum];
        if (in_array($_FILES["file"]["type"][$filenum], $allowedFileType)) {
            echo 'filename->'.$filename;
            $lul->importExcel($filename, $message);
        } else {
            $message->error .= "Invalid File Type. Upload Excel File.<br/>";
        }
    }

    header('Content-Type: application/json');
    echo json_encode(['value' => $message]);
    
} else {
    //==========================================================
    print_error(400, "Unsupported method in request: " . $_SERVER['REQUEST_METHOD']);
}



        // IPOTIZZO (completamente a caso) che il foglio excel contiene matricola, data, ore

        /*
        for ($i = 0; $i <= $numRows; $i ++) {
            $matricola = "";
            if (isset($spreadSheetAry[$i][0])) {
                $matricola = mysqli_real_escape_string($conn, $spreadSheetAry[$i][0]);
            }
            $data = "";
            if (isset($spreadSheetAry[$i][1])) {
                // spero che funzioni anche con le date...
                $data = mysqli_real_escape_string($conn, "" . $spreadSheetAry[$i][1]);
            }
            $ore = 0;
            if (isset($spreadSheetAry[$i][2])) {
                $ore = $spreadSheetAry[$i][2];
            }

            if (!empty($matricola) && !empty($data) && $ore > 0) {
                $query = "insert into ore_presenza_lul(MATRICOLA_DIPENDENTE,DATA,ORE_PRESENZA_ORDINARIE) values('$matricola','$data',$ore)";
                execute_update($query);
                $message->success = "Caricamento Effettuato correttamente.<br/>"
            }
        }
        */

?>