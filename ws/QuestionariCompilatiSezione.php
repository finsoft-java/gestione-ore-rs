<?php

// Prevedo le seguenti richieste:
// GET QuestionariCompilati?storico=true|false    -> restituisce la lista dei questionari compilabili oppure lo storico dei questionari compilati dall'utente
// GET QuestionariCompilati?progressivo_quest_comp=xxx&utente_valutato_corrente=aaa&sezione_corrente=bbb   -> restituisce il singolo questionario compilato
// PUT QuestionariCompilat          -> procedura di creazione del nuovo questionario, e anche di tutte le risposte
// POST QuestionariCompilati        -> serve per aggiornare lo stato del questionario (non le risposte!!!)

include("include/all.php");
$con = connect();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    //do nothing, HTTP 200
    exit();
}

require_logged_user_JWT();

$progressivo_quest_comp = '';
$utente_valutato_corrente = '';
$sezione_corrente = '';

if (isset($_GET['progressivo_quest_comp'])) {
    $progressivo_quest_comp = $con->escape_string($_GET['progressivo_quest_comp']);
}
if (isset($_GET['utente_valutato_corrente'])) {
    $utente_valutato_corrente = $con->escape_string($_GET['utente_valutato_corrente']);
}
if (isset($_GET['sezione_corrente'])) {
    $sezione_corrente = $con->escape_string($_GET['sezione_corrente']);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    
    if ($progressivo_quest_comp) {
        $questionario_compilato = $questionariCompilatiManager->get_sezione_questionario_compilato($progressivo_quest_comp, $sezione_corrente, $utente_valutato_corrente);
        if (!$questionario_compilato) {
            print_error(404, 'Not found');
        }
        header('Content-Type: application/json');
        echo json_encode(['value' => $questionario_compilato]);
    } else {
        print_error(404, "Missing progressivo_quest_comp");
    }
} else {
    print_error(400, "Unsupported method in request: " . $_SERVER['REQUEST_METHOD']);
}
?>