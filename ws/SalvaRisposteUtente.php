<?php

//
// Prevedo le seguenti richieste:
// POST SalvaRisposteUtente?progressivo_quest_comp=xxx

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
    $json_data_array = json_decode($postdata);
    if (!$json_data_array) {
        print_error(400, "Missing JSON data");
    }
    if (!is_array($json_data_array)) {
        print_error(400, "Era atteso un Array di risposte.");
    }
    $progressivo_quest_comp = $json_data_array[0]->progressivo_quest_comp;
    $progressivo_sezione = $json_data_array[0]->progressivo_sezione;
    $nome_utente_valutato = $json_data_array[0]->nome_utente_valutato;

    foreach ($json_data_array as $json_data) {
        if ($json_data->progressivo_quest_comp != $progressivo_quest_comp ||
            $json_data->progressivo_sezione != $progressivo_sezione ||
            $json_data->nome_utente_valutato != $nome_utente_valutato ) {
            print_error(400, "Le risposte salvate devono appartenere tutte allo stesso questionario / sezione / utente valutato");
        }
    }
    $questionario_compilato = $questionariCompilatiManager->get_questionario_compilato($progressivo_quest_comp);
    if (!$questionario_compilato) {
        print_error(404, "Not found.");
    }
    if ($questionario_compilato->utente_compilazione !== $logged_user->nome_utente) {
        print_error(403, "L'utente sta cercando di salvare un questionario che non è suo.");
    }
    if ($questionario_compilato->stato <> '0') {
        print_error(403, "Il Questionario Compilato deve essere in stato Bozza.");
    }
    if ($questionario_compilato->get_questionario()->stato <> '1') {
        print_error(403, "Il Questionario non è in stato Valido.");
    }
    $questionariCompilatiManager->update_risposte_sezione($json_data_array, $questionario_compilato, $progressivo_sezione, $nome_utente_valutato);
    
    
} else {
    //==========================================================
    print_error(400, "Unsupported method in request: " . $_SERVER['REQUEST_METHOD']);
}

?>