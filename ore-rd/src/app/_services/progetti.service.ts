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
        return this.http.put<any>(environment.wsUrl+`Progetti.php`, progetto);
    }
    update(progetto: Progetto) {
        return this.http.post<any>(environment.wsUrl+`Progetti.php`, progetto);
    }
    delete(id_progetto: number) {
        return this.http.delete(environment.wsUrl+`Progetti.php?id_progetto=${id_progetto}`);
    }

    getAllTipiCostoPanthera() {
        return '{"value":["TIPO1","TIPO2,"TIPO3"]}';
    }
    getAllMatricole() {
        return '{"value":[{"id_matricola":"1","nome":"A"},{"id_matricola":"2","nome":"B"}]}';
    }
}