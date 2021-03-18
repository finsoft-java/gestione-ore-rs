<?php

// Mi aspetto un solo parametro, il periodo di lancio, nel formato YYYY-MM

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
    $periodo = $json_data->periodo;
    if (! $periodo) {
        print_error(400, "Missing parameter: periodo");
    }
    if (strlen($periodo) != 7) {
        print_error(400, "Bad parameter: Il periodo di lancio deve essere nella forma YYYY-MM");
    }
    $anno = substr($periodo, 0, 4);
    $mese = substr($periodo, 5, 2);

    // REPERIRE DATI DA DB
    $primo = "DATE('$anno-$mese-01')";
    $metaMese = "DATE('$anno-$mese-15')";


    
    $query = "SELECT p.TITOLO,p.MATRICOLA_SUPERVISOR,pwr.MATRICOLA_DIPENDENTE from progetti p inner join progetti_wp_risorse pwr on p.ID_PROGETTO = pwr.ID_PROGETTO WHERE p.DATA_FINE >= $primo AND p.DATA_INIZIO <= LAST_DAY($primo) group by 1,2,3";
            
    $dateFirma = select_list($query);
/**
 *  ciclo precompilazione Data_firma
 */
    for($i = 0 ; $i < count($dateFirma); $i++){
        $matr_sup = $dateFirma[$i]['MATRICOLA_SUPERVISOR'];
        $matr_dip = $dateFirma[$i]['MATRICOLA_DIPENDENTE'];
        $query = "SELECT min(a.DATA) FROM ore_presenza_lul a join ore_presenza_lul b on a.DATA= b.DATA AND b.MATRICOLA_DIPENDENTE = '$matr_sup' AND b.ORE_PRESENZA_ORDINARIE > 0 where a.ORE_PRESENZA_ORDINARIE > 0 AND a.MATRICOLA_DIPENDENTE = '$matr_dip' AND a.DATA >= $metaMese";
        $dataDefault = select_column($query);
        $dateFirma[$i]['DATA_FIRMA'] = $dataDefault;
    }
    header('Content-Type: application/json');
    echo json_encode(['data' => $dateFirma]);
    
} else {
    //==========================================================
    print_error(400, "Unsupported method in request: " . $_SERVER['REQUEST_METHOD']);
}

?>