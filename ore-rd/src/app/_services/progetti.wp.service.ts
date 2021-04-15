import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { environment } from '../../environments/environment';
import { ListBean, ValueBean, ProgettoWp } from '../_models';

@Injectable({ providedIn: 'root' })
export class ProgettiWpService {
    constructor(private http: HttpClient) { }

    getById(id_progetto: number) {
        return this.http.get<ListBean<ProgettoWp>>(environment.wsUrl + `ProgettiWp.php?id_progetto=${id_progetto}`);
    }

    insert(progettoWp: ProgettoWp) {
        return this.http.post<ValueBean<ProgettoWp>>(environment.wsUrl + `ProgettiWp.php`, progettoWp);
    }

    update(progettoWp: ProgettoWp) {
        return this.http.put<ValueBean<ProgettoWp>>(environment.wsUrl + `ProgettiWp.php`, progettoWp);
    }

    delete(id_wp: number, id_progetto: number) {
        return this.http.delete<void>(environment.wsUrl + `ProgettiWp.php?id_wp=${id_wp}&id_progetto=${id_progetto}`);
    }
}