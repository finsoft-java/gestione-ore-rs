<?php
include("include/all.php");
$con = connect();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    //do nothing, HTTP 200
    exit();
}
require_logged_user_JWT();
if (isset($_GET['progressivo_quest_comp'])) {
    $progressivo_quest_comp = $con->escape_string($_GET['progressivo_quest_comp']);
}
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    //==========================================================QuestionarioCompilato
    $questionario_compilato = $questionariManager->get_questionari_validi();
    header('Content-Type: application/json');
    echo json_encode(['value' => $questionario_compilato]);
   
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    //==========================================================
    
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
} else {
    //==========================================================
    print_error(400, "Unsupported method in request: " . $_SERVER['REQUEST_METHOD']);
}


?>