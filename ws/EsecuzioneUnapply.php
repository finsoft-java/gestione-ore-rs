<?php

include("include/all.php");
$con = connect();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    //do nothing, HTTP 200
    exit();
}

require_logged_user_JWT();

$idEsecuzione = isset($_GET['idEsecuzione']) ? $con->escape_string($_GET['idEsecuzione']) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //==========================================================
    $object = $consuntiviProgettiManager->get_esecuzione($idEsecuzione);
    if (!$object) {
        print_error(404, 'Not found');
    }
    $consuntiviProgettiManager->unapply($idEsecuzione);
    $object = $consuntiviProgettiManager->get_esecuzione($idEsecuzione);
    header('Content-Type: application/json');
    echo json_encode(['value' => $object]);
} else {
    //==========================================================
    print_error(400, "Unsupported method in request: " . $_SERVER['REQUEST_METHOD']);
}


?>