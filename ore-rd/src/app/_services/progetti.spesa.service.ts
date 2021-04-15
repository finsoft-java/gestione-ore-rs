import { ListBean, ValueBean, ProgettoSpesa } from '../_models';
import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { environment } from '../../environments/environment';

@Injectable({ providedIn: 'root' })
export class ProgettiSpesaService {
    constructor(private http: HttpClient) { }

    getById(id_progetto: number) {
        return this.http.get<ListBean<ProgettoSpesa>>(environment.wsUrl + `ProgettiSpesa.php?id_progetto=${id_progetto}`);
    }

    insert(progettoSpesa: ProgettoSpesa) {
        return this.http.post<ValueBean<ProgettoSpesa>>(environment.wsUrl + `ProgettiSpesa.php`, progettoSpesa);
    }

    update(progettoSpesa: ProgettoSpesa) {
        return this.http.put<ValueBean<ProgettoSpesa>>(environment.wsUrl + `ProgettiSpesa.php`, progettoSpesa);
    }

    delete(id_progetto: number, id_spesa: number) {
        return this.http.delete<void>(environment.wsUrl + `ProgettiSpesa.php?id_progetto=${id_progetto}&id_spesa=${id_spesa}`);
    }
}