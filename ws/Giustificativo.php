<?php

include("include/all.php");
$con = connect();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    //do nothing, HTTP 200
    exit();
}

// require_logged_user_JWT();

$id_progetto = isset($_GET['id_progetto']) ? $con->escape_string($_GET['id_progetto']) : null;
$cod_commessa = isset($_GET['cod_commessa']) ? $con->escape_string($_GET['cod_commessa']) : null;


if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // DOWNLOAD ==========================================================
    if (!$id_progetto) {
        print_error(400, 'Missing id_progetto');
    }
    if (!$cod_commessa) {
        print_error(400, 'Missing cod_commessa');
    }
    $object = $progettiCommesseManager->get_commessa($id_progetto, $cod_commessa);
    if (!$object) {
        print_error(404, 'Not found');
    }

    $lista = $progettiCommesseManager->download_giustificativo($id_progetto, $cod_commessa);
        
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // UPLOAD ==========================================================
    if (!isset($_FILES["file"]["name"])) {
        print_error(400, "Missing uploaded file.");
    }
    $origfilename =  $_FILES["file"]["name"];
    $tmpfilename =  $_FILES["file"]["tmp_name"];

    if (!$id_progetto) {
        print_error(400, 'Missing id_progetto');
    }
    if (!$cod_commessa) {
        print_error(400, 'Missing cod_commessa');
    }
    $object = $progettiCommesseManager->get_commessa($id_progetto, $cod_commessa);
    if (!$object) {
        print_error(404, 'Not found');
    }

    $progettiCommesseManager->upload_giustificativo($id_progetto, $cod_commessa, $tmpfilename, $origfilename);

} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    //==========================================================
    if (!$id_progetto) {
        print_error(400, 'Missing id_progetto');
    }
    if (!$cod_commessa) {
        print_error(400, 'Missing cod_commessa');
    }
    $object = $progettiCommesseManager->get_commessa($id_progetto, $cod_commessa);
    if (!$object) {
        print_error(404, 'Not found');
    }

    $progettiCommesseManager->elimina_giustificativo($id_progetto, $cod_commessa);
    
} else {
    //==========================================================
    print_error(400, "Unsupported method in request: " . $_SERVER['REQUEST_METHOD']);
}


?>