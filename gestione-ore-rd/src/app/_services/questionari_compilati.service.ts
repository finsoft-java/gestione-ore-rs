import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';

import { VistaQuestionariCompilabili, QuestionarioCompilato, Sezione, RispostaQuestionarioCompilato } from '@/_models';

@Injectable({ providedIn: 'root' })
export class QuestionariCompilatiService {
    constructor(private http: HttpClient) { }

    getAll(storico: boolean) {
        // FIXME bisognerebbe passare dei parametri
        let storico_str: string = storico ? '1' : '0';
        return this.http.get<VistaQuestionariCompilabili[]>(`${config.apiUrl}/QuestionariCompilati.php?storico=${storico_str}`);
    }

    getAllFiltered(storico: boolean,top:number, skip:number,search : string, orderBy : string,mostra_solo_admin: boolean) {
        // FIXME bisognerebbe passare dei parametri
        let storico_str: string = storico ? '1' : '0';
        return this.http.get<VistaQuestionariCompilabili[]>(`${config.apiUrl}/QuestionariCompilati.php?storico=${storico_str}&top=${top}&skip=${skip}&search=${search}&orderby=${orderBy}&mostra_solo_admin=${mostra_solo_admin}`);
    }
    getRisposteUtenti(progressivo_quest_comp) {
        // gli ultimi due possono anche essere null
        return this.http.get<Object[]>(`${config.apiUrl}/QuestionariValidati.php?progressivo_quest_comp=${progressivo_quest_comp}`);
    }
    
    getById(progressivo_quest_comp) {
        // gli ultimi due possono anche essere null
        let url = `${config.apiUrl}/QuestionariCompilati.php?progressivo_quest_comp=${progressivo_quest_comp}`;
        return this.http.get<QuestionarioCompilato>(url);
    }
    getSezione(progressivo_quest_comp: number, progressivo_sezione_corrente?: number, utente_valutato_corrente?: string) {
        // gli ultimi due possono anche essere null
        let url = `${config.apiUrl}/QuestionariCompilatiSezione.php?progressivo_quest_comp=${progressivo_quest_comp}`;
        if (utente_valutato_corrente) {
            url += `&utente_valutato_corrente=${utente_valutato_corrente}`;
        }
        if (progressivo_sezione_corrente) {
            url += `&sezione_corrente=${progressivo_sezione_corrente}`;
        }
        return this.http.get<Sezione>(url);
    }
    creaNuovo(id_progetto: number, id_questionario: number){
        return this.http.put<QuestionarioCompilato>(`${config.apiUrl}/QuestionariCompilati.php`, {
            'id_progetto' : id_progetto,
            'id_questionario' : id_questionario
        });
    }
    invalida(progressivo_quest_comp: number) {
        return this.http.post(`${config.apiUrl}/InvalidaQuestionarioCompilato.php?progressivo_quest_comp=${progressivo_quest_comp}`, "");
    }
    convalida(progressivo_quest_comp: number) {
        return this.http.post(`${config.apiUrl}/ConvalidaQuestionarioCompilato.php?progressivo_quest_comp=${progressivo_quest_comp}`, "");
    }
    salvaRisposte(progressivo_quest_comp: number, risposte: RispostaQuestionarioCompilato[]) {
        return this.http.post(`${config.apiUrl}/SalvaRisposteUtente.php?progressivo_quest_comp=${progressivo_quest_comp}`, risposte);
    }
}