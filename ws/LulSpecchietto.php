<?php

include("include/all.php");
$con = connect();
$panthera->connect();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    //do nothing, HTTP 200
    exit();
}

require_logged_user_JWT();

$dataInizio = isset($_GET['dataInizio']) ? $con->escape_string($_GET['dataInizio']) : null;
$dataFine = isset($_GET['dataFine']) ? $con->escape_string($_GET['dataFine']) : null;
$month = isset($_GET['month']) ? $con->escape_string($_GET['month']) : null;
$matricola = isset($_GET['matricola']) ? $con->escape_string($_GET['matricola']) : null;
$top = isset($_GET['top']) ? $con->escape_string($_GET['top']) : null;
$skip = isset($_GET['skip']) ? $con->escape_string($_GET['skip']) : null;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    //==========================================================
    [$objects, $count] = $lul->getSpecchietto($top, $skip, $month, $matricola, $dataInizio, $dataFine);
    $newObj = array();
    $arrayMesi = array("Gennaio","Febbraio","Marzo","Aprile","Maggio","Giugno","Luglio","Agosto","Settembre","Ottobre","Novembre","Dicembre");
        
    foreach ($objects as $object) {

        $nome_dipendente = $panthera->getUtenteByMatricola($object["MATRICOLA_DIPENDENTE"]);
        $object["MATRICOLA_DIPENDENTE"] = $nome_dipendente ? $nome_dipendente : null;
        $object["MESE"]  = $arrayMesi[intval($object["MESE"])-1];
        array_push($newObj, $object);

    }
    header('Content-Type: application/json');
    echo json_encode(['data' => $newObj, 'count' => $count]);
} else {
    //==========================================================
    print_error(400, "Unsupported method in request: " . $_SERVER['REQUEST_METHOD']);
}


?>