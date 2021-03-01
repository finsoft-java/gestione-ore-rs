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
    $query_matr = "SELECT DISTINCT MATRICOLA_DIPENDENTE from ORE_PRESENZA_LUL " .
                "WHERE DATA >= $primo AND DATA <= LAST_DAY($primo) AND ORE_PRESENZA_ORDINARIE > 0";
    $query_wp = "SELECT ID_PROGETTO, ID_WP, DATA_INIZIO, DATA_FINE, MONTE_ORE_RESIDUO " . 
                "FROM progetti_wp_residuo ".
                "WHERE DATA_FINE >= $primo AND DATA_INIZIO <= LAST_DAY($primo) AND MONTE_ORE_RESIDUO > 0";
    $query_date = "SELECT DISTINCT DATA FROM ORE_PRESENZA_LUL " .
                "WHERE DATA >= $primo AND DATA <= LAST_DAY($primo) AND ORE_PRESENZA_ORDINARIE > 0";
    $query_lul = "SELECT DATA,MATRICOLA_DIPENDENTE,ORE_PRESENZA_ORDINARIE FROM ORE_PRESENZA_LUL " .
                "WHERE DATA >= $primo AND DATA <= LAST_DAY($primo) AND ORE_PRESENZA_ORDINARIE > 0";
    $query_pregresso = "SELECT ID_PROGETTO,ID_WP,DATA,MATRICOLA_DIPENDENTE,ORE_LAVORATE FROM ORE_CONSUNTIVATE " .
                "WHERE DATA >= $primo AND DATA <= LAST_DAY($primo) AND ORE_LAVORATE > 0";
    
    // TODO selezionare anche i dati preesistenti

    $matricole = select_column($query_matr);
    $wp = select_list($query_wp);
    $date = select_column($query_date);
    $lul = select_list($query_lul);
    $pregresso = select_list($query_pregresso);
    

    // Inizializzo la struttura x (ore lavorate dal dipendente i sul WP j il giorno k)
    $x = array();
    for ($i = 0; $i < count($matricole); $i++) {
        $x[$i] = array();
        for ($j = 0; $j < count($wp); $j++) {
            $x[$i][$j] = array();
            for ($k = 0; $k < count($date); $k++) {
                $x[$i][$j][$k] = 0;
                foreach ($pregresso as $p) {
                    if ($p["MATRICOLA_DIPENDENTE"] == $matricole[$i] and $p["DATA"] == $date[$k] and $p["ID_PROGETTO"] == $wp[$j]["ID_PROGETTO"] and $p["ID_WP"] == $wp[$j]["ID_WP"]) {
                        $x[$i][$j][$k] = (int) $p["ORE_LAVORATE"]; 
                        break;
                    }
                }
            }
        }
    }

    // Inizializzo la struttura M (monte ore) e la $wp_rsr (progetti-risorse)
    $M = array();
    $wp_rsr = array();
    for ($j = 0; $j < count($wp); $j++) {
        $M[$j] = $wp[$j]["MONTE_ORE_RESIDUO"];
        
        $idprogetto = $wp[$j]["ID_PROGETTO"];
        $idwp = $wp[$j]["ID_WP"];
        $query_wp_rsr = "SELECT MATRICOLA_DIPENDENTE FROM progetti_wp_risorse WHERE ID_PROGETTO='$idprogetto' AND ID_WP='$idwp' ";
        $wp_rsr[$j] = select_list($query_wp_rsr);
    }
    
    // Inizializzo la struttura L (ore lavorate dal dipendente i il giorno k)
    $L = array();
    for ($i = 0; $i < count($matricole); $i++) {
        $L[$i] = array();
        for ($k = 0; $k < count($date); $k++) {
            $L[$i][$k] = 0;
            foreach ($lul as $lul_row) {
                if ($lul_row["MATRICOLA_DIPENDENTE"] == $matricole[$i] and $lul_row["DATA"] == $date[$k]) {
                    $L[$i][$k] = $lul_row["ORE_PRESENZA_ORDINARIE"];
                    break;
                }
            }
        }
    }
    
    // LOOP PRINCIPALE
    for ($k = 0; $k < count($date); $k++) {
        $data = $date[$k];
        for ($i = 0; $i < count($matricole); $i++) {
            $matricola = $matricole[$i];
            $probabilita = array();
            $almeno_un_positivo = false;
            for ($j = 0; $j < count($wp); $j++) {
                $probabilita[$j] = 0;
                // la probabilita resta zero se $j non è ammissibile
                if ($M[$j] > 0 and in_array($matricola, $wp_rsr[$j]) and $data >= $wp["DATA_INIZIO"] and $data <= $wp["DATA_FINE"]) {
                    $diff = date_diff($wp[$j]["DATA_FINE"], $data); // giorni alla scadenza del WP
                    $probabilita[$j] = $M[$j] / ($diff + 1);
                    $almeno_un_positivo = true;
                }
            }
            if (! $almeno_un_positivo) {
                //nessuna scelta disponibile
                continue;                
            }
            for ($ora = 0; $ora < $L[$i][$k]; $ora++) {
                $j = random_probability($probabilita);
                $x[$i][$j][$k]++;
                $M[$j]--;
                if ($M[$j] == 0) $probabilita[$j] = 0;
            }
        }
    }
    
    // SALVO SU DATABASE
    for ($i = 0; $i < count($matricole); $i++) {
        $matr = $matricole[$i];
        for ($j = 0; $j < count($wp); $j++) {
            $idprogetto = $wp[$j]["ID_PROGETTO"];
            $idwp = $wp[$j]["ID_WP"];
            for ($k = 0; $k < count($date); $k++) {
                $data = $date[$k];
                $ore = $x[$i][$j][$k];
                $query = "REPLACE INTO ore_consuntivate(ID_PROGETTO, ID_WP, MATRICOLA_DIPENDENTE, DATA, ORE_LAVORATE) VALUES($idprogetto,$idwp,'$matr','$data',$ore)";
                execute_update($query);
            }
        }
    }
    
    
} else {
    //==========================================================
    print_error(400, "Unsupported method in request: " . $_SERVER['REQUEST_METHOD']);
}

?>