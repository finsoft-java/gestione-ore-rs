import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { environment } from '../../environments/environment';
import { ValueBean, ListBean, Esecuzione } from '../_models';

@Injectable({ providedIn: 'root' })
export class EsecuzioniService {
    constructor(private http: HttpClient) { }

    getAll(top: number, skip: number) {
        return this.http.get<ListBean<Esecuzione>>(environment.wsUrl + `Esecuzioni.php?top=${top}&skip=${skip}`);
    }

    delete(idEsecuzione: number) {
        return this.http.delete<void>(environment.wsUrl + `Esecuzioni.php?idEsecuzione=${idEsecuzione}`);
    }
    
    apply(idEsecuzione: number) {
        return this.http.post<ValueBean<Esecuzione>>(environment.wsUrl + `EsecuzioneApply.php?idEsecuzione=${idEsecuzione}`, null);
    }
    
    unapply(idEsecuzione: number) {
        return this.http.post<ValueBean<Esecuzione>>(environment.wsUrl + `EsecuzioneUnapply.php?idEsecuzione=${idEsecuzione}`, null);
    }

}