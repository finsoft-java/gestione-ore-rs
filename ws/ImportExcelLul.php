<?php

// cfr. https://phppot.com/php/import-excel-file-into-mysql-database-using-php/

include("include/all.php");    
$con = connect();
$panthera->connect();

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
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    ];

    for ($filenum = 0; $filenum < count($_FILES["file"]["name"]); ++$filenum) {
        $message->success .= "Analysing file " . $_FILES["file"]["name"][$filenum] . "...<br/>";
        $filename =  $_FILES["file"]["tmp_name"][$filenum];
        if (in_array($_FILES["file"]["type"][$filenum], $allowedFileType)) {
            $lul->importExcel($filename, $message, $_FILES["file"]["type"][$filenum]);
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
?>