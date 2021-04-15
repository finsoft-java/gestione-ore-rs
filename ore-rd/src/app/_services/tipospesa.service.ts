import { Tipologia, ListBean, ValueBean } from './../_models';
import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { environment } from '../../environments/environment';

@Injectable({ providedIn: 'root' })
export class TipologiaSpesaService {
    constructor(private http: HttpClient) { }

    getAll() {
        return this.http.get<ListBean<Tipologia>>(environment.wsUrl + `TipologiaSpesa.php`);
    }
    
    insert(tipologia: Tipologia) {
        return this.http.put<ValueBean<Tipologia>>(environment.wsUrl + `TipologiaSpesa.php`, tipologia);
    }

    update(tipologia: Tipologia) {
        return this.http.post<ValueBean<Tipologia>>(environment.wsUrl + `TipologiaSpesa.php`, tipologia);
    }

    delete(id_tipologia: number) {
        return this.http.delete(environment.wsUrl + `TipologiaSpesa.php?id_tipologia=${id_tipologia}`);
    }
}