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

    $allowedFileType = [
        'application/vnd.ms-excel',
        'text/xls',
        'text/xlsx',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    ];
    for($i = 0; $i < count($_FILES); $i++){
        if (! isset($_FILES["file$i"]["name"]) or count($_FILES["file$i"]) == 0) {
            print_error(400, "You have to upload at least 1 file.");
        }
    }
  
    
    $message = "";
    for ($filenum = 0; $filenum < count($_FILES); ++$filenum) {
        $message .= "Analysing file " . $_FILES["file$filenum"]["name"][$filenum] . "...<br/>";
        $filename = $_FILES["file$filenum"]["name"];
        if (in_array($_FILES["file$filenum"]["type"], $allowedFileType)) {
            $rapportini->importExcel($filename, $message);
        } else {
            $message .= "Invalid File Type. Upload Excel File.</br>";
        }
    }

    header('Content-Type: application/json');
    echo json_encode(['value' => $message]);
    
} else {
    //==========================================================
    print_error(400, "Unsupported method in request: " . $_SERVER['REQUEST_METHOD']);
}

?>