import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { environment } from '../../environments/environment';
import { ListBean, Caricamento } from '../_models';

@Injectable({ providedIn: 'root' })
export class CaricamentiRDService {
    constructor(private http: HttpClient) { }

    getAll(top: number, skip: number) {
        return this.http.get<ListBean<Caricamento>>(environment.wsUrl + `CaricamentiRD.php?top=${top}&skip=${skip}`);
    }

    /**
     * Restituisce una lista di 0 oppure 1 elementi
     */
    getLast() {
        return this.http.get<ListBean<Caricamento>>(environment.wsUrl + `CaricamentiRD.php?top=1&skip=0`);
    }

    delete(idCaricamento: number) {
        return this.http.delete<void>(environment.wsUrl + `CaricamentiRD.php?idCaricamento=${idCaricamento}`);
    }

}