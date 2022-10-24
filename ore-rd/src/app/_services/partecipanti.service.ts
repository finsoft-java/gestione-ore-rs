import { Partecipante, ListBean, ValueBean } from './../_models';
import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { environment } from '../../environments/environment';

@Injectable({ providedIn: 'root' })
export class PartecipantiService {

    constructor(private http: HttpClient) { }

    getAll() {

        return this.http.get<ListBean<Partecipante>>(environment.wsUrl + `Partecipanti.php`);
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