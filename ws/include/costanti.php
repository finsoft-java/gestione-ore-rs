<?php 

// Costanti per la codifica/decodifica dei campi su database
// Esempio:
//    $BOOLEAN['0'] restituisce 'No'
//    $BOOLEAN_DEC['No'] restituisce '0'


$BOOLEAN = array(
    '0' => 'No',
    '1' => 'Sì'
    );

$BOOLEAN_DEC = array_flip($BOOLEAN);


?>