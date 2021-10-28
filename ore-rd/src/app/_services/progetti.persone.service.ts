import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { environment } from '../../environments/environment';
import { ListBean, ValueBean, ProgettoPersona } from '../_models';

@Injectable({ providedIn: 'root' })
export class ProgettiPersoneService {
    constructor(private http: HttpClient) { }

    getById(idProgetto: number) {
        return this.http.get<ListBean<ProgettoPersona>>(environment.wsUrl + `ProgettiPersone.php?id_progetto=${idProgetto}`);
    }

    insert(p: ProgettoPersona) {
        return this.http.post<ValueBean<ProgettoPersona>>(environment.wsUrl + `ProgettiPersone.php`, p);
    }

    update(p: ProgettoPersona) {
        console.log("Shoud call:" + environment.wsUrl + `ProgettiPersone.php`, p)
        return this.http.put<ValueBean<ProgettoPersona>>(environment.wsUrl + `ProgettiPersone.php`, p);
    }

    delete(idProgetto: number, matricola: string) {
        return this.http.delete<void>(environment.wsUrl + `ProgettiPersone.php?matricola=${matricola}&id_progetto=${idProgetto}`);
    }
}