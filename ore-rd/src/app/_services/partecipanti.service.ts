import { Partecipante, ListBean, ValueBean } from './../_models';
import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { environment } from '../../environments/environment';

@Injectable({ providedIn: 'root' })
export class PartecipantiService {

    constructor(private http: HttpClient) { }

    getAll(top: number, skip: number) {

        return this.http.get<ListBean<Partecipante>>(environment.wsUrl + `Partecipanti.php?top=${top}&skip=${skip}`);
    }

    getAllWithFilter(top:number, skip:number, denominazione: string, matricola: string, prcUtilizzo:string, mansione: string, dataInizio: string, dataFine: string, controlloCosto: boolean) {

        return this.http.get<ListBean<Partecipante>>(environment.wsUrl + `Partecipanti.php?filter=Y&top=${top}&skip=${skip}&denominazione=${denominazione}&matricola=${matricola}&prcUtilizzo=${prcUtilizzo}&controlloCosto=${controlloCosto}&mansione=${mansione}&dataInizio=${dataInizio}&dataFine=${dataFine}`);
    }

    insert(partecipante: Partecipante) {

        return this.http.post<ValueBean<Partecipante>>(environment.wsUrl + `Partecipanti.php`, partecipante);
    }

    update(partecipante: Partecipante) {

        return this.http.put<ValueBean<Partecipante>>(environment.wsUrl + `Partecipanti.php`, partecipante);
    }

    delete(id_dipendente: string) {

        return this.http.delete(environment.wsUrl + `Partecipanti.php?id_dipendente=${id_dipendente}`);
    }

    getAllMatricole() {

        return this.http.get<ListBean<any>>(environment.wsUrl + `GetNomiUtenti.php`);
    }

}