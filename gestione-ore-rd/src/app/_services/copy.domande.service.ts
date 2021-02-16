import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';

import { Progetto, UserRuoli, Domanda } from '@/_models';

@Injectable({ providedIn: 'root' })
export class CopyDomandeService {
    constructor(private http: HttpClient) { }
    copiaDomandaConRisposte(id_questionario: number, progressivo_sezione: number, progressivo_domanda: number) {
        return this.http.post(`${config.apiUrl}/CopyDomanda.php?id_questionario=${id_questionario}&progressivo_sezione=${progressivo_sezione}&progressivo_domanda=${progressivo_domanda}`, "");
    }
    
}