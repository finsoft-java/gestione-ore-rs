import { Progetto } from './../_models/progetto';
import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { environment } from './../../environments/environment';

@Injectable({ providedIn: 'root' })
export class ProgettiService {
    constructor(private http: HttpClient) { }
    getAll() {
        return this.http.get<any>(environment.wsUrl+`Progetti.php`);
    }
    getById(id_progetto: number) {
        return this.http.get<any>(environment.wsUrl+`Progetti.php?id_progetto=${id_progetto}`);
    }
    insert(progetto: Progetto) {
        return this.http.put(environment.wsUrl+`Progetti.php`, progetto);
    }
    update(progetto: Progetto) {
        return this.http.post(environment.wsUrl+`Progetti.php`, progetto);
    }
    delete(id_progetto: number) {
        return this.http.delete(environment.wsUrl+`Progetti.php?id_progetto=${id_progetto}`);
    }
}