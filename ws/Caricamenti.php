<?php

include("include/all.php");
$con = connect();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    //do nothing, HTTP 200
    exit();
}

require_logged_user_JWT();

$idCaricamento = isset($_GET['idCaricamento']) ? $con->escape_string($_GET['idCaricamento']) : null;
$top = isset($_GET['top']) ? $con->escape_string($_GET['top']) : null;
$skip = isset($_GET['skip']) ? $con->escape_string($_GET['skip']) : null;
$orderby = isset($_GET['orderby']) ? $con->escape_string($_GET['orderby']) : null;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    //==========================================================
    [$objects, $count] = $rapportini->get_caricamenti($skip, $top, $orderby);
        
    header('Content-Type: application/json');
    echo json_encode(['data' => $objects, 'count' => $count]);
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    //==========================================================
    if (!$idCaricamento) {
        print_error(400, 'Missing idCaricamento');
    }
    $oggetto_su_db = $rapportini->get_caricamento($idCaricamento);
    if (!$oggetto_su_db) {
        print_error(404, 'Not found');
    }
    // TODO CHECK CHE NON SIA UTILIZZATO
    
    $rapportini->elimina_caricamento($idCaricamento);
    
} else {
    //==========================================================
    print_error(400, "Unsupported method in request: " . $_SERVER['REQUEST_METHOD']);
}


?>