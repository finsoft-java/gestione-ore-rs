import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { environment } from '../../environments/environment';
import { ListBean, Lul, LulSpecchietto, ValueBean } from '../_models';
import { HttpCrudService } from './HttpCrudService';
import { Observable } from 'rxjs';

@Injectable({ providedIn: 'root' })
export class LulService implements HttpCrudService<Lul> {
    constructor(private http: HttpClient) { }

    getAll(parameters: any) {
        let url = environment.wsUrl + 'Lul.php?';
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
        return this.http.get<ListBean<Lul>>(url);
    }

    getSpecchietto(top: number, skip: number, parameters: any) {
        let url = environment.wsUrl + 'LulSpecchietto.php?';
        if (top) {
            url += `&top=${top}`;
        }
        if (skip) {
            url += `&skip=${skip}`;
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
        return this.http.get<ListBean<LulSpecchietto>>(url);
    }

    create(obj: Lul): Observable<ValueBean<Lul>> {
        throw new Error('Method not implemented.');
    }
    update(obj: Lul): Observable<ValueBean<Lul>> {
        throw new Error('Method not implemented.');
    }
    delete(obj: Lul): Observable<void> {
        throw new Error('Method not implemented.');
    }
}