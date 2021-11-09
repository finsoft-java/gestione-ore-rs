import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { environment } from '../../environments/environment';
import { ListBean, Esecuzione } from '../_models';

@Injectable({ providedIn: 'root' })
export class EsecuzioniService {
    constructor(private http: HttpClient) { }

    getAll(top: number, skip: number) {
        return this.http.get<ListBean<Esecuzione>>(environment.wsUrl + `Esecuzioni.php?top=${top}&skip=${skip}`);
    }

    /**
     * Restituisce una lista di 0 oppure 1 elementi
     */
    getLast() {
        return this.http.get<ListBean<Esecuzione>>(environment.wsUrl + `Esecuzioni.php?top=1&skip=0`);
    }

    delete(idEsecuzione: number) {
        return this.http.delete<void>(environment.wsUrl + `Esecuzioni.php?idEsecuzione=${idEsecuzione}`);
    }

}