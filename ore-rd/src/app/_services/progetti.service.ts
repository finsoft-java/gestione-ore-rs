import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { environment } from './../../environments/environment';
import { ValueBean, Progetto, ListBean } from '../_models';

@Injectable({ providedIn: 'root' })
export class ProgettiService {
    constructor(private http: HttpClient) { }

    getAll(top: number, skip: number) {
        return this.http.get<ListBean<Progetto>>(environment.wsUrl + `Progetti.php?top=${top}&skip=${skip}`);
    }
    
    getById(id_progetto: number) {
        return this.http.get<ValueBean<Progetto>>(environment.wsUrl + `Progetti.php?id_progetto=${id_progetto}`);
    }

    insert(progetto: Progetto) {
        return this.http.post<ValueBean<Progetto>>(environment.wsUrl + `Progetti.php`, progetto);
    }

    update(progetto: Progetto) {
        return this.http.put<ValueBean<Progetto>>(environment.wsUrl + `Progetti.php`, progetto);
    }

    delete(id_progetto: number) {
        return this.http.delete<void>(environment.wsUrl + `Progetti.php?id_progetto=${id_progetto}`);
    }

    getAllTipiCostoPanthera() {
        return this.http.get<ListBean<any>>(environment.wsUrl + `GetTipiCosto.php`);;
    }

    getAllMatricole() {
        return this.http.get<ListBean<string>>(environment.wsUrl + `GetNomiUtenti.php`);;
    }
}