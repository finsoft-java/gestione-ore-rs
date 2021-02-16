<?php

/*
Per creare fogli Excel usiamo https://github.com/mk-j/PHP_XLSXWriter
Richiede delle estensioni PHP
in Ubuntu:
sudo apt-get install php-zip
*/
include("include/all.php");
$con = connect();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    //do nothing, HTTP 200
    exit();
}

require_logged_user_JWT();

if (isset($_GET['id_progetto'])) {
    $id_progetto = $con->escape_string($_GET['id_progetto']);
} else {
    print_error(400, "Missing id_progetto");
}

if (!$progettiManager->get_progetto($id_progetto)->utente_puo_modificarlo()) {
    print_error(403, "Utente non abilitato alla stampa del Progetto");
}

$pq = $progettiManager->get_progetti_questionari_validi($id_progetto);
if (!$pq) {
    # N.B. sto cotrollando solo l'esistenza di Questionari, non di Questionari compilati :(
    print_error(404, "Questo Progetto non ha Questionari Validi associati");
}

$filename = $xlsxManager->crea_file_xlsx($id_progetto, $pq);

header('Content-Description: File Transfer');
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename='.basename($filename));
header('Content-Transfer-Encoding: binary');
header("Content-Encoding: UTF-8");
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($filename));
ob_clean();
flush();
readfile($filename);

?>