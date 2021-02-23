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

    if (in_array($_FILES["file"]["type"], $allowedFileType)) {

        //$targetPath = 'uploads/' . $_FILES['file']['name'];
        //move_uploaded_file($_FILES['file']['tmp_name'], $targetPath);

        //TODO ELIMINARE I DATI PREESISTENTI

        $Reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();

        $spreadSheet = $Reader->load($_FILES['file']['tmp_name']);
        $excelSheet = $spreadSheet->getActiveSheet();
        $spreadSheetAry = $excelSheet->toArray();
        $numRows = count($spreadSheetAry);

        // IPOTIZZO (completamente a caso) che il foglio excel contiene matricola, data, ore

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

            if (!empty($matricola) && !empty($data) && $ore > 0)) {
                $query = "insert into ore_presenza_lul(MATRICOLA_DIPENDENTE,DATA,ORE_PRESENZA_ORDINARIE) values('$matricola','$data',$ore)";
                mysqli_query($conn, $query);
                if ($con ->error) {
                    print_error(500, $con ->error);
                }
            }
        }
    } else {
        print_error(400, "Invalid File Type. Upload Excel File.");
    }
    
} else {
    //==========================================================
    print_error(400, "Unsupported method in request: " . $_SERVER['REQUEST_METHOD']);
}

?>