import { ProgettoWp } from './../_models/progetto';
import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { environment } from '../../environments/environment';

@Injectable({ providedIn: 'root' })
export class ProgettiWpService {
    constructor(private http: HttpClient) { }
    getById(id_progetto: number) {
        return this.http.get<any>(environment.wsUrl+`ProgettiWp.php?id_progetto=${id_progetto}`);
    }

    insert(progettoWp: ProgettoWp) {
        return this.http.put<any>(environment.wsUrl+`ProgettiWp.php`, progettoWp);
    }

    update(progettoWp: ProgettoWp) {
        return this.http.post<any>(environment.wsUrl+`ProgettiWp.php`, progettoWp);
    }

    delete(id_wp: number,id_progetto: number) {
        return this.http.delete(environment.wsUrl+`ProgettiWp.php?id_wp=${id_wp}&id_progetto=${id_progetto}`);
    }
}