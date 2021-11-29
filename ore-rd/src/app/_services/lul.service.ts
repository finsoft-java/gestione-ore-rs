import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { environment } from '../../environments/environment';
import { ListBean, Lul, ValueBean } from '../_models';
import { HttpCrudService } from './HttpCrudService';
import { Observable } from 'rxjs';

@Injectable({ providedIn: 'root' })
export class LulService implements HttpCrudService<Lul> {
    constructor(private http: HttpClient) { }

    getAll(parameters: any) {
        let url = environment.wsUrl + `Lul.php?top=${parameters.top}&skip=${parameters.skip}`;
        if (parameters.matricola) {
            url += `&matricola=${parameters.matricola}`;
        }
        if (parameters.month) {
            url += `&month=${parameters.month}`;
        }
        return this.http.get<ListBean<Lul>>(url);
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