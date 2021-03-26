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
    $primo = "DATE('$anno-$mese-01')";
    $metaMese = "DATE('$anno-$mese-15')";

    $query = "SELECT P.ID_PROGETTO,p.TITOLO,p.MATRICOLA_SUPERVISOR,pwr.MATRICOLA_DIPENDENTE from progetti p inner join progetti_wp_risorse pwr on p.ID_PROGETTO = pwr.ID_PROGETTO WHERE p.DATA_FINE >= $primo AND p.DATA_INIZIO <= LAST_DAY($primo) group by 1,2,3,4";            
    $dateFirma = select_list($query);
    /**
    *  ciclo precompilazione Data_firma
    */
    for($i = 0 ; $i < count($dateFirma); $i++){
        $matr_sup = $dateFirma[$i]['MATRICOLA_SUPERVISOR'];
        $matr_dip = $dateFirma[$i]['MATRICOLA_DIPENDENTE'];
        $query = "SELECT min(a.DATA) FROM ore_presenza_lul a join ore_presenza_lul b on a.DATA= b.DATA AND b.MATRICOLA_DIPENDENTE = '$matr_sup' AND b.ORE_PRESENZA_ORDINARIE > 0 where a.ORE_PRESENZA_ORDINARIE > 0 AND a.MATRICOLA_DIPENDENTE = '$matr_dip' AND a.DATA >= $metaMese";
        
        $matr_dipendente = $panthera->getUtente($matr_dip);
        $matr_supervisor = $panthera->getUtente($matr_sup);
        $dateFirma[$i]['MATRICOLA_DIPENDENTE'] = $matr_dipendente;
        $dateFirma[$i]['MATRICOLA_SUPERVISOR'] = $matr_supervisor;
        $dataDefault = select_single_value($query);
        $dateFirma[$i]['DATA_FIRMA'] = $dataDefault;
    }
    header('Content-Type: application/json');
    echo json_encode(['data' => $dateFirma]);
    
} else if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $postdata = file_get_contents("php://input");
    $json_data = json_decode($postdata);
    $annoMese = $json_data->data_firma[count($json_data->data_firma)-1]->ANNO_MESE;
    $sql = "DELETE FROM date_firma WHERE ANNO_MESE = '$annoMese'";    
    mysqli_query($con, $sql);
    if ($con ->error) {
        print_error(500, $con ->error);
    }

    for($i=0; $i < count($json_data->data_firma)-1; $i++){
        if($json_data->data_firma[$i] != null){
            if($json_data->data_firma[$i]->DATA_FIRMA != null){
                $sql = insert("date_firma", ["ANNO_MESE" => $annoMese,
                                            "ID_PROGETTO" => $json_data->data_firma[$i]->ID_PROGETTO,
                                            "DATA_FIRMA" => $json_data->data_firma[$i]->DATA_FIRMA,
                                            "MATRICOLA_DIPENDENTE" => $json_data->data_firma[$i]->MATRICOLA_DIPENDENTE
                                            ]);                    
                mysqli_query($con, $sql);
                if ($con ->error) {
                    print_error(500, $con ->error);
                }
            }
        }
    }


} else {
    //==========================================================
    print_error(400, "Unsupported method in request: " . $_SERVER['REQUEST_METHOD']);
}

?>