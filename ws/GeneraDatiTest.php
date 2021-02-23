<?php

// Mi aspetto un solo parametro, il periodo di lancio, nel formato YYYY-MM oppure YYYY-MM-DD

include("include/all.php");    
$con = connect();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    //do nothing, HTTP 200
    exit();
}
    
require_logged_user_JWT();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //==========================================================

    // CONTROLLO PARAMETRI

    $postdata = file_get_contents("php://input");
    $json_data = json_decode($postdata);
    if (!$json_data) {
        print_error(400, "Missing JSON data");
    }
    if (!$json_data->periodo) {
        print_error(400, "Missing parameter: periodo");
    }
    $anno = substr($json_data->periodo, 0, 4);
    $mese = substr($json_data->periodo, 5, 2);
    if (empty($anno) || empty($mese)) {
        print_error(400, "Il periodo di lancio deve essere nella forma YYYY-MM");
    }

    // REPERIRE DATI DA DB
    $primo = "DATE('$anno-$mese-01')";
    $query1 = "SELECT DISTINCT data FROM ore_presenza_lul WHERE data >= $primo AND data <= LAST_DAY($primo) AND ore_presenza > 0";
    $query2 = "SELECT DISTINCT matricola_dipendente FROM ore_presenza_lul WHERE data >= $primo AND data <= LAST_DAY($primo) AND ore_presenza > 0";
    $query3 = "SELECT DISTINCT id_progetto,id_wp FROM progetti_wp WHERE (data_inizio >= $primo AND data_inizio <= LAST_DAY($primo)) OR (data_fine >= $primo AND data_fine <= LAST_DAY($primo))";
    
    // ...
    
    
} else {
    //==========================================================
    print_error(400, "Unsupported method in request: " . $_SERVER['REQUEST_METHOD']);
}

?>