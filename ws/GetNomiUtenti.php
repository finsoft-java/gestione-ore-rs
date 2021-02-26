<?php

include("include/all.php");    
$con = connect();


if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    //do nothing, HTTP 200
    exit();
}
    
//require_logged_user_JWT();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    //==========================================================

    $query = "SELECT DISTINCT MATRICOLA,NOME,COGNOME FROM THIP.DIPENDENTI_V01 WHERE ID_AZIENDA='001'";
    // TODO BISOGNA RICHIAMARE DB2, $matricole = select_list($query)
    
    $matricole = [ [ 'MATRICOLA' => '1234', 'NOME' => 'Mario', "COGNOME" => 'Rossi' ],
                  [ 'MATRICOLA' => '4321', 'NOME' => 'Carlo', "COGNOME" => 'Verdi' ],
                  [ 'MATRICOLA' => '6666', 'NOME' => 'Gianni', "COGNOME" => 'Bianchi' ]
                 ];
    $result = [];
    foreach ($matricole as $m) {
        $result[$m['MATRICOLA']] = $m['COGNOME'] . ' ' . $m['NOME'];
    }

    header('Content-Type: application/json');
    echo json_encode(['data' => $result]);

} else {
    //==========================================================
    print_error(400, "Unsupported method in request: " . $_SERVER['REQUEST_METHOD']);
}

?>