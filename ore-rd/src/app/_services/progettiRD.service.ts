import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { environment } from '../../environments/environment';
import { ListBean, Lul, LulSpecchietto, ProgettoRD, ValueBean } from '../_models';
import { HttpCrudService } from './HttpCrudService';
import { Observable } from 'rxjs';

@Injectable({ providedIn: 'root' })
export class ProgettiRDService implements HttpCrudService<ProgettoRD> {
    constructor(private http: HttpClient) { }

    getAll(parameters: any) {
        let url = environment.wsUrl + 'OreProgettiRD.php?';
        console.log("parameters ",parameters);
        if (parameters.top) {
            url += `&top=${parameters.top}`;
        }
        if (parameters.skip) {
            url += `&skip=${parameters.skip}`;
        }
        if (parameters.matricola) {
            url += `&matricola=${parameters.matricola}`;
        }
        if (parameters.month) {
            url += `&month=${parameters.month}`;
        }
        if (parameters.dataInizio) {
            url += `&dataInizio=${parameters.dataInizio}`;
        }
        if (parameters.dataFine) {
            url += `&dataFine=${parameters.dataFine}`;
        }
        if (parameters.progetto) {
            url += `&progetto=${parameters.progetto}`;
        }
        if (parameters.searchProgetto) {
            url += `&searchProgetto=${parameters.searchProgetto}`;
        }
        return this.http.get<ListBean<ProgettoRD>>(url);
    }

    create(obj: ProgettoRD): Observable<ValueBean<ProgettoRD>> {
        throw new Error('Method not implemented.');
    }
    update(obj: ProgettoRD): Observable<ValueBean<ProgettoRD>> {
        throw new Error('Method not implemented.');
    }
    delete(obj: ProgettoRD): Observable<void> {
        throw new Error('Method not implemented.');
    }
}