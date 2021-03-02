<?php

// Mi aspetto un solo parametro, il periodo di lancio, nel formato YYYY-MM

include("include/all.php");    
$con = connect();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    //do nothing, HTTP 200
    exit(); 
}

//require_logged_user_JWT();



if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    //==========================================================

    // CONTROLLO PARAMETRI

    if (! isset($_GET['id_progetto']) or ! $_GET['id_progetto']) {
        print_error(400, "Missing parameter: id_progetto");
    }
    $idprogetto = (int) $_GET['id_progetto'];
    
    if (! isset($_GET['periodo']) or ! $_GET['periodo']) {
        $anno = null;
        $periodo = null;
    } else {
        $periodo = $_GET['periodo'];
        if (strlen($periodo) != 7) {
            print_error(400, "Bad parameter: Il periodo di lancio deve essere nella forma YYYY-MM");
        }
        $anno = substr($periodo, 0, 4);
        $mese = substr($periodo, 5, 2);
    }
    
    if (! isset($_GET['completo']) or ! $_GET['completo']) {
        $completo = false;
    } else {
        $completo = ($_GET['completo'] === 'true');
    }
    
    $budget->sendReport($idprogetto, $anno, $mese, $completo);
    
} else {
    //==========================================================
    print_error(400, "Unsupported method in request: " . $_SERVER['REQUEST_METHOD']);
}

?>