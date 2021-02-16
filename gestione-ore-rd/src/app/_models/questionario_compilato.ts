import { Sezione, RispostaAmmessa } from ".";
import { Progetto } from "./progetto";
import { Questionario } from "./questionario";
import { User } from "./user";

export class QuestionarioCompilato {
    progressivo_quest_comp: number;
    id_progetto: number;
    id_questionario: number;
    stato: string;
    utente_compilazione: string;
    data_compilazione: Date;
    progetto: Progetto;
    questionario: Questionario;
    sezioni?: Sezione[];
    utenti_valutati?: User[];
    is_compilato: string;
    progressivo_sezione_corrente : number;
    utente_valutato_corrente : string;
}

export class RispostaQuestionarioCompilato {
    progressivo_quest_comp: number;
    progressivo_sezione: number;
    progressivo_domanda: number;
    nome_utente_valutato: string;
    progressivo_risposta: number;
    risposta_aperta: string;
    note: string;
}

export class VistaQuestionariCompilabili {
    id_progetto: number;
    titolo_progetto: string;
    stato_progetto: string;
    stato_progetto_dec: string;
    id_questionario: number;
    titolo_questionario: string;
    stato_questionario: string;
    stato_questionario_dec: string;
    gruppo_compilanti: string;
    gruppo_compilanti_dec: string;
    gruppo_valutati: string;
    gruppo_valutati_dec: string;
    autovalutazione: string;
    autovalutazione_dec: string;
    progressivo_quest_comp: number;
    stato_quest_comp: string;
    stato_quest_comp_dec: string;
    nome_utente: string;
    nome:string;
    cognome:string;
    data_compilazione: string;

}