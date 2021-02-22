<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: OPTIONS,GET,POST");
header("Access-Control-Allow-Headers: Authorization");

include("include/functions.php");
$con = connect();

if (isset($_GET['tipo'])) {
    $tipo = $con->escape_string($_GET['tipo']);
} else {
    print_error(400, "Missing tipo");
}

$options = [];

switch ($tipo) {
    case "BOOLEAN" :
        $options = $BOOLEAN;
        break;
    case "RUOLO" :
        $options = $RUOLO;
        break;
    case "STATO_PROGETTO" :
        $options = $STATO_PROGETTO;
        break;
    case "FUNZIONE" :
        $options = $FUNZIONE;
        break;
    case "GRUPPI" :
        $options = $GRUPPI;
        break;
    case "STATO_QUESTIONARIO" :
        $options = $STATO_QUESTIONARIO;
        break;
    case "QUESTIONARIO_COMPILATO_DA" :
        $options = $QUESTIONARIO_COMPILATO_DA;
        break;
    case "HTML_TYPE" :
        $options = $HTML_TYPE;
        break;
    case "STATO_QUEST_COMP" :
        $options = $STATO_QUEST_COMP;
        break;
    default:
        print_error(400, "Unknown tipo: " . $tipo);
}

header('Content-Type: application/json');
echo json_encode(['data' => $options]);
        
?>