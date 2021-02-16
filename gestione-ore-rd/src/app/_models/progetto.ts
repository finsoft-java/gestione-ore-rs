export class Progetto {
    id_progetto: number;
    titolo: string;
    stato: string;
    stato_dec: string;
    gia_compilato: string;
    gia_compilato_dec: string;
    utente_creazione: string;
    nome:string;
    cognome:string;
    data_creazione: Date;
    utenti: ProgettoUtenti[];
    questionari: ProgettoQuestionari[];
}

export class ProgettoUtenti {
    id_progetto: number;
    nome_utente: string;
    funzione: string;
    funzione_dec: string;
}

export class ProgettoQuestionari {
    id_progetto: number;
    id_questionario: number;
    titolo_questionario: string;
    stato_questionario: string;
    stato_questionario_dec: string;
    tipo_questionario: string;
    tipo_questionario_dec: string;
    gruppo_compilanti: string;
    gruppo_compilanti_dec: string;
    gruppo_valutati: string;
    gruppo_valutati_dec: string;
    autovalutazione: string;
    autovalutazione_dec: string;
    autovalutazione_bool: boolean;
    ut_creaz_questionario: string;
    data_creaz_questionario: Date;
    // Questi servono per gestire la modifica via web
    editing?: boolean;
    creating?: boolean;
}