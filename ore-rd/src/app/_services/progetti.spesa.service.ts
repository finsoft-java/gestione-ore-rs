import { Matricola } from '../_models/matricola';
import { ProgettoSpesa } from '../_models/progetto';
import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { environment } from '../../environments/environment';

@Injectable({ providedIn: 'root' })
export class ProgettiSpesaService {
    constructor(private http: HttpClient) { }
    getById(id_progetto: number) {
        return this.http.get<any>(environment.wsUrl+`ProgettiSpesa.php?id_progetto=${id_progetto}`);
    }

    insert(progettoSpesa: ProgettoSpesa) {
        return this.http.put<any>(environment.wsUrl+`ProgettiSpesa.php`, progettoSpesa);
    }

    update(progettoSpesa: ProgettoSpesa) {
        return this.http.post<any>(environment.wsUrl+`ProgettiSpesa.php`, progettoSpesa);
    }

    delete(id_spesa: number) {
        return this.http.delete(environment.wsUrl+`ProgettiSpesa.php?id_spesa=${id_spesa}`);
    }
}