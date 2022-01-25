import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { environment } from '../../environments/environment';
import { ListBean, OreCommesse, ValueBean } from '../_models';
import { HttpCrudService } from './HttpCrudService';
import { Observable } from 'rxjs';

@Injectable({ providedIn: 'root' })
export class OreCommesseService implements HttpCrudService<OreCommesse> {
    constructor(private http: HttpClient) { }

    getAll(parameters: any) {

        let url = environment.wsUrl + 'OreCommesse.php?';
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
        return this.http.get<ListBean<OreCommesse>>(url);
    }

    create(obj: OreCommesse): Observable<ValueBean<OreCommesse>> {
        throw new Error('Method not implemented.');
    }
    update(obj: OreCommesse): Observable<ValueBean<OreCommesse>> {
        throw new Error('Method not implemented.');
    }
    delete(obj: OreCommesse): Observable<void> {
        throw new Error('Method not implemented.');
    }

}