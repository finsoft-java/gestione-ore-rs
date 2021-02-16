<?php

/*
Per creare fogli Excel usiamo https://github.com/mk-j/PHP_XLSXWriter
Richiede delle estensioni PHP
in Ubuntu:
sudo apt-get install php-zip
*/


$xlsxManager = new XLSXManager();

class XLSXManager {

    /**
     * Routine principale, che genera l'intero file XLSX
     */
    function crea_file_xlsx($id_progetto, $pq) {
        $writer = new XLSXWriter();
        foreach ($pq as $progetto_questionario) {
            $this->crea_sheet_questionario($writer, $progetto_questionario);
        }
        $filename = $this->get_new_file($id_progetto);
        if (file_exists($filename)) unlink($filename);
        $writer->writeToFile($filename);
        return $filename;
    }

    /**
     * Restituisce un nome (completo) idoneo per un file XLSX.
     */
    function get_new_file($id_progetto) {
        return sys_get_temp_dir() . DIRECTORY_SEPARATOR . "ReportQuestionariProgetto-" . str_pad($id_progetto, 4, '0', STR_PAD_LEFT) . ".xlsx";
    }

    /**
     * Crea un singolo Sheet dentro il file XLSX
     * 1 sheet = 1 Questionario
     * 1 row = 1 Questionario compilato e 1 utente valutato
     */
    function crea_sheet_questionario($writer, $progetto_questionario) {
        global $questionariCompilatiManager;
        global $progettiManager;
        
        $titoloSheet = $progetto_questionario->titolo_questionario;
        $domande = $progetto_questionario->get_questionario()->get_domande_appiattite();
        $header = ["Utente compilante" => "string", "Utente valutato" => "string"];
        foreach ($domande as $d) {
            #la cosa pazzesca è che non posso avere 2 colonne con la stessa header!!!
            $html_dm = strip_tags($d->descrizione);
            $caption = "$d->progressivo_sezione.$d->progressivo_domanda $html_dm";
            $header[$caption] = "string";

            $caption = "$d->progressivo_sezione.$d->progressivo_domanda Punteggio Calcolato";
            $header[$caption] = "string";

            $caption = "$d->progressivo_sezione.$d->progressivo_domanda Note";
            $header[$caption] = "string";
        }

        // TODO AUTOSIZE ?!?
        // TODO e il valore della risposta? che ce ne facciamo?

        $col_options = [
            'font-style'=>'bold',
            'fill'=>'#66ffb3',
            'wrap_text' => true,
            'widths' => array_merge(['20', '20'], array_fill(0, count($domande)+2,'30'))
        ];

        $writer->writeSheetHeader($titoloSheet, $header, $col_options);

        $questionatiCompilati = $questionariCompilatiManager->get_questionari_compilati($progetto_questionario->id_progetto, $progetto_questionario->id_questionario);
        $utenti_compilanti = $progettiManager->get_utenti_compilanti($progetto_questionario->id_progetto, $progetto_questionario->id_questionario);
        //ora cerco gli utenti mancanticd
        if (!$questionatiCompilati) {
            $writer->writeSheetRow($titoloSheet, ['Questo Questionario non è mai stato compilato!']);
        } else {
            //devo comporre le righe con i nomi utenti e poi le risposte
            foreach ($questionatiCompilati as $qc) {

                $tutte_le_risposte = $qc->get_tutte_le_risposte_divise_per_utente();
                $pos = array_search($qc->utente_compilazione, $utenti_compilanti);
                unset($utenti_compilanti[$pos]);
                
                foreach($tutte_le_risposte as $utente_valutato => $risposte) {
                    $this->crea_riga_sheet($writer, $titoloSheet, $qc->utente_compilazione, $utente_valutato, $risposte);
                }
            }
            foreach($utenti_compilanti as $u) {
                $this->crea_riga_sheet($writer, $titoloSheet, $u, "", []);
            }
        }
    }

    /**
     * Scrive 1 riga su uno sheet XLSX
     * 1 row = 1 Questionario compilato e 1 utente valutato
     * 
     */
    function crea_riga_sheet($writer, $titoloSheet, $utente_compilazione, $utente_valutato, $risposte) {
        $row = [$utente_compilazione, $utente_valutato];
        for($i = 0; $i < count($risposte); $i++){
            $row[] = $risposte[$i]->get_desc_risposta();
            $row[] = $risposte[$i]->prodotto;
            $row[] = $risposte[$i]->note;
        }
        $row = array_map(function($x){return ($x != null) ? $x : "-";}, $row);
        $writer->writeSheetRow($titoloSheet, $row);
    }
}
