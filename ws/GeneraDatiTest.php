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
    $query_matr = "SELECT DISTINCT MATRICOLA_DIPENDENTE from ORE_PRESENZA_LUL " .
                "WHERE DATA >= $primo AND DATA <= LAST_DAY($primo) AND ORE_PRESENZA > 0";
    $query_wp = "SELECT ID_PROGETTO, ID_WP, DATA_INIZIO, DATA_FINE, MONTE_ORE_RESIDUO " . 
                "FROM progetti_wp_residuo ".
                "WHERE (DATA_INIZIO >= $primo AND DATA_INIZIO <= LAST_DAY($primo)) OR (DATA_FINE >= $primo AND DATA_FINE <= LAST_DAY($primo)) AND MONTE_ORE_RESIDUO > 0";
    $query_date = "SELECT DISTINCT DATA FROM ORE_PRESENZA_LUL " .
                "WHERE DATA >= $primo AND DATA <= LAST_DAY($primo) AND ORE_PRESENZA > 0";
    
    // TODO selezionare anche i dati preesistenti

    $matricole = select_column($query_matr);
    $wp = select_list($query_wp);
    $date = select_column($query_date);
    
    
    // Inizializzo la struttura x (ore lavorate dal dipendente i sul WP j il giorno k)
    $x = array();
    for ($i = 1; $i <= count($matricole); $i++) {
        $x[$i] = array();
        for ($j = 1; $j <= count($wp); $j++) {
            $x[$i][$j] = array();
            for ($k = 1; $k <= count($date); $k++) {
                $x[$i][$j][$k] = 0;
            }
        }
    }

    // Inizializzo la struttura M (monte ore) e la $wp_rsr (progetti-risorse)
    $M = array();
    $wp_rsr = array();
    for ($j = 1; $j <= count($wp); $j++) {
        $M[$j] = $wp["MONTE_ORE_RESIDUO"];
        
        $idprogetto = $wp["ID_PROGETTO"];
        $idwp = $wp["ID_WP"];
        $query_wp_rsr = "SELECT MATRICOLA_DIPENDENTE FROM progetti_wp_risorse WHERE ID_PROGETTO='$idprogetto' AND ID_WP='$idwp' ";
        $wp_rsr[$j] = select_list($query_wp_rsr);
    }
    
    // Inizializzo la struttura progetti-matricole
    for 
    
} else {
    //==========================================================
    print_error(400, "Unsupported method in request: " . $_SERVER['REQUEST_METHOD']);
}

?>