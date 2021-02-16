<?php 

// Costanti per la codifica/decodifica dei campi su database
// Esempio:
//    $BOOLEAN['0'] restituisce 'No'
//    $BOOLEAN_DEC['No'] restituisce '0'


// BOOLEAN lo possiamo usare per diversi campi checkbox, es. i campi AUTOVALUTAZIONE e COMUNE
$BOOLEAN = array(
    '0' => 'No',
    '1' => 'Sì'
    );

$BOOLEAN_DEC = array_flip($BOOLEAN);

// Tabella UTENTI
$RUOLO = array(
    '0' => 'Utente normale',
    '1' => 'Gestore progetti',
    '2' => 'Amministratore'
    );

$RUOLO_DEC = array_flip($RUOLO);

// Tabella PROGETTI
$STATO_PROGETTO = array(
    '0' => 'Bozza',
    '1' => 'Valido',
    '2' => 'Annullato',
    '3' => 'Completato'
    );

$STATO_PROGETTO_DEC = array_flip($STATO_PROGETTO);

// Tabella PROGETTI_UTENTI
$FUNZIONE = array(
    '0' => 'Utente finale',
    '1' => 'Responsabile L.2',
    '2' => 'Responsabile L.1'
    );

$FUNZIONE_DEC = array_flip($FUNZIONE);

// Tabella PROGETTI_QUESTIONARI
// Attenzione assumo che gli array GRUPPI e FUNZIONE siano allineati!!!
$GRUPPI = array(
    '0' => 'Utenti finali',
    '1' => 'Responsabili L.2',
    '2' => 'Responsabili L.1'
    );

$GRUPPI_DEC = array_flip($GRUPPI);

// Tabella QUESTIONARI
$TIPO_QUESTIONARIO = array(
    '0' => 'Q. di valutazione',
    '1' => 'Q. generico'
    );

$TIPO_QUESTIONARIO_DEC = array_flip($TIPO_QUESTIONARIO);

$STATO_QUESTIONARIO = array(
    '0' => 'Bozza',
    '1' => 'Valido',
    '2' => 'Annullato'
    );

$STATO_QUESTIONARIO_DEC = array_flip($STATO_QUESTIONARIO);

// Tabella DOMANDE
$HTML_TYPE = array(
    '0' => 'text',
    '1' => 'number',
    '2' => 'date',
    '3' => 'button',
    '4' => 'checkbox',
    '5' => 'color',
    '6' => 'datetime-local',
    '7' => 'month',
    '8' => 'range',
    '9' => 'tel',
    'A' => 'time',
    'B' => 'week'
    );

$HTML_TYPE_DEC = array_flip($HTML_TYPE);

// Tabella QUESTIONARI COLPILATI
$STATO_QUEST_COMP = array(
    '0' => 'Bozza',
    '1' => 'Valido',
    '2' => 'Annullato'
    );

$STATO_QUEST_COMP_DEC = array_flip($STATO_QUEST_COMP);


?>