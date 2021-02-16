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

$storico = isset($_GET['storico']) ? $con->escape_string($_GET['storico']) : false;
$progressivo_quest_comp = isset($_GET['progressivo_quest_comp']) ? $con->escape_string($_GET['progressivo_quest_comp']) : null;
$top = isset($_GET['top']) ? $con->escape_string($_GET['top']) : null;
$skip = isset($_GET['skip']) ? $con->escape_string($_GET['skip']) : null;
$search = isset($_GET['search']) ? $con->escape_string($_GET['search']) : null;
$orderby = isset($_GET['orderby']) ? $con->escape_string($_GET['orderby']) : null;
$mostra_solo_admin = isset($_GET['mostra_solo_admin']) ? ($con->escape_string($_GET['mostra_solo_admin']) === "true" ? true : false) : false;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    
    if ($progressivo_quest_comp) {
        
        //==========================================================
        $questionario_compilato = $questionariCompilatiManager->get_questionario_compilato($progressivo_quest_comp);
        if (!$questionario_compilato) {
            print_error(404, 'Not found');
        }
        header('Content-Type: application/json');
        echo json_encode(['value' => $questionario_compilato]);
    } else {
        //==========================================================
        [$questionari, $count] = $questionariCompilatiManager->get_vista_questionari_compilabili_o_compilati($storico, null, null, $top, $skip, $orderby, $search, $mostra_solo_admin);
        
        header('Content-Type: application/json');
        echo json_encode(['data' => $questionari, 'count' => $count]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    //==========================================================
    
    $postdata = file_get_contents("php://input");
    $json_data = json_decode($postdata);
    if(!$json_data) {
        print_error(400, "Missing json_data");
    }
    $pq = $progettiManager->get_progetto_questionari($json_data->id_progetto, $json_data->id_questionario);
    if (!$pq) {
        print_error(404, "id_progetto e/o id_questionario errati");
    }
    $pq = $questionariCompilatiManager->get_vista_questionario_compilabile_o_compilato($json_data->id_progetto, $json_data->id_questionario);
    if (!$pq) {
        print_error(404, 'Not found');
    }
    if ($pq->stato_progetto <> '1') {
        print_error(403, "Progetto non Valido");
    }
    if ($pq->stato_questionario <> '1') {
        print_error(403, "Questionario non Valido");
    }
    
    $questionario_compilato = $questionariCompilatiManager->crea_questionario_compilato($pq);
        
    header('Content-Type: application/json');
    echo json_encode(['value' => $questionario_compilato]);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //==========================================================
    
    //FIXME tutta qusta parte forse è inutile, perchè dobbiamo salvare una sezione per volta
    
    
    
    $postdata = file_get_contents("php://input");
    $json_data = json_decode($postdata);
    
    if(!$json_data) {
        print_error(400, "Missing json_data");
    }
    
    $questionario_compilato = $questionariCompilatiManager->get_questionario_compilato($progressivo_quest_comp);
    if (!$questionario_compilato) {
        print_error(404, 'Not found');
    }
    
    // L'amministratore può confermarlo oppure annullarlo
    // L'utente può solo confermare il suo lavoro
    if (utente_admin()) {
        $questionariCompilatiManager->cambia_stato($questionario_compilato, $json_data->stato);
    } else if ($questionario_compilato->utente_compilazione === $logged_user->nome_utente) {
        if ($json_data->stato == '1') {
            $questionariCompilatiManager->cambia_stato($questionario_compilato, $json_data->stato);
        } else {
            print_error(403, "L'unica modifica ammessa dall'utente è la conferma del Questionario Compilato");
        }
    } else {
        print_error(403, "Utente non autorizzato alla modifica di questo Questionario Compilato");
    }
    
} else {
    //==========================================================
    print_error(400, "Unsupported method in request: " . $_SERVER['REQUEST_METHOD']);
}


?>