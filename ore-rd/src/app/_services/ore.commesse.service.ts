import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { environment } from '../../environments/environment';
import { ListBean, OreCommesse, ValueBean } from '../_models';
import { HttpCrudService } from './HttpCrudService';
import { Observable } from 'rxjs';

@Injectable({ providedIn: 'root' })
export class OreCommesseService implements HttpCrudService<OreCommesse> {
    constructor(private http: HttpClient) { }

    getAllDettagli(parameters: any) {
        console.log(parameters);
        let url = environment.wsUrl + 'OreCommesse.php?dettagli=Y';
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

        if (parameters.dataFine && parameters.dataInizio) {
            url += `&dataFine=${parameters.dataFine}&dataInizio=${parameters.dataInizio}`;
        }
        return this.http.get<ListBean<string>>(url);
    }

    getAll(parameters: any) {
        console.log(parameters);
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

        if (parameters.dataFine && parameters.dataInizio) {
            url += `&dataFine=${parameters.dataFine}&dataInizio=${parameters.dataInizio}`;
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