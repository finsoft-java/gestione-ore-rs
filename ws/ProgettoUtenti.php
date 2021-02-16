<?php

// Prevedo le seguenti richieste:
// OPTIONS
// GET User  -> lista di tutti gli utenti
// GET User?username=xxx  -> singolo utente
// PUT User -> creazione nuovo utente
// POST User -> update utente esistente
// DELETE User?username=xxx -> elimina utente esistente

include("include/all.php");    
$con = connect();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    //do nothing, HTTP 200
    exit();
}
    
require_logged_user_JWT();

$username = null;
if (isset($_GET['username'])) {
    $username = $con->escape_string($_GET['username']);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $id_progetto = null;
    if (isset($_GET['id_progetto'])) {
        $id_progetto = $con->escape_string($_GET['id_progetto']);
    }
    if (!$id_progetto) {
        print_error(404, "Missing id_progetto");
    }
    //==========================================================
    $utenti = $progettiManager->get_utenti_funzioni($id_progetto);
    if (!$utenti) {
        print_error(404, 'Not found');
    }
    header('Content-Type: application/json');
    echo json_encode(['data' => $utenti]);    

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
  $postdata = file_get_contents("php://input");
  $json_data = json_decode($postdata);
  if (!$json_data) {
      print_error(400, "Missing JSON data");
  }
  $id_progetto = $json_data[0]->id_progetto;
  $progetto_su_db = $progettiManager->get_progetto($id_progetto);
  if (!$progetto_su_db) {
      print_error(404, 'Not found');
  }
  if (!$progetto_su_db->utente_puo_modificarlo()) {
      print_error(403, "Utente non autorizzato a modificare questo Progetto.");
  }
  /*
  if ($progetto_su_db->is_gia_compilato()) {
      print_error(403, "Esistono questionari già compilati, le uniche modifiche permesse sono la conferma e la riapertura");
  }
  */
  $progettiManager->save_utenti_funzioni($progetto_su_db, $json_data);

} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    print_error(401, 'PUT NON IMPLEMENTATO');
} else {
    print_error(400, "Unsupported method in request: " . $_SERVER['REQUEST_METHOD']);
}

?>