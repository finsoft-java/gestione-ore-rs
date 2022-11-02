import { Commessa, ListBean } from './../_models';
import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { environment } from '../../environments/environment';

@Injectable({ providedIn: 'root' })
export class CommesseService {

    constructor(private http: HttpClient) { }

    getAll() {

        return this.http.get<ListBean<Commessa>>(environment.wsUrl + `Commesse.php`);
    }

}