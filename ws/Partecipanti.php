<?php

include("include/all.php");
$con = connect();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    //do nothing, HTTP 200
    exit();
}

require_logged_user_JWT();
$filter = isset($_GET['filter']) ? $_GET['filter'] : null;
$denominazione = isset($_GET['denominazione']) ? $con->escape_string($_GET['denominazione']) : null;
$matricola = isset($_GET['matricola']) ? $con->escape_string($_GET['matricola']) : null;
$prcUtilizzo = isset($_GET['prcUtilizzo']) ? $con->escape_string($_GET['prcUtilizzo']) : null;
$mansione = isset($_GET['mansione']) ? $con->escape_string($_GET['mansione']) : null;
$dataInizio = isset($_GET['dataInizio']) ? $con->escape_string($_GET['dataInizio']) : null;
$dataFine = isset($_GET['dataFine']) ? $con->escape_string($_GET['dataFine']) : null;
$controlloCosto = isset($_GET['controlloCosto']) ? $con->escape_string($_GET['controlloCosto']) : false;

$id_dipendente = isset($_GET['id_dipendente']) ? $con->escape_string($_GET['id_dipendente']) : null;
$top = isset($_GET['top']) ? $con->escape_string($_GET['top']) : null;
$skip = isset($_GET['skip']) ? $con->escape_string($_GET['skip']) : null;
$orderby = isset($_GET['orderby']) ? $con->escape_string($_GET['orderby']) : null;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($id_dipendente) {
        //==========================================================
        $progetto = $partecipantiManager->get_partecipante($id_dipendente);
        if (!$progetto) {
            print_error(404, 'Not found');
        }
        header('Content-Type: application/json');
        echo json_encode(['value' => $progetto]);
    } else {
        //==========================================================
        if($dataInizio == null && $dataFine == null) {
            $allPeriodi = $commesseManager->get_periodi();
            foreach ($allPeriodi as $data) {
                $dataInizio = $data['DATA_INIZIO'];
                $dataFine = $data['DATA_FINE'];
                break;
            }
        }
        [$progetti, $count] = $partecipantiManager->get_partecipanti($top, $skip, $orderby, $denominazione, $matricola, $prcUtilizzo, $mansione, $dataInizio, $dataFine, $controlloCosto);
        
        header('Content-Type: application/json');
        echo json_encode(['data' => $progetti, 'count' => $count, 'dataInizio' => $dataInizio, 'dataFine' => $dataFine ]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //==========================================================
    $postdata = file_get_contents("php://input");
    $json_data = json_decode($postdata);
    
    if (!$json_data) {
        print_error(400, "Missing JSON data");
    }
    $object = $partecipantiManager->crea($json_data);
    
    header('Content-Type: application/json');
    echo json_encode(['value' => $object]);
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    //==========================================================
    $postdata = file_get_contents("php://input");
    $json_data = json_decode($postdata);
    if (!$json_data) {
        print_error(400, "Missing JSON data");
    }
    $object = $partecipantiManager->get_partecipante($json_data->ID_DIPENDENTE);
    if (!$object) {
        print_error(404, 'Not found');
    }
    $partecipantiManager->aggiorna($object, $json_data);
    
    $object = $partecipantiManager->get_partecipante($json_data->ID_DIPENDENTE);
    header('Content-Type: application/json');
    echo json_encode(['value' => $object]);
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    //==========================================================
    if (!$id_dipendente) {
        print_error(400, 'Missing id_dipendente');
    }
    $progetto_su_db = $partecipantiManager->get_partecipante($id_dipendente);
    if (!$progetto_su_db) {
        print_error(404, 'Not found');
    }
    
    $partecipantiManager->elimina($id_dipendente);
    
} else {
    //==========================================================
    print_error(400, "Unsupported method in request: " . $_SERVER['REQUEST_METHOD']);
}


?>