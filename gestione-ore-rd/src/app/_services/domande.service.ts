import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';

import { Progetto, UserRuoli, Domanda } from '@/_models';

@Injectable({ providedIn: 'root' })
export class DomandeService {
    constructor(private http: HttpClient) { }
    creaDomandaConRisposte(d: Domanda) {
        return this.http.put(`${config.apiUrl}/Domande.php`, d);
    }
    updateDomandaConRisposte(d: Domanda) {        
        return this.http.post(`${config.apiUrl}/Domande.php`, d);
    }
    eliminaDomandaConRisposte(id_questionario: number, progressivo_sezione: number, progressivo_domanda: number) {
        return this.http.delete(`${config.apiUrl}/Domande.php?id_questionario=${id_questionario}&progressivo_sezione=${progressivo_sezione}&progressivo_domanda=${progressivo_domanda}`);
    }
}