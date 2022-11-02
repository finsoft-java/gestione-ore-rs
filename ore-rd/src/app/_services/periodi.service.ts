import { ListBean, Periodo } from './../_models';
import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { environment } from '../../environments/environment';

@Injectable({ providedIn: 'root' })
export class PeriodiService {

    constructor(private http: HttpClient) { }

    getAll() {
        return this.http.get<ListBean<Periodo>>(environment.wsUrl + `Periodi.php`);
    }

}