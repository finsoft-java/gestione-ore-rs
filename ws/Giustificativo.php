<?php

include("include/all.php");
$con = connect();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    //do nothing, HTTP 200
    exit();
}

// require_logged_user_JWT();

$cod_commessa = isset($_GET['cod_commessa']) ? $con->escape_string($_GET['cod_commessa']) : null;


if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // DOWNLOAD ==========================================================
    if (!$cod_commessa) {
        print_error(400, 'Missing cod_commessa');
    }
    $object = $commesseManager->get_commessa($cod_commessa);
    if (!$object) {
        print_error(404, 'Not found');
    }

    $lista = $commesseManager->download_giustificativo($cod_commessa);
        
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // UPLOAD ==========================================================
    if (!isset($_FILES["file"]["name"])) {
        print_error(400, "Missing uploaded file.");
    }
    $origfilename =  $_FILES["file"]["name"];
    $tmpfilename =  $_FILES["file"]["tmp_name"];

    if (!$cod_commessa) {
        print_error(400, 'Missing cod_commessa');
    }
    $object = $commesseManager->get_commessa($cod_commessa);
    if (!$object) {
        print_error(404, 'Not found');
    }

    $commesseManager->upload_giustificativo($cod_commessa, $tmpfilename, $origfilename);

} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    //==========================================================
    if (!$cod_commessa) {
        print_error(400, 'Missing cod_commessa');
    }
    $object = $commesseManager->get_commessa($cod_commessa);
    if (!$object) {
        print_error(404, 'Not found');
    }

    $commesseManager->elimina_giustificativo($cod_commessa);
    
} else {
    //==========================================================
    print_error(400, "Unsupported method in request: " . $_SERVER['REQUEST_METHOD']);
}


?>