import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';

import { Questionario, Sezione, Domanda } from '@/_models';

@Injectable({ providedIn: 'root' })
export class QuestionariService {
    constructor(private http: HttpClient) { }

    getAll() {
        return this.http.get<Questionario[]>(`${config.apiUrl}/Questionari.php`);
    }
    getAllFiltered(top:number, skip:number,search : string, orderBy : string,mostra_solo_validi : boolean) {
        return this.http.get<Questionario[]>(`${config.apiUrl}/Questionari.php?top=${top}&skip=${skip}&search=${search}&orderby=${orderBy}&mostra_solo_validi=${mostra_solo_validi}`);
    }
    getAllValidi() {
        return this.http.get<Questionario[]>(`${config.apiUrl}/QuestionariValidi.php`);
    }
    getById(id_questionario: number) {
        return this.http.get(`${config.apiUrl}/Questionari.php?id_questionario=${id_questionario}`);
    }
    insert(q: Questionario) {
        return this.http.put(`${config.apiUrl}/Questionari.php`, q);
    }
    update(q: Questionario) {
        return this.http.post(`${config.apiUrl}/Questionari.php`, q);
    }
    delete(id_questionario: number) {
        return this.http.delete(`${config.apiUrl}/Questionari.php?id_questionario=${id_questionario}`);
    }

    deleteSezione(id_questionario:number,id_sezione: number) {
        return this.http.delete(`${config.apiUrl}/Sezioni.php?progressivo_sezione=${id_sezione}&id_questionario=${id_questionario}`);
    }
    duplica(id_questionario: number) {
        return this.http.post(`${config.apiUrl}/CopyQuestionario.php?id_questionario=${id_questionario}`, "");
    }
    getSezioneById(id_questionario: number, progressivo_sezione: number) {
        return this.http.get(`${config.apiUrl}/Sezioni.php?id_questionario=${id_questionario}&progressivo_sezione=${progressivo_sezione}`);
    }
    updateSezione(s: Sezione) {
        return this.http.post(`${config.apiUrl}/Sezioni.php`, s);
    }
    creaSezione(s: Sezione) {
        return this.http.put(`${config.apiUrl}/Sezioni.php`, s);
    }
    duplicaSezione(s: Sezione) {
        // FIXME verificare come passare i parametri
        return this.http.post(`${config.apiUrl}/CopySezione.php`,s);
    }
    
}